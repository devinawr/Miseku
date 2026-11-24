<?php

namespace App\Services;

use App\Models\CustomerOrder;
use App\Models\FileUpload;
use App\Models\ProductReview;
use App\Models\Review;
use App\Models\User;
use App\Support\Fail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

/** Rating dua arah + ulasan produk — hanya pesanan completed, dalam jendela waktu. */
class ReviewService
{
    public function __construct(private RatingService $ratings) {}

    public function buyerCanReview(CustomerOrder $order, User $user): bool
    {
        return ! $this->hasReturnOrRefund($order) && $order->buyer_id === $user->id && $order->order_status === 'completed' && $order->order_type !== 'ots_event'
            && $order->completed_at && now()->lte($order->completed_at->copy()->addDays(config('miseku.review_days_buyer')));
    }

    public function artistCanReview(CustomerOrder $order, User $user): bool
    {
        return ! $this->hasReturnOrRefund($order) && $order->artistProfile->user_id === $user->id && $order->order_status === 'completed' && $order->order_type !== 'ots_event'
            && $order->completed_at && now()->lte($order->completed_at->copy()->addDays(config('miseku.review_days_artist')));
    }

    /** Jangan membuat ulasan baru selama retur berjalan atau refund dilakukan. */
    private function hasReturnOrRefund(CustomerOrder $order): bool
    {
        return $order->returns()->whereIn('return_status', ['submitted', 'processing', 'approved', 'completed'])->exists()
            || $order->payments()->where('payment_type', 'refund')->whereIn('payment_status', ['pending', 'success'])->exists();
    }

    /**
     * @param  array<int, array{rating:int, comment:?string}>  $products  product_id => ulasan
     */
    /** Files have been validated by the calling Livewire component. */
    public function submitBuyer(CustomerOrder $order, User $buyer, int $shopRating, ?string $shopComment, array $products, array $shopPhotos = [], array $productPhotos = []): void
    {
        Fail::unless($this->buyerCanReview($order, $buyer), 'Ulasan hanya bisa diberikan untuk pesanan selesai dalam ' . config('miseku.review_days_buyer') . ' hari.');

        $stored = [];
        try {
            DB::transaction(function () use ($order, $buyer, $shopRating, $shopComment, $products, $shopPhotos, $productPhotos, &$stored) {
                if (! Review::where('customer_order_id', $order->id)->where('reviewer_id', $buyer->id)->exists()) {
                    $review = Review::create([
                        'customer_order_id' => $order->id, 'reviewer_id' => $buyer->id, 'reviewee_id' => $order->artistProfile->user_id,
                        'direction' => 'buyer_to_artist', 'rating' => max(1, min(5, $shopRating)), 'comment' => $shopComment ?: null,
                    ]);
                    $this->savePhotos($review, 'review', $buyer, $shopPhotos, $stored);
                    $this->ratings->recalcArtist($order->artist_profile_id);
                }

                $ids = $order->items()->pluck('product_id')->all();
                foreach ($products as $productId => $row) {
                    if (! in_array((int) $productId, $ids, true) || empty($row['rating'])) continue;
                    if (ProductReview::where('customer_order_id', $order->id)->where('product_id', $productId)->exists()) continue;
                    $review = ProductReview::create([
                        'customer_order_id' => $order->id, 'product_id' => $productId, 'reviewer_id' => $buyer->id,
                        'rating' => max(1, min(5, (int) $row['rating'])), 'comment' => ($row['comment'] ?? null) ?: null,
                    ]);
                    $this->savePhotos($review, 'product_review', $buyer, $productPhotos[$productId] ?? [], $stored);
                    $this->ratings->recalcProduct((int) $productId);
                }
            });
        } catch (Throwable $e) {
            Storage::disk('uploads')->delete($stored);
            throw $e;
        }
    }

    public function submitArtist(CustomerOrder $order, User $artist, int $rating, ?string $comment, array $photos = []): void
    {
        Fail::unless($this->artistCanReview($order, $artist), 'Ulasan pembeli hanya bisa diberikan untuk pesanan selesai dalam ' . config('miseku.review_days_artist') . ' hari.');
        Fail::unless(! Review::where('customer_order_id', $order->id)->where('reviewer_id', $artist->id)->exists(), 'Kamu sudah menilai pembeli untuk pesanan ini.');

        $stored = [];
        try {
            DB::transaction(function () use ($order, $artist, $rating, $comment, $photos, &$stored) {
                $review = Review::create([
                    'customer_order_id' => $order->id, 'reviewer_id' => $artist->id, 'reviewee_id' => $order->buyer_id,
                    'direction' => 'artist_to_buyer', 'rating' => max(1, min(5, $rating)), 'comment' => $comment ?: null,
                ]);
                $this->savePhotos($review, 'review', $artist, $photos, $stored);
                $this->ratings->recalcBuyer($order->buyer_id);
            });
        } catch (Throwable $e) {
            Storage::disk('uploads')->delete($stored);
            throw $e;
        }
    }

    private function savePhotos(Review|ProductReview $review, string $table, User $uploader, array $photos, array &$stored): void
    {
        foreach ($photos as $i => $photo) {
            $path = $photo->store('reviews', 'uploads');
            $stored[] = $path;
            FileUpload::create(['url' => '/uploads/' . $path, 'source_table' => $table, 'source_id' => $review->id, 'uploaded_by' => $uploader->id, 'sort_order' => $i + 1]);
        }
    }
}
