<?php

namespace App\Services;

use App\Models\ArtistProfile;
use App\Models\CustomerOrder;
use App\Models\EventBooth;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Support\Fail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Penjualan OTS (on the spot) di booth event — order_type ots_event,
 * pembeli = akun "Pembeli Umum (Event)", bayar tunai di tempat, admin_fee 0,
 * shipping_method NULL, langsung selesai. Stok ready stock ikut berkurang.
 * Uang tunai dipegang artis, jadi TIDAK masuk saldo platform (tidak ada balance_transaction).
 */
class OtsService
{
    public function buyerAccount(): User
    {
        return User::firstOrCreate(
            ['email' => config('miseku.ots_buyer_email')],
            ['name' => 'Pembeli Umum (Event)', 'password' => Str::random(40), 'role' => 'buyer', 'is_active' => true]
        );
    }

    /** @param  array<int,int>  $quantities  product_id => qty */
    public function sell(ArtistProfile $artist, EventBooth $booth, array $quantities, string $method = 'Tunai'): CustomerOrder
    {
        Fail::unless($booth->artist_profile_id === $artist->id, 'Booth ini bukan milikmu.');
        $booth->loadMissing('event');
        Fail::unless($booth->event->is_ongoing, 'Penjualan OTS hanya saat event berlangsung (' . $booth->event->period_label . ').');

        $quantities = array_filter(array_map('intval', $quantities), fn ($q) => $q > 0);
        Fail::unless(count($quantities) > 0, 'Isi jumlah minimal satu produk.');

        return DB::transaction(function () use ($artist, $booth, $quantities, $method) {
            $products = Product::whereIn('id', array_keys($quantities))->where('artist_profile_id', $artist->id)
                ->where('availability_type', 'ready_stock')->where('product_status', '!=', 'deleted')->lockForUpdate()->get()->keyBy('id');

            $subtotal = 0;
            foreach ($quantities as $id => $qty) {
                $p = $products->get($id);
                Fail::unless((bool) $p, 'Produk tidak valid untuk penjualan OTS (harus ready stock milikmu).');
                Fail::unless($p->stock >= $qty, "Stok {$p->product_name} tersisa {$p->stock}.");
                $subtotal += (float) $p->price * $qty;
            }

            $code = 'OTS-' . now()->format('Ymd') . '-' . strtoupper(Str::random(5));
            $order = CustomerOrder::create([
                'order_code' => $code, 'buyer_id' => $this->buyerAccount()->id, 'artist_profile_id' => $artist->id,
                'event_booth_id' => $booth->id, 'order_type' => 'ots_event', 'shipping_method' => null, 'payment_scheme' => 'full',
                'product_subtotal' => $subtotal, 'shipping_cost' => 0, 'admin_fee' => 0, 'total_amount' => $subtotal,
                'order_status' => 'completed', 'completed_at' => now(),
            ]);

            foreach ($quantities as $id => $qty) {
                $p = $products->get($id);
                OrderItem::create(['customer_order_id' => $order->id, 'product_id' => $id, 'quantity' => $qty, 'unit_price' => $p->price, 'subtotal' => (float) $p->price * $qty]);
                $p->decrement('stock', $qty);
            }

            Payment::create([
                'customer_order_id' => $order->id, 'payment_type' => 'full', 'amount' => $subtotal,
                'payment_method' => mb_substr($method, 0, 60), 'payment_status' => 'success', 'paid_at' => now(),
            ]);

            return $order;
        });
    }
}
