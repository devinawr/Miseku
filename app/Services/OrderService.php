<?php

namespace App\Services;

use App\Models\Address;
use App\Models\ArtistProfile;
use App\Models\CartItem;
use App\Models\CustomerOrder;
use App\Models\CustomOffer;
use App\Models\EventBooth;
use App\Models\EventPickup;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PoBatch;
use App\Models\Product;
use App\Models\User;
use App\Support\Fail;
use App\Support\Money;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Inti BPMN 1 (checkout) & BPMN 2 (pemrosesan pesanan).
 *
 * Checkout: item dikelompokkan per artis → ready stock dari artis yang sama jadi
 * SATU pesanan; tiap item pre-order jadi pesanan sendiri karena status produksinya
 * mengikuti batch PO masing-masing (customer_order.order_type hanya satu nilai).
 * Setiap pesanan punya kode & tagihan Midtrans sendiri.
 */
class OrderService
{
    public function __construct(
        private PaymentService $payments,
        private BalanceService $balance,
        private NotificationService $notify,
        private ShippingService $shipping,
    ) {}

    // =====================================================================
    // CHECKOUT (BPMN 1)
    // =====================================================================

    /**
     * @param  array<int, array{product_id:int, quantity:int, scheme:string}>  $lines
     * @param  array<int, array{method:string, courier?:string, service?:string, booth_id?:int}>  $shippingByArtist
     * @return Collection<int, CustomerOrder>
     */
    public function checkout(User $buyer, array $lines, array $shippingByArtist, ?int $addressId, array $cartItemIds = []): Collection
    {
        Fail::unless(count($lines) > 0, 'Belum ada produk yang dipilih untuk checkout.');

        return DB::transaction(function () use ($buyer, $lines, $shippingByArtist, $addressId, $cartItemIds) {
            $products = Product::whereIn('id', collect($lines)->pluck('product_id'))
                ->with(['artistProfile', 'character.fandom', 'merchandiseType'])
                ->lockForUpdate()->get()->keyBy('id');

            // 1) validasi tiap baris + tentukan harga & batch
            $prepared = collect($lines)->map(function (array $line) use ($products, $buyer) {
                /** @var Product|null $p */
                $p = $products->get($line['product_id']);
                Fail::unless($p && $p->isPubliclyVisible(), 'Ada produk yang sudah tidak dijual. Muat ulang keranjang.');
                Fail::unless($p->artistProfile->user_id !== $buyer->id, 'Kamu tidak bisa membeli produk tokomu sendiri.');

                $qty = (int) $line['quantity'];
                Fail::unless($qty >= 1, 'Jumlah tidak valid.');

                $batch = null;
                if ($p->is_pre_order) {
                    $batch = PoBatch::where('product_id', $p->id)->open()->latest('id')->lockForUpdate()->first();
                    Fail::unless((bool) $batch, "Pre-order {$p->product_name} sudah ditutup.");
                    $left = $batch->remainingSlot();
                    Fail::unless($left === null || $qty <= $left, "Sisa slot PO {$p->product_name} tinggal {$left} pcs.");
                    $scheme = ($line['scheme'] ?? 'full') === 'dp' && $batch->is_dp ? 'dp' : 'full';
                    $price = (float) $batch->price_po;
                } else {
                    Fail::unless($p->stock >= $qty, "Stok {$p->product_name} tersisa {$p->stock} pcs.");
                    $scheme = 'full';
                    $price = (float) $p->price;
                }

                return compact('p', 'qty', 'batch', 'scheme', 'price');
            });

            $address = $addressId ? Address::where('user_id', $buyer->id)->find($addressId) : null;

            // 2) kelompokkan per artis, lalu pecah: ready stock (1 pesanan) + tiap PO (1 pesanan)
            $orders = collect();
            foreach ($prepared->groupBy(fn ($r) => $r['p']->artist_profile_id) as $artistId => $rows) {
                $artist = $rows->first()['p']->artistProfile;
                $ship = $this->resolveShipping($artist, $shippingByArtist[$artistId] ?? [], $address, $rows->pluck('p'));

                $packages = collect();
                $ready = $rows->filter(fn ($r) => ! $r['batch']);
                if ($ready->isNotEmpty()) {
                    $packages->push(['type' => 'ready_stock', 'scheme' => 'full', 'rows' => $ready]);
                }
                foreach ($rows->filter(fn ($r) => $r['batch']) as $r) {
                    $packages->push(['type' => 'pre_order', 'scheme' => $r['scheme'], 'rows' => collect([$r])]);
                }

                foreach ($packages as $pkg) {
                    $orders->push($this->createOrder($buyer, $artist, $pkg, $ship, $address));
                }
            }

            if ($cartItemIds) {
                CartItem::where('user_id', $buyer->id)->whereIn('id', $cartItemIds)->delete();
            }

            return $orders;
        });
    }

