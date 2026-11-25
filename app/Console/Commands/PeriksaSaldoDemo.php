<?php

namespace App\Console\Commands;

use App\Models\ArtistProfile;
use App\Services\WithdrawalEligibilityService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/** Setup khusus contoh lokal: tidak menulis DB atau membuat settlement Midtrans. */
class PeriksaSaldoDemo extends Command
{
    protected $signature = 'miseku:saldo-demo {--aktifkan : Atur .env lokal untuk database demo yang sudah diimpor}';
    protected $description = 'Periksa atau aktifkan pengaturan saldo contoh di database demo lokal';

    private const DEMO_DB = 'miseku_demo_25nov2026';
    private const HOLIDAYS = '2026-01-01,2026-01-16,2026-02-16,2026-02-17,2026-03-18,2026-03-19,2026-03-20,2026-03-23,2026-03-24,2026-04-03,2026-05-01,2026-05-14,2026-05-15,2026-05-27,2026-05-28,2026-06-01,2026-06-16,2026-08-17,2026-08-25,2026-12-24,2026-12-25';

    public function handle(): int
    {
        $this->line('=== Pemeriksaan saldo DEMO Miseku ===');
        $this->line('Jam aplikasi (WIB): '.now()->setTimezone('Asia/Jakarta')->format('d-m-Y H:i:s'));
        $this->line('Lingkungan: '.app()->environment());
        $this->line('Database aplikasi: '.(string) config('database.connections.mysql.database'));
        $this->line('Mode demo disetel: '.(config('miseku.demo_settlement') ? 'ya' : 'tidak'));
        $this->line('Kunci Midtrans terisi: '.((filled(config('services.midtrans.server_key')) || filled(config('services.midtrans.client_key'))) ? 'ya' : 'tidak'));
        $this->line('Kalender: '.(config('miseku.settlement_calendar_from') ?: '(kosong)').' sampai '.(config('miseku.settlement_calendar_through') ?: '(kosong)'));
        $service = app(WithdrawalEligibilityService::class);
        if (! method_exists($service, 'isDemoSimulation')) {
            $this->error('Patch saldo 3 hari sebelumnya belum lengkap. Pasang patch itu terlebih dahulu.');
            return self::FAILURE;
        }
        $this->line('Mode demo AKTIF: '.($service->isDemoSimulation() ? 'ya' : 'tidak'));
        if ($this->option('aktifkan')) return $this->activate();
        if (! $service->isDemoSimulation()) {
            $this->warn('Mode belum aktif: periksa petunjuk di paket. Jangan kirim isi .env atau nilai kunci ke siapa pun.');
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

    private function activate(): int
    {
        if (app()->configurationIsCached()) {
            $this->error('Batal: konfigurasi masih di-cache. Jalankan php artisan optimize:clear lebih dahulu.'); return self::FAILURE;
        }
        $mysql = config('database.connections.mysql');
        $host = (string) ($mysql['host'] ?? '');
        if (! app()->environment('local') || ! in_array($host, ['127.0.0.1', 'localhost', '::1'], true) || filled($mysql['url'] ?? null)) {
            $this->error('Batal: hanya untuk APP_ENV=local, MySQL lokal dan tanpa DB_URL.'); return self::FAILURE;
        }
        if (config('services.midtrans.is_production') || filled(config('services.midtrans.server_key')) || filled(config('services.midtrans.client_key'))) {
            $this->error('Batal: kunci Midtrans aktif atau mode produksi. Gunakan salinan aplikasi lokal tanpa kunci Midtrans.'); return self::FAILURE;
        }
        // Sambungkan hanya ke DB contoh, lalu pastikan database yang benar dibuka.
        try {
            $mysql['database'] = self::DEMO_DB;
            config(['database.connections.saldo_demo_inspect' => $mysql]);
            $db = DB::connection('saldo_demo_inspect');
            if ($db->getDatabaseName() !== self::DEMO_DB || $db->selectOne('SELECT DATABASE() AS db')->db !== self::DEMO_DB) throw new \RuntimeException();
            $hasUser = $db->table('user')->where('email', 'kirara@miseku.test')->where('role', 'artist')->exists();
            $hasPayment = $db->table('payment')->where('id', 104)->where('midtrans_transaction_id', 'SIM-E4FYB5GXT7')->exists();
            $hasShop = $db->table('artist_profile')->where('shop_name', 'Kirara Studio')->exists();
            if (! $hasUser || ! $hasPayment || ! $hasShop) throw new \RuntimeException();
        } catch (\Throwable $e) {
            $this->error('Batal: database miseku_demo_25nov2026 belum ada, tidak dapat diakses, atau bukan data contoh yang sesuai.');
            $this->error('Impor SQL demo lengkap hanya jika database itu belum ada; jangan impor ulang database berisi data.');
            return self::FAILURE;
        }
        $path = base_path('.env');
        if (! is_file($path) || ! is_readable($path) || ! is_writable($path)) {
            $this->error('Batal: file .env tidak ditemukan atau tidak dapat diubah.'); return self::FAILURE;
        }
        $text = file_get_contents($path);
        $values = ['APP_ENV'=>'local', 'DB_CONNECTION'=>'mysql', 'DB_DATABASE'=>self::DEMO_DB,
            'MISEKU_DEMO_SETTLEMENT'=>'true', 'MISEKU_SETTLEMENT_CALENDAR_FROM'=>'2026-01-01',
            'MISEKU_SETTLEMENT_CALENDAR_THROUGH'=>'2026-12-31', 'MISEKU_SETTLEMENT_HOLIDAYS'=>self::HOLIDAYS];
        // Tolak duplikat, supaya tidak salah mengedit konfigurasi ambigu.
        foreach (array_keys($values) as $key) {
            if (preg_match_all('/^[ \t]*'.preg_quote($key, '/').'[ \t]*=/m', $text) > 1) {
                $this->error("Batal: $key ada lebih dari satu baris di .env. Rapikan dulu."); return self::FAILURE;
            }
        }
        $backup = $path.'.sebelum-demo-'.date('YmdHis').'-'.bin2hex(random_bytes(3));
        if (! copy($path, $backup)) { $this->error('Batal: backup .env tidak dapat dibuat.'); return self::FAILURE; }
        foreach ($values as $key => $value) {
            $pattern = '/^[ \t]*'.preg_quote($key, '/').'[ \t]*=[^\r\n]*$/m';
            if (preg_match($pattern, $text)) $text = preg_replace_callback($pattern, fn () => "$key=$value", $text, 1);
            else $text = rtrim($text, "\r\n").PHP_EOL."$key=$value".PHP_EOL;
        }
        if (file_put_contents($path, $text, LOCK_EX) === false) {
            $this->error('Gagal menulis .env; gunakan backup untuk memulihkan.'); return self::FAILURE;
        }
        Artisan::call('config:clear');
        $this->info('Pengaturan demo lokal disimpan; backup .env telah dibuat di folder proyek.');
        $this->line('Tidak ada data SQL diubah. Jalankan ulang perintah tanpa --aktifkan, lalu restart php artisan serve.');
        $this->warn('Simpan backup .env dengan aman; jangan unggah atau kirim isinya.');
        return self::SUCCESS;
    }
}
