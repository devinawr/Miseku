<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PoBatch extends Model
{
    protected $table = 'po_batch';

    protected $fillable = [
        'product_id', 'price_po', 'payment_scheme', 'dp_amount', 'min_order',
        'max_slot', 'opens_at', 'closes_at', 'estimated_month', 'batch_status', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'price_po'        => 'decimal:2',
            'dp_amount'       => 'decimal:2',
            'opens_at'        => 'datetime',
            'closes_at'       => 'datetime',
            'estimated_month' => 'date',
            'completed_at'    => 'datetime',
            'created_at'      => 'datetime',
            'updated_at'      => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /** Batch yang masih menerima pesanan: status open dan berada dalam periode. */
    public function scopeOpen(Builder $q): Builder
    {
        return $q->where('batch_status', 'open')
            ->where('opens_at', '<=', now())
            ->where('closes_at', '>=', now());
    }

    public function getIsDpAttribute(): bool
    {
        return $this->payment_scheme === 'dp' && $this->dp_amount !== null;
    }
}