    /** Checkout pesanan custom (BPMN 4 → call activity checkout). Selalu full payment. */
    public function checkoutCustom(User $buyer, CustomOffer $offer, array $ship, ?int $addressId): CustomerOrder
    {
        return DB::transaction(function () use ($buyer, $offer, $ship, $addressId) {
            $offer = CustomOffer::whereKey($offer->id)->lockForUpdate()->with(['request', 'artistProfile'])->firstOrFail();
            $request = $offer->request;

            Fail::unless($request->buyer_id === $buyer->id, 'Permintaan custom ini bukan milikmu.');
            Fail::unless(in_array($request->request_status, ['open', 'offered'], true), 'Permintaan ini sudah diproses.');
            Fail::unless($offer->offer_status === 'pending', 'Penawaran ini sudah tidak berlaku.');

            $address = $addressId ? Address::where('user_id', $buyer->id)->find($addressId) : null;
            $shipInfo = $this->resolveShipping($offer->artistProfile, $ship, $address, collect(), (int) $request->quantity);

            // tandai penawaran terpilih, penawaran lain tidak dipilih
            $offer->update(['offer_status' => 'selected']);
            $request->offers()->whereKeyNot($offer->id)->where('offer_status', 'pending')->update(['offer_status' => 'cancelled', 'updated_at' => now()]);
            $request->update(['request_status' => 'selected']);

            $cost = $shipInfo['method'] === 'courier'
                ? $this->shippingCost($offer->artistProfile, $address, (int) $request->quantity, $shipInfo['courier'], $shipInfo['service'])
                : 0;

            $order = $this->insertOrder([
                'buyer_id'          => $buyer->id,
                'artist_profile_id' => $offer->artist_profile_id,
                'address_id'        => $shipInfo['method'] === 'courier' ? $address->id : null,
                'event_booth_id'    => $shipInfo['booth']?->id,
                'custom_request_id' => $request->id,
                'order_type'        => 'custom',
                'shipping_method'   => $shipInfo['method'],
                'payment_scheme'    => 'full',
                'product_subtotal'  => (float) $offer->offer_price,
                'shipping_cost'     => $cost,
                'total_amount'      => (float) $offer->offer_price + $cost,
                'courier'           => $shipInfo['label'],
            ]);

            $this->payments->createInvoice($order, 'full', (float) $order->total_amount);

            // Penawaran lain dikabari
            $request->offers()->where('offer_status', 'cancelled')->with('artistProfile')->get()->each(fn ($o) => $this->notify->send(
                $o->artistProfile->user_id, 'custom', 'Penawaran custom tidak dipilih',
                "Pembeli memilih penawaran artis lain untuk permintaan \"{$request->design_character}\"."
            ));

            return $order;
        });
    }

    /** Ongkir satu paket (dipakai halaman checkout & saat membuat pesanan). */
    public function shippingCost(ArtistProfile $artist, ?Address $address, int $quantity, string $courier, string $service): float
    {
        Fail::unless((bool) $address, 'Pilih alamat pengiriman dulu.');

        $quotes = $this->shipping->quote($this->shipping->originFor($artist), $address->rajaongkir_city_id, $this->shipping->weightFor($quantity), $courier);
        $row = collect($quotes)->firstWhere('service', $service) ?? collect($quotes)->first();
        Fail::unless((bool) $row, 'Ongkir untuk kurir ini tidak tersedia. Pilih kurir lain.');

        return (float) $row['cost'];
    }

