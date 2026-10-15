<?php

namespace App\Livewire\Admin;

use App\Models\CommunityReport;
use App\Models\DiscussionTopic;
use App\Models\Fandom;
use App\Models\TopicMessage;
use App\Services\CommunityModerationService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Panel admin — Komunitas.
 *  Tab "Laporan" : antrean laporan dari pengguna → hapus konten (dengan alasan) atau tolak laporan.
 *  Tab "Semua topik": pantau semua topik di semua fandom, buka isinya, hapus bila melanggar.
 * Setiap penghapusan oleh admin wajib beralasan; penulis & pelapor mendapat notifikasi.
 */
#[Layout('components.layouts.app')]
#[Title('Komunitas')]
class Community extends Component
{
    #[Url]
    public string $tab = 'laporan';

    #[Url(as: 'status')]
    public string $reportStatus = 'pending';

    #[Url(as: 'fandom')]
    public string $filterFandom = '';

    #[Url(as: 'q')]
    public string $q = '';

    #[Url(as: 'topik')]
    public ?int $topicId = null;

    /** Form hapus: ['message'|'topic', id] */
    public ?string $deleteType = null;

    public ?int $deleteId = null;

    public string $deleteReason = '';

    /** Form tolak laporan */
    public ?int $dismissId = null;

    public string $dismissNote = '';

    public function mount(): void
    {
        if ($this->topicId) {
            $this->tab = 'topik';
        }
    }

    public function open(int $id): void
    {
        $this->topicId = $id;
        $this->tab = 'topik';
    }

    public function close(): void
    {
        $this->topicId = null;
    }

    /* ------------------------------------------------------------ hapus */

    public function askDelete(string $type, int $id, ?string $prefill = null): void
    {
        abort_unless(in_array($type, ['message', 'topic'], true), 422);
        $this->resetValidation();
        $this->deleteType = $type;
        $this->deleteId = $id;
        $this->deleteReason = $prefill ?? '';
    }

    /** Dari kartu laporan: alasan otomatis diisi kategori laporannya (bisa diubah). */
    public function askDeleteFromReport(int $reportId): void
    {
        $r = CommunityReport::findOrFail($reportId);

        if ($r->topic_message_id && $r->message) {
            $this->askDelete('message', $r->topic_message_id, $r->reason_label);
        } elseif ($r->discussion_topic_id && $r->topic) {
            $this->askDelete('topic', $r->discussion_topic_id, $r->reason_label);
        } else {
            $this->dispatch('toast', message: 'Konten ini sudah tidak ada (sudah dihapus).');
        }
    }

    public function cancelDelete(): void
    {
        $this->reset('deleteType', 'deleteId', 'deleteReason');
    }

    public function confirmDelete(): void
    {
        $this->validate(
            ['deleteReason' => 'required|string|min:5|max:255'],
            ['deleteReason.required' => 'Alasan penghapusan wajib diisi.', 'deleteReason.min' => 'Alasan minimal 5 karakter.'],
        );

        $svc = app(CommunityModerationService::class);
        $admin = Auth::user();

        if ($this->deleteType === 'message') {
            $svc->deleteMessage(TopicMessage::findOrFail($this->deleteId), $admin, trim($this->deleteReason));
            $msg = 'Pesan dihapus. Penulis sudah diberi tahu alasannya.';
        } else {
            $svc->deleteTopic(DiscussionTopic::findOrFail($this->deleteId), $admin, trim($this->deleteReason));
            if ($this->topicId === $this->deleteId) {
                $this->topicId = null;
            }
            $msg = 'Topik dihapus. Pembuat topik sudah diberi tahu alasannya.';
        }

        $this->cancelDelete();
        $this->dispatch('toast', message: $msg);
    }

    /* ------------------------------------------------------------ tolak laporan */

    public function askDismiss(int $id): void
    {
        $this->dismissId = $id;
        $this->dismissNote = '';
    }

    public function confirmDismiss(): void
    {
        $this->validate(['dismissNote' => 'nullable|string|max:255']);
        $report = CommunityReport::where('report_status', 'pending')->findOrFail($this->dismissId);
        app(CommunityModerationService::class)->dismiss($report, Auth::user(), $this->dismissNote ?: null);
        $this->reset('dismissId', 'dismissNote');
        $this->dispatch('toast', message: 'Laporan ditolak. Pelapor sudah diberi tahu.');
    }

    public function render()
    {
        $topic = $this->topicId ? DiscussionTopic::with(['fandom', 'creator'])->find($this->topicId) : null;

        return view('livewire.admin.community', [
            'pendingCount' => CommunityReport::where('report_status', 'pending')->count(),
            'reports'      => $this->tab === 'laporan'
                ? CommunityReport::with(['reporter', 'reportedUser', 'topic.fandom', 'message', 'handler'])
                    ->where('report_status', $this->reportStatus)
                    ->orderBy($this->reportStatus === 'pending' ? 'created_at' : 'handled_at', $this->reportStatus === 'pending' ? 'asc' : 'desc')
                    ->limit(100)->get()
                : collect(),
            'fandoms' => Fandom::active()->withCount(['members', 'topics'])->orderBy('fandom_name')->get(),
            'topics'  => $this->tab === 'topik'
                ? DiscussionTopic::with(['fandom', 'creator'])->withCount('messages')->withMax('messages', 'sent_at')
                    ->when($this->filterFandom, fn ($w) => $w->where('fandom_id', $this->filterFandom))
                    ->when($this->q, fn ($w) => $w->where('title', 'like', '%' . $this->q . '%'))
                    ->orderByDesc('messages_max_sent_at')->limit(100)->get()
                : collect(),
            'topic'    => $topic,
            'messages' => $topic
                ? TopicMessage::where('discussion_topic_id', $topic->id)->whereNull('parent_message_id')
                    ->with(['user', 'images', 'attachedProduct.primaryImage', 'attachedArtist', 'replies' => fn ($q) => $q->orderBy('sent_at')->with(['user', 'images', 'attachedProduct.primaryImage', 'attachedArtist'])])
                    ->orderBy('sent_at')->get()
                : collect(),
        ]);
    }
}
