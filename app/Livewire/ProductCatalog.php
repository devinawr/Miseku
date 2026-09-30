<?php

namespace App\Livewire;

use App\Models\Fandom;
use App\Models\FandomCharacter;
use App\Models\MerchandiseType;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Modul 1 — Katalog Produk / Karya.
 *
 * Satu komponen Livewire menangani search, filter fandom & jenis merchandise,
 * filter ketersediaan (ready stock / pre-order), pengurutan, dan pagination.
 * Semua state diikat ke query string lewat #[Url] supaya hasil filter bisa
 * di-share / di-bookmark dan tetap utuh saat halaman di-refresh.
 */
#[Layout('components.layouts.app')]
#[Title('Katalog Karya')]
class ProductCatalog extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'fandom', except: '')]
    public string $fandomId = '';

    #[Url(as: 'karakter', except: '')]
    public string $characterId = '';

    #[Url(as: 'tipe', except: '')]
    public string $typeId = '';

    /** semua | ready_stock | pre_order */
    #[Url(as: 'status', except: 'semua')]
    public string $availability = 'semua';

    /** terbaru | termurah | termahal | rating */
    #[Url(as: 'urut', except: 'terbaru')]
    public string $sort = 'terbaru';

    /** Ganti fandom = pilihan karakter lama tidak relevan lagi. */
    public function updatedFandomId(): void
    {
        $this->characterId = '';
    }

    /** Setiap perubahan filter harus kembali ke halaman 1. */
    public function updated(string $property): void
    {
        if ($property !== 'page') {
            $this->resetPage();
        }
    }

    public function resetFilter(): void
    {
        $this->reset(['search', 'fandomId', 'characterId', 'typeId', 'availability', 'sort']);
        $this->resetPage();
    }

    public function clearSearch(): void
    {
        $this->search = '';
        $this->resetPage();
    }

    /** Jumlah filter aktif — dipakai untuk badge di tombol "Reset". */
    #[Computed]
    public function activeFilterCount(): int
    {
        return collect([
            $this->search !== '',
            $this->fandomId !== '',
            $this->characterId !== '',
            $this->typeId !== '',
            $this->availability !== 'semua',
        ])->filter()->count();
    }

    /** Dropdown filter hanya berisi master ber-status active (sudah disetujui admin). */
    #[Computed]
    public function fandoms()
    {
        return Fandom::active()->orderBy('fandom_name')->get(['id', 'fandom_name']);
    }

    /** Karakter mengikuti fandom terpilih; kalau fandom belum dipilih, tampil semua per grup. */
    #[Computed]
    public function characters()
    {
        return FandomCharacter::query()
            ->active()
            ->when($this->fandomId !== '', fn (Builder $q) => $q->where('fandom_id', $this->fandomId))
            ->with('fandom:id,fandom_name')
            ->orderBy('character_name')
            ->get(['id', 'fandom_id', 'character_name'])
            ->groupBy(fn (FandomCharacter $c) => $c->fandom->fandom_name);
    }

    #[Computed]
    public function merchandiseTypes()
    {
        return MerchandiseType::active()->orderBy('type_name')->get(['id', 'type_name']);
    }

    private function products(): LengthAwarePaginator
    {
        return Product::query()
            ->visible()
            ->with([
                'primaryImage',
                'openBatch',
                'artistProfile:id,user_id,shop_name,rating',
                'character:id,fandom_id,character_name',
                'character.fandom:id,fandom_name',
                'merchandiseType:id,type_name',
            ])
            ->search($this->search)
            ->when($this->fandomId !== '', fn (Builder $q) => $q->whereHas(
                'character',
                fn (Builder $c) => $c->where('fandom_id', $this->fandomId)
            ))
            ->when($this->characterId !== '', fn (Builder $q) => $q->where('fandom_character_id', $this->characterId))
            ->when($this->typeId !== '', fn (Builder $q) => $q->where('merchandise_type_id', $this->typeId))
            ->when($this->availability !== 'semua', fn (Builder $q) => $q->where('availability_type', $this->availability))
            ->tap(fn (Builder $q) => match ($this->sort) {
                'termurah' => $q->orderBy('price'),
                'termahal' => $q->orderByDesc('price'),
                'rating'   => $q->orderByDesc('rating')->orderByDesc('rating_count'),
                default    => $q->orderByDesc('created_at'),
            })
            ->paginate(12);
    }

    public function render()
    {
        return view('livewire.product-catalog', [
            'products' => $this->products(),
        ]);
    }
}