    /** Booth event yang bisa dipilih untuk pick-up: milik artis & event belum berakhir. */
    public function pickupBooths(int $artistProfileId): Collection
    {
        return EventBooth::with('event')->where('artist_profile_id', $artistProfileId)
            ->whereHas('event', fn ($q) => $q->whereDate('end_date', '>=', today()))
            ->get();
    }

    private function resolveShipping(ArtistProfile $artist, array $ship, ?Address $address, Collection $products, int $customQty = 0): array
    {
        $method = $ship['method'] ?? 'courier';

        if ($method === 'event_pickup') {
            Fail::unless($products->every(fn (Product $p) => $p->allow_event_pickup), "Tidak semua produk {$artist->shop_name} bisa diambil di booth event.");
            $booth = $this->pickupBooths($artist->id)->firstWhere('id', (int) ($ship['booth_id'] ?? 0));
            Fail::unless((bool) $booth, "Pilih booth event {$artist->shop_name} untuk pengambilan.");

            return ['method' => 'event_pickup', 'booth' => $booth, 'courier' => null, 'service' => null, 'label' => null];
        }

        Fail::unless((bool) $address, 'Pilih alamat pengiriman dulu.');
        $courier = $ship['courier'] ?? '';
        Fail::unless(array_key_exists($courier, config('miseku.couriers')), "Pilih kurir untuk pesanan dari {$artist->shop_name}.");
        $service = (string) ($ship['service'] ?? 'REG');

        return [
            'method'  => 'courier', 'booth' => null, 'courier' => $courier, 'service' => $service,
            'label'   => mb_substr(config('miseku.couriers')[$courier] . ' ' . $service, 0, 40),
        ];
    }

    private function createOrder(User $buyer, ArtistProfile $artist, array $pkg, array $ship, ?Address $address): CustomerOrder
    {
        $rows = $pkg['rows'];
        $subtotal = $rows->sum(fn ($r) => $r['price'] * $r['qty']);
        $qty = (int) $rows->sum('qty');
        $cost = $ship['method'] === 'courier' ? $this->shippingCost($artist, $address, $qty, $ship['courier'], $ship['service']) : 0;

        $order = $this->insertOrder([
            'buyer_id'          => $buyer->id,
            'artist_profile_id' => $artist->id,
            'address_id'        => $ship['method'] === 'courier' ? $address->id : null,
            'event_booth_id'    => $ship['booth']?->id,
            'order_type'        => $pkg['type'],
            'shipping_method'   => $ship['method'],
            'payment_scheme'    => $pkg['scheme'],
            'product_subtotal'  => $subtotal,
            'shipping_cost'     => $cost,
            'total_amount'      => $subtotal + $cost,
            'courier'           => $ship['label'],
        ]);

        foreach ($rows as $r) {
            OrderItem::create([
                'customer_order_id' => $order->id,
                'product_id'        => $r['p']->id,
                'po_batch_id'       => $r['batch']?->id,
                'quantity'          => $r['qty'],
                'unit_price'        => $r['price'],
                'subtotal'          => $r['price'] * $r['qty'],
            ]);

            if (! $r['batch']) {
                $r['p']->decrement('stock', $r['qty']); // kurangi stok saat pesanan dibuat
            }
        }

        $order->load('items.batch');
        $this->payments->createInvoice($order, $order->isDp() ? 'dp' : 'full', $order->firstInvoiceAmount());

        return $order;
    }

