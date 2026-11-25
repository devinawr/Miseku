<?php

namespace App\Services;

use App\Models\ArtistProfile;
use App\Models\EventBooth;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\OtsStockLog;
use App\Support\Fail;
use Illuminate\Support\Facades\DB;

/** OTS mencatat stok yang berkurang; tidak membuat pesanan atau pembayaran. */
class OtsService
{
    /** @param array<int,int> $quantities product_id => pcs */
    public function sell(ArtistProfile $artist, EventBooth $booth, array $quantities, array $variantQuantities = []): int
    {
        Fail::unless($booth->artist_profile_id === $artist->id, 'Booth ini bukan milikmu.');
        $booth->loadMissing('event');
        Fail::unless($booth->event->is_ongoing, 'Pengurangan stok OTS hanya saat event berlangsung.');
        $normalize = function (array $values): array {
            foreach ($values as $qty) {
                if ($qty === '' || $qty === null) continue;
                Fail::unless(is_numeric($qty) && (float) $qty >= 0 && (float) $qty === (float) (int) $qty, 'Jumlah harus bilangan bulat tidak negatif.');
            }
            return array_filter(array_map('intval', $values), fn ($q) => $q > 0);
        };
        $quantities = $normalize($quantities); $variantQuantities = $normalize($variantQuantities);
        Fail::unless(count($quantities) + count($variantQuantities) > 0, 'Isi jumlah minimal satu produk.');
        return DB::transaction(function () use ($artist, $booth, $quantities, $variantQuantities) {
            $variantProducts = ProductVariant::whereIn('id', array_keys($variantQuantities))->pluck('product_id')->all();
            $ids = array_unique(array_merge(array_keys($quantities), $variantProducts));
            $products = Product::whereIn('id', $ids)->where('artist_profile_id', $artist->id)
                ->where('availability_type', 'ready_stock')->where('product_status', '!=', 'deleted')->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $variants = ProductVariant::whereIn('id', array_keys($variantQuantities))->where('is_active', true)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            foreach ($quantities as $id => $qty) {
                $product = $products->get($id);
                Fail::unless($product && ! $product->variants()->exists(), 'Produk OTS tidak valid atau harus memilih varian.');
                Fail::unless($product->stock >= $qty, "Stok {$product->product_name} tersisa {$product->stock}.");
            }
            foreach ($variantQuantities as $id => $qty) {
                $variant = $variants->get($id); $product = $variant ? $products->get($variant->product_id) : null;
                Fail::unless($variant && $product, 'Varian OTS tidak valid.');
                Fail::unless($variant->stock >= $qty, "Stok varian {$variant->name} tersisa {$variant->stock}.");
            }
            foreach ($quantities as $id => $qty) {
                $product = $products->get($id); $product->decrement('stock', $qty);
                OtsStockLog::create(['artist_profile_id'=>$artist->id,'event_booth_id'=>$booth->id,'product_id'=>$product->id,
                    'event_name'=>$booth->event->event_name,'booth_name'=>$booth->booth_number,'product_name'=>$product->product_name,'quantity'=>$qty]);
            }
            foreach ($variantQuantities as $id => $qty) {
                $variant = $variants->get($id); $product = $products->get($variant->product_id);
                $variant->decrement('stock', $qty); $product->decrement('stock', $qty);
                OtsStockLog::create(['artist_profile_id'=>$artist->id,'event_booth_id'=>$booth->id,'product_id'=>$product->id,
                    'product_variant_id'=>$variant->id,'variant_name'=>$variant->name,'event_name'=>$booth->event->event_name,
                    'booth_name'=>$booth->booth_number,'product_name'=>$product->product_name,'quantity'=>$qty]);
            }
            return array_sum($quantities) + array_sum($variantQuantities);
        });
    }

}
