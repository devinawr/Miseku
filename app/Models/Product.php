<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Product extends Model
{
    protected $table = 'product';

    protected $fillable = [
        'artist_profile_id', 'fandom_character_id', 'merchandise_type_id', 'design_option_id',
        'product_name', 'description', 'price', 'size', 'stock',
        'availability_type', 'allow_event_pickup', 'product_status', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'price'              => 'decimal:2',
            'stock'              => 'integer',
            'allow_event_pickup' => 'boolean',
            'rating'             => 'decimal:2',
            'rating_count'       => 'integer',
            'created_at'         => 'datetime',
            'updated_at'         => 'datetime',
        ];
    }

    // ---------- Relasi ----------

    public function artistProfile(): BelongsTo
    {
        return $this->belongsTo(ArtistProfile::class, 'artist_profile_id');
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(FandomCharacter::class, 'fandom_character_id');
    }

    public function merchandiseType(): BelongsTo
    {
        return $this->belongsTo(MerchandiseType::class, 'merchandise_type_id');
    }

    public function batches(): HasMany
    {
        return $this->hasMany(PoBatch::class, 'product_id');
    }

    public function openBatch(): HasOne
    {
        return $this->hasOne(PoBatch::class, 'product_id')->open()->latestOfMany();
    }

    /** Galeri produk lewat file_upload (polimorfik, bukan FK). */
    public function images(): HasMany
    {
        return $this->hasMany(FileUpload::class, 'source_id')
            ->where('source_table', 'product')
            ->orderBy('sort_order');
    }

    public function primaryImage(): HasOne
    {
        return $this->hasOne(FileUpload::class, 'source_id')
            ->where('source_table', 'product')
            ->orderBy('sort_order');
    }

    public function productReviews(): HasMany
    {
        return $this->hasMany(ProductReview::class, 'product_id');
    }

    // ---------- Scope ----------

    /** Hanya produk yang boleh tampil di katalog publik. */
    public function scopeVisible(Builder $q): Builder
    {
        return $q->where('product_status', 'active');
    }

    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        $term = trim((string) $term);
        if ($term === '') {
            return $q;
        }

        return $q->where(function (Builder $w) use ($term) {
            $w->where('product_name', 'like', "%{$term}%")
                ->orWhereHas('character', fn (Builder $c) => $c->where('character_name', 'like', "%{$term}%"))
                ->orWhereHas('character.fandom', fn (Builder $f) => $f->where('fandom_name', 'like', "%{$term}%"))
                ->orWhereHas('merchandiseType', fn (Builder $m) => $m->where('type_name', 'like', "%{$term}%"))
                ->orWhereHas('artistProfile', fn (Builder $a) => $a->where('shop_name', 'like', "%{$term}%"));
        });
    }

    // ---------- Accessor tampilan ----------

    public function getIsPreOrderAttribute(): bool
    {
        return $this->availability_type === 'pre_order';
    }

    /** Harga yang ditampilkan: harga batch PO yang sedang buka, kalau tidak ada pakai harga katalog. */
    public function getDisplayPriceAttribute(): float
    {
        return (float) ($this->is_pre_order && $this->openBatch
            ? $this->openBatch->price_po
            : $this->price);
    }

    public function getCoverUrlAttribute(): string
    {
        return $this->primaryImage?->url ?: '/img/dummy/merch-1.jpg';
    }

    public function getIsSoldOutAttribute(): bool
    {
        return ! $this->is_pre_order && $this->stock < 1;
    }

    /** Label badge status untuk kartu produk. */
    public function getStatusBadgeAttribute(): array
    {
        if ($this->is_pre_order) {
            return $this->openBatch
                ? ['label' => 'Pre-Order', 'tone' => 'po']
                : ['label' => 'PO Ditutup', 'tone' => 'muted'];
        }

        return $this->is_sold_out
            ? ['label' => 'Stok Habis', 'tone' => 'muted']
            : ['label' => 'Ready Stock', 'tone' => 'ready'];
    }
}
