<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Jejak produk yang dilihat user — bahan vektor multi-hot untuk rekomendasi (BPMN 8).
 * Baru bisa terisi setelah modul login ada, karena butuh user_id.
 */
class ProductView extends Model
{
    protected $table = 'product_view';

    public $timestamps = false;

    protected $fillable = ['user_id', 'product_id', 'viewed_at'];

    protected function casts(): array
    {
        return ['viewed_at' => 'datetime'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
