<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductReview extends Model
{
    protected $table = 'product_review';

    /** ERD hanya punya created_at. */
    const UPDATED_AT = null;

    protected $fillable = ['customer_order_id', 'product_id', 'reviewer_id', 'rating', 'comment', 'created_at'];

    protected function casts(): array
    {
        return ['rating' => 'integer', 'created_at' => 'datetime'];
    }

    public function photos(): HasMany
    {
        return $this->hasMany(FileUpload::class, 'source_id')->where('source_table', 'product_review')->orderBy('sort_order');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
