<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** QR pengambilan di booth — 1:1 dengan pesanan. */
class EventPickup extends Model
{
    protected $table = 'event_pickup';

    protected $fillable = ['customer_order_id', 'event_booth_id', 'qr_code', 'pickup_status', 'picked_up_at'];

    protected function casts(): array
    {
        return ['picked_up_at' => 'datetime'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(CustomerOrder::class, 'customer_order_id');
    }

    public function booth(): BelongsTo
    {
        return $this->belongsTo(EventBooth::class, 'event_booth_id');
    }
}
