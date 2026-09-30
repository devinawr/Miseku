<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerOrder extends Model
{
    protected $table = 'customer_order';

    protected $fillable = [
        'order_code', 'buyer_id', 'artist_profile_id', 'address_id', 'order_type',
        'shipping_method', 'payment_scheme', 'product_subtotal', 'shipping_cost',
        'admin_fee', 'total_amount', 'order_status', 'completed_at', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'product_subtotal'    => 'decimal:2',
            'shipping_cost'       => 'decimal:2',
            'admin_fee'           => 'decimal:2',
            'total_amount'        => 'decimal:2',
            'settlement_deadline' => 'datetime',
            'shipping_deadline'   => 'datetime',
            'completed_at'        => 'datetime',
            'created_at'          => 'datetime',
            'updated_at'          => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'customer_order_id');
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }
}
