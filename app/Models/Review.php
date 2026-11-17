<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Review extends Model
{
    protected $table = 'review';

    const UPDATED_AT = null;

    protected $fillable = ['customer_order_id', 'reviewer_id', 'reviewee_id', 'direction', 'rating', 'comment', 'created_at'];

    protected function casts(): array
    {
        return ['rating' => 'integer', 'created_at' => 'datetime'];
    }

    /** Ulasan toko berkaitan dengan pesanan, bukan satu produk tertentu. */
    public function order(): BelongsTo
    {
        return $this->belongsTo(CustomerOrder::class, 'customer_order_id');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(FileUpload::class, 'source_id')->where('source_table', 'review')->orderBy('sort_order');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function reviewee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewee_id');
    }
}
