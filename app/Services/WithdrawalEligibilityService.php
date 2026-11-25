<?php
namespace App\Services;
use App\Models\ArtistProfile;
use App\Models\BalanceTransaction;
use App\Models\Payment;
use App\Models\Withdrawal;
use App\Services\MidtransGateway;
use Carbon\CarbonImmutable;

/** Perhitungan konservatif jurnal Miseku, bukan saldo provider. */
class WithdrawalEligibilityService
{
    private function cents($value): int
    {
        $value = (string) $value; $negative = str_starts_with($value, '-'); $value = ltrim($value, '+-');
        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');
        $amount = (int) $whole * 100 + (int) substr(str_pad($fraction, 2, '0'), 0, 2);
        return $negative ? -$amount : $amount;
    }
    /** Simulasi hanya untuk database demo lokal, tidak pernah aktif dengan key Midtrans. */
    public function isDemoSimulation(): bool
    {
        $db = (string) config('database.connections.mysql.database');
        $isDemoDb = $db === 'miseku_demo_25nov2026'
            || (app()->environment('testing') && str_ends_with($db, '_test'));
        return (bool) config('miseku.demo_settlement') && app()->environment(['local', 'testing'])
            && config('database.default') === 'mysql' && $isDemoDb
            && ! app(MidtransGateway::class)->isLive() && ! filled(config('services.midtrans.client_key'))
            && ! config('services.midtrans.is_production');
    }

    /** Bayar simulasi hanya memakai waktu contoh dalam mode demo, bukan bukti Midtrans. */
    private function settlementAt(Payment $payment): ?CarbonImmutable
    {
        if (str_starts_with((string) $payment->midtrans_transaction_id, 'SIM-')) {
            return $this->isDemoSimulation() && $payment->paid_at ? CarbonImmutable::parse($payment->paid_at) : null;
        }
        return $payment->midtrans_settled_at ? CarbonImmutable::parse($payment->midtrans_settled_at) : null;
    }

    private function holidayDates(): array
    {
        return config('miseku.settlement_holidays', []);
    }

    public function readyAt($settledAt): CarbonImmutable
    {
        $date = CarbonImmutable::parse($settledAt)->setTimezone('Asia/Jakarta');
        $holidays = $this->holidayDates();
        for ($days = 0; $days < (int) config('miseku.withdrawal_settlement_business_days', 3);) {
            $date = $date->addDay();
            if (! $date->isWeekend() && ! in_array($date->toDateString(), $holidays, true)) $days++;
        }
        return $date;
    }
    private function calendarCovers(string $from, string $through): bool
    {
        $start = config('miseku.settlement_calendar_from');
        $end = config('miseku.settlement_calendar_through');
        foreach ([$start, $end] as $date) {
            if (! is_string($date) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) return false;
            [$year, $month, $day] = array_map('intval', explode('-', $date));
            if (! checkdate($month, $day, $year)) return false;
        }
        return $start <= $from && $end >= $through;
    }
    /** Debit dialokasikan ke saldo matang terlebih dahulu, sengaja konservatif. */
    public function summary(ArtistProfile $artist): array
    {
        $txs = BalanceTransaction::where('artist_profile_id', $artist->id)->orderBy('id')->get();
        $ids = $txs->where('mutation_type', 'credit')->whereIn('category', ['release', 'forfeiture'])->pluck('customer_order_id')->filter()->unique()->values();
        // Refund sebelum release mengurangi dana tertahan, bukan saldo tersedia.
        // Urutan ID jurnal menunjukkan apakah refund terjadi sebelum/sesudah release.
        $releaseIds = $txs->where('mutation_type', 'credit')->whereIn('category', ['release', 'forfeiture'])
            ->whereNotNull('customer_order_id')->groupBy('customer_order_id')->map(fn ($rows) => (int) $rows->min('id'));
        $payments = Payment::whereIn('customer_order_id', $ids)->where('payment_type', '!=', 'refund')->whereIn('payment_status', ['success', 'refunded'])->get()->groupBy('customer_order_id');
        $mature = 0; $immature = 0; $unverified = 0; $withdrawn = 0; $refunds = 0; $returned = 0; $missing = false; $calendarMissing = false; $nextAt = null;
        foreach ($txs as $tx) {
            $amount = $this->cents($tx->amount);
            if ($tx->mutation_type === 'credit' && in_array($tx->category, ['release', 'forfeiture'], true)) {
                $rows = $payments->get($tx->customer_order_id, collect());
                $settlements = $rows->map(fn (Payment $p) => $this->settlementAt($p));
                if (! $tx->customer_order_id || $rows->isEmpty() || $settlements->contains(fn ($value) => ! $value)) { $missing = true; $unverified += $amount; continue; }
                $ready = $settlements->map(fn ($date) => $this->readyAt($date))->max();
                $earliest = $settlements->map(fn ($date) => $date->setTimezone('Asia/Jakarta')->toDateString())->min();
                if (! $this->calendarCovers($earliest, $ready->toDateString())) { $calendarMissing = true; $unverified += $amount; continue; }
                if (now()->gte($ready)) $mature += $amount;
                else {
                    $immature += $amount;
                    if ($nextAt === null || $ready->lt($nextAt)) $nextAt = $ready;
                }
            } elseif ($tx->mutation_type === 'debit' && $tx->category === 'withdrawal') $withdrawn += $amount;
            elseif ($tx->mutation_type === 'debit' && $tx->category === 'refund') {
                $releaseId = $tx->customer_order_id ? $releaseIds->get($tx->customer_order_id) : null;
                if (! $tx->customer_order_id || ($releaseId && (int) $tx->id > $releaseId)) $refunds += $amount;
            }
            elseif ($tx->mutation_type === 'credit' && $tx->category === 'withdrawal') $returned += $amount;
        }
        $available = max(0, $this->cents($artist->available_balance));
        $netMature = max(0, $mature - $refunds - max(0, $withdrawn - $returned));
        $eligible = min($available, $netMature);
        $waiting = $available - $eligible;
        $accounted = max(0, $mature + $immature + $unverified - $refunds - max(0, $withdrawn - $returned));
        $uncertainPotential = $unverified + max(0, $available - $accounted);
        // Alokasi belum diverifikasi lebih dulu: bagian 3 hari adalah batas bawah konservatif.
        $waitingSettlement = min($immature, max(0, $waiting - $uncertainPotential));
        $waitingOther = $waiting - $waitingSettlement;
        if ($waitingOther > 0 && ! $calendarMissing) $missing = true;
        $pending = Withdrawal::where('artist_profile_id', $artist->id)->whereIn('withdrawal_status', ['submitted', 'processing'])->sum('amount');
        return ['eligible' => $eligible / 100, 'waiting' => $waiting / 100, 'waiting_settlement' => $waitingSettlement / 100,
            'waiting_other' => $waitingOther / 100, 'pending_withdrawal' => (float) $pending,
            'next_at' => $nextAt, 'missing' => $missing, 'calendar_missing' => $calendarMissing];
    }
}
