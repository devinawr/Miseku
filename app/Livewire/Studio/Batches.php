<?php

namespace App\Livewire\Studio;

use App\Livewire\Concerns\RunsActions;
use App\Models\PoBatch;
use App\Services\PoService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Batch PO (BPMN 3): pantau slot & target minimum, tutup lebih awal,
 * mulai produksi & tandai selesai untuk semua pesanan batch sekaligus.
 */
#[Layout('components.layouts.app')]
#[Title('Batch PO')]
class Batches extends Component
{
    use RunsActions;

    private function batch(int $id): PoBatch
    {
        return PoBatch::whereHas('product', fn ($q) => $q->where('artist_profile_id', Auth::user()->artistProfile->id))->findOrFail($id);
    }

    public function closeNow(int $id): void
    {
        $r = $this->attempt(fn () => app(PoService::class)->closeNow($this->batch($id)));
        if ($r) {
            $this->dispatch('toast', message: $r['reached'] ? "PO ditutup ({$r['total']} pcs) — target tercapai." : "PO ditutup tapi target tidak tercapai ({$r['total']} pcs) — pembeli direfund otomatis.", tone: $r['reached'] ? 'ok' : 'error');
        }
    }

    public function start(int $id): void
    {
        $n = $this->attempt(fn () => app(PoService::class)->startProduction($this->batch($id)));
        if ($n !== false) {
            $this->dispatch('toast', message: "Produksi dimulai untuk {$n} pesanan.");
        }
    }

    public function complete(int $id): void
    {
        $n = $this->attempt(fn () => app(PoService::class)->completeProduction($this->batch($id)));
        if ($n !== false) {
            $this->dispatch('toast', message: "Produksi selesai untuk {$n} pesanan. Pesanan DP menerima tagihan pelunasan.");
        }
    }

    public function render()
    {
        $batches = PoBatch::with('product.primaryImage')
            ->whereHas('product', fn ($q) => $q->where('artist_profile_id', Auth::user()->artistProfile->id))
            ->orderByRaw("FIELD(batch_status, 'open', 'closed', 'in_production', 'completed', 'cancelled')")->latest('id')->get()
            ->each(function (PoBatch $b) {
                $b->paid_qty = $b->orderedQuantity(true);
                $b->all_qty = $b->orderedQuantity();
            });

        return view('livewire.studio.batches', compact('batches'));
    }
}
