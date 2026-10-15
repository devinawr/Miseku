<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/** Pengajuan custom merchandise oleh pembeli (BPMN 4). */
class CustomRequest extends Model
{
    protected $table = 'custom_request';

    protected $fillable = [
        'buyer_id', 'merchandise_type_id', 'design_character', 'quantity',
        'specification', 'budget_price', 'request_status',
    ];

    protected function casts(): array
    {
        return ['quantity' => 'integer', 'budget_price' => 'decimal:2'];
    }

    public const STATUS_LABELS = [
        'open' => 'Menunggu penawaran', 'offered' => 'Ada penawaran', 'selected' => 'Penawaran dipilih',
        'completed' => 'Selesai', 'cancelled' => 'Dibatalkan',
    ];

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function merchandiseType(): BelongsTo
    {
        return $this->belongsTo(MerchandiseType::class, 'merchandise_type_id');
    }

    public function offers(): HasMany
    {
        return $this->hasMany(CustomOffer::class, 'custom_request_id');
    }

    public function order(): HasOne
    {
        return $this->hasOne(CustomerOrder::class, 'custom_request_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->request_status] ?? $this->request_status;
    }
}
