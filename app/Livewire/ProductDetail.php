<?php

namespace App\Livewire;

use App\Models\Product;
use App\Models\ProductView;
use App\Services\CartService;
use App\Services\ContentBasedRecommender;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Halaman Detail Produk.
 *
 * - Mencatat product_view (sinyal CBF "produk dilihat").
 * - Tambah ke keranjang lewat CartService (aturan stok / batch PO).
 * - "Beli sekarang" langsung ke checkout (tanpa lewat keranjang) + pilihan skema DP / lunas.
 * - Tombol "Chat artis" membuka percakapan dengan konteks produk.
 * - "Produk serupa" dihitung item-to-item dengan weighted cosine similarity.
 */
#[Layout('components.layouts.app')]
class ProductDetail extends Component
{
    public Product $product;

    public int $quantity = 1;

    /** Skema bayar PO: 'full' (lunas) atau 'dp' (hanya bila batch mengizinkan DP). */
    public string $scheme = 'full';

    public function mount(Product $product): void
    {
        abort_unless($product->isPubliclyVisible(), 404);

        $this->product = $product->load([
            'images',
            'openBatch',
            'artistProfile.user',
            'character.fandom',
            'merchandiseType',
        ]);

        $this->scheme = $this->product->openBatch?->is_dp ? 'dp' : 'full';

        $this->recordView();
    }

    public function updatedScheme(): void
    {
        $this->scheme = $this->scheme === 'dp' && $this->product->openBatch?->is_dp ? 'dp' : 'full';
    }

    /** Jejak "produk dilihat" (tidak dicatat ulang dalam 1 jam). */
    private function recordView(): void
    {
        if (! Auth::check() || $this->isOwnProduct()) {
            return;
        }

        $alreadyLogged = ProductView::query()
            ->where('user_id', Auth::id())
            ->where('product_id', $this->product->id)
            ->where('viewed_at', '>=', now()->subHour())
            ->exists();

        if (! $alreadyLogged) {
            ProductView::create([
                'user_id'    => Auth::id(),
                'product_id' => $this->product->id,
                'viewed_at'  => now(),
            ]);
        }
    }

    public function isOwnProduct(): bool
    {
        return Auth::check() && $this->product->artistProfile?->user_id === Auth::id();
    }

    #[Computed]
    public function maxQuantity(): int
    {
        return max(1, app(CartService::class)->maxQuantity($this->product));
    }

    #[Computed]
    public function unavailableReason(): ?string
    {
        return app(CartService::class)->unavailableReason($this->product);
    }

    public function updatedQuantity(): void
    {
        $this->quantity = max(1, min($this->maxQuantity, (int) $this->quantity));
    }

    public function increment(): void
    {
        $this->quantity++;
        $this->updatedQuantity();
    }

    public function decrement(): void
    {
        $this->quantity--;
        $this->updatedQuantity();
    }

    public function addToCart(bool $buyNow = false)
    {
        if ($buyNow) {
            return $this->buyNow();
        }

        if (! Auth::check()) {
            return $this->toLogin('Masuk dulu untuk menambahkan ke keranjang.');
        }

        try {
            $item = app(CartService::class)->add(Auth::user(), $this->product, $this->quantity);
        } catch (ValidationException $e) {
            $this->dispatch('toast', message: $e->errors()['cart'][0] ?? 'Gagal menambahkan.', tone: 'error');

            return null;
        }

        // skema PO yang dipilih dibawa ke checkout dari keranjang
        session(["cart_scheme.{$this->product->id}" => $this->scheme]);

        $this->dispatch('cart-updated');
        $this->dispatch('toast', message: "{$this->quantity} pcs masuk keranjang.");
        $this->quantity = 1;

        return null;
    }

    /** Beli langsung: tidak masuk keranjang, langsung ke halaman checkout. */
    public function buyNow()
    {
        if (! Auth::check()) {
            return $this->toLogin('Masuk dulu untuk membeli.');
        }

        if ($this->isOwnProduct()) {
            $this->dispatch('toast', message: 'Tidak bisa membeli produk dari tokomu sendiri.', tone: 'error');

            return null;
        }

        $cart = app(CartService::class);
        $this->product->load('openBatch');
        if ($reason = $cart->unavailableReason($this->product)) {
            $this->dispatch('toast', message: $reason, tone: 'error');

            return null;
        }

        $qty = max(1, min($cart->maxQuantity($this->product), (int) $this->quantity));
        $this->updatedScheme();

        session([
            'checkout_lines'    => [['product_id' => $this->product->id, 'quantity' => $qty, 'scheme' => $this->scheme]],
            'checkout_cart_ids' => [],
        ]);

        return $this->redirect(route('checkout'), navigate: true);
    }

    private function toLogin(string $message)
    {
        session()->put('url.intended', url()->route('product.detail', $this->product));
        session()->flash('status', $message);

        return $this->redirect(route('login'), navigate: true);
    }

    #[Computed]
    public function reviews()
    {
        return $this->product->productReviews()
            ->with('reviewer:id,name,profile_photo')
            ->latest('created_at')
            ->take(8)
            ->get();
    }

    #[Computed]
    public function ratingBreakdown(): array
    {
        $counts = $this->product->productReviews()
            ->selectRaw('rating, COUNT(*) as total')
            ->groupBy('rating')
            ->pluck('total', 'rating');

        $sum = max(1, (int) $counts->sum());

        return collect(range(5, 1))
            ->mapWithKeys(fn (int $star) => [$star => [
                'count'   => (int) ($counts[$star] ?? 0),
                'percent' => round(((int) ($counts[$star] ?? 0)) / $sum * 100),
            ]])
            ->all();
    }

    #[Computed]
    public function moreFromArtist()
    {
        return Product::query()
            ->visible()
            ->with(['primaryImage', 'openBatch', 'artistProfile', 'merchandiseType', 'character.fandom'])
            ->where('artist_profile_id', $this->product->artist_profile_id)
            ->whereKeyNot($this->product->id)
            ->latest('created_at')
            ->take(4)
            ->get();
    }

    /** Produk serupa (CBF item-to-item), tidak termasuk produk toko yang sama agar variatif. */
    #[Computed]
    public function similar()
    {
        $exclude = $this->moreFromArtist->pluck('id');

        return app(ContentBasedRecommender::class)
            ->similarTo($this->product, 12)
            ->reject(fn ($p) => $exclude->contains($p->id))
            ->take(4)
            ->values();
    }

    public function render()
    {
        return view('livewire.product-detail')
            ->title($this->product->product_name);
    }
}
