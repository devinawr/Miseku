<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Penawaran artis — 1 artis 1 penawaran per request (direvisi saat nego). */
class CustomOffer extends Model
{
    protected $table = 'custom_offer';

    protected $fillable = ['custom_request_id', 'artist_profile_id', 'offer_price', 'estimated_days', 'note', 'offer_status'];

    protected function casts(): array
    {
        return ['offer_price' => 'decimal:2', 'estimated_days' => 'integer'];
    }

    public const STATUS_LABELS = ['pending' => 'Menunggu jawaban', 'selected' => 'Dipilih', 'rejected' => 'Ditolak', 'cancelled' => 'Tidak dipilih'];

    public function request(): BelongsTo
    {
        return $this->belongsTo(CustomRequest::class, 'custom_request_id');
    }

    public function artistProfile(): BelongsTo
    {
        return $this->belongsTo(ArtistProfile::class, 'artist_profile_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->offer_status] ?? $this->offer_status;
    }
}
