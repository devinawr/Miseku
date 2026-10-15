<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Fandom extends Model
{
    protected $table = 'fandom';

    /** Tabel master tanpa created_at/updated_at di ERD. */
    public $timestamps = false;

    protected $fillable = ['fandom_name', 'status', 'proposed_by'];

    public function characters(): HasMany
    {
        return $this->hasMany(FandomCharacter::class, 'fandom_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(FandomMember::class, 'fandom_id');
    }

    /** Skin/tema tampilan fandom ini (dikelola admin). */
    public function themings(): HasMany
    {
        return $this->hasMany(FandomTheming::class, 'fandom_id');
    }

    public function topics(): HasMany
    {
        return $this->hasMany(DiscussionTopic::class, 'fandom_id');
    }

    /** Hanya master yang sudah disetujui admin yang boleh tampil di filter. */
    public function scopeActive(Builder $q): Builder
    {
        return $q->where('status', 'active');
    }

    /** User (biasanya artis) yang mengusulkan; NULL = dibuat admin/seed. */
    public function proposer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'proposed_by');
    }

    /** Nama file gambar fandom, mis. "Honkai: Star Rail" → honkai-star-rail */
    public function getSlugAttribute(): string
    {
        return Str::slug($this->fandom_name);
    }

    /**
     * Gambar kartu fandom (beranda): public/img/fandom/{slug}.jpg|png|webp.
     * Ganti filenya untuk mengganti gambar; null = kartu gradasi bawaan.
     */
    public function getImageUrlAttribute(): ?string
    {
        return self::publicImage("img/fandom/{$this->slug}");
    }

    /** Banner slider beranda khusus fandom ini: public/img/banner/fandom-{slug}.jpg */
    public function getBannerUrlAttribute(): ?string
    {
        return self::publicImage("img/banner/fandom-{$this->slug}");
    }

    public static function publicImage(string $base): ?string
    {
        foreach (['webp', 'jpg', 'jpeg', 'png'] as $ext) {
            if (is_file(public_path("{$base}.{$ext}"))) {
                return "/{$base}.{$ext}";
            }
        }

        return null;
    }
}
