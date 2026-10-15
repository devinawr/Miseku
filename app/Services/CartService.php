<?php

namespace App\Services;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Aturan bisnis keranjang (tabel cart_item, unik per user+produk).
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

    public function add(User $user, Product $product, int $qty = 1): CartItem
    {
        $qty = max(1, $qty);

        $item = CartItem::firstOrNew(['user_id' => $user->id, 'product_id' => $product->id]);
        $newQty = ($item->exists ? $item->quantity : 0) + $qty;

        $this->assertCanBuy($user, $product, $newQty);

        $item->quantity = $newQty;
        $item->save();

        return $item;
    }

    public function update(CartItem $item, int $qty): CartItem
    {
        $qty = max(1, $qty);
        $this->assertCanBuy($item->user, $item->product, $qty);

        $item->update(['quantity' => $qty]);

        return $item;
    }

    /** Batas maksimal jumlah yang boleh dipesan untuk produk ini. */
    public function maxQuantity(Product $product): int
    {
        if ($product->is_pre_order) {
            // sisa slot batch yang sedang dibuka (null = tanpa batas slot)
            $left = $product->openBatch?->remainingSlot();

            return (int) min(self::MAX_PER_ITEM, $left === null ? self::MAX_PER_ITEM : max(0, $left));
        }

        return (int) min(self::MAX_PER_ITEM, max(0, $product->stock));
    }

    /** Alasan produk tidak bisa dibeli saat ini, atau null kalau aman. */
    public function unavailableReason(Product $product): ?string
    {
        if ($product->product_status !== 'active') {
            return 'Produk sudah tidak dijual.';
        }

        if ($product->is_pre_order && ! $product->openBatch) {
            return 'Periode pre-order sudah ditutup.';
        }

        if ($product->is_pre_order && $product->openBatch->remainingSlot() === 0) {
            return 'Slot pre-order batch ini sudah penuh.';
        }

        if (! $product->is_pre_order && $product->stock < 1) {
            return 'Stok habis.';
        }

        return null;
    }

    private function assertCanBuy(User $user, Product $product, int $qty): void
    {
        $product->loadMissing(['openBatch', 'artistProfile']);

        if ($product->artistProfile?->user_id === $user->id) {
            $this->fail('Ini produkmu sendiri — tidak bisa dimasukkan ke keranjang.');
        }

        if ($reason = $this->unavailableReason($product)) {
            $this->fail($reason);
        }

        $max = $this->maxQuantity($product);

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
