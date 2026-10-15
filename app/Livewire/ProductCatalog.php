<?php

namespace App\Livewire;

use App\Models\Fandom;
use App\Models\FandomCharacter;
use App\Models\MerchandiseType;
use App\Models\Product;
use App\Models\SearchHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Modul 1 — Katalog Produk.
 *
 * Satu komponen Livewire menangani search, filter fandom → karakter → jenis merchandise,
 * ketersediaan (ready stock / pre-order), rentang harga, pengurutan, dan pagination.
 * Setiap pencarian user yang login dicatat ke search_history (bahan rekomendasi CBF).
 * Semua state diikat ke query string lewat #[Url] supaya hasil filter bisa
 * di-share / di-bookmark dan tetap utuh saat halaman di-refresh.
 */
#[Layout('components.layouts.app')]
#[Title('Katalog Produk')]
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

    /** Rentang harga (rupiah), kosong = tanpa batas. */
    #[Url(as: 'min', except: '')]
    public string $minPrice = '';

    #[Url(as: 'max', except: '')]
    public string $maxPrice = '';

    public function mount(): void
    {
        // Datang dari kotak cari di header (/katalog?q=...) → langsung dicatat.
        $this->recordSearch();
    }

    /** Ganti fandom = pilihan karakter lama tidak relevan lagi. */
    public function updatedFandomId(): void
    {
        $this->characterId = '';
    }

    /** Setiap perubahan filter harus kembali ke halaman 1, lalu dicatat ke search_history. */
    public function updated(string $property): void
    {
        if ($property === 'page') {
            return;
        }

        $this->resetPage();

        if (in_array($property, ['search', 'fandomId', 'characterId', 'typeId'], true)) {
            $this->recordSearch();
        }
    }

    public function resetFilter(): void
    {
        $this->reset(['search', 'fandomId', 'characterId', 'typeId', 'availability', 'sort', 'minPrice', 'maxPrice']);
        $this->resetPage();
    }

    /**
     * Simpan riwayat pencarian (bahan profil CBF).
     *
     * Kata kunci diterjemahkan ke atribut: kalau "gojo" cocok dengan satu
     * karakter, kolom fandom_character_id & fandom_id ikut terisi. Filter
     * dropdown yang dipilih langsung dipakai apa adanya.
     *
     * Supaya tidak banjir data saat mengetik: kalau entri terakhir (≤ 2 menit)
     * adalah awalan kata kunci sekarang ("goj" → "gojo"), entri itu diperbarui,
     * bukan ditambah. Kombinasi yang sama persis dalam 30 menit tidak dicatat ulang.
     */
    private function recordSearch(): void
    {
        if (! Auth::check()) {
            return;
        }

        $keyword = mb_substr(trim($this->search), 0, 200);

        if ($keyword !== '' && mb_strlen($keyword) < 3) {
            return;
        }

        $row = [
            'keyword'             => $keyword,
            'fandom_id'           => $this->fandomId !== '' ? (int) $this->fandomId : null,
            'fandom_character_id' => $this->characterId !== '' ? (int) $this->characterId : null,
            'merchandise_type_id' => $this->typeId !== '' ? (int) $this->typeId : null,
        ];

        if ($keyword !== '') {
            $row = $this->resolveKeyword($keyword, $row);
        }

        if (! $row['fandom_id'] && ! $row['fandom_character_id'] && ! $row['merchandise_type_id'] && $keyword === '') {
            return;
        }

        $last = SearchHistory::where('user_id', Auth::id())->latest('searched_at')->first();

        if ($last) {
            $same = $last->keyword === $row['keyword']
                && $last->fandom_id === $row['fandom_id']
                && $last->fandom_character_id === $row['fandom_character_id']
                && $last->merchandise_type_id === $row['merchandise_type_id'];

            if ($same && $last->searched_at->gt(now()->subMinutes(30))) {
                return;
            }

            $typing = $keyword !== '' && $last->keyword !== ''
                && str_starts_with(mb_strtolower($keyword), mb_strtolower($last->keyword))
                && $last->searched_at->gt(now()->subMinutes(2));

            if ($typing) {
                $last->update($row + ['searched_at' => now()]);

                return;
            }
        }

        SearchHistory::create($row + ['user_id' => Auth::id(), 'searched_at' => now()]);
    }

    /** Cocokkan kata kunci ke master karakter / fandom / jenis merchandise. */
    private function resolveKeyword(string $keyword, array $row): array
    {
        $like = '%' . $keyword . '%';

        if (! $row['fandom_character_id']) {
            $chars = FandomCharacter::active()->where('character_name', 'like', $like)->limit(2)->get(['id', 'fandom_id']);
            if ($chars->count() === 1) {
                $row['fandom_character_id'] = $chars->first()->id;
                $row['fandom_id'] ??= $chars->first()->fandom_id;
            }
        }

        if (! $row['fandom_id']) {
            $fandoms = Fandom::active()->where('fandom_name', 'like', $like)->limit(2)->pluck('id');
            if ($fandoms->count() === 1) {
                $row['fandom_id'] = $fandoms->first();
            }
        }

        if (! $row['merchandise_type_id']) {
            $types = MerchandiseType::active()->where('type_name', 'like', $like)->limit(2)->pluck('id');
            if ($types->count() === 1) {
                $row['merchandise_type_id'] = $types->first();
            }
        }

        return $row;
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
            $this->minPrice !== '' || $this->maxPrice !== '',
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
            ->when(is_numeric($this->minPrice), fn (Builder $q) => $q->where('price', '>=', (float) $this->minPrice))
            ->when(is_numeric($this->maxPrice), fn (Builder $q) => $q->where('price', '<=', (float) $this->maxPrice))
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
