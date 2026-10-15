<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MerchandiseType extends Model
{
    protected $table = 'merchandise_type';

    public $timestamps = false;

    protected $fillable = ['type_name', 'status', 'proposed_by'];

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
