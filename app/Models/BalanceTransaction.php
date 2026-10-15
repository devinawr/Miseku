<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Jurnal mutasi saldo artis — sumber kebenaran available/held balance. */
class BalanceTransaction extends Model
{
    protected $table = 'balance_transaction';

    const UPDATED_AT = null;

    protected $fillable = [
        'artist_profile_id', 'customer_order_id', 'payment_id', 'withdrawal_id', 'mutation_type',
        'category', 'gross_amount', 'fee_amount', 'amount', 'description',
    ];

    protected function casts(): array
    {
        return [
            'gross_amount' => 'decimal:2', 'fee_amount' => 'decimal:2', 'amount' => 'decimal:2',
            'created_at'   => 'datetime',
        ];
    }

    public const CATEGORY_LABELS = [
        'hold' => 'Dana ditahan', 'release' => 'Dana dilepas', 'forfeiture' => 'DP/dana hangus ke artis',
        'refund' => 'Refund ke pembeli', 'withdrawal' => 'Penarikan',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(CustomerOrder::class, 'customer_order_id');
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORY_LABELS[$this->category] ?? $this->category;
    }
}
