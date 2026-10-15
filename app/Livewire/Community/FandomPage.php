<?php

namespace App\Livewire\Community;

use App\Models\DiscussionTopic;
use App\Models\Fandom;
use App\Models\FandomMember;
use App\Models\TopicMessage;
use App\Livewire\Concerns\HasPhotoAttachments;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

/** Halaman satu fandom: daftar topik + buat topik baru (khusus anggota). */
#[Layout('components.layouts.app')]
class FandomPage extends Component
{
    use WithFileUploads, HasPhotoAttachments;

    public Fandom $fandom;

    public string $title = '';

    public string $body = '';

    public function mount(Fandom $fandom): void
    {
        abort_unless($fandom->status === 'active', 404);
        $this->fandom = $fandom;
    }

    private function isMember(): bool
    {
        return Auth::check() && FandomMember::where('fandom_id', $this->fandom->id)->where('user_id', Auth::id())->exists();
    }

    public function join(): void
    {
        if (! Auth::check()) {
            $this->redirectRoute('login', navigate: true);

            return;
        }
        // admin hanya memantau, tidak menjadi anggota komunitas
        abort_if(Auth::user()->isAdmin(), 403);
        FandomMember::firstOrCreate(['fandom_id' => $this->fandom->id, 'user_id' => Auth::id()], ['role' => 'member', 'joined_at' => now()]);
        $this->dispatch('toast', message: 'Bergabung dengan komunitas ' . $this->fandom->fandom_name . '!');
    }

    public function createTopic(): void
    {
        abort_unless($this->isMember(), 403);
        $this->validate(['title' => 'required|string|min:5|max:200', 'body' => 'required|string|min:5|max:3000'] + $this->photoRules(), $this->photoMessages(), ['title' => 'judul', 'body' => 'isi']);

        $topic = DB::transaction(function () {
            $topic = DiscussionTopic::create(['fandom_id' => $this->fandom->id, 'creator_id' => Auth::id(), 'title' => $this->title]);
            $msg = TopicMessage::create(['discussion_topic_id' => $topic->id, 'user_id' => Auth::id(), 'message_body' => $this->body, 'sent_at' => now()]);
            $this->storePhotos('topic_message', $msg->id, 'komunitas');

            return $topic;
        });

        $this->redirectRoute('community.topic', ['topic' => $topic->id], navigate: true);
    }

    public function render()
    {
        return view('livewire.community.fandom-page', [
            'isMember' => $this->isMember(),
            'members'  => FandomMember::where('fandom_id', $this->fandom->id)->count(),
            'topics'   => DiscussionTopic::where('fandom_id', $this->fandom->id)->with('creator')->withCount('messages')
                ->withMax('messages', 'sent_at')->orderByDesc('messages_max_sent_at')->get(),
        ])->title('Komunitas ' . $this->fandom->fandom_name);
    }
}
