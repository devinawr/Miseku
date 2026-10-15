<?php

namespace App\Livewire;

use App\Livewire\Concerns\RunsActions;
use App\Models\Address;
use App\Models\CustomOffer;
use App\Models\Product;
use App\Services\OrderService;
use App\Services\ShippingService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Checkout (BPMN 1): pilih alamat, metode pengiriman per toko (kurir / ambil di booth event),
 * skema bayar PO (DP / penuh), lalu buat pesanan + tagihan pertama.
 *
 * Sumber baris: session('checkout_lines') dari keranjang / "Beli langsung",
 * atau ?offer=ID untuk checkout penawaran custom.
 */
#[Layout('components.layouts.app')]
#[Title('Checkout')]
class Checkout extends Component
{
    use RunsActions;

    #[Url(as: 'offer')]
    public ?int $offerId = null;

    /** @var array<int, array{product_id:int, quantity:int, scheme:string}> */
    public array $lines = [];

    public array $cartItemIds = [];

    public ?int $addressId = null;

    /** artistProfileId => ['method','courier','service','booth_id'] */
    public array $ship = [];

    public function mount(): void
    {
        $user = Auth::user();

        if ($this->offerId) {
            $offer = $this->offer;
            abort_unless($offer && $offer->request->buyer_id === $user->id, 404);
        } else {
            $this->lines = array_values(session('checkout_lines', []));
            $this->cartItemIds = session('checkout_cart_ids', []);

            if (! $this->lines) {
                session()->flash('status', 'Pilih produk di keranjang dulu sebelum checkout.');
                $this->redirectRoute('cart', navigate: true);

                return;
            }
        }

        $this->addressId = $user->addresses()->orderByDesc('is_primary')->value('id');

        foreach ($this->artistIds() as $artistId) {
            $this->ship[$artistId] = ['method' => 'courier', 'courier' => 'jne', 'service' => 'REG', 'booth_id' => null];
        }
    }

    #[Computed]
    public function offer(): ?CustomOffer
    {
        return $this->offerId ? CustomOffer::with(['request.merchandiseType', 'artistProfile'])->find($this->offerId) : null;
    }

    #[Computed]
    public function products()
    {
        return Product::with(['primaryImage', 'openBatch', 'artistProfile', 'character.fandom'])
            ->whereIn('id', collect($this->lines)->pluck('product_id'))->get()->keyBy('id');
    }

    #[Computed]
    public function addresses()
    {
        return Auth::user()->addresses()->orderByDesc('is_primary')->orderBy('id')->get();
    }

    private function artistIds(): array
    {
        if ($this->offer) {
            return [$this->offer->artist_profile_id];
        }

        return $this->products->pluck('artist_profile_id')->unique()->values()->all();
    }

