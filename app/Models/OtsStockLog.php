<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class OtsStockLog extends Model
{
    protected $table = 'ots_stock_log';
    const UPDATED_AT = null;
    protected $fillable = ['artist_profile_id', 'event_booth_id', 'product_id', 'event_name', 'booth_name', 'product_name', 'quantity'];
    protected function casts(): array { return ['created_at' => 'datetime', 'quantity' => 'integer']; }
}
