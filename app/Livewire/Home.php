<?php

namespace App\Livewire;

use App\Models\Fandom;
use App\Models\Product;
use App\Models\ProductView;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Beranda — halaman rekomendasi.
 *
 * Beranda TIDAK memuat pencarian maupun filter; keduanya milik halaman Katalog.
 * Kotak cari di sini hanya mengantar ke /katalog?q=... lewat form GET biasa.
 *
 * Rekomendasi memakai pendekatan bobot yang sama dengan rancangan BPMN 8:
 * karakter 0,5 · fandom 0,3 · jenis merchandise 0,2. Bahan vektornya diambil
 * dari tabel product_view (produk yang pernah dilihat user). Pengunjung yang
 * belum login otomatis mendapat daftar berperingkat tertinggi.
 */
#[Layout('components.layouts.app')]
#[Title('Beranda')]
class Home extends Component
{
    private const BOBOT_KARAKTER = 0.5;
    private const BOBOT_FANDOM   = 0.3;
    private const BOBOT_JENIS    = 0.2;

    /** Produk yang pernah dilihat user, jadi bahan vektor rekomendasi. */
    private function riwayat(): Collection
    {
        if (! Auth::check()) {
            return collect();
        }

        return ProductView::query()
            ->where('user_id', Auth::id())
            ->with(['product:id,fandom_character_id,merchandise_type_id', 'product.character:id,fandom_id'])
            ->orderByDesc('viewed_at')
            ->limit(30)
            ->get()
            ->pluck('product')
            ->filter();
    }

    /**
     * @return array{items: Collection, personal: bool}
     */
    #[Computed]
    public function rekomendasi(): array
    {
        $riwayat = $this->riwayat();

        $query = Product::query()
            ->visible()
            ->with(['primaryImage', 'openBatch', 'artistProfile', 'merchandiseType', 'character.fandom']);

        // Belum ada jejak: tampilkan yang paling disukai pembeli lain.
        if ($riwayat->isEmpty()) {
            return [
                'items' => $query->orderByDesc('rating')->orderByDesc('rating_count')->take(8)->get(),
                'personal' => false,
            ];
        }

        $karakter = $riwayat->pluck('fandom_character_id')->countBy();
        $jenis    = $riwayat->pluck('merchandise_type_id')->countBy();
        $fandom   = $riwayat->pluck('character.fandom_id')->filter()->countBy();
        $dilihat  = $riwayat->pluck('id')->unique();

        $kandidat = $query->whereNotIn('id', $dilihat)->limit(200)->get();

        $terurut = $kandidat
            ->map(function (Product $p) use ($karakter, $fandom, $jenis) {
                $skor = 0.0;

                if ($karakter->has($p->fandom_character_id)) {
                    $skor += self::BOBOT_KARAKTER;
                }
                if ($fandom->has($p->character?->fandom_id)) {
                    $skor += self::BOBOT_FANDOM;
                }
                if ($jenis->has($p->merchandise_type_id)) {
                    $skor += self::BOBOT_JENIS;
                }

                // Rating dipakai sebagai pemecah seri, bukan penentu utama.
                $p->skor_rekomendasi = $skor + ((float) $p->rating / 100);

                return $p;
            })
            ->sortByDesc('skor_rekomendasi')
            ->values()
            ->take(8);

        return ['items' => $terurut, 'personal' => true];
    }

    #[Computed]
    public function terbaru()
    {
        return Product::query()
            ->visible()
            ->with(['primaryImage', 'openBatch', 'artistProfile', 'merchandiseType', 'character.fandom'])
            ->orderByDesc('created_at')
            ->take(4)
            ->get();
    }

    #[Computed]
    public function preOrderBuka()
    {
        return Product::query()
            ->visible()
            ->where('availability_type', 'pre_order')
            ->whereHas('batches', fn ($q) => $q->open())
            ->with(['primaryImage', 'openBatch', 'artistProfile', 'merchandiseType', 'character.fandom'])
            ->orderByDesc('created_at')
            ->take(4)
            ->get();
    }

    /** Fandom aktif beserta jumlah karyanya, untuk pintasan jelajah. */
    #[Computed]
    public function fandomPopuler()
    {
        $jumlahKarya = Product::query()
            ->visible()
            ->join('fandom_character', 'fandom_character.id', '=', 'product.fandom_character_id')
            ->whereColumn('fandom_character.fandom_id', 'fandom.id')
            ->selectRaw('count(*)');

        return Fandom::query()
            ->active()
            ->select('fandom.id', 'fandom.fandom_name')
            ->selectSub($jumlahKarya, 'karya_count')
            ->orderByDesc('karya_count')
            ->take(6)
            ->get();
    }

    public function render()
    {
        return view('livewire.home');
    }
}
