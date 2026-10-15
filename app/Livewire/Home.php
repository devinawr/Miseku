<?php

namespace App\Livewire;

use App\Models\ArtistProfile;
use App\Models\Fandom;
use App\Models\Product;
use App\Services\ContentBasedRecommender;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Beranda — konten bisnis langsung di depan (masukan dosen):
 * banner slider → fandom → rekomendasi CBF → pre-order → produk baru → toko.
 */
#[Layout('components.layouts.app')]
#[Title('Beranda')]
class Home extends Component
{
    private const WITH = ['primaryImage', 'openBatch', 'artistProfile', 'merchandiseType', 'character.fandom'];

    /**
     * Slide banner = gambar jadi (seperti banner toko hobi), 2 per layar di desktop.
     * File di public/img/banner/ (lihat README.txt di folder itu):
     *   preorder.jpg · fandom-{slug}.jpg (satu per fandom) · baru.jpg · artis.jpg
     * Urutan: pre-order → fandom favorit user → fandom lain (terbanyak produk) → baru → artis.
     * Slide yang filenya tidak ada dilewati.
     */
    #[Computed]
    public function slides(): array
    {
        $slides = [];
        $add = function (?string $image, string $alt, string $href) use (&$slides) {
            if ($image) {
                $slides[] = ['image' => $image, 'alt' => $alt, 'href' => $href];
            }
        };

        $hasPo = Product::visible()->where('availability_type', 'pre_order')
            ->whereHas('batches', fn ($q) => $q->open())->exists();
        if ($hasPo) {
            $add(Fandom::publicImage('img/banner/preorder'), 'Pre-order dibuka — bisa DP dulu', route('catalog', ['status' => 'pre_order']));
        }

        $fav = $this->favoriteIds();
        $this->fandomPopuler
            ->sortBy(fn ($f) => $fav->contains($f->id) ? 0 : 1, SORT_REGULAR, false)
            ->each(fn ($f) => $add($f->banner_url, $f->fandom_name . ' — merch collection', route('catalog', ['fandom' => $f->id])));

        $add(Fandom::publicImage('img/banner/baru'), 'Produk baru', route('catalog'));

        if (! Auth::check() || Auth::user()->role === 'buyer') {
            $add(Fandom::publicImage('img/banner/artis'), 'Buka toko di Miseku', Auth::check() ? route('account', ['tab' => 'toko']) : route('register'));
        }

        return $slides;
    }

    private function favoriteIds(): Collection
    {
        return Auth::check() ? Auth::user()->favoriteFandoms()->pluck('fandom.id') : collect();
    }

    /** Rekomendasi CBF (login) atau produk terpopuler (tamu / belum ada jejak). */
    #[Computed]
    public function rekomendasi(): array
    {
        if (Auth::check()) {
            $items = app(ContentBasedRecommender::class)->forUser(Auth::user(), 10);

            if ($items->isNotEmpty()) {
                // Kalau hasil CBF kurang dari 1 baris penuh, lengkapi dengan produk populer
                // (ditandai berbeda) supaya beranda tidak terlihat kosong.
                if ($items->count() < 10) {
                    $own = Auth::user()->artistProfile?->id;
                    $filler = Product::visible()->with(self::WITH)
                        ->whereNotIn('id', $items->pluck('id'))
                        ->when($own, fn ($q) => $q->where('artist_profile_id', '!=', $own))
                        ->orderByDesc('rating')->orderByDesc('rating_count')
                        ->take(10 - $items->count())->get()
                        ->each(fn ($p) => $p->reason = 'Populer di Miseku');
                    $items = $items->concat($filler)->values();
                }

                return ['items' => $items, 'personal' => true];
            }
        }

        return [
            'items'    => Product::visible()->with(self::WITH)->orderByDesc('rating')->orderByDesc('rating_count')->take(10)->get(),
            'personal' => false,
        ];
    }

    #[Computed]
    public function needsOnboarding(): bool
    {
        return Auth::check() && ! Auth::user()->isAdmin() && $this->favoriteIds()->isEmpty();
    }

    #[Computed]
    public function preOrderBuka()
    {
        return Product::visible()->with(self::WITH)
            ->where('availability_type', 'pre_order')
            ->whereHas('batches', fn ($q) => $q->open())
            ->get()
            ->sortBy(fn (Product $p) => $p->openBatch?->closes_at)
            ->take(5)
            ->values();
    }

    #[Computed]
    public function terbaru()
    {
        return Product::visible()->with(self::WITH)->latest('created_at')->take(10)->get();
    }

    /** Fandom aktif, diurutkan dari yang produknya paling banyak (untuk baris pintasan). */
    #[Computed]
    public function fandomPopuler()
    {
        $jumlahProduk = Product::query()
            ->visible()
            ->join('fandom_character', 'fandom_character.id', '=', 'product.fandom_character_id')
            ->whereColumn('fandom_character.fandom_id', 'fandom.id')
            ->selectRaw('count(*)');

        return Fandom::active()
            ->select('fandom.id', 'fandom.fandom_name')
            ->selectSub($jumlahProduk, 'product_count')
            ->orderByDesc('product_count')
            ->get();
    }

    #[Computed]
    public function tokoPilihan()
    {
        return ArtistProfile::query()
            // Tanpa ->take() di dalam eager load: Laravel akan memakai ROW_NUMBER() OVER(),
            // yang tidak didukung MySQL 5.7 / MariaDB lama (XAMPP). Dibatasi 3 di PHP saja.
            ->with(['user', 'banner', 'products' => fn ($q) => $q->visible()->with('primaryImage')->latest('created_at')])
            ->withCount(['products as product_count' => fn ($q) => $q->visible()])
            ->orderByDesc('rating')
            ->take(3)
            ->get()
            ->each(fn ($shop) => $shop->setRelation('products', $shop->products->take(3)->values()));
    }

    public function render()
    {
        return view('livewire.home');
    }
}
