<?php

namespace App\Livewire\Orders;

use App\Models\CustomerOrder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/** Daftar pesanan pembeli (track transaksi). */
#[Layout('components.layouts.app')]
#[Title('Pesanan saya')]
class Index extends Component
{
    use WithPagination;

    public const TABS = [
        'semua'   => ['Semua', null],
        'bayar'   => ['Belum bayar', ['awaiting_payment', 'awaiting_settlement']],
        'proses'  => ['Diproses', ['paid', 'awaiting_production', 'in_production', 'production_completed']],
        'kirim'   => ['Dikirim / siap diambil', ['shipped', 'ready_for_pickup']],
        'selesai' => ['Selesai', ['completed']],
        'batal'   => ['Batal / gagal', ['cancelled', 'failed']],
    ];

    #[Url]
    public string $tab = 'semua';

    public function updatedTab(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $statuses = self::TABS[$this->tab][1] ?? null;

        $orders = CustomerOrder::query()
            ->where('buyer_id', Auth::id())
            ->with(['artistProfile', 'items.product.primaryImage', 'customRequest', 'payments'])
            ->when($statuses, fn ($q) => $q->whereIn('order_status', $statuses))
            ->latest('created_at')->latest('id')
            ->paginate(10);

        $counts = CustomerOrder::where('buyer_id', Auth::id())
            ->selectRaw('order_status, COUNT(*) AS n')->groupBy('order_status')->pluck('n', 'order_status');

        return view('livewire.orders.index', compact('orders', 'counts'));
    }
}
