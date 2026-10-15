<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    /** Tabel singular sesuai ERD TA. */
    protected $table = 'user';

    protected $hidden = ['password'];

    /** ERD tidak punya kolom remember_token, jadi fitur "ingat saya" dimatikan. */
    protected $rememberTokenName = '';

    protected $fillable = [
        'name', 'email', 'password', 'phone', 'role', 'profile_photo', 'is_active', 'created_at',
        'rating_as_buyer', 'rating_count_as_buyer', 'theming_mode', 'active_theming_id',
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

    /** Fandom favorit (boleh lebih dari satu) — pivot favorite_fandom. */
    public function favoriteFandoms(): BelongsToMany
    {
        return $this->belongsToMany(Fandom::class, 'favorite_fandom', 'user_id', 'fandom_id')
            ->withPivot('created_at');
    }

    /** Tema pilihan sendiri (dipakai saat theming_mode = manual). */
    public function activeTheming(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(FandomTheming::class, 'active_theming_id');
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class, 'user_id');
    }

    public function addresses(): HasMany
    {
        // buku alamat pengiriman (alamat toko tidak termasuk)
        return $this->hasMany(Address::class, 'user_id')->where('address_label', '!=', Address::SHOP_LABEL)
            ->orderByDesc('is_primary')->orderBy('id');
    }

    /** Alamat toko (asal pengiriman) — hanya untuk akun artis. */
    public function shopAddress(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Address::class, 'user_id')->where('address_label', Address::SHOP_LABEL);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(CustomerOrder::class, 'buyer_id');
    }

    public function notificationsInbox(): HasMany
    {
        return $this->hasMany(TrackTransaksi::class, 'user_id');
    }

    public function fandomMemberships(): HasMany
    {
        return $this->hasMany(FandomMember::class, 'user_id');
    }

    public function isArtist(): bool
    {
        return $this->role === 'artist';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function reviewsReceived(): HasMany
    {
        return $this->hasMany(Review::class, 'reviewee_id');
    }

    /** URL foto profil, atau null kalau belum ada (tampilan memakai inisial). */
    public function getAvatarUrlAttribute(): ?string
    {
        return $this->profile_photo ?: null;
    }

    public function getInitialsAttribute(): string
    {
        return mb_strtoupper(mb_substr($this->name, 0, 1));
    }
}
