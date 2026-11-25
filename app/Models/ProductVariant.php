<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class ProductVariant extends Model
{
    protected $table = 'product_variant';
    protected $fillable = ['product_id', 'name', 'image_url', 'stock', 'is_active'];
    protected function casts(): array { return ['stock' => 'integer', 'is_active' => 'boolean']; }
    public function product(): BelongsTo { return $this->belongsTo(Product::class, 'product_id'); }
    /** Pada PO, stock adalah kuota tiap varian per batch, bukan stok fisik. */
    public function remainingForBatch(PoBatch $batch): int
    {
        $ordered = OrderItem::where('po_batch_id', $batch->id)->where('product_variant_id', $this->id)
            ->whereHas('order', fn ($q) => $q->whereNotIn('order_status', ['cancelled', 'failed']))->sum('quantity');
        return max(0, $this->stock - (int) $ordered);
    }
}
