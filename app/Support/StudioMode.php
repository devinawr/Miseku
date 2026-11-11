<?php

namespace App\Support;

use App\Models\DesignPoll;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Mode kerja artis ("Studio") vs mode belanja (pembeli).
 *
 * - Semua halaman /studio/* = mode Studio.
 * - Halaman bersama (chat, notifikasi, akun) mengikuti mode terakhir: kalau dibuka dari Studio,
 *   tampilannya tetap Studio (header "Studio artis" + navigasi Studio, tanpa menu belanja).
 * - Pratinjau milik sendiri (detail produk sendiri, halaman toko sendiri, voting sendiri) juga tetap Studio.
 * - Halaman belanja lain (beranda, katalog, keranjang, pesanan saya, dll.) = kembali ke mode pembeli.
 * Mode disimpan di session('studio_mode') oleh middleware TrackStudioMode.
 */
class StudioMode
{
    /** Halaman bersama yang mengikuti mode terakhir. */
    public const SHARED = ['chat', 'notifications', 'account'];

    /** Halaman publik yang tetap Studio bila milik artis itu sendiri. */
    public const OWN_PREVIEW = ['product.detail', 'artist.shop', 'polls.show'];

    public static function eligible(?User $user): bool
    {
        return (bool) ($user && $user->isArtist() && $user->artistProfile);
    }

    /** Apakah tampilan halaman yang sedang dibuka memakai mode Studio. */
    public static function active(): bool
    {
        if (! self::eligible(auth()->user())) {
            return false;
        }
        if (CurrentRoute::is('studio.*')) {
            return true;
        }

        return (bool) session('studio_mode') && CurrentRoute::is(...self::SHARED, ...self::OWN_PREVIEW);
    }

    /** Dipanggil middleware pada setiap GET halaman penuh: tentukan mode berikutnya. */
    public static function track(Request $request, User $user): void
    {
        $route = $request->route();
        $name  = $route?->getName();
        if (! $name || str_starts_with($name, 'livewire.') || $name === 'logout') {
            return;
        }

        if (str_starts_with($name, 'studio.')) {
            $mode = true;
        } elseif (in_array($name, self::SHARED, true)) {
            $mode = (bool) session('studio_mode');
        } elseif (in_array($name, self::OWN_PREVIEW, true)) {
            $mode = (bool) session('studio_mode') && self::ownsPreview($name, $route, $user);
        } else {
            $mode = false;
        }

        session(['studio_mode' => $mode]);
    }

    private static function ownsPreview(string $name, $route, User $user): bool
    {
        $id = fn ($v) => is_object($v) ? $v->getKey() : $v;
        $profileId = $user->artistProfile->id;

        return match ($name) {
            'artist.shop'    => (int) $id($route->parameter('artist')) === $profileId,
            'product.detail' => (int) Product::whereKey($id($route->parameter('product')))->value('artist_profile_id') === $profileId,
            'polls.show'     => (int) DesignPoll::whereKey($id($route->parameter('poll')))->value('artist_profile_id') === $profileId,
            default          => false,
        };
    }
}
