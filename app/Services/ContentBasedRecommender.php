<?php

namespace App\Services;

use App\Models\CartItem;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductView;
use App\Models\SearchHistory;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Rekomendasi produk — Content-Based Filtering
 * dengan Multi-Hot Encoding + Weighted Cosine Similarity (Persamaan 2.1 TA).
 *
 * 1. Representasi item
 *    Setiap produk = vektor multi-hot di atas tiga blok atribut:
 *      [karakter_1..karakter_n | fandom_1..fandom_m | jenis_1..jenis_k]
 *    Produk punya tepat satu karakter, satu fandom (turunan karakter), dan satu
 *    jenis merchandise → tiga posisi bernilai 1, sisanya 0.
 *
 * 2. Profil pengguna
 *    Vektor berdimensi sama, berisi jumlah (berbobot) atribut dari:
 *      - fandom favorit (onboarding)  → mengatasi cold start
 *      - riwayat pencarian/filter     (search_history)
 *      - produk yang dilihat          (product_view)
 *      - produk di keranjang          (cart_item)
 *      - produk yang dibeli           (order_item)
 *
 * 3. Kemiripan
 *                      Σ wᵢ·Aᵢ·Bᵢ
 *    sim(A,B) = ─────────────────────────
 *               √(Σ wᵢ·Aᵢ²) · √(Σ wᵢ·Bᵢ²)
 *    dengan wᵢ = bobot blok atribut: karakter 0,5 · fandom 0,3 · jenis 0,2.
 */
class ContentBasedRecommender
{
    /** Bobot atribut (wᵢ) — sama untuk semua dimensi dalam satu blok. */
    public const WEIGHTS = [
        'character' => 0.5,
        'fandom'    => 0.3,
        'type'      => 0.2,
    ];

    /** Seberapa kuat tiap jenis interaksi mengisi profil pengguna. */
    public const SIGNALS = [
        'favorite' => 1.5,
        'search'   => 1.0,
        'view'     => 1.0,
        'cart'     => 2.0,
        'purchase' => 3.0,
    ];

    private const LIMIT_VIEW   = 50;
    private const LIMIT_SEARCH = 30;

    /** @var array<int, array> cache profil per request */
    private array $profiles = [];

    /**
     * Profil pengguna dalam bentuk vektor jarang (sparse):
     * ['character' => [id => nilai], 'fandom' => [...], 'type' => [...], 'sources' => [...]]
     */
    public function profile(User $user): array
    {
        if (isset($this->profiles[$user->id])) {
            return $this->profiles[$user->id];
        }

        $v = ['character' => [], 'fandom' => [], 'type' => []];
        $sources = ['favorite' => 0, 'search' => 0, 'view' => 0, 'cart' => 0, 'purchase' => 0];

        $add = function (string $block, $id, float $value) use (&$v) {
            if ($id) {
                $v[$block][(int) $id] = ($v[$block][(int) $id] ?? 0) + $value;
            }
        };

        $addProduct = function (?Product $p, float $value) use ($add) {
            if (! $p) {
                return;
            }
            $add('character', $p->fandom_character_id, $value);
            $add('fandom', $p->character?->fandom_id, $value);
            $add('type', $p->merchandise_type_id, $value);
        };

        // (a) fandom favorit — hanya blok fandom yang terisi
        foreach ($user->favoriteFandoms()->pluck('fandom.id') as $fandomId) {
            $add('fandom', $fandomId, self::SIGNALS['favorite']);
            $sources['favorite']++;
        }

        // (b) riwayat pencarian — kolom atribut hasil resolusi kata kunci/filter
        SearchHistory::query()
            ->where('user_id', $user->id)
            ->latest('searched_at')
            ->limit(self::LIMIT_SEARCH)
            ->get()
            ->each(function (SearchHistory $h) use ($add, &$sources) {
                $add('character', $h->fandom_character_id, self::SIGNALS['search']);
                $add('fandom', $h->fandom_id, self::SIGNALS['search']);
                $add('type', $h->merchandise_type_id, self::SIGNALS['search']);
                $sources['search']++;
            });

        // (c) produk yang dilihat
        ProductView::query()
            ->where('user_id', $user->id)
            ->latest('viewed_at')
            ->limit(self::LIMIT_VIEW)
            ->with('product.character:id,fandom_id')
            ->get()
            ->each(function (ProductView $pv) use ($addProduct, &$sources) {
                $addProduct($pv->product, self::SIGNALS['view']);
                $sources['view']++;
            });

        // (d) keranjang
        CartItem::query()
            ->where('user_id', $user->id)
            ->with('product.character:id,fandom_id')
            ->get()
            ->each(function (CartItem $c) use ($addProduct, &$sources) {
                $addProduct($c->product, self::SIGNALS['cart']);
                $sources['cart']++;
            });

        // (e) pembelian (pesanan yang tidak batal/gagal)
        OrderItem::query()
            ->whereHas('order', fn ($q) => $q->where('buyer_id', $user->id)
                ->whereNotIn('order_status', ['cancelled', 'failed']))
            ->with('product.character:id,fandom_id')
            ->get()
            ->each(function (OrderItem $oi) use ($addProduct, &$sources) {
                $addProduct($oi->product, self::SIGNALS['purchase']);
                $sources['purchase']++;
            });

        return $this->profiles[$user->id] = $v + ['sources' => $sources];
    }

    public function hasProfile(User $user): bool
    {
        $p = $this->profile($user);

        return $p['character'] || $p['fandom'] || $p['type'];
    }

