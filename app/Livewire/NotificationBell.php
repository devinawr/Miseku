<?php

namespace App\Livewire;

use App\Models\TrackTransaksi;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/** Ikon lonceng di header: jumlah belum dibaca + 6 notifikasi terbaru (polling 30 detik). */
class NotificationBell extends Component
{
    public function open(int $id): void
    {
        $note = TrackTransaksi::where('user_id', Auth::id())->findOrFail($id);
        $note->update(['is_read' => true]);

        $this->redirect(app(NotificationService::class)->linkFor($note, Auth::user()), navigate: true);
    }

    public function readAll(): void
    {
        TrackTransaksi::where('user_id', Auth::id())->where('is_read', false)->update(['is_read' => true]);
    }

    public function render()
    {
        $base = TrackTransaksi::where('user_id', Auth::id());

        return view('livewire.notification-bell', [
            'unread' => (clone $base)->where('is_read', false)->count(),
            'latest' => (clone $base)->latest('id')->take(6)->get(),
        ]);
    }
}
