<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FandomMember extends Model
{
    protected $table = 'fandom_member';

    public $timestamps = false;

    protected $fillable = ['fandom_id', 'user_id', 'role', 'joined_at'];

    protected function casts(): array
    {
        return ['joined_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function fandom(): BelongsTo
    {
        return $this->belongsTo(Fandom::class, 'fandom_id');
    }
}
