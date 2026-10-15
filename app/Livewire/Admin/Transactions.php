<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\RunsActions;
use App\Models\CustomerOrder;
use App\Models\Payment;
use App\Services\RefundService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

/** Monitoring transaksi: pesanan, pembayaran, dan refund manual (transfer + bukti). */
#[Layout('components.layouts.app')]
#[Title('Transaksi & refund')]
class Transactions extends Component
{
    use RunsActions, WithFileUploads, WithPagination;

    #[Url]
    public string $tab = 'pesanan';

    #[Url]
    public string $q = '';

    #[Url]
    public string $status = '';

    /** refund payment id => file */
    public array $proof = [];

    public function updatedTab(): void
    {
        $this->resetPage();
    }

    public function updatedQ(): void
    {
        $this->resetPage();
    }

    public function completeRefund(int $id): void
    {
        $this->validate(["proof.$id" => 'required|image|max:4096'], [], ["proof.$id" => 'bukti transfer']);
        $refund = Payment::where('payment_type', 'refund')->where('payment_status', 'pending')->findOrFail($id);
        $url = '/uploads/' . $this->proof[$id]->store('refunds', 'uploads');
        $this->attempt(fn () => app(RefundService::class)->completeManual($refund, $url), 'Refund manual ditandai selesai.');
        unset($this->proof[$id]);
    }

    public function render()
    {
        $data = ['statuses' => CustomerOrder::STATUS_LABELS];

        if ($this->tab === 'pesanan') {
            $data['orders'] = CustomerOrder::with(['buyer', 'artistProfile'])
                ->when($this->q, fn ($w) => $w->where(fn ($x) => $x->where('order_code', 'like', "%{$this->q}%")->orWhereHas('buyer', fn ($b) => $b->where('name', 'like', "%{$this->q}%"))))
                ->when($this->status, fn ($w) => $w->where('order_status', $this->status))
                ->latest('created_at')->latest('id')->paginate(20);
        } elseif ($this->tab === 'pembayaran') {
            $data['payments'] = Payment::with('order.buyer')->where('payment_type', '!=', 'refund')
                ->when($this->q, fn ($w) => $w->where('midtrans_order_id', 'like', "%{$this->q}%"))
                ->latest('created_at')->latest('id')->paginate(20);
        } else {
            $data['refunds'] = Payment::with('order.buyer', 'order.artistProfile')->where('payment_type', 'refund')
                ->orderByRaw("payment_status = 'pending' DESC")->latest('created_at')->latest('id')->paginate(20);
        }

        return view('livewire.admin.transactions', $data);
    }
}
