<?php

namespace App\Livewire;

use App\Models\CartItem;
use App\Services\CartService;
use App\Services\ContentBasedRecommender;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Keranjang belanja (tabel cart_item).
 *
 * Item dikelompokkan per toko artis. Pembeli bisa mengubah jumlah, menghapus,
 * dan mencentang item yang akan di-checkout. Tombol Checkout membawa item yang
 * dicentang (beserta skema DP/lunas yang dipilih di detail produk) ke halaman checkout.
 */
#[Layout('components.layouts.app')]
#[Title('Keranjang')]
class Cart extends Component
{
    /** @var array<int> id cart_item yang dicentang */
    public array $selected = [];

    public function mount(): void
    {
        $this->selected = $this->items
            ->filter(fn (CartItem $i) => $i->unavailable === null)
            ->pluck('id')->map(fn ($id) => (string) $id)->all();
    }

    #[Computed]
    public function items()
    {
        $service = app(CartService::class);

        return CartItem::query()
            ->where('user_id', Auth::id())
            ->with(['product.primaryImage', 'product.openBatch', 'product.artistProfile', 'product.character.fandom', 'product.merchandiseType'])
            ->latest('added_at')
            ->get()
            ->each(function (CartItem $item) use ($service) {
                $item->unavailable = $service->unavailableReason($item->product);
                $item->max = $service->maxQuantity($item->product);
                if ($item->unavailable === null && $item->quantity > $item->max) {
                    $item->unavailable = "Jumlah melebihi batas ({$item->max} pcs). Kurangi jumlahnya.";
                }
            });
    }

    #[Computed]
    public function groups()
    {
        return $this->items->groupBy(fn (CartItem $i) => $i->product->artist_profile_id);
    }

    #[Computed]
    public function summary(): array
    {
        $chosen = $this->items->filter(fn (CartItem $i) => in_array((string) $i->id, $this->selected, true) && $i->unavailable === null);

        return [
            'count'    => (int) $chosen->sum('quantity'),
            'subtotal' => (float) $chosen->sum(fn (CartItem $i) => $i->quantity * $i->product->display_price),
            'dp'       => (float) $chosen->sum(fn (CartItem $i) => $i->quantity * ($i->product->is_pre_order && $i->product->openBatch?->is_dp
                && $this->schemeFor($i->product_id) === 'dp'
                ? $i->product->openBatch->dp_amount
                : $i->product->display_price)),
        ];
    }

    /** Rekomendasi di bawah keranjang (CBF — produk di keranjang ikut membentuk profil). */
    #[Computed]
    public function suggestions()
    {
        return app(ContentBasedRecommender::class)->forUser(Auth::user(), 4);
    }

    public function changeQty(int $id, int $delta): void
    {
        $this->setQty($id, $this->find($id)->quantity + $delta);
    }

    public function setQty(int $id, $qty): void
    {
        $item = $this->find($id);
        $qty = (int) $qty;

        if ($qty < 1) {
            $this->remove($id);

            return;
        }

        try {
            app(CartService::class)->update($item, $qty);
        } catch (ValidationException $e) {
            $this->dispatch('toast', message: $e->errors()['cart'][0], tone: 'error');
        }

        $this->refresh();
    }

    public function remove(int $id): void
    {
        $this->find($id)->delete();
        $this->selected = array_values(array_diff($this->selected, [(string) $id]));
        $this->dispatch('toast', message: 'Item dihapus dari keranjang.');
        $this->refresh();
    }

    public function removeSelected(): void
    {
        CartItem::where('user_id', Auth::id())->whereIn('id', $this->selected)->delete();
        $this->selected = [];
        $this->dispatch('toast', message: 'Item terpilih dihapus.');
        $this->refresh();
    }

    public function toggleAll(): void
    {
        $available = $this->items->filter(fn ($i) => $i->unavailable === null)->pluck('id')->map(fn ($id) => (string) $id)->all();
        $this->selected = count($this->selected) === count($available) ? [] : $available;
    }

    public function toggleShop(int $artistProfileId): void
    {
        $ids = $this->groups->get($artistProfileId, collect())->filter(fn ($i) => $i->unavailable === null)->pluck('id')->map(fn ($id) => (string) $id)->all();
        $allOn = empty(array_diff($ids, $this->selected));
        $this->selected = $allOn
            ? array_values(array_diff($this->selected, $ids))
            : array_values(array_unique(array_merge($this->selected, $ids)));
    }

    /** Skema PO yang dipilih pembeli di detail produk (default DP bila batch mengizinkan). */
    private function schemeFor(int $productId): string
    {
        return session("cart_scheme.{$productId}", 'dp') === 'full' ? 'full' : 'dp';
    }

    /** Bawa item yang dicentang & masih tersedia ke halaman checkout. */
    public function checkout()
    {
        $chosen = $this->items->filter(fn (CartItem $i) => in_array((string) $i->id, $this->selected, true) && $i->unavailable === null);

        if ($chosen->isEmpty()) {
            $this->dispatch('toast', message: 'Centang minimal satu produk yang masih tersedia.', tone: 'error');

            return null;
        }

        session([
            'checkout_lines' => $chosen->map(fn (CartItem $i) => [
                'product_id' => $i->product_id,
                'quantity'   => (int) $i->quantity,
                'scheme'     => $i->product->openBatch?->is_dp ? $this->schemeFor($i->product_id) : 'full',
            ])->values()->all(),
            'checkout_cart_ids' => $chosen->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
        ]);

        return $this->redirect(route('checkout'), navigate: true);
    }

    private function find(int $id): CartItem
    {
        return CartItem::where('user_id', Auth::id())->findOrFail($id);
    }

    private function refresh(): void
    {
        unset($this->items, $this->groups, $this->summary);
        $this->dispatch('cart-updated');
    }

    public function render()
    {
        return view('livewire.cart');
    }
}
