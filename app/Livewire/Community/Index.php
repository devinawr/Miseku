<?php

namespace App\Livewire\Community;

use App\Models\Fandom;
use App\Models\FandomMember;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/** Komunitas fandom: gabung fandom, lihat topik diskusi. */
#[Layout('components.layouts.app')]
#[Title('Komunitas')]
class Index extends Component
{
    public string $q = '';

    public function toggle(int $fandomId): void
    {
        if (! Auth::check()) {
            $this->redirectRoute('login', navigate: true);

            return;
        }
        // admin hanya memantau, tidak menjadi anggota komunitas
        abort_if(Auth::user()->isAdmin(), 403);

        $row = FandomMember::where('fandom_id', $fandomId)->where('user_id', Auth::id())->first();
        if ($row) {
            $row->delete();
            $this->dispatch('toast', message: 'Keluar dari komunitas.');
        } else {
            FandomMember::create(['fandom_id' => $fandomId, 'user_id' => Auth::id(), 'role' => 'member', 'joined_at' => now()]);
            $this->dispatch('toast', message: 'Bergabung! Kamu akan dikabari produk baru dari fandom ini.');
        }
    }

    public function render()
    {
        $mine = Auth::check() ? FandomMember::where('user_id', Auth::id())->pluck('fandom_id')->all() : [];

        return view('livewire.community.index', [
            'mine'    => $mine,
            'fandoms' => Fandom::active()->withCount(['members', 'topics'])
                ->when($this->q, fn ($w) => $w->where('fandom_name', 'like', '%' . $this->q . '%'))
                ->orderByDesc('members_count')->orderBy('fandom_name')->get(),
        ]);
    }
}
