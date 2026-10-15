<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Berkas pembatalan (barang belum diterima) — juga sumber penalti rating. */
class Cancellation extends Model
{
    protected $table = 'cancellation';

    protected $fillable = [
        'customer_order_id', 'requester_id', 'cancellation_type', 'reason',
        'cancellation_status', 'artist_response', 'resolved_at',
    ];

    protected function casts(): array
    {
        return ['resolved_at' => 'datetime'];
    }

    public const TYPE_LABELS = [
        'voluntary' => 'Diajukan pengguna', 'settlement_timeout' => 'Lewat tenggat pelunasan', 'shipping_timeout' => 'Lewat tenggat kirim',
    ];

    public const STATUS_LABELS = [
        'submitted' => 'Menunggu tanggapan artis', 'in_discussion' => 'Didiskusikan lewat chat', 'approved' => 'Disetujui', 'rejected' => 'Ditolak',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(CustomerOrder::class, 'customer_order_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->cancellation_status] ?? $this->cancellation_status;
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPE_LABELS[$this->cancellation_type] ?? $this->cancellation_type;
    }
}
