<?php

namespace App\Livewire\Admin;

use App\Models\User;
use App\Services\RatingService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/** Kelola pengguna: cari, filter peran, aktif/nonaktifkan akun, lihat rating & penalti. */
#[Layout('components.layouts.app')]
#[Title('Pengguna')]
class Users extends Component
{
    use WithPagination;

    #[Url]
    public string $q = '';

    #[Url]
    public string $role = '';

    public function updatedQ(): void
    {
        $this->resetPage();
    }

    public function updatedRole(): void
    {
        $this->resetPage();
    }

    public function toggle(int $id): void
    {
        abort_if($id === Auth::id(), 403);
        $u = User::findOrFail($id);
        $u->update(['is_active' => ! $u->is_active]);
        $this->dispatch('toast', message: $u->is_active ? "{$u->name} diaktifkan." : "{$u->name} dinonaktifkan (tidak bisa login, produknya tetap tersimpan).");
    }

    public function render()
    {
        $users = User::with('artistProfile')
            ->when($this->q, fn ($w) => $w->where(fn ($x) => $x->where('name', 'like', "%{$this->q}%")->orWhere('email', 'like', "%{$this->q}%")))
            ->when($this->role, fn ($w) => $w->where('role', $this->role))
            ->withCount('orders')->orderBy('role')->orderBy('name')->paginate(20);

        return view('livewire.admin.users', ['users' => $users, 'ratings' => app(RatingService::class)]);
    }
}
