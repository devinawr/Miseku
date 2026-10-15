<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FandomCharacter extends Model
{
    protected $table = 'fandom_character';

    public $timestamps = false;

    protected $fillable = ['fandom_id', 'character_name', 'status', 'proposed_by'];

    public function fandom(): BelongsTo
    {
        return $this->belongsTo(Fandom::class, 'fandom_id');
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('status', 'active');
    }

    /** User (biasanya artis) yang mengusulkan; NULL = dibuat admin/seed. */
    public function proposer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'proposed_by');
    }
}