    private function insertOrder(array $values): CustomerOrder
    {
        for ($i = 0; $i < 5; $i++) {
            try {
                return CustomerOrder::create($values + ['order_code' => $this->nextCode(), 'order_status' => 'awaiting_payment', 'admin_fee' => 0]);
            } catch (QueryException $e) {
                if (! str_contains($e->getMessage(), 'uq_order_code')) {
                    throw $e;
                }
            }
        }

        return CustomerOrder::create($values + ['order_code' => 'ORD-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6)), 'order_status' => 'awaiting_payment', 'admin_fee' => 0]);
    }

    /** ORD-YYYYMMDD-0001 (urut per hari). */
    private function nextCode(): string
    {
        $prefix = 'ORD-' . now()->format('Ymd') . '-';
        $last = CustomerOrder::where('order_code', 'like', $prefix . '%')->orderByDesc('order_code')->value('order_code');
        $seq = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    // =====================================================================
    // SETELAH BAYAR (webhook)
    // =====================================================================

    public function afterPayment(CustomerOrder $order, Payment $payment): void
    {
        $order->loadMissing(['items.product', 'buyer', 'artistProfile']);

        if ($payment->payment_type === 'settlement') {
            // Pelunasan → siap kirim; tenggat kirim 14 hari sejak lunas (kurir saja)
            $order->update([
                'order_status'            => 'production_completed',
                'shipping_deadline'       => $order->isCourier() ? now()->addDays(config('miseku.shipping_days')) : null,
                'shipping_reminder_stage' => 0,
            ]);

            $this->notify->send($order->buyer_id, 'po_settlement', 'Pelunasan berhasil', "Pelunasan {$order->order_code} sebesar " . Money::rp($payment->amount) . ' diterima. Artis akan segera mengirim pesananmu.', $order);
            $this->notify->send($order->artistProfile->user_id, 'po_settlement', 'Pesanan sudah lunas — siap dikirim', "Pembeli melunasi {$order->order_code}. " . ($order->isCourier() ? 'Kirim paling lambat ' . $order->shipping_deadline->translatedFormat('d M Y') . '.' : 'Siapkan untuk pengambilan di booth.'), $order);

            return;
        }

        $values = ['order_status' => 'paid'];
        if ($order->order_type === 'ready_stock' && $order->isCourier()) {
            $values['shipping_deadline'] = now()->addDays(config('miseku.shipping_days'));
        }
        $order->update($values);

        $label = $payment->payment_type === 'dp' ? 'DP' : 'Pembayaran';
        $this->notify->send($order->buyer_id, 'payment', "{$label} berhasil", "{$label} untuk {$order->order_code} sebesar " . Money::rp($payment->amount) . ' sudah kami terima. ' . $this->nextStepText($order), $order);
        $this->notify->send($order->artistProfile->user_id, 'order', 'Pesanan baru masuk', "Pesanan {$order->order_code} ({$order->type_label}) dari {$order->buyer->name} sudah dibayar" . ($payment->payment_type === 'dp' ? ' (DP)' : '') . '.', $order);
    }

    private function nextStepText(CustomerOrder $order): string
    {
        return match ($order->order_type) {
            'pre_order' => 'Produksi dimulai setelah PO ditutup dan target minimum tercapai.',
            'custom'    => 'Artis akan mulai mengerjakan pesanan custom-mu.',
            default     => $order->isCourier() ? 'Artis wajib mengirim paling lambat ' . optional($order->shipping_deadline)->translatedFormat('d M Y') . '.' : 'Tunggu info pengambilan di booth.',
        };
    }

    // =====================================================================
    // PEMROSESAN OLEH ARTIS (BPMN 2)
    // =====================================================================

    /** Artis memperbarui status produksi: awaiting_production / in_production. */
    public function setProductionStatus(CustomerOrder $order, string $status): void
    {
        Fail::unless(in_array($status, ['awaiting_production', 'in_production'], true), 'Status tidak dikenal.');
        Fail::unless(in_array($order->order_type, ['pre_order', 'custom'], true), 'Pesanan ready stock tidak melalui produksi — langsung kirim.');

        $allowed = $status === 'awaiting_production' ? ['paid'] : ['paid', 'awaiting_production'];
        Fail::unless(in_array($order->order_status, $allowed, true), 'Status pesanan saat ini tidak bisa diubah ke tahap itu.');

        if ($order->order_type === 'pre_order') {
            $batch = $order->load('items.batch')->poBatch();
            Fail::unless($batch && in_array($batch->batch_status, ['closed', 'in_production'], true),
                'Produksi PO baru bisa dimulai setelah PO ditutup dan target minimum tercapai (menu Batch PO).');
        }

        $order->update(['order_status' => $status]);

        $this->notify->send($order->buyer_id, 'order', $status === 'in_production' ? 'Pesanan mulai diproduksi' : 'Pesanan menunggu produksi',
            "Status {$order->order_code}: " . CustomerOrder::STATUS_LABELS[$status] . '.', $order);
    }

    /** Artis menandai produksi selesai → DP: tagihan pelunasan terbit; full: tenggat kirim mulai. */
    public function completeProduction(CustomerOrder $order): void
    {
        DB::transaction(function () use ($order) {
            $order = CustomerOrder::whereKey($order->id)->lockForUpdate()->with(['payments', 'items.batch'])->firstOrFail();
            Fail::unless(in_array($order->order_type, ['pre_order', 'custom'], true), 'Pesanan ready stock tidak melalui produksi.');
            Fail::unless(in_array($order->order_status, ['awaiting_production', 'in_production'], true), 'Pesanan belum dalam tahap produksi.');

            if ($order->isDp() && ! $order->isFullyPaid()) {
                $rest = (float) $order->total_amount - $order->paidAmount();
                $order->update([
                    'order_status'              => 'awaiting_settlement',
                    'settlement_deadline'       => now()->addDays(config('miseku.settlement_days')),
                    'settlement_reminder_stage' => 0,
                ]);
                $this->payments->createInvoice($order, 'settlement', $rest);

                $this->notify->send($order->buyer_id, 'po_settlement', 'Tagihan pelunasan terbit',
                    "Produksi {$order->order_code} selesai. Lunasi sisa " . Money::rp($rest) . ' paling lambat ' . $order->settlement_deadline->translatedFormat('d M Y H:i') . '. Lewat tenggat, pesanan gagal dan DP hangus.', $order);

                return;
            }

            $order->update([
                'order_status'            => 'production_completed',
                'shipping_deadline'       => $order->isCourier() ? now()->addDays(config('miseku.shipping_days')) : null,
                'shipping_reminder_stage' => 0,
            ]);

            $this->notify->send($order->buyer_id, 'order', 'Produksi selesai', "Pesanan {$order->order_code} selesai diproduksi dan segera " . ($order->isCourier() ? 'dikirim.' : 'disiapkan di booth.'), $order);
        });
    }

    /** Siap kirim = sudah lunas & (ready stock sudah dibayar, atau produksi selesai). */
    public function isReadyToShip(CustomerOrder $order): bool
    {
        return match ($order->order_type) {
            'ready_stock' => $order->order_status === 'paid',
            'pre_order', 'custom' => $order->order_status === 'production_completed' && $order->loadMissing('payments')->isFullyPaid(),
            default => false,
        };
    }

    public function ship(CustomerOrder $order, string $courier, string $tracking): void
    {
        Fail::unless($order->isCourier(), 'Pesanan ini diambil di booth, bukan dikirim kurir.');
        Fail::unless($this->isReadyToShip($order), 'Pesanan belum siap dikirim (belum lunas / produksi belum selesai).');

        $order->update(['order_status' => 'shipped', 'courier' => mb_substr($courier, 0, 40), 'tracking_number' => mb_substr($tracking, 0, 60)]);

        $this->notify->send($order->buyer_id, 'order', 'Pesanan dikirim', "Pesanan {$order->order_code} dikirim lewat {$order->courier}, resi {$order->tracking_number}. Konfirmasi setelah barang diterima.", $order);
    }

    public function readyForPickup(CustomerOrder $order): EventPickup
    {
        return DB::transaction(function () use ($order) {
            Fail::unless($order->isPickup(), 'Pesanan ini dikirim lewat kurir.');
            Fail::unless($this->isReadyToShip($order), 'Pesanan belum siap (belum lunas / produksi belum selesai).');
            $order->loadMissing('booth.event');

            $pickup = EventPickup::firstOrCreate(
                ['customer_order_id' => $order->id],
                ['event_booth_id' => $order->event_booth_id, 'qr_code' => 'MSK-' . strtoupper(Str::random(12)), 'pickup_status' => 'not_picked_up']
            );

            $order->update(['order_status' => 'ready_for_pickup']);
            $b = $order->booth;

            $this->notify->send($order->buyer_id, 'event_pickup', 'Pesanan siap diambil di booth',
                "Ambil {$order->order_code} di {$b->event->event_name} ({$b->event->location}), booth " . ($b->booth_number ?: '-') . ', ' . $b->event->period_label
                . ($b->operating_hours ? ", jam {$b->operating_hours}" : '') . ($b->days ? ', hari ke-' . implode(', ', $b->days) : '')
                . '. Tunjukkan QR di halaman pesanan.', $order);

            return $pickup;
        });
    }

    /** Artis scan QR di booth → validasi → pesanan selesai. */
    public function scanPickup(ArtistProfile $artist, string $code): CustomerOrder
    {
        return DB::transaction(function () use ($artist, $code) {
            $pickup = EventPickup::where('qr_code', trim($code))->lockForUpdate()->with(['order', 'booth.event'])->first();

            Fail::unless((bool) $pickup, 'QR tidak dikenal.');
            Fail::unless($pickup->booth->artist_profile_id === $artist->id, 'QR ini untuk booth artis lain.');
            Fail::unless($pickup->pickup_status === 'not_picked_up', $pickup->pickup_status === 'picked_up'
                ? 'Pesanan ini sudah diambil pada ' . $pickup->picked_up_at->translatedFormat('d M Y H:i') . '.'
                : 'Pengambilan ini sudah hangus (event berakhir).');
            Fail::unless($pickup->order->order_status === 'ready_for_pickup', 'Pesanan belum berstatus siap diambil.');

            $event = $pickup->booth->event;
            Fail::unless(today()->between($event->start_date, $event->end_date), 'Scan hanya bisa dilakukan selama event berlangsung (' . $event->period_label . ').');

            $pickup->update(['pickup_status' => 'picked_up', 'picked_up_at' => now()]);
            $this->complete($pickup->order, 'Pesanan diambil di booth');

            return $pickup->order->fresh();
        });
    }

    public function confirmReceived(CustomerOrder $order, User $buyer): void
    {
        Fail::unless($order->buyer_id === $buyer->id, 'Bukan pesananmu.');
        Fail::unless($order->order_status === 'shipped', 'Pesanan belum dikirim.');

        $this->complete($order, 'Pesanan diterima pembeli');
    }

    /** Pesanan selesai → lepas dana ke artis (dipotong admin fee) + ajak beri ulasan. */
    public function complete(CustomerOrder $order, string $reason): void
    {
        DB::transaction(function () use ($order, $reason) {
            $order = CustomerOrder::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($order->order_status === 'completed') {
                return;
            }

            $order->update(['order_status' => 'completed', 'completed_at' => now()]);
            $this->balance->release($order);
            $order->customRequest?->update(['request_status' => 'completed']);

            $this->notify->send($order->buyer_id, 'order', 'Pesanan selesai', "{$reason}: {$order->order_code}. Beri ulasan untuk produk & artis dalam " . config('miseku.review_days_buyer') . ' hari.', $order);
            $this->notify->send($order->artistProfile->user_id, 'order', 'Pesanan selesai — dana dilepas',
                "{$order->order_code} selesai. Dana " . Money::rp((float) $order->total_amount - (float) $order->admin_fee) . ' (setelah admin fee ' . Money::rp($order->admin_fee) . ') masuk ke saldo tersedia.', $order);
        });
    }

    // =====================================================================
    // PEMBATALAN SEDERHANA (belum dibayar)
    // =====================================================================

    /** Pesanan belum dibayar dibatalkan → stok kembali, tagihan ditutup. Tidak ada penalti. */
    public function cancelUnpaid(CustomerOrder $order, string $reason, bool $notify = true): void
    {
        DB::transaction(function () use ($order, $reason, $notify) {
            $order = CustomerOrder::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($order->order_status !== 'awaiting_payment') {
                return;
            }

            $order->update(['order_status' => 'cancelled']);
            $order->payments()->where('payment_status', 'pending')->update(['payment_status' => 'failed', 'updated_at' => now()]);
            $this->restoreStock($order);
            $order->customRequest?->update(['request_status' => 'cancelled']);

            if ($notify) {
                $this->notify->send($order->buyer_id, 'payment', 'Pesanan dibatalkan', "{$order->order_code}: {$reason}", $order);
            }
        });
    }

    public function restoreStock(CustomerOrder $order): void
    {
        $order->items()->whereNull('po_batch_id')->get()->each(fn (OrderItem $i) => Product::whereKey($i->product_id)->increment('stock', $i->quantity));
    }
}
