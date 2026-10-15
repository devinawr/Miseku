<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Riwayat pencarian & filter di katalog — salah satu sumber profil user
 * pada Content-Based Filtering (TA: "histori pencarian").
 */
class SearchHistory extends Model
{
    protected $table = 'search_history';

    public $timestamps = false;

    protected $fillable = [
        'user_id', 'keyword', 'fandom_id', 'fandom_character_id', 'merchandise_type_id', 'searched_at',
    ];

    protected function casts(): array
    {
        return ['searched_at' => 'datetime'];
    }
}
