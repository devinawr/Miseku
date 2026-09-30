<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MerchandiseType extends Model
{
    protected $table = 'merchandise_type';

    public $timestamps = false;

    protected $fillable = ['type_name', 'status', 'proposed_by'];

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('status', 'active');
    }
}
