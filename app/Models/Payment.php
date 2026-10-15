<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Baris tagihan / pembayaran / refund per pesanan (DP & pelunasan = 2 baris, 1 order). */
class Payment extends Model
{
    protected $table = 'payment';

    protected $fillable = [
        'customer_order_id', 'payment_type', 'amount', 'payment_method', 'midtrans_order_id',
        'midtrans_transaction_id', 'refund_method', 'refund_proof', 'payment_status', 'paid_at',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'paid_at' => 'datetime'];
    }

    public const TYPE_LABELS = ['dp' => 'DP', 'settlement' => 'Pelunasan', 'full' => 'Bayar penuh', 'refund' => 'Refund'];

    public const STATUS_LABELS = [
        'pending' => 'Menunggu pembayaran', 'success' => 'Berhasil', 'failed' => 'Gagal',
        'expired' => 'Kedaluwarsa', 'refunded' => 'Sudah direfund',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(CustomerOrder::class, 'customer_order_id');
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPE_LABELS[$this->payment_type] ?? $this->payment_type;
    }

    public function getStatusLabelAttribute(): string
    {
        if ($this->payment_type === 'refund') {
            return match ($this->payment_status) {
                'success' => 'Refund selesai',
                'pending' => 'Refund diproses admin',
                default   => self::STATUS_LABELS[$this->payment_status] ?? $this->payment_status,
            };
        }

        return self::STATUS_LABELS[$this->payment_status] ?? $this->payment_status;
    }
}
