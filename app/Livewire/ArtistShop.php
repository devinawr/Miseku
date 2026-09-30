<?php

namespace App\Livewire;

use App\Models\ArtistProfile;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Modul 3 — Halaman Toko Artist.
 *
 * Banner + avatar + bio + rating toko, dengan tiga tab: Karya, Review, Tentang.
 * Tab disimpan di query string supaya link ke tab tertentu bisa dibagikan.
 */
#[Layout('components.layouts.app')]
class ArtistShop extends Component
{
    use WithPagination;

    public ArtistProfile $artist;

    /** karya | review | tentang */
    #[Url(as: 'tab', except: 'karya')]
    public string $tab = 'karya';

    #[Url(as: 'urut', except: 'terbaru')]
    public string $sort = 'terbaru';

    public function mount(ArtistProfile $artist): void
    {
        $this->artist = $artist->load(['user', 'banner']);
    }

    public function selectTab(string $tab): void
    {
        $this->tab = in_array($tab, ['karya', 'review', 'tentang'], true) ? $tab : 'karya';
        $this->resetPage();
    }

    public function updatedSort(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function stats(): array
    {
        return [
            'karya'  => $this->artist->products()->visible()->count(),
            'po'     => $this->artist->products()->visible()->where('availability_type', 'pre_order')->count(),
            'review' => $this->artist->shopReviews()->count(),
        ];
    }

    #[Computed]
    public function reviews()
    {
        return $this->artist->shopReviews()
            ->with('reviewer:id,name,profile_photo')
            ->latest('created_at')
            ->take(10)
            ->get();
    }

    /** Fandom yang paling sering digarap toko ini — dipakai di tab "Tentang". */
    #[Computed]
    public function topFandoms()
    {
        return $this->artist->products()
            ->visible()
            ->with('character.fandom')
            ->get()
            ->groupBy(fn (Product $p) => $p->character->fandom->fandom_name)
            ->map->count()
            ->sortDesc()
            ->take(6);
    }

    public function render()
    {
        $products = $this->artist->products()
            ->visible()
            ->with(['primaryImage', 'openBatch', 'artistProfile', 'merchandiseType', 'character.fandom'])
            ->tap(fn (Builder $q) => match ($this->sort) {
                'termurah' => $q->orderBy('price'),
                'termahal' => $q->orderByDesc('price'),
                'rating'   => $q->orderByDesc('rating')->orderByDesc('rating_count'),
                default    => $q->orderByDesc('created_at'),
            })
            ->paginate(8);

        return view('livewire.artist-shop', ['products' => $products])
            ->title($this->artist->shop_name);
    }
}
