<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Penarikan dana artis — diproses manual admin (BPMN 6). */
class Withdrawal extends Model
{
    protected $table = 'withdrawal';

    const CREATED_AT = 'requested_at';

    protected $fillable = [
        'artist_profile_id', 'amount', 'bank_name', 'bank_account_number',
        'withdrawal_status', 'processed_by', 'transfer_proof', 'rejection_reason', 'processed_at',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'requested_at' => 'datetime', 'processed_at' => 'datetime'];
    }

    public const STATUS_LABELS = [
        'submitted' => 'Diajukan', 'processing' => 'Diproses admin', 'completed' => 'Selesai ditransfer', 'rejected' => 'Ditolak',
    ];

    /** Kelas warna chip status (dipakai Studio → Saldo & Admin → Penarikan). */
    public const STATUS_TONES = [
        'submitted'  => 'bg-amber-50 text-amber-700 ring-1 ring-amber-200',
        'processing' => 'bg-sky-50 text-sky-700 ring-1 ring-sky-200',
        'completed'  => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200',
        'rejected'   => 'bg-red-50 text-red-700 ring-1 ring-red-200',
    ];

    public function getStatusToneAttribute(): string
    {
        return self::STATUS_TONES[$this->withdrawal_status] ?? 'bg-ink-100 text-ink-600';
    }

    public function artistProfile(): BelongsTo
    {
        return $this->belongsTo(ArtistProfile::class, 'artist_profile_id');
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->withdrawal_status] ?? $this->withdrawal_status;
    }
}
