<?php

namespace App\Livewire\Admin;

use App\Models\Fandom;
use App\Models\FandomCharacter;
use App\Models\MerchandiseType;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Admin — persetujuan usulan master data dari artis:
 * fandom, karakter (fandom_character), dan jenis merchandise (merchandise_type).
 *
 * Usulan berstatus 'pending'. Admin bisa:
 *  - Setujui → status 'active'. Produk yang memakainya otomatis tampil di katalog.
 *  - Tolak   → status 'rejected'. Produk yang memakainya tetap tersembunyi,
 *              artis diminta mengganti pilihan di produknya.
 * Menyetujui karakter dari fandom yang masih pending ikut menyetujui fandomnya.
 * Menolak fandom ikut menolak karakter pending di dalamnya.
 */
#[Layout('components.layouts.app')]
#[Title('Persetujuan master data')]
class MasterApproval extends Component
{
    #[Url(as: 'status', except: 'pending')]
    public string $status = 'pending';

    public function mount(): void
    {
        if (! in_array($this->status, ['pending', 'rejected', 'active'], true)) {
            $this->status = 'pending';
        }
    }

    #[Computed]
    public function fandoms()
    {
        return Fandom::where('status', $this->status)
            ->with('proposer:id,name')
            ->withCount('characters')
            ->orderBy('fandom_name')->get();
    }

    #[Computed]
    public function characters()
    {
        return FandomCharacter::where('status', $this->status)
            ->with(['fandom:id,fandom_name,status', 'proposer:id,name'])
            ->orderBy('fandom_id')->orderBy('character_name')->get();
    }

    #[Computed]
    public function types()
    {
        return MerchandiseType::where('status', $this->status)
            ->with('proposer:id,name')
            ->orderBy('type_name')->get();
    }

    /** Jumlah usulan pending per jenis — untuk badge tab. */
    #[Computed]
    public function pendingCounts(): array
    {
        return [
            'fandom'    => Fandom::where('status', 'pending')->count(),
            'character' => FandomCharacter::where('status', 'pending')->count(),
            'type'      => MerchandiseType::where('status', 'pending')->count(),
        ];
    }

    /** Jumlah produk (belum dihapus) yang memakai master tertentu. */
    public function productsUsing(string $kind, int $id): int
    {
        $q = Product::where('product_status', '!=', 'deleted');

        return match ($kind) {
            'fandom'    => $q->whereHas('character', fn ($c) => $c->where('fandom_id', $id))->count(),
            'character' => $q->where('fandom_character_id', $id)->count(),
            'type'      => $q->where('merchandise_type_id', $id)->count(),
        };
    }

    public function approve(string $kind, int $id): void
    {
        DB::transaction(function () use ($kind, $id) {
            $item = $this->find($kind, $id);
            $item->update(['status' => 'active']);

            if ($kind === 'character' && $item->fandom->status !== 'active') {
                $item->fandom->update(['status' => 'active']);
            }
        });

        $this->refresh('Usulan disetujui. Produk yang memakainya sekarang tampil di katalog.');
    }

    public function reject(string $kind, int $id): void
    {
        DB::transaction(function () use ($kind, $id) {
            $item = $this->find($kind, $id);
            $item->update(['status' => 'rejected']);

            if ($kind === 'fandom') {
                FandomCharacter::where('fandom_id', $item->id)->where('status', 'pending')->update(['status' => 'rejected']);
            }
        });

        $this->refresh('Usulan ditolak. Produk yang memakainya tetap tersembunyi.');
    }

    private function find(string $kind, int $id)
    {
        $model = match ($kind) {
            'fandom'    => Fandom::class,
            'character' => FandomCharacter::class,
            'type'      => MerchandiseType::class,
            default     => abort(400),
        };

        return $model::findOrFail($id);
    }

    private function refresh(string $message): void
    {
        unset($this->fandoms, $this->characters, $this->types, $this->pendingCounts);
        $this->dispatch('toast', message: $message);
    }

    public function render()
    {
        return view('livewire.admin.master-approval');
    }
}
