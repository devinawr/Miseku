<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Akun admin hanya bekerja sebagai admin: tidak ada beranda / belanja / keranjang.
 * Halaman pembeli (beranda, katalog, keranjang, pesanan saya, custom, voting, dll.)
 * diarahkan ke Panel admin. Yang tetap boleh: semua /admin, akun (profil & sandi),
 * chat, notifikasi, serta halaman yang dibuka dari panel untuk moderasi
 * (detail produk, halaman toko, detail pesanan, topik komunitas).
 */
class KeepAdminInPanel
{
    private const ALLOWED = [
        'admin.*', 'account', 'chat', 'notifications', 'logout',
        'product.detail', 'artist.shop', 'orders.show', 'community.topic',
        'livewire.*', 'midtrans.*',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->role === 'admin' && $request->isMethod('GET') && ! $request->routeIs(...self::ALLOWED)) {
            return redirect()->route('admin.dashboard');
        }

        return $next($request);
    }
}
