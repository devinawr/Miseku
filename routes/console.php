<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Proses otomatis Miseku (deadline pelunasan/pengiriman, PO tutup, event, voting).
// Jalankan scheduler: php artisan schedule:work
Schedule::command('miseku:tick')->everyFifteenMinutes()->withoutOverlapping();

// Memeriksa transaksi lama satu per satu langsung ke API Midtrans.
Artisan::command('miseku:check-settlement {paymentId}', function (string $paymentId) {
    $p = \App\Models\Payment::whereKey($paymentId)->where('payment_type', '!=', 'refund')->first();
    if (! $p || ! $p->midtrans_order_id || ! app(\App\Services\MidtransGateway::class)->isLive()) {
        $this->error('Pembayaran/Midtrans tidak tersedia. Jangan mengisi tanggal secara manual.'); return 1;
    }
    try {
        $payload = app(\App\Services\MidtransGateway::class)->status($p->midtrans_order_id);
        if (! $payload || ($payload['transaction_status'] ?? '') !== 'settlement') { $this->warn('Belum ada status settlement terverifikasi.'); return 1; }
        app(\App\Services\PaymentService::class)->handleMidtrans($payload);
        if (! $p->fresh()->midtrans_settled_at) { $this->error('Verifikasi status atau jumlah tidak berhasil.'); return 1; }
        $this->info('Settlement terverifikasi untuk payment #' . $p->id . '.'); return 0;
    } catch (\Throwable $e) { $this->error('Pemeriksaan gagal; coba lagi setelah koneksi tersedia.'); return 1; }
})->purpose('Verifikasi status settlement pembayaran lama ke Midtrans');

// Bersifat opt-in: biaya/rate limit tergantung paket penyedia. Scheduler wajib berjalan.
Artisan::command('miseku:track-deliveries', function () {
    if (! config('services.rajaongkir.tracking_enabled')) { $this->info('Tracking otomatis belum diaktifkan.'); return 0; }
    $checked = 0; $delivered = 0;
    \App\Models\CustomerOrder::where('order_status', 'shipped')->where('shipping_method', 'courier')
        ->whereNull('delivered_at')->whereNotNull('tracking_number')->orderBy('id')
        ->chunkById(100, function ($orders) use (&$checked, &$delivered) {
            foreach ($orders as $order) { $checked++; if (app(\App\Services\CourierTrackingService::class)->check($order)) $delivered++; }
        });
    $this->info("Diperiksa: {$checked}; tiba terverifikasi: {$delivered}."); return 0;
})->purpose('Periksa status kiriman melalui RajaOngkir');
Schedule::command('miseku:track-deliveries')->everySixHours()->withoutOverlapping();
