<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    protected $table = 'cart_item';

    /** ERD memakai added_at (bukan created_at) + updated_at. */
    const CREATED_AT = 'added_at';

    protected $fillable = ['user_id', 'product_id', 'quantity'];

    protected function casts(): array
    {
        return [
            'quantity'   => 'integer',
            'added_at'   => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
