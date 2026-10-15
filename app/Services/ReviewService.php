<?php

namespace App\Services;

use App\Models\CustomerOrder;
use App\Models\ProductReview;
use App\Models\Review;
use App\Models\User;
use App\Support\Fail;
use Illuminate\Support\Facades\DB;

/** Rating dua arah + ulasan produk — hanya pesanan completed, dalam jendela waktu. */
class ReviewService
{
    public function __construct(private RatingService $ratings) {}

    public function buyerCanReview(CustomerOrder $order, User $user): bool
    {
        return $order->buyer_id === $user->id && $order->order_status === 'completed' && $order->order_type !== 'ots_event'
            && $order->completed_at && now()->lte($order->completed_at->copy()->addDays(config('miseku.review_days_buyer')));
    }

    public function artistCanReview(CustomerOrder $order, User $user): bool
    {
        return $order->artistProfile->user_id === $user->id && $order->order_status === 'completed' && $order->order_type !== 'ots_event'
            && $order->completed_at && now()->lte($order->completed_at->copy()->addDays(config('miseku.review_days_artist')));
    }

    /**
     * @param  array<int, array{rating:int, comment:?string}>  $products  product_id => ulasan
     */
    public function submitBuyer(CustomerOrder $order, User $buyer, int $shopRating, ?string $shopComment, array $products): void
    {
        Fail::unless($this->buyerCanReview($order, $buyer), 'Ulasan hanya bisa diberikan untuk pesanan selesai dalam ' . config('miseku.review_days_buyer') . ' hari.');

        DB::transaction(function () use ($order, $buyer, $shopRating, $shopComment, $products) {
            if (! Review::where('customer_order_id', $order->id)->where('reviewer_id', $buyer->id)->exists()) {
                Review::create([
                    'customer_order_id' => $order->id, 'reviewer_id' => $buyer->id, 'reviewee_id' => $order->artistProfile->user_id,
                    'direction' => 'buyer_to_artist', 'rating' => max(1, min(5, $shopRating)), 'comment' => $shopComment ?: null,
                ]);
                $this->ratings->recalcArtist($order->artist_profile_id);
            }

            $ids = $order->items()->pluck('product_id')->all();
            foreach ($products as $productId => $row) {
                if (! in_array((int) $productId, $ids, true) || empty($row['rating'])) {
                    continue;
                }
                if (ProductReview::where('customer_order_id', $order->id)->where('product_id', $productId)->exists()) {
                    continue;
                }
                ProductReview::create([
                    'customer_order_id' => $order->id, 'product_id' => $productId, 'reviewer_id' => $buyer->id,
                    'rating' => max(1, min(5, (int) $row['rating'])), 'comment' => ($row['comment'] ?? null) ?: null,
                ]);
                $this->ratings->recalcProduct((int) $productId);
            }
        });
    }

    public function submitArtist(CustomerOrder $order, User $artist, int $rating, ?string $comment): void
    {
        Fail::unless($this->artistCanReview($order, $artist), 'Ulasan pembeli hanya bisa diberikan untuk pesanan selesai dalam ' . config('miseku.review_days_artist') . ' hari.');
        Fail::unless(! Review::where('customer_order_id', $order->id)->where('reviewer_id', $artist->id)->exists(), 'Kamu sudah menilai pembeli untuk pesanan ini.');

        Review::create([
            'customer_order_id' => $order->id, 'reviewer_id' => $artist->id, 'reviewee_id' => $order->buyer_id,
            'direction' => 'artist_to_buyer', 'rating' => max(1, min(5, $rating)), 'comment' => $comment ?: null,
        ]);
        $this->ratings->recalcBuyer($order->buyer_id);
    }
}
