<?php

namespace App\Console\Commands;

use App\Models\ArtistProfile;
use App\Services\WithdrawalEligibilityService;
use Illuminate\Console\Command;

/** Periksa contoh lokal tanpa mengubah .env atau data database. */
class PeriksaSaldoDemo extends Command
{
    protected $signature = 'miseku:saldo-demo {--aktifkan : Tidak diperlukan lagi; hanya periksa}';
    protected $description = 'Periksa saldo contoh di database demo lokal';


    public function handle(): int
    {
        $this->line('=== Pemeriksaan saldo DEMO Miseku ===');
        $this->line('Jam aplikasi (WIB): '.now()->setTimezone('Asia/Jakarta')->format('d-m-Y H:i:s'));
        $this->line('Lingkungan: '.app()->environment());
        $this->line('Database aplikasi: '.(string) config('database.connections.mysql.database'));
        $this->line('Kunci Midtrans terisi: '.((filled(config('services.midtrans.server_key')) || filled(config('services.midtrans.client_key'))) ? 'ya' : 'tidak'));
        $this->line('Kalender: '.(config('miseku.settlement_calendar_from') ?: '(kosong)').' sampai '.(config('miseku.settlement_calendar_through') ?: '(kosong)'));
        $service = app(WithdrawalEligibilityService::class);
        if (! method_exists($service, 'isDemoSimulation')) {
            $this->error('Patch saldo 3 hari sebelumnya belum lengkap. Pasang patch itu terlebih dahulu.');
            return self::FAILURE;
        }
        $this->line('Mode demo AKTIF: '.($service->isDemoSimulation() ? 'ya' : 'tidak'));
        if ($this->option('aktifkan')) $this->line('Opsi --aktifkan tidak diperlukan lagi; tidak ada file yang diubah.');
        if (! $service->isDemoSimulation()) {
            $this->warn('Simulasi hanya untuk DB demo, MySQL lokal dan kunci Sandbox atau kosong, bukan produksi. Jangan kirim isi .env atau nilai kunci.');
            return self::FAILURE;
        }
        try {
            $shop = ArtistProfile::where('shop_name', 'Kirara Studio')->first();
            if (! $shop) { $this->error('Kirara Studio tidak ditemukan pada database yang aktif.'); return self::FAILURE; }
            $summary = $service->summary($shop);
            $this->line('Kirara siap diajukan: Rp'.number_format($summary['eligible'], 0, ',', '.'));
            $this->line('Kirara menunggu 3 hari kerja: Rp'.number_format($summary['waiting_settlement'], 0, ',', '.'));
            $this->line('Kirara belum siap lainnya: Rp'.number_format($summary['waiting_other'], 0, ',', '.'));
            $sample = \App\Models\Payment::where('id', 104)->where('midtrans_transaction_id', 'SIM-E4FYB5GXT7')->first();
            if ($sample && $sample->paid_at) {
                $this->line('Contoh pembayaran SIM dibayar: '.$sample->paid_at->copy()->setTimezone('Asia/Jakarta')->format('d-m-Y H:i').' WIB');
                $this->line('Contoh tersebut siap sekitar: '.$service->readyAt($sample->paid_at)->format('d-m-Y H:i').' WIB');
            }
            if ($summary['next_at']) $this->line('Berikutnya siap sekitar: '.$summary['next_at']->setTimezone('Asia/Jakarta')->format('d-m-Y H:i').' WIB');
            if ($summary['calendar_missing']) $this->warn('Kalender belum mencakup tanggal pembayaran sampai tanggal siap.');
            $this->line('Jika menunggu Rp0 dan saldo sudah siap, waktu contoh mungkin sudah lewat.');
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Data demo tidak dapat dibaca. Periksa database dan struktur SQL demo.');
            return self::FAILURE;
        }
    }

}
