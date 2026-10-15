<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ArtistProfile extends Model
{
    protected $table = 'artist_profile';

    protected $fillable = [
        'user_id', 'shop_name', 'shop_description', 'bank_name', 'bank_account_number', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'available_balance' => 'decimal:2',
            'held_balance'      => 'decimal:2',
            'rating'            => 'decimal:2',
            'rating_count'      => 'integer',
            'created_at'        => 'datetime',
            'updated_at'        => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(CustomerOrder::class, 'artist_profile_id');
    }

    public function booths(): HasMany
    {
        return $this->hasMany(EventBooth::class, 'artist_profile_id');
    }

    public function balanceTransactions(): HasMany
    {
        return $this->hasMany(BalanceTransaction::class, 'artist_profile_id');
    }

    public function withdrawals(): HasMany
    {
        return $this->hasMany(Withdrawal::class, 'artist_profile_id');
    }

    public function polls(): HasMany
    {
        return $this->hasMany(DesignPoll::class, 'artist_profile_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'artist_profile_id');
    }

    /** Banner toko disimpan di file_upload (polimorfik: source_table = 'artist_profile'). */
    public function banner(): HasOne
    {
        return $this->hasOne(FileUpload::class, 'source_id')
            ->where('source_table', 'artist_profile')
            ->orderBy('sort_order');
    }

    public function getBannerUrlAttribute(): string
    {
        return $this->banner?->url ?: '/img/dummy/banner-1.jpg';
    }

    /** Ulasan toko = review arah buyer_to_artist yang ditujukan ke akun pemilik toko. */
    public function shopReviews(): HasMany
    {
        return $this->hasMany(Review::class, 'reviewee_id', 'user_id')
            ->where('direction', 'buyer_to_artist');
    }
}
