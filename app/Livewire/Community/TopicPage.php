<?php

namespace App\Livewire\Community;

use App\Models\ArtistProfile;
use App\Models\DiscussionTopic;
use App\Models\FandomMember;
use App\Models\Product;
use App\Models\TopicMessage;
use App\Livewire\Concerns\HasPhotoAttachments;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Satu topik diskusi: pesan + balasan (1 tingkat), lampirkan produk / artis rekomendasi.
 * Moderator fandom & admin bisa menghapus pesan/topik.
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

    private function role(): ?string
    {
        if (! Auth::check()) {
            return null;
        }
        if (Auth::user()->isAdmin()) {
            return 'moderator';
        }

        return FandomMember::where('fandom_id', $this->topic->fandom_id)->where('user_id', Auth::id())->value('role');
    }

    public function reply(int $id): void
    {
        $this->replyTo = $id;
    }

    /** Admin memantau & memoderasi (hapus), tidak ikut berdiskusi. */
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

    public function deleteMessage(int $id): void
    {
        $msg = TopicMessage::where('discussion_topic_id', $this->topic->id)->findOrFail($id);
        abort_unless($this->role() === 'moderator' || $msg->user_id === Auth::id(), 403);
        TopicMessage::where('parent_message_id', $msg->id)->delete();
        $msg->delete();
        $this->dispatch('toast', message: 'Pesan dihapus.');
    }

    public function deleteTopic(): void
    {
        abort_unless($this->role() === 'moderator' || $this->topic->creator_id === Auth::id(), 403);
        $fandomId = $this->topic->fandom_id;
        TopicMessage::where('discussion_topic_id', $this->topic->id)->whereNotNull('parent_message_id')->delete();
        TopicMessage::where('discussion_topic_id', $this->topic->id)->delete();
        $this->topic->delete();
        session()->flash('status', 'Topik dihapus.');
        $this->redirectRoute('community.fandom', ['fandom' => $fandomId], navigate: true);
    }

    public function render()
    {
        $topic = $this->topic->load(['fandom', 'creator']);

        return view('livewire.community.topic-page', [
            'role'     => $this->role(),
            'canPost'  => $this->canPost(),
            'messages' => TopicMessage::where('discussion_topic_id', $topic->id)->whereNull('parent_message_id')
                ->with(['user', 'images', 'attachedProduct.primaryImage', 'attachedArtist', 'replies' => fn ($q) => $q->orderBy('sent_at')->with(['user', 'images', 'attachedProduct.primaryImage', 'attachedArtist'])])
                ->orderBy('sent_at')->get(),
            'products' => Product::visible()->whereHas('character', fn ($q) => $q->where('fandom_id', $topic->fandom_id))->orderBy('product_name')->take(60)->get(['id', 'product_name']),
            'artists'  => ArtistProfile::orderBy('shop_name')->get(['id', 'shop_name']),
        ])->title($topic->title);
    }
}
