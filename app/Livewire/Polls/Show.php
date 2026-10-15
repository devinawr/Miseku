<?php

namespace App\Livewire\Polls;

use App\Livewire\Concerns\RunsActions;
use App\Models\DesignPoll;
use App\Models\DesignVote;
use App\Services\PollService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/** Detail voting: pilih satu desain (perlu login). Hasil tampil setelah memilih / voting selesai. */
#[Layout('components.layouts.app')]
class Show extends Component
{
    use RunsActions;

    public DesignPoll $poll;

    public function vote(int $optionId): void
    {
        if (! Auth::check()) {
            $this->redirectRoute('login', navigate: true);

            return;
        }

        $option = $this->poll->options()->findOrFail($optionId);
        $this->attempt(fn () => app(PollService::class)->vote($this->poll, $option, Auth::user()), 'Suaramu tercatat. Terima kasih!');
    }

    public function render()
    {
        $poll = $this->poll->load(['artistProfile', 'options' => fn ($q) => $q->withCount('votes')->with('product')]);
        $myVote = Auth::check() ? DesignVote::where('design_poll_id', $poll->id)->where('user_id', Auth::id())->value('design_option_id') : null;
        $myVote = $myVote ? (int) $myVote : null;
        $total = max(1, $poll->options->sum('votes_count'));
        $showResult = $myVote || $poll->poll_status !== 'running' || ! $poll->is_open || Auth::id() === $poll->artistProfile->user_id;

        return view('livewire.polls.show', [
            'myVote' => $myVote, 'total' => $total, 'showResult' => $showResult,
            'winner' => $poll->poll_status === 'completed' ? $poll->winner() : null,
        ])->title($poll->poll_title);
    }
}
