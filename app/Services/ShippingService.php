<?php

namespace App\Services;

use App\Models\ArtistProfile;
use App\Models\Address;
use App\Support\Cities;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Ongkir (BPMN 1). Dua mode:
 *  - LIVE  : RAJAONGKIR_API_KEY diisi → API RajaOngkir (Komerce) untuk cari tujuan & hitung ongkir.
 *  - LOKAL : key kosong → daftar kota cadangan (App\Support\Cities) + perkiraan tarif berbasis jarak.
 * Asal pengiriman = alamat toko artis (Address::SHOP_LABEL); bila belum ada → alamat utama → MISEKU_DEFAULT_ORIGIN.
 */
class ShippingService
{
    public function isLive(): bool
    {
        return filled(config('services.rajaongkir.key'));
    }

    /** Daftar provinsi atau kota dari API; kegagalan tidak disamarkan sebagai data API. */
    public function regionOptions(string $type, string $parent = ''): array
    {
        if (! $this->isLive()) {
            if ($type === 'province') {
                return ['items' => collect(Cities::LIST)->pluck(1)->unique()->sort()->values()->map(fn ($name) => ['id' => $name, 'name' => $name])->all(), 'error' => false];
            }
            return ['items' => collect(Cities::LIST)->filter(fn ($c) => $c[1] === $parent)->map(fn ($c, $id) => ['id' => (string) $id, 'name' => $c[0]])->sortBy('name')->values()->all(), 'error' => false];
        }
        if (! in_array($type, ['province', 'city'], true) || ($type === 'city' && ! ctype_digit($parent))) {
            return ['items' => [], 'error' => true];
        }
        $path = $type === 'province' ? '/destination/province' : '/destination/city/' . $parent;
        $cacheKey = 'ro_regions_' . $type . '_' . $parent;
        if ($cached = Cache::get($cacheKey)) {
            return ['items' => $cached, 'error' => false];
        }
        try {
            $res = Http::withHeaders(['key' => config('services.rajaongkir.key')])->timeout(10)->get(config('services.rajaongkir.base_url') . $path);
            if ($res->successful() && is_array($res->json('data'))) {
                $items = collect($res->json('data'))->filter(fn ($r) => isset($r['id'], $r['name']))->map(fn ($r) => ['id' => (string) $r['id'], 'name' => (string) $r['name']])->sortBy('name')->values()->all();
                if ($items) Cache::put($cacheKey, $items, now()->addHours(24));
                return ['items' => $items, 'error' => false];
            }
        } catch (\Throwable $e) {
            Log::warning('RajaOngkir daftar wilayah gagal: ' . $e->getMessage());
        }
        return ['items' => [], 'error' => true];
    }

    /** Hasil ID berasal dari Search Domestic Destination, sesuai endpoint ongkir yang dipakai. */
    public function lookupCities(string $term, int $limit = 50, int $offset = 0): array
    {
        $term = trim($term);
        if (mb_strlen($term) < 3) return ['items' => [], 'error' => false];
        if (! $this->isLive()) {
            $items = collect(Cities::LIST)->filter(fn ($c) => str_contains(mb_strtolower($c[0] . ' ' . $c[1]), mb_strtolower($term)))
                ->map(fn ($c, $id) => ['id' => (string) $id, 'label' => $c[0], 'province' => $c[1]])->sortBy('label')->values()->slice($offset, $limit)->values()->all();
            return ['items' => $items, 'error' => false];
        }
        try {
            $res = Http::withHeaders(['key' => config('services.rajaongkir.key')])->timeout(10)
                ->get(config('services.rajaongkir.base_url') . '/destination/domestic-destination', ['search' => $term, 'limit' => $limit, 'offset' => $offset]);
            if ($res->status() === 404) return ['items' => [], 'error' => false];
            if ($res->successful() && is_array($res->json('data'))) {
                $items = collect($res->json('data'))->filter(fn ($row) => isset($row['id']))->map(function ($row) {
                    $label = (string) ($row['label'] ?? $row['city_name'] ?? '');
                    $zip = trim((string) ($row['zip_code'] ?? ''));
                    if (! preg_match('/^\d{5}$/', $zip)) {
                        $zip = preg_match('/(?<!\d)(\d{5})\s*$/u', $label, $match) ? $match[1] : '';
                    }
                    $item = ['id' => (string) $row['id'], 'label' => $label,
                        'province' => (string) ($row['province_name'] ?? ''), 'city' => (string) ($row['city_name'] ?? ''), 'zip' => $zip];
                    Cache::put('ro_city_' . $item['id'], $item['label'], now()->addDays(30));
                    return $item;
                })->values()->all();
                return ['items' => $items, 'error' => false];
            }
        } catch (\Throwable $e) {
            Log::warning('RajaOngkir search gagal: ' . $e->getMessage());
        }
        return ['items' => [], 'error' => true];
    }

