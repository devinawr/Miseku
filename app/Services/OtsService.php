<?php

namespace App\Services;

use App\Models\ArtistProfile;
use App\Models\EventBooth;
use App\Models\Product;
use App\Models\OtsStockLog;
use App\Support\Fail;
use Illuminate\Support\Facades\DB;

/** OTS mencatat stok yang berkurang; tidak membuat pesanan atau pembayaran. */
class OtsService
{
    /** @param array<int,int> $quantities product_id => pcs */
    public function sell(ArtistProfile $artist, EventBooth $booth, array $quantities): int
    {
        Fail::unless($booth->artist_profile_id === $artist->id, 'Booth ini bukan milikmu.');
        $booth->loadMissing('event');
        Fail::unless($booth->event->is_ongoing, 'Pengurangan stok OTS hanya saat event berlangsung.');
        $quantities = array_map(fn ($qty) => $qty === '' || $qty === null ? 0 : $qty, $quantities);
        foreach ($quantities as $qty) {
            Fail::unless(is_numeric($qty) && (float) $qty >= 0 && (float) $qty === (float) (int) $qty, 'Jumlah harus bilangan bulat tidak negatif.');
        }
        $quantities = array_filter(array_map('intval', $quantities), fn ($q) => $q > 0);
        Fail::unless(count($quantities) > 0, 'Isi jumlah minimal satu produk.');
        return DB::transaction(function () use ($artist, $booth, $quantities) {
            $products = Product::whereIn('id', array_keys($quantities))->where('artist_profile_id', $artist->id)
                ->where('availability_type', 'ready_stock')->where('product_status', '!=', 'deleted')->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            foreach ($quantities as $id => $qty) {
                $product = $products->get($id);
                Fail::unless((bool) $product, 'Produk tidak valid untuk OTS.');
                Fail::unless($product->stock >= $qty, "Stok {$product->product_name} tersisa {$product->stock}.");
            }
            foreach ($quantities as $id => $qty) {
                $product = $products->get($id);
                $product->decrement('stock', $qty);
                OtsStockLog::create([
                    'artist_profile_id' => $artist->id, 'event_booth_id' => $booth->id, 'product_id' => $product->id,
                    'event_name' => $booth->event->event_name, 'booth_name' => $booth->booth_number,
                    'product_name' => $product->product_name, 'quantity' => $qty,
                ]);
            }
            return array_sum($quantities);
        });
    }
}
