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
        'max_slot', 'opens_at', 'closes_at', 'estimated_month', 'batch_status', 'completed_at', 'created_at',
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

    public function orderItems(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(OrderItem::class, 'po_batch_id');
    }

    /** Jumlah pcs yang sudah dipesan di batch ini (pesanan yang tidak batal/gagal). */
    public function orderedQuantity(bool $paidOnly = false): int
    {
        return (int) OrderItem::query()
            ->where('po_batch_id', $this->id)
            ->whereHas('order', fn ($q) => $paidOnly
                ? $q->whereNotIn('order_status', ['awaiting_payment', 'cancelled', 'failed'])
                : $q->whereNotIn('order_status', ['cancelled', 'failed']))
            ->sum('quantity');
    }

    /** Sisa slot (null = tanpa batas). */
    public function remainingSlot(): ?int
    {
        return $this->max_slot ? max(0, (int) $this->max_slot - $this->orderedQuantity()) : null;
    }

    public const STATUS_LABELS = [
        'open' => 'Dibuka', 'closed' => 'Ditutup · menunggu produksi', 'in_production' => 'Sedang diproduksi',
        'completed' => 'Produksi selesai', 'cancelled' => 'Dibatalkan (target tidak tercapai)',
    ];

    public function getStatusLabelAttribute(): string
    {
        if ($this->batch_status === 'open' && $this->closes_at->isPast()) {
            return 'Lewat tanggal tutup · menunggu rekap';
        }

        return self::STATUS_LABELS[$this->batch_status] ?? $this->batch_status;
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
