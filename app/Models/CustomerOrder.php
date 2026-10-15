<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Pesanan — satu order = satu artis (keranjang multi-artis di-split).
 * Satu order_code dipakai untuk DP & pelunasan (dua baris payment).
 *
 * Mesin status (11 nilai):
 *   awaiting_payment → paid → awaiting_production → in_production → production_completed
 *   → (DP) awaiting_settlement → production_completed (lunas) → shipped / ready_for_pickup → completed
 *   + cancelled / failed
 */
class CustomerOrder extends Model
{
    protected $table = 'customer_order';

    protected $fillable = [
        'order_code', 'buyer_id', 'artist_profile_id', 'address_id', 'event_booth_id', 'custom_request_id',
        'order_type', 'shipping_method', 'payment_scheme', 'product_subtotal', 'shipping_cost', 'admin_fee',
        'total_amount', 'order_status', 'courier', 'tracking_number', 'settlement_deadline',
        'settlement_reminder_stage', 'shipping_deadline', 'shipping_reminder_stage', 'completed_at', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'product_subtotal'          => 'decimal:2',
            'shipping_cost'             => 'decimal:2',
            'admin_fee'                 => 'decimal:2',
            'total_amount'              => 'decimal:2',
            'settlement_deadline'       => 'datetime',
            'shipping_deadline'         => 'datetime',
            'settlement_reminder_stage' => 'integer',
            'shipping_reminder_stage'   => 'integer',
            'completed_at'              => 'datetime',
            'created_at'                => 'datetime',
            'updated_at'                => 'datetime',
        ];
    }

    public const STATUS_LABELS = [
        'awaiting_payment'     => 'Menunggu pembayaran',
        'paid'                 => 'Dibayar',
        'awaiting_production'  => 'Menunggu produksi',
        'in_production'        => 'Sedang diproduksi',
        'production_completed' => 'Siap dikirim',
        'awaiting_settlement'  => 'Menunggu pelunasan',
        'shipped'              => 'Dikirim',
        'ready_for_pickup'     => 'Siap diambil di booth',
        'completed'            => 'Selesai',
        'cancelled'            => 'Dibatalkan',
        'failed'               => 'Gagal',
    ];

    public const STATUS_TONES = [
        'awaiting_payment' => 'bg-amber-50 text-amber-700', 'paid' => 'bg-sky-50 text-sky-700',
        'awaiting_production' => 'bg-sky-50 text-sky-700', 'in_production' => 'bg-brand-50 text-brand-700',
        'production_completed' => 'bg-brand-50 text-brand-700', 'awaiting_settlement' => 'bg-amber-50 text-amber-700',
        'shipped' => 'bg-pop-50 text-pop-700', 'ready_for_pickup' => 'bg-pop-50 text-pop-700',
        'completed' => 'bg-emerald-50 text-emerald-700', 'cancelled' => 'bg-ink-100 text-ink-600', 'failed' => 'bg-red-50 text-red-600',
    ];

    public const TYPE_LABELS = ['ready_stock' => 'Ready stock', 'pre_order' => 'Pre-order', 'custom' => 'Custom', 'ots_event' => 'Penjualan di booth (OTS)'];

    /** Status "masih berjalan" (belum selesai/batal/gagal). */
    public const ACTIVE = ['awaiting_payment', 'paid', 'awaiting_production', 'in_production', 'production_completed', 'awaiting_settlement', 'shipped', 'ready_for_pickup'];

    // ---------- Relasi ----------

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'customer_order_id');
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function artistProfile(): BelongsTo
    {
        return $this->belongsTo(ArtistProfile::class, 'artist_profile_id');
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'address_id');
    }

    public function booth(): BelongsTo
    {
        return $this->belongsTo(EventBooth::class, 'event_booth_id');
    }

    public function customRequest(): BelongsTo
    {
        return $this->belongsTo(CustomRequest::class, 'custom_request_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'customer_order_id')->orderBy('id');
    }

    public function pickup(): HasOne
    {
        return $this->hasOne(EventPickup::class, 'customer_order_id');
    }

    public function cancellations(): HasMany
    {
        return $this->hasMany(Cancellation::class, 'customer_order_id')->orderBy('id');
    }

    public function returns(): HasMany
    {
        return $this->hasMany(OrderReturn::class, 'customer_order_id')->orderBy('id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'customer_order_id');
    }

    public function productReviews(): HasMany
    {
        return $this->hasMany(ProductReview::class, 'customer_order_id');
    }

    public function balanceTransactions(): HasMany
    {
        return $this->hasMany(BalanceTransaction::class, 'customer_order_id')->orderBy('id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(TrackTransaksi::class, 'customer_order_id');
    }

    // ---------- Scope ----------

    public function scopeForArtist(Builder $q, int $artistProfileId): Builder
    {
        return $q->where('artist_profile_id', $artistProfileId);
    }

    // ---------- Helper ----------

    public function getStatusLabelAttribute(): string
    {
        if ($this->order_status === 'production_completed' && $this->order_type === 'pre_order'
            && $this->payment_scheme === 'dp' && ! $this->isFullyPaid()) {
            return 'Produksi selesai';
        }

        if ($this->order_status === 'paid' && $this->order_type === 'pre_order') {
            return 'Dibayar · menunggu PO ditutup';
        }

        return self::STATUS_LABELS[$this->order_status] ?? $this->order_status;
    }

    public function getStatusToneAttribute(): string
    {
        return self::STATUS_TONES[$this->order_status] ?? 'bg-ink-100 text-ink-600';
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPE_LABELS[$this->order_type] ?? $this->order_type;
    }

    public function isCourier(): bool
    {
        return $this->shipping_method === 'courier';
    }

    public function isPickup(): bool
    {
        return $this->shipping_method === 'event_pickup';
    }

    public function isDp(): bool
    {
        return $this->payment_scheme === 'dp';
    }

    /** Total pembayaran berhasil (DP + pelunasan / full), tidak termasuk refund. */
    public function paidAmount(): float
    {
        return (float) $this->payments->where('payment_status', 'success')->where('payment_type', '!=', 'refund')->sum('amount')
            + (float) $this->payments->where('payment_status', 'refunded')->where('payment_type', '!=', 'refund')->sum('amount');
    }

    /** Uang yang sudah masuk dan belum dikembalikan. */
    public function refundableAmount(): float
    {
        $paid = (float) $this->payments->whereIn('payment_status', ['success', 'refunded'])->where('payment_type', '!=', 'refund')->sum('amount');
        $refunded = (float) $this->payments->where('payment_type', 'refund')->whereIn('payment_status', ['success', 'pending'])->sum('amount');

        return max(0, $paid - $refunded);
    }

    public function isFullyPaid(): bool
    {
        return $this->paidAmount() + 0.001 >= (float) $this->total_amount;
    }

    /** Tagihan yang sedang menunggu dibayar (DP / pelunasan / full). */
    public function pendingPayment(): ?Payment
    {
        return $this->payments->where('payment_type', '!=', 'refund')->where('payment_status', 'pending')->last();
    }

    /** Nominal tagihan pertama: DP = Σ dp_amount × qty + ongkir; full = total. */
    public function firstInvoiceAmount(): float
    {
        if (! $this->isDp()) {
            return (float) $this->total_amount;
        }

        $dp = $this->items->sum(fn (OrderItem $i) => (float) ($i->batch?->dp_amount ?? $i->unit_price) * $i->quantity);

        return (float) $dp + (float) $this->shipping_cost;
    }

    public function getBuyerUrlAttribute(): string
    {
        return route('orders.show', $this);
    }

    public function getArtistUrlAttribute(): string
    {
        return route('studio.orders.show', $this);
    }

    /** Batch PO pesanan ini (order pre_order selalu 1 batch). */
    public function poBatch(): ?PoBatch
    {
        return $this->items->first()?->batch;
    }
}
