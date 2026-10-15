<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Notifikasi transaksional (in-app + status email pendamping). */
class TrackTransaksi extends Model
{
    protected $table = 'track_transaksi';

    const UPDATED_AT = null;

    protected $fillable = ['user_id', 'customer_order_id', 'title', 'body', 'notification_type', 'is_read', 'email_status'];

    protected function casts(): array
    {
        return ['is_read' => 'boolean', 'created_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(CustomerOrder::class, 'customer_order_id');
    }
}
