<?php

namespace App\Livewire\Admin;

use App\Models\User;
use App\Models\ArtistApplication;
use App\Models\ArtistProfile;
use App\Models\Address;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
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
    public array $applicationNotes = [];

    public function decideApplication(int $id, bool $approve): void
    {
        abort_unless(Auth::user()->isAdmin(), 403);
        $note = trim((string) ($this->applicationNotes[$id] ?? ''));
        if ((! $approve && mb_strlen($note) < 5) || mb_strlen($note) > 1000) {
            $this->addError("applicationNotes.$id", 'Alasan penolakan 5–1000 karakter.'); return;
        }
        DB::transaction(function () use ($id, $approve, $note) {
            $a = ArtistApplication::whereKey($id)->lockForUpdate()->firstOrFail();
            abort_unless($a->status === 'pending', 422);
            $user = User::whereKey($a->user_id)->lockForUpdate()->firstOrFail();
            abort_unless($user->role === 'buyer' && $user->is_active && ! $user->artistProfile()->exists(), 422);
            if ($approve) {
                if (ArtistProfile::where('shop_name', $a->shop_name)->exists()) {
                    $this->addError("applicationNotes.$id", 'Nama toko sudah dipakai. Tolak dan minta nama lain.'); return;
                }
                ArtistProfile::create(['user_id'=>$user->id, 'shop_name'=>$a->shop_name, 'shop_description'=>$a->shop_description,
                    'bank_name'=>$a->bank_name, 'bank_account_number'=>$a->bank_account_number]);
                Address::updateOrCreate(['user_id'=>$user->id, 'address_label'=>Address::SHOP_LABEL], [
                    'recipient_name'=>$a->shop_name, 'full_address'=>$a->shop_address, 'rajaongkir_city_id'=>$a->shop_city_id,
                    'province'=>$a->shop_province, 'postal_code'=>$a->shop_postal_code, 'is_primary'=>false]);
                $user->update(['role'=>'artist']);
            }
            $a->update(['status'=>$approve ? 'approved' : 'rejected', 'reviewed_by'=>Auth::id(), 'reviewed_at'=>now(), 'review_note'=>$note ?: null]);
            app(NotificationService::class)->send($user, 'order', 'Pengajuan toko ' . ($approve ? 'disetujui' : 'ditolak'),
                $approve ? 'Toko disetujui dan Studio artis sudah dapat digunakan.' : "Pengajuan toko ditolak. Alasan: {$note}");
        });
        unset($this->applicationNotes[$id]);
    }


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

        return view('livewire.admin.users', ['users' => $users, 'ratings' => app(RatingService::class), 'applications' => ArtistApplication::with('user')->where('status','pending')->latest('id')->get()]);
    }
}