    public function searchCities(string $term, int $limit = 12): array
    {
        return $this->lookupCities($term, $limit)['items'];
    }

    public function cityName(?string $id): ?string
    {
        if ($id === null) {
            return null;
        }

        return Cache::get('ro_city_' . $id) ?? (Cities::LIST[$id][0] ?? null);
    }

    public function provinceOf(string $id): ?string
    {
        return Cities::LIST[$id][1] ?? null;
    }

    public function originFor(ArtistProfile $artist): string
    {
        // 1) alamat toko (diisi saat buka toko)  2) alamat utama akun  3) kota default
        $shop = Address::where('user_id', $artist->user_id)->where('address_label', Address::SHOP_LABEL)->first();
        $primary = $shop ?? Address::where('user_id', $artist->user_id)->orderByDesc('is_primary')->orderBy('id')->first();

        return $primary?->rajaongkir_city_id ?: (string) config('miseku.default_origin_city');
    }

    /**
     * Daftar layanan + ongkir untuk satu kurir.
     * Hasil: [['service' => 'REG', 'description' => '...', 'cost' => 18000, 'etd' => '2-3 hari']]
     */
    public function quote(string $origin, string $destination, int $weightGram, string $courier): array
    {
        $weightGram = max(1, $weightGram);
        $key = "ongkir_{$origin}_{$destination}_{$weightGram}_{$courier}";

        return Cache::remember($key, now()->addHours(6), function () use ($origin, $destination, $weightGram, $courier) {
            if ($this->isLive()) {
                try {
                    $res = Http::withHeaders(['key' => config('services.rajaongkir.key')])->asForm()->timeout(15)
                        ->post(config('services.rajaongkir.base_url') . '/calculate/domestic-cost', [
                            'origin' => $origin, 'destination' => $destination, 'weight' => $weightGram,
                            'courier' => $courier, 'price' => 'lowest',
                        ]);

                    if ($res->successful()) {
                        $rows = collect($res->json('data') ?? [])->map(fn ($r) => [
                            'service'     => (string) ($r['service'] ?? 'REG'),
                            'description' => (string) ($r['description'] ?? ''),
                            'cost'        => (int) ($r['cost'] ?? 0),
                            'etd'         => trim((string) ($r['etd'] ?? '')),
                        ])->filter(fn ($r) => $r['cost'] > 0)->values()->all();

                        if ($rows) {
                            return $rows;
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning('RajaOngkir cost gagal: ' . $e->getMessage());
                }
            }

            return $this->localQuote($origin, $destination, $weightGram, $courier);
        });
    }

    /** Perkiraan tarif lokal: dasar per kurir + jarak, dikali berat (kg dibulatkan ke atas). */
    private function localQuote(string $origin, string $destination, int $weightGram, string $courier): array
    {
        $from = Cities::LIST[$origin] ?? Cities::LIST[(string) config('miseku.default_origin_city')] ?? null;
        $to = Cities::LIST[$destination] ?? null;
        $km = ($from && $to) ? $this->distanceKm($from[2], $from[3], $to[2], $to[3]) : 600;
        $kg = (int) max(1, ceil($weightGram / 1000));

        $base = ['jne' => 9000, 'pos' => 8000, 'tiki' => 9500, 'sicepat' => 8500, 'jnt' => 9000][$courier] ?? 9000;
        $regular = (int) (round(($base + $km * 9) / 500) * 500) * $kg;
        $days = $km < 60 ? [1, 2] : ($km < 400 ? [2, 3] : ($km < 1200 ? [3, 5] : [4, 7]));

        return [
            ['service' => 'REG', 'description' => 'Layanan reguler', 'cost' => $regular, 'etd' => "{$days[0]}-{$days[1]} hari"],
            ['service' => 'EXP', 'description' => 'Layanan kilat', 'cost' => (int) (round($regular * 1.6 / 500) * 500), 'etd' => max(1, $days[0] - 1) . '-' . max(1, $days[1] - 2) . ' hari'],
        ];
    }

    private function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $r = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $r * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    public function weightFor(int $quantity): int
    {
        return max(1, $quantity) * (int) config('miseku.default_item_weight', 200);
    }
}
