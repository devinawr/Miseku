<?php

namespace App\Services;

use App\Models\ArtistProfile;
use App\Models\Cancellation;
use App\Models\PlatformSetting;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\Review;
use App\Models\User;

/**
 * Cache rating (kamus data v4.1).
 *   rating = rata-rata bintang − penalti pembatalan (min 0, maks 5)
 *   penalti pembeli : pembatalan sukarela yang ia ajukan & disetujui × penalty_voluntary
 *                     + pesanan gagal karena lewat tenggat pelunasan × penalty_settlement_timeout
 *   penalti artis   : pembatalan sukarela oleh artis × penalty_voluntary
 *                     + pesanan batal karena lewat tenggat kirim × penalty_shipping_timeout
 * Bila belum ada ulasan sama sekali, rating tetap 0 (tampil "belum ada ulasan").
 */
class RatingService
{
    public function recalcProduct(int $productId): void
    {
        $agg = ProductReview::where('product_id', $productId)->selectRaw('AVG(rating) a, COUNT(*) c')->first();

        Product::whereKey($productId)->update([
            'rating'       => round((float) ($agg->a ?? 0), 2),
            'rating_count' => (int) ($agg->c ?? 0),
        ]);
    }

    public function recalcArtist(int $artistProfileId): void
    {
        $artist = ArtistProfile::find($artistProfileId);
        if (! $artist) {
            return;
        }

        $agg = Review::where('reviewee_id', $artist->user_id)->where('direction', 'buyer_to_artist')->selectRaw('AVG(rating) a, COUNT(*) c')->first();
        $set = PlatformSetting::current();

        $penalty = $this->artistPenaltyCount($artist, 'voluntary') * (float) $set->penalty_voluntary
            + $this->artistPenaltyCount($artist, 'shipping_timeout') * (float) $set->penalty_shipping_timeout;

        $count = (int) ($agg->c ?? 0);
        $artist->forceFill([
            'rating'       => $count ? $this->clamp((float) $agg->a - $penalty) : 0,
            'rating_count' => $count,
        ])->save();
    }

    public function recalcBuyer(int $userId): void
    {
        $user = User::find($userId);
        if (! $user) {
            return;
        }

        $agg = Review::where('reviewee_id', $userId)->where('direction', 'artist_to_buyer')->selectRaw('AVG(rating) a, COUNT(*) c')->first();
        $set = PlatformSetting::current();

        $voluntary = Cancellation::where('requester_id', $userId)->where('cancellation_type', 'voluntary')->where('cancellation_status', 'approved')
            ->whereHas('order', fn ($q) => $q->where('buyer_id', $userId))->count();
        $timeouts = Cancellation::where('cancellation_type', 'settlement_timeout')->whereHas('order', fn ($q) => $q->where('buyer_id', $userId))->count();

        $penalty = $voluntary * (float) $set->penalty_voluntary + $timeouts * (float) $set->penalty_settlement_timeout;
        $count = (int) ($agg->c ?? 0);

        $user->forceFill([
            'rating_as_buyer'       => $count ? $this->clamp((float) $agg->a - $penalty) : 0,
            'rating_count_as_buyer' => $count,
        ])->save();
    }

    /** Ringkasan penalti untuk ditampilkan (transparansi). */
    public function penaltySummary(?ArtistProfile $artist = null, ?User $buyer = null): array
    {
        $set = PlatformSetting::current();

        if ($artist) {
            $v = $this->artistPenaltyCount($artist, 'voluntary');
            $s = $this->artistPenaltyCount($artist, 'shipping_timeout');

            return ['voluntary' => $v, 'timeout' => $s, 'total' => round($v * $set->penalty_voluntary + $s * $set->penalty_shipping_timeout, 2)];
        }

        $v = Cancellation::where('requester_id', $buyer->id)->where('cancellation_type', 'voluntary')->where('cancellation_status', 'approved')
            ->whereHas('order', fn ($q) => $q->where('buyer_id', $buyer->id))->count();
        $s = Cancellation::where('cancellation_type', 'settlement_timeout')->whereHas('order', fn ($q) => $q->where('buyer_id', $buyer->id))->count();

        return ['voluntary' => $v, 'timeout' => $s, 'total' => round($v * $set->penalty_voluntary + $s * $set->penalty_settlement_timeout, 2)];
    }

    private function artistPenaltyCount(ArtistProfile $artist, string $type): int
    {
        $q = Cancellation::where('cancellation_type', $type)
            ->whereHas('order', fn ($o) => $o->where('artist_profile_id', $artist->id));

        if ($type === 'voluntary') {
            $q->where('cancellation_status', 'approved')->where('requester_id', $artist->user_id);
        }

        return $q->count();
    }

    private function clamp(float $v): float
    {
        return round(max(0, min(5, $v)), 2);
    }
}
