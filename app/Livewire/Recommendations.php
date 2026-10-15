<?php

namespace App\Livewire;

use App\Services\ContentBasedRecommender;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Halaman "Lihat semua" dari bagian Rekomendasi untukmu di beranda.
 * Isinya HANYA produk hasil CBF (similarity > 0), diurutkan dari yang paling mirip
 * dengan profil minat user — bukan seluruh katalog.
 */
#[Layout('components.layouts.app')]
#[Title('Rekomendasi untukmu')]
class Recommendations extends Component
{
    /** Batas tampilan, bertambah lewat tombol "Muat lebih banyak". */
    public int $limit = 20;

    public function more(): void
    {
        $this->limit += 20;
    }

    #[Computed]
    public function items()
    {
        if (! Auth::check()) {
            return collect();
        }

        // ambil satu lebih banyak untuk tahu apakah masih ada sisa
        return app(ContentBasedRecommender::class)->forUser(Auth::user(), $this->limit + 1);
    }

    public function render()
    {
        return view('livewire.recommendations');
    }
}
