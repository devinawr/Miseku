<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    /** Hanya master yang sudah disetujui admin yang boleh tampil di filter. */
    public function scopeActive(Builder $q): Builder
    {
        return $q->where('status', 'active');
    }
}
