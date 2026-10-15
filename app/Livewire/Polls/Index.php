<?php

namespace App\Livewire\Polls;

use App\Models\DesignPoll;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/** Daftar voting desain dari artis (berlangsung & selesai). */
#[Layout('components.layouts.app')]
#[Title('Voting desain')]
class Index extends Component
{
    public function render()
    {
        $base = DesignPoll::with(['artistProfile', 'options'])->withCount('votes');

        return view('livewire.polls.index', [
            'running'  => (clone $base)->where('poll_status', 'running')->where('end_date', '>=', now())->orderBy('end_date')->get(),
            'finished' => (clone $base)->where('poll_status', 'completed')->latest('end_date')->take(9)->get(),
        ]);
    }
}
