<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\RunsActions;
use App\Models\Withdrawal;
use App\Models\ArtistProfile;
use App\Services\WithdrawalEligibilityService;
use App\Services\WithdrawalService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

/** Proses penarikan dana artis: proses → transfer + unggah bukti, atau tolak (saldo kembali). */
#[Layout('components.layouts.app')]
#[Title('Penarikan dana')]
class Withdrawals extends Component
{
    use RunsActions, WithFileUploads;

    public array $proof = [];

    public array $reason = [];

    private function find(int $id): Withdrawal
    {
        return Withdrawal::with('artistProfile')->findOrFail($id);
    }

    public function process(int $id): void
    {
        $this->attempt(fn () => app(WithdrawalService::class)->markProcessing($this->find($id), Auth::user()), 'Ditandai sedang diproses.');
    }

    public function complete(int $id): void
    {
        $this->validate(["proof.$id" => 'required|image|max:4096'], ["proof.$id.required" => 'Pilih foto bukti transfer dulu.'], ["proof.$id" => 'bukti transfer']);
        $url = '/uploads/' . $this->proof[$id]->store('withdrawals', 'uploads');
        $this->attempt(fn () => app(WithdrawalService::class)->complete($this->find($id), Auth::user(), $url), 'Penarikan selesai.');
        unset($this->proof[$id]);
    }

    public function reject(int $id): void
    {
        $this->validate(["reason.$id" => 'required|string|min:3|max:255'], ["reason.$id.required" => 'Tulis alasan penolakan dulu.'], ["reason.$id" => 'alasan']);
        $this->attempt(fn () => app(WithdrawalService::class)->reject($this->find($id), Auth::user(), $this->reason[$id]), 'Penarikan ditolak, saldo dikembalikan.');
    }

    public function render()
    {
        $service = app(WithdrawalEligibilityService::class);
        // Hitung semua toko, tidak terbatas halaman daftar penarikan terbaru.
        $shops = ArtistProfile::with('user')->orderBy('shop_name')->get()->map(function ($shop) use ($service) {
            return ['profile' => $shop, 'eligibility' => $service->summary($shop)];
        });
        $sumCents = fn (callable $fn) => $shops->sum(fn ($row) => (int) round($fn($row) * 100)) / 100;
        $totals = [
            'available' => $sumCents(fn ($r) => (float) $r['profile']->available_balance),
            'held' => $sumCents(fn ($r) => (float) $r['profile']->held_balance),
            'eligible' => $sumCents(fn ($r) => $r['eligibility']['eligible']),
            'pending' => $sumCents(fn ($r) => $r['eligibility']['pending_withdrawal']),
        ];
        return view('livewire.admin.withdrawals', [
            'rows' => Withdrawal::with(['artistProfile', 'processor'])->orderByRaw("withdrawal_status IN ('submitted','processing') DESC")->latest('requested_at')->take(100)->get(),
            'shops' => $shops, 'totals' => $totals, 'demoSimulation' => $service->isDemoSimulation(),
        ]);
    }
}
