<?php

namespace App\Livewire\Studio;

use App\Livewire\Concerns\RunsActions;
use App\Models\BalanceTransaction;
use App\Models\Withdrawal;
use App\Services\WithdrawalService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/** Saldo artis: tertahan vs tersedia, mutasi (balance_transaction), tarik dana. */
#[Layout('components.layouts.app')]
#[Title('Saldo & penarikan')]
class Balance extends Component
{
    use RunsActions, WithPagination;

    public $amount = null;

    /** Penarikan yang sedang dibuka detailnya (modal). */
    public ?int $detailId = null;

    public function showWithdrawal(int $id): void
    {
        $this->detailId = Withdrawal::where('artist_profile_id', Auth::user()->artistProfile->id)->whereKey($id)->value('id');
    }

    public function withdraw(): void
    {
        $this->validate(['amount' => 'required|numeric|min:1'], [], ['amount' => 'jumlah']);
        $ok = $this->attempt(fn () => app(WithdrawalService::class)->request(Auth::user()->artistProfile, (float) $this->amount), 'Pengajuan penarikan dikirim ke admin.');
        if ($ok) {
            $this->amount = null;
        }
    }

    public function render()
    {
        $profile = Auth::user()->artistProfile->fresh();

        return view('livewire.studio.balance', [
            'profile'     => $profile,
            'mutations'   => BalanceTransaction::with('order:id,order_code')->where('artist_profile_id', $profile->id)->latest('created_at')->latest('id')->paginate(15),
            'withdrawals' => Withdrawal::where('artist_profile_id', $profile->id)->latest('requested_at')->latest('id')->take(10)->get(),
            'detail'      => $this->detailId ? Withdrawal::with('processor')->where('artist_profile_id', $profile->id)->find($this->detailId) : null,
        ]);
    }
}
