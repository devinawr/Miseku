<?php

namespace App\Models;

use App\Support\ThemePalette;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Skin / tema tampilan per fandom (dikelola admin).
 * Mengubah warna utama, warna gradasi, warna aksen & banner — konten tidak berubah.
 */
class FandomTheming extends Model
{
    protected $table = 'fandom_theming';

    protected $fillable = [
        'fandom_id', 'theming_name', 'primary_color', 'secondary_color', 'accent_color',
        'banner_image', 'is_active', 'created_by',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function fandom(): BelongsTo
    {
        return $this->belongsTo(Fandom::class, 'fandom_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Pengguna yang memilih tema ini secara manual (user.active_theming_id). */
    public function chosenBy(): HasMany
    {
        return $this->hasMany(User::class, 'active_theming_id');
    }

    /** Hanya tema aktif dari fandom yang aktif. */
    public function scopeUsable(Builder $q): Builder
    {
        return $q->where('is_active', true)->whereHas('fandom', fn ($f) => $f->where('status', 'active'));
    }

    /** Variabel CSS (--color-brand-*, --color-accent-*, --color-pop-*) untuk tema ini. */
    public function cssVariables(): array
    {
        return ThemePalette::variables($this->primary_color, $this->secondary_color, $this->accent_color);
    }

    public function getLabelAttribute(): string
    {
        return $this->theming_name . ' · ' . ($this->fandom?->fandom_name ?? '-');
    }
}