    /**
     * Rekomendasi untuk user. Setiap produk hasil diberi atribut tambahan:
     *   ->similarity  (0..1)  dan  ->reason (kalimat singkat alasan)
     */
    public function forUser(User $user, int $limit = 8): Collection
    {
        $profile = $this->profile($user);

        if (! $this->hasProfile($user)) {
            return collect();
        }

        // Produk yang tidak perlu direkomendasikan lagi
        $exclude = collect()
            ->merge(CartItem::where('user_id', $user->id)->pluck('product_id'))
            ->merge(ProductView::where('user_id', $user->id)->pluck('product_id'))
            ->merge(DB::table('order_item')
                ->join('customer_order', 'customer_order.id', '=', 'order_item.customer_order_id')
                ->where('customer_order.buyer_id', $user->id)
                ->pluck('order_item.product_id'))
            ->unique();

        $candidates = $this->candidates()
            ->when($user->artistProfile, fn ($q) => $q->where('artist_profile_id', '!=', $user->artistProfile->id))
            ->whereNotIn('id', $exclude)
            ->get();

        return $this->rank($profile, $candidates, $limit);
    }

    /** Produk serupa (item-to-item): profil = vektor produk itu sendiri. */
    public function similarTo(Product $product, int $limit = 4): Collection
    {
        $product->loadMissing('character');

        $profile = [
            'character' => [$product->fandom_character_id => 1.0],
            'fandom'    => [$product->character?->fandom_id => 1.0],
            'type'      => [$product->merchandise_type_id => 1.0],
        ];

        $candidates = $this->candidates()->whereKeyNot($product->id)->get();

        return $this->rank($profile, $candidates, $limit, withReason: false);
    }

    /**
     * Weighted cosine similarity antara vektor profil (A) dan vektor item (B).
     * B multi-hot → Bᵢ ∈ {0,1}, sehingga Σ wᵢ·Bᵢ² = jumlah bobot blok yang dimiliki item.
     */
    public function similarity(array $profile, Product $p): float
    {
        $normA = 0.0;
        foreach (self::WEIGHTS as $block => $w) {
            foreach ($profile[$block] ?? [] as $value) {
                $normA += $w * $value * $value;
            }
        }

        $item = [
            'character' => $p->fandom_character_id,
            'fandom'    => $p->character?->fandom_id,
            'type'      => $p->merchandise_type_id,
        ];

        $dot = 0.0;
        $normB = 0.0;
        foreach (self::WEIGHTS as $block => $w) {
            if (! $item[$block]) {
                continue;
            }
            $normB += $w;                                            // wᵢ · 1²
            $dot   += $w * ($profile[$block][$item[$block]] ?? 0);   // wᵢ · Aᵢ · 1
        }

        if ($normA <= 0 || $normB <= 0) {
            return 0.0;
        }

        return $dot / (sqrt($normA) * sqrt($normB));
    }

    private function candidates()
    {
        return Product::query()
            ->visible()
            ->with(['primaryImage', 'openBatch', 'artistProfile', 'merchandiseType', 'character.fandom']);
    }

    private function rank(array $profile, Collection $candidates, int $limit, bool $withReason = true): Collection
    {
        return $candidates
            ->map(function (Product $p) use ($profile, $withReason) {
                $p->similarity = round($this->similarity($profile, $p), 4);
                if ($withReason) {
                    $p->reason = $this->reason($profile, $p);
                }

                return $p;
            })
            ->filter(fn (Product $p) => $p->similarity > 0)
            // skor sama → yang rating-nya lebih tinggi, lalu yang terbaru
            ->sortBy([
                ['similarity', 'desc'],
                fn ($a, $b) => (float) $b->rating <=> (float) $a->rating,
                fn ($a, $b) => $b->created_at <=> $a->created_at,
            ])
            ->values()
            ->take($limit);
    }

    /** Atribut dengan kontribusi terbesar → dijadikan alasan yang tampil di kartu. */
    private function reason(array $profile, Product $p): string
    {
        $contrib = [
            'character' => self::WEIGHTS['character'] * ($profile['character'][$p->fandom_character_id] ?? 0),
            'fandom'    => self::WEIGHTS['fandom'] * ($profile['fandom'][$p->character?->fandom_id] ?? 0),
            'type'      => self::WEIGHTS['type'] * ($profile['type'][$p->merchandise_type_id] ?? 0),
        ];

        arsort($contrib);

        return match (array_key_first($contrib)) {
            'character' => 'Karena kamu suka ' . $p->character?->character_name,
            'fandom'    => 'Dari fandom ' . $p->character?->fandom?->fandom_name,
            default     => 'Kamu sering lihat ' . $p->merchandiseType?->type_name,
        };
    }

    /**
     * Ringkasan profil untuk ditampilkan ke user (halaman akun):
     * atribut teratas tiap blok beserta persentase relatifnya.
     */
    public function summary(User $user, int $top = 4): array
    {
        $profile = $this->profile($user);

        $names = [
            'character' => DB::table('fandom_character')->whereIn('id', array_keys($profile['character']))->pluck('character_name', 'id'),
            'fandom'    => DB::table('fandom')->whereIn('id', array_keys($profile['fandom']))->pluck('fandom_name', 'id'),
            'type'      => DB::table('merchandise_type')->whereIn('id', array_keys($profile['type']))->pluck('type_name', 'id'),
        ];

        $out = [];
        foreach (['character', 'fandom', 'type'] as $block) {
            $values = $profile[$block];
            arsort($values);
            $max = max($values ?: [1]);
            $out[$block] = collect($values)->take($top)->map(fn ($v, $id) => [
                'name'    => $names[$block][$id] ?? '—',
                'value'   => round($v, 1),
                'percent' => (int) round($v / $max * 100),
            ])->values()->all();
        }

        return $out + ['sources' => $profile['sources']];
    }
}
