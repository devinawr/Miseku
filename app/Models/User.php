<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    /** Tabel singular sesuai ERD TA. */
    protected $table = 'user';

    protected $hidden = ['password'];

    protected $fillable = [
        'name', 'email', 'password', 'phone', 'role', 'profile_photo', 'is_active', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active'             => 'boolean',
            'password'              => 'hashed',
            'rating_as_buyer'       => 'decimal:2',
            'rating_count_as_buyer' => 'integer',
            'created_at'            => 'datetime',
            'updated_at'            => 'datetime',
        ];
    }

    public function artistProfile(): HasOne
    {
        return $this->hasOne(ArtistProfile::class, 'user_id');
    }

    public function reviewsReceived(): HasMany
    {
        return $this->hasMany(Review::class, 'reviewee_id');
    }

    /** Foto profil dengan fallback avatar default. */
    public function getAvatarUrlAttribute(): string
    {
        return $this->profile_photo ?: '/img/dummy/avatar-1.jpg';
    }

    public function getInitialsAttribute(): string
    {
        return mb_strtoupper(mb_substr($this->name, 0, 1));
    }
}
