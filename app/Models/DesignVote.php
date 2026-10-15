<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DesignVote extends Model
{
    protected $table = 'design_vote';

    public $timestamps = false;

    protected $fillable = ['design_poll_id', 'design_option_id', 'user_id', 'voted_at'];

    protected function casts(): array
    {
        return ['voted_at' => 'datetime'];
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(DesignOption::class, 'design_option_id');
    }
}
