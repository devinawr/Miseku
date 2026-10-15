<?php

namespace App\Livewire\Community;

use App\Models\ArtistProfile;
use App\Models\CommunityReport;
use App\Models\DiscussionTopic;
use App\Models\FandomMember;
use App\Models\Product;
use App\Models\TopicMessage;
use App\Livewire\Concerns\HasPhotoAttachments;
use App\Services\CommunityModerationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Satu topik diskusi: pesan + balasan (1 tingkat), lampirkan produk / artis rekomendasi.
 * Penulis bisa menghapus pesan/topiknya sendiri; konten orang lain bisa DILAPORKAN
 * ke admin (Panel admin → Komunitas). Admin dialihkan ke panel, tidak membuka halaman ini.
 */
#[Layout('components.layouts.app')]
class TopicPage extends Component
{
    use WithFileUploads, HasPhotoAttachments;

    public DiscussionTopic $topic;

    public string $body = '';

    public ?int $replyTo = null;

    public ?int $attachProduct = null;

    public ?int $attachArtist = null;

    /** Form laporan: null = tertutup, 0 = lapor topik, >0 = id pesan yang dilaporkan. */
    public ?int $reportTarget = null;

    public string $reportCategory = '';

    public string $reportNote = '';

    private function role(): ?string
    {
        if (! Auth::check()) {
            return null;
        }

        return FandomMember::where('fandom_id', $this->topic->fandom_id)->where('user_id', Auth::id())->value('role');
    }

    public function reply(int $id): void
    {
        $this->replyTo = $id;
    }

    /** Hanya anggota komunitas (bukan admin) yang bisa berdiskusi. */
    private function canPost(): bool
    {
        return (bool) $this->role() && ! Auth::user()->isAdmin();
    }

    public function post(): void
    {
        abort_unless($this->canPost(), 403);
        $this->validate([
            'body'          => (count($this->photos) ? 'nullable' : 'required') . '|string|max:3000',
            'attachProduct' => 'nullable|exists:product,id',
            'attachArtist'  => 'nullable|exists:artist_profile,id',
        ] + $this->photoRules(), $this->photoMessages() + ['body.required' => 'Tulis pesan atau lampirkan foto.'], ['body' => 'pesan']);

        $parent = $this->replyTo ? TopicMessage::where('discussion_topic_id', $this->topic->id)->find($this->replyTo) : null;

        $msg = TopicMessage::create([
            'discussion_topic_id' => $this->topic->id,
            'parent_message_id'   => $parent ? ($parent->parent_message_id ?: $parent->id) : null,
            'user_id'             => Auth::id(),
            'message_body'        => trim((string) $this->body),
            'attached_product_id' => $this->attachProduct ?: null,
            'attached_artist_id'  => $this->attachArtist ?: null,
            'sent_at'             => now(),
        ]);
        $this->storePhotos('topic_message', $msg->id, 'komunitas');
        $this->topic->touch();

        $this->reset('body', 'replyTo', 'attachProduct', 'attachArtist');
    }

    /** Penulis menghapus pesannya sendiri (foto lampiran ikut terhapus). */
    public function deleteMessage(int $id): void
    {
        $msg = TopicMessage::where('discussion_topic_id', $this->topic->id)->findOrFail($id);
        abort_unless(Auth::check() && $msg->user_id === Auth::id(), 403);
        app(CommunityModerationService::class)->deleteMessage($msg);
        $this->dispatch('toast', message: 'Pesan dihapus.');
    }

    /** Pembuat topik menghapus topiknya sendiri. */
    public function deleteTopic(): void
    {
        abort_unless(Auth::check() && $this->topic->creator_id === Auth::id(), 403);
        $fandomId = $this->topic->fandom_id;
        app(CommunityModerationService::class)->deleteTopic($this->topic);
        session()->flash('status', 'Topik dihapus.');
        $this->redirectRoute('community.fandom', ['fandom' => $fandomId], navigate: true);
    }

    /* ---------------------------------------------------------- laporan */

    public function openReport(int $messageId = 0): void
    {
        if (! Auth::check()) {
            $this->redirectRoute('login', navigate: true);

            return;
        }
        $this->resetValidation();
        $this->reset('reportCategory', 'reportNote');
        $this->reportTarget = $messageId;
    }

    public function closeReport(): void
    {
        $this->reportTarget = null;
    }

    public function submitReport(): void
    {
        abort_unless(Auth::check() && ! Auth::user()->isAdmin() && $this->reportTarget !== null, 403);

        $this->validate([
            'reportCategory' => ['required', Rule::in(array_keys(CommunityReport::REASONS))],
            'reportNote'     => [$this->reportCategory === 'lainnya' ? 'required' : 'nullable', 'string', 'max:255'],
        ], [
            'reportCategory.required' => 'Pilih alasan laporan.',
            'reportNote.required'     => 'Jelaskan alasannya.',
        ]);

        $message = $this->reportTarget ? TopicMessage::where('discussion_topic_id', $this->topic->id)->findOrFail($this->reportTarget) : null;
        $svc = app(CommunityModerationService::class);

        if ($svc->alreadyReported(Auth::user(), $this->topic, $message)) {
            $this->reportTarget = null;
            $this->dispatch('toast', message: 'Kamu sudah melaporkan konten ini. Admin sedang meninjaunya.');

            return;
        }

        $svc->report(Auth::user(), $this->topic, $message, $this->reportCategory, $this->reportNote ?: null);
        $this->reportTarget = null;
        $this->dispatch('toast', message: 'Laporan terkirim. Terima kasih, admin akan meninjaunya.');
    }

    public function render()
    {
        $topic = $this->topic->load(['fandom', 'creator']);

        return view('livewire.community.topic-page', [
            'role'     => $this->role(),
            'canPost'  => $this->canPost(),
            'reasons'  => CommunityReport::REASONS,
            'messages' => TopicMessage::where('discussion_topic_id', $topic->id)->whereNull('parent_message_id')
                ->with(['user', 'images', 'attachedProduct.primaryImage', 'attachedArtist', 'replies' => fn ($q) => $q->orderBy('sent_at')->with(['user', 'images', 'attachedProduct.primaryImage', 'attachedArtist'])])
                ->orderBy('sent_at')->get(),
            'products' => Product::visible()->whereHas('character', fn ($q) => $q->where('fandom_id', $topic->fandom_id))->orderBy('product_name')->take(60)->get(['id', 'product_name']),
            'artists'  => ArtistProfile::orderBy('shop_name')->get(['id', 'shop_name']),
        ])->title($topic->title);
    }
}
