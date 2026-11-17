<?php

namespace App\Http\Middleware;

use App\Services\AutomationService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;
use Symfony\Component\HttpFoundation\Response;

/**
 * Proses otomatis Miseku (PO lewat tanggal tutup, voting berakhir, tagihan kedaluwarsa,
 * tenggat pelunasan/pengiriman, event berakhir) dijalankan juga saat halaman dibuka —
 * maksimal 1x per menit — supaya tetap berjalan walau `php artisan schedule:work` tidak dinyalakan
 * (mis. saat demo di XAMPP). Scheduler tetap bisa dipakai; prosesnya idempoten (yang sudah diproses dilewati).
 * Matikan dengan MISEKU_AUTO_TICK=false di .env.
 */
class RunAutomationOnRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('miseku.auto_tick_on_request', true)
            && $request->isMethod('GET')
            && ! Livewire::isLivewireRequest()
            && ! $request->is('midtrans/*', 'up', 'build/*', 'uploads/*')
            && Cache::add('miseku:auto-tick', now()->timestamp, 60)) {
            try {
                app(AutomationService::class)->run();
            } catch (\Throwable $e) {
                // jangan sampai halaman gagal dibuka karena proses otomatis
                Log::warning('Proses otomatis Miseku gagal: ' . $e->getMessage());
            }
        }

        return $next($request);
    }
}