    /**
     * Rangkuman per toko: paket (pesanan yang akan terbentuk), booth, ongkir, tagihan pertama.
     */
    #[Computed]
    public function groups(): array
    {
        $orders = app(OrderService::class);
        $shipping = app(ShippingService::class);
        $address = $this->addresses->firstWhere('id', $this->addressId);
        $groups = [];

        if ($this->offer) {
            $o = $this->offer;
            $groups[$o->artist_profile_id] = [
                'artist'   => $o->artistProfile,
                'packages' => [[
                    'label' => 'Pesanan custom', 'qty' => (int) $o->request->quantity, 'subtotal' => (float) $o->offer_price,
                    'first' => null, 'rows' => [],
                ]],
                'pickupAllowed' => true,
            ];
        } else {
            foreach ($this->lines as $i => $line) {
                $p = $this->products->get($line['product_id']);
                if (! $p) {
                    continue;
                }
                $aid = $p->artist_profile_id;
                $groups[$aid] ??= ['artist' => $p->artistProfile, 'packages' => [], 'pickupAllowed' => true, 'ready' => null];
                $groups[$aid]['pickupAllowed'] = $groups[$aid]['pickupAllowed'] && $p->allow_event_pickup;

                $batch = $p->is_pre_order ? $p->openBatch : null;
                $price = $batch ? (float) $batch->price_po : (float) $p->price;
                $scheme = $batch && $batch->is_dp && ($line['scheme'] ?? 'full') === 'dp' ? 'dp' : 'full';
                $row = ['index' => $i, 'product' => $p, 'qty' => (int) $line['quantity'], 'price' => $price, 'batch' => $batch, 'scheme' => $scheme];

                if ($batch) {
                    $groups[$aid]['packages'][] = ['label' => 'Pre-order · ' . $p->product_name, 'qty' => $row['qty'], 'subtotal' => $price * $row['qty'],
                        'first' => $scheme === 'dp' ? (float) $batch->dp_amount * $row['qty'] : null, 'rows' => [$row]];
                } else {
                    if ($groups[$aid]['ready'] === null) {
                        $groups[$aid]['ready'] = count($groups[$aid]['packages']);
                        $groups[$aid]['packages'][] = ['label' => 'Ready stock', 'qty' => 0, 'subtotal' => 0, 'first' => null, 'rows' => []];
                    }
                    $k = $groups[$aid]['ready'];
                    $groups[$aid]['packages'][$k]['rows'][] = $row;
                    $groups[$aid]['packages'][$k]['qty'] += $row['qty'];
                    $groups[$aid]['packages'][$k]['subtotal'] += $price * $row['qty'];
                }
            }
        }

        foreach ($groups as $aid => &$g) {
            $s = $this->ship[$aid] ?? ['method' => 'courier', 'courier' => 'jne', 'service' => 'REG'];
            $g['booths'] = $orders->pickupBooths($aid);
            $g['quotes'] = [];
            $g['error'] = null;
            $totalQty = array_sum(array_column($g['packages'], 'qty'));

            if (($s['method'] ?? 'courier') === 'courier') {
                if ($address) {
                    $g['quotes'] = $shipping->quote($shipping->originFor($g['artist']), $address->rajaongkir_city_id, $shipping->weightFor(max(1, $totalQty)), $s['courier'] ?? 'jne');
                }
                foreach ($g['packages'] as &$pkg) {
                    $pkg['shipping'] = 0;
                    if ($address) {
                        try {
                            $pkg['shipping'] = $orders->shippingCost($g['artist'], $address, $pkg['qty'], $s['courier'] ?? 'jne', $s['service'] ?? 'REG');
                        } catch (\Illuminate\Validation\ValidationException $e) {
                            $g['error'] = $e->errors()['action'][0] ?? 'Ongkir tidak tersedia.';
                        }
                    }
                }
                unset($pkg);
            } else {
                foreach ($g['packages'] as &$pkg) {
                    $pkg['shipping'] = 0;
                }
                unset($pkg);
            }

            foreach ($g['packages'] as &$pkg) {
                $pkg['total'] = $pkg['subtotal'] + $pkg['shipping'];
                $pkg['pay_now'] = ($pkg['first'] ?? $pkg['subtotal']) + $pkg['shipping'];
            }
            unset($pkg);
        }
        unset($g);

        return $groups;
    }

    #[Computed]
    public function totals(): array
    {
        $packages = collect($this->groups)->flatMap(fn ($g) => $g['packages']);

        return [
            'orders'   => $packages->count(),
            'subtotal' => $packages->sum('subtotal'),
            'shipping' => $packages->sum('shipping'),
            'total'    => $packages->sum('total'),
            'pay_now'  => $packages->sum('pay_now'),
        ];
    }

    public function setScheme(int $index, string $scheme): void
    {
        if (isset($this->lines[$index])) {
            $this->lines[$index]['scheme'] = $scheme === 'dp' ? 'dp' : 'full';
            session(['checkout_lines' => $this->lines]);
        }
        unset($this->groups, $this->totals);
    }

    public function updated(): void
    {
        unset($this->groups, $this->totals);
    }

    public function place(): void
    {
        $user = Auth::user();

        $result = $this->attempt(function () use ($user) {
            $orders = app(OrderService::class);

            if ($this->offer) {
                return collect([$orders->checkoutCustom($user, $this->offer, $this->ship[$this->offer->artist_profile_id] ?? [], $this->addressId)]);
            }

            return $orders->checkout($user, $this->lines, $this->ship, $this->addressId, $this->cartItemIds);
        });

        if (! $result) {
            return;
        }

        session()->forget(['checkout_lines', 'checkout_cart_ids']);
        $this->dispatch('cart-updated');

        if ($result->count() === 1) {
            $payment = $result->first()->pendingPayment();
            $this->redirectRoute('payments.show', ['payment' => $payment->id], navigate: false);

            return;
        }

        session()->flash('status', $result->count() . ' pesanan dibuat (dipisah per toko & per PO). Bayar tiap tagihannya ya.');
        $this->redirectRoute('orders.index', ['tab' => 'bayar'], navigate: true);
    }

    public function render()
    {
        return view('livewire.checkout');
    }
}
