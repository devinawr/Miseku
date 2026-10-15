<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Retur barang yang SUDAH diterima.
 * submitted  = diajukan pembeli (menunggu tinjauan artis)
 * processing = artis setuju, pembeli mengirim barang kembali
 * approved   = barang retur diterima & sesuai → refund diproses
 * rejected   = ditolak artis (saat tinjauan / pemeriksaan)
 * completed  = refund selesai
 */
class OrderReturn extends Model
{
    protected $table = 'order_return';

    protected $fillable = ['customer_order_id', 'buyer_id', 'reason', 'photo_evidence', 'return_status'];

    public const STATUS_LABELS = [
        'submitted' => 'Menunggu tinjauan artis', 'processing' => 'Disetujui — kirim barang kembali',
        'approved' => 'Barang diterima artis — refund diproses', 'rejected' => 'Ditolak', 'completed' => 'Selesai (dana dikembalikan)',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(CustomerOrder::class, 'customer_order_id');
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    /** Foto bukti tambahan (file_upload polimorfik). */
    public function photos(): HasMany
    {
        return $this->hasMany(FileUpload::class, 'source_id')->where('source_table', 'order_return')->orderBy('sort_order');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->return_status] ?? $this->return_status;
    }
}
