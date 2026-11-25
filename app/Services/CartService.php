<?php

namespace App\Services;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Aturan bisnis keranjang (tabel cart_item, unik per user+produk+varian).
 *
 * - Hanya produk berstatus active yang bisa dimasukkan.
 * - Artis tidak bisa membeli produknya sendiri.
 * - Ready stock: jumlah di keranjang tidak boleh melebihi stok.
 * - Pre-order: harus ada batch PO yang sedang buka; kalau batch punya
 *   max_slot, jumlah tidak boleh melebihinya.
 */
class CartService
{
    public const MAX_PER_ITEM = 99;

    public function add(User $user, Product $product, int $qty = 1, ?ProductVariant $variant = null): CartItem
    {
        $qty = max(1, $qty);

        $item = CartItem::firstOrNew(['user_id' => $user->id, 'product_id' => $product->id, 'product_variant_id' => $variant?->id]);
        $newQty = ($item->exists ? $item->quantity : 0) + $qty;

        $this->assertCanBuy($user, $product, $newQty, $variant);

        $item->quantity = $newQty;
        $item->save();

        return $item;
    }

    public function update(CartItem $item, int $qty): CartItem
    {
        $qty = max(1, $qty);
        $this->assertCanBuy($item->user, $item->product, $qty, $item->variant);

        $item->update(['quantity' => $qty]);

        return $item;
    }

    /** Batas maksimal jumlah yang boleh dipesan untuk produk ini. */
    public function maxQuantity(Product $product, ?ProductVariant $variant = null): int
    {
        if ($product->is_pre_order) {
            // sisa slot batch yang sedang dibuka (null = tanpa batas slot)
            $left = $product->openBatch?->remainingSlot();

            $variantLeft = $variant && $product->openBatch ? $variant->remainingForBatch($product->openBatch) : self::MAX_PER_ITEM;
            return (int) min(self::MAX_PER_ITEM, $variantLeft, $left === null ? self::MAX_PER_ITEM : max(0, $left));
        }

        return (int) min(self::MAX_PER_ITEM, max(0, $variant?->stock ?? $product->stock));
    }

    /** Alasan produk tidak bisa dibeli saat ini, atau null kalau aman. */
    public function unavailableReason(Product $product, ?ProductVariant $variant = null): ?string
    {
        if ($variant && ((int) $variant->product_id !== (int) $product->id || ! $variant->is_active)) return 'Varian tidak tersedia.';
        if (! $variant && $product->variants()->exists()) return 'Pilih varian terlebih dahulu.';
        if ($product->product_status !== 'active') {
            return 'Produk sudah tidak dijual.';
        }

        if ($product->is_pre_order && ! $product->openBatch) {
            return 'Periode pre-order sudah ditutup.';
        }

        if ($product->is_pre_order && $product->openBatch->remainingSlot() === 0) {
            return 'Slot pre-order batch ini sudah penuh.';
        }

        if ($variant && $product->is_pre_order && $product->openBatch && $variant->remainingForBatch($product->openBatch) < 1) return 'Kuota varian habis.';
        if (! $product->is_pre_order && ($variant?->stock ?? $product->stock) < 1) {
            return 'Stok habis.';
        }

        return null;
    }

    private function assertCanBuy(User $user, Product $product, int $qty, ?ProductVariant $variant = null): void
    {
        $product->loadMissing(['openBatch', 'artistProfile']);

        if ($product->artistProfile?->user_id === $user->id) {
            $this->fail('Ini produkmu sendiri — tidak bisa dimasukkan ke keranjang.');
        }

        if ($reason = $this->unavailableReason($product, $variant)) {
            $this->fail($reason);
        }

        $max = $this->maxQuantity($product, $variant);

        if ($qty > $max) {
            $this->fail($product->is_pre_order
                ? "Maksimal {$max} pcs untuk batch pre-order ini."
                : "Stok tersisa {$max} pcs (termasuk yang sudah ada di keranjangmu).");
        }
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['cart' => $message]);
    }
}
