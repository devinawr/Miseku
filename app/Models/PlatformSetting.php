<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Aturan admin (fee + penalti). Tiap perubahan = 1 baris baru (snapshot histori). */
class PlatformSetting extends Model
{
    protected $table = 'platform_setting';

    public $timestamps = false;

    protected $fillable = [
        'fee_percentage', 'fee_minimum', 'penalty_voluntary', 'penalty_settlement_timeout',
        'penalty_shipping_timeout', 'effective_from', 'changed_by_id',
    ];

    protected function casts(): array
    {
        return [
            'fee_percentage' => 'decimal:2', 'fee_minimum' => 'decimal:2',
            'penalty_voluntary' => 'decimal:2', 'penalty_settlement_timeout' => 'decimal:2',
            'penalty_shipping_timeout' => 'decimal:2', 'effective_from' => 'datetime',
        ];
    }

    public function changer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_id');
    }

    /** Baris terbaru yang sudah berlaku; kalau tabel kosong pakai nilai default kamus data. */
    public static function current(): self
    {
        return static::query()->where('effective_from', '<=', now())->orderByDesc('effective_from')->orderByDesc('id')->first()
            ?? new static([
                'fee_percentage' => 5, 'fee_minimum' => 1000, 'penalty_voluntary' => 0.20,
                'penalty_settlement_timeout' => 0.20, 'penalty_shipping_timeout' => 0.30,
            ]);
    }

    /** Admin fee untuk subtotal produk tertentu (tidak pernah melebihi subtotal). */
    public function feeFor(float $productSubtotal): float
    {
        if ($productSubtotal <= 0) {
            return 0;
        }

        $fee = max(round($productSubtotal * (float) $this->fee_percentage / 100), (float) $this->fee_minimum);

        return min($fee, $productSubtotal);
    }
}
