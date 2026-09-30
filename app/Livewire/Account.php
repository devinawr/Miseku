<?php

namespace App\Livewire;

use App\Models\Product;
use App\Models\ProductView;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Modul 4c — Akun saya.
 *
 * Halaman ringkas setelah login: identitas, peran, dan pintasan sesuai peran.
 * Sekaligus menampilkan riwayat produk yang dilihat (tabel product_view) —
 * bahan vektor rekomendasi yang baru bisa terisi setelah ada login.
 */
#[Layout('components.layouts.app')]
#[Title('Akun saya')]
class Account extends Component
{
    #[Computed]
    public function user()
    {
        return Auth::user()->load('artistProfile');
    }

    #[Computed]
    public function recentlyViewed()
    {
        $ids = ProductView::query()
            ->where('user_id', Auth::id())
            ->orderByDesc('viewed_at')
            ->limit(30)
            ->pluck('product_id')
            ->unique()
            ->take(4);

        if ($ids->isEmpty()) {
            return collect();
        }

        return Product::query()
            ->visible()
            ->with(['primaryImage', 'openBatch', 'artistProfile', 'merchandiseType', 'character.fandom'])
            ->whereIn('id', $ids)
            ->get()
            ->sortBy(fn (Product $p) => $ids->search($p->id))
            ->values();
    }

    public function logout()
    {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return $this->redirect(route('home'), navigate: true);
    }

    public function render()
    {
        return view('livewire.account');
    }
}
