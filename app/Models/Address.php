<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Buku alamat pembeli (BPMN 1: pengiriman kurir). */
class Address extends Model
{
    /**
     * Alamat toko artis disimpan di tabel address yang sama dengan label khusus ini
     * (tanpa ubah ERD). Tidak tampil di buku alamat pembeli / checkout, hanya dipakai
     * sebagai kota asal pengiriman (ongkir).
     */
    public const SHOP_LABEL = 'Alamat toko';

    protected $table = 'address';

    protected $fillable = [
        'user_id', 'address_label', 'recipient_name', 'full_address',
        'rajaongkir_city_id', 'province', 'postal_code', 'is_primary',
    ];

    protected function casts(): array
    {
        return ['is_primary' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Nama kota diambil dari daftar kota (RajaOngkir / lokal) karena tabel hanya menyimpan ID. */
    public function getCityNameAttribute(): string
    {
        return app(\App\Services\ShippingService::class)->cityName($this->rajaongkir_city_id) ?? ('Kota #' . $this->rajaongkir_city_id);
    }

    public function getOneLineAttribute(): string
    {
        return trim($this->full_address . ', ' . $this->city_name . ', ' . $this->province . ($this->postal_code ? ' ' . $this->postal_code : ''));
    }
}
