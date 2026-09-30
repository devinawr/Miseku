<?php

namespace App\Livewire;

use App\Models\Product;
use App\Models\ProductView;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Modul 2 — Halaman Detail Produk.
 *
 * Galeri gambar (state gambar aktif dipegang Alpine.js karena murni tampilan),
 * info artis, harga + info batch PO, badge status, pengatur jumlah, dan
 * section ulasan produk lengkap dengan sebaran bintang.
 */
#[Layout('components.layouts.app')]
class ProductDetail extends Component
{
    public Product $product;

    public int $quantity = 1;

    public function mount(Product $product): void
    {
        abort_unless($product->product_status === 'active', 404);

        $this->product = $product->load([
            'images',
            'openBatch',
            'artistProfile.user',
            'character.fandom',
            'merchandiseType',
        ]);

        $this->recordView();
    }

    /**
     * Simpan jejak "produk pernah dilihat" — bahan vektor rekomendasi (BPMN 8).
     * Hanya untuk user yang sudah login, dan tidak dicatat berulang dalam 1 jam
     * agar refresh halaman tidak menggandakan data.
     */
    private function recordView(): void
    {
        if (! Auth::check()) {
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

    /** Batas kuantitas: stok untuk ready stock, longgar untuk pre-order. */
    public function updatedQuantity(): void
    {
        $max = $this->product->is_pre_order ? 99 : max(1, (int) $this->product->stock);
        $this->quantity = max(1, min($max, (int) $this->quantity));
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

    /**
     * Tahap bimbingan: modul keranjang & checkout belum dikerjakan.
     * Yang sudah berfungsi adalah penjagaan aksesnya — pengunjung yang belum
     * login diarahkan ke halaman masuk, dan dikembalikan ke sini setelahnya.
     */
    public function addToCart()
    {
        if (! Auth::check()) {
            session()->put('url.intended', url()->current());
            session()->flash('status', 'Masuk dulu untuk melanjutkan pemesanan.');

            return $this->redirect(route('login'), navigate: true);
        }

        $this->dispatch('toast', message: 'Modul keranjang belum aktif — tampilan detail produk sudah siap.');

        return null;
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

    /** Sebaran bintang 5→1 untuk bar ulasan. */
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

    /** Karya lain dari toko yang sama. */
    #[Computed]
    public function moreFromArtist()
    {
        return Product::query()
            ->visible()
            ->with(['primaryImage', 'openBatch', 'artistProfile', 'merchandiseType'])
            ->where('artist_profile_id', $this->product->artist_profile_id)
            ->whereKeyNot($this->product->id)
            ->latest('created_at')
            ->take(4)
            ->get();
    }

    public function render()
    {
        return view('livewire.product-detail')
            ->title($this->product->product_name);
    }
}
