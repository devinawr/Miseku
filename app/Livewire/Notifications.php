<?php

namespace App\Livewire;

use App\Models\TrackTransaksi;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/** Pusat notifikasi: notifikasi transaksi (track_transaksi) + produk baru sesuai minat (dihitung). */
#[Layout('components.layouts.app')]
#[Title('Notifikasi')]
class Notifications extends Component
{
    use WithPagination;

    #[Url]
    public string $jenis = 'semua';

    public const TYPES = [
        'semua' => 'Semua', 'order' => 'Pesanan', 'payment' => 'Pembayaran', 'po_settlement' => 'Pelunasan PO',
        'event_pickup' => 'Pick-up event', 'cancellation' => 'Pembatalan', 'custom' => 'Custom', 'design_poll' => 'Voting',
        'withdrawal' => 'Penarikan', 'attribute' => 'Master data',
    ];

    public function updatedJenis(): void
    {
        $this->resetPage();
    }

    public function open(int $id): void
    {
        $note = TrackTransaksi::where('user_id', Auth::id())->findOrFail($id);
        $note->update(['is_read' => true]);
        $this->redirect(app(NotificationService::class)->linkFor($note, Auth::user()), navigate: true);
    }

    public function readAll(): void
    {
        TrackTransaksi::where('user_id', Auth::id())->where('is_read', false)->update(['is_read' => true]);
        $this->dispatch('toast', message: 'Semua notifikasi ditandai dibaca.');
    }

    public function render()
    {
        $notes = TrackTransaksi::where('user_id', Auth::id())
            ->when($this->jenis !== 'semua', fn ($q) => $q->where('notification_type', $this->jenis))
            ->latest('id')->paginate(15);

        return view('livewire.notifications', [
            'notes'       => $notes,
            'newProducts' => app(NotificationService::class)->newProductsFor(Auth::user(), 14, 8),
        ]);
    }
}
