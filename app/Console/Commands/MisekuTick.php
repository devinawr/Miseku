<?php

namespace App\Console\Commands;

use App\Services\AutomationService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Menjalankan semua proses otomatis Miseku (timer BPMN):
 * tagihan kedaluwarsa, PO tutup otomatis, pengingat & timeout pelunasan,
 * pengingat & timeout pengiriman, event berakhir (pick-up), voting berakhir.
 *
 * Dijadwalkan tiap 15 menit lewat routes/console.php (php artisan schedule:work).
 * Untuk simulasi waktu: php artisan miseku:tick --now="2026-08-01 10:00"
 */
class MisekuTick extends Command
{
    protected $signature = 'miseku:tick {--now= : Simulasikan waktu sekarang (Y-m-d H:i)}';

    protected $description = 'Jalankan proses otomatis Miseku (deadline, pengingat, PO, event, voting)';

    public function handle(AutomationService $automation): int
    {
        if ($now = $this->option('now')) {
            Carbon::setTestNow(Carbon::parse($now));
            $this->comment('Waktu simulasi: ' . now()->format('d M Y H:i'));
        }

        $result = $automation->run();

        foreach ($result as $key => $count) {
            $this->line(str_pad((string) $key, 28) . ': ' . (is_scalar($count) ? $count : json_encode($count)));
        }

        $this->info('Selesai.');

        return self::SUCCESS;
    }
}