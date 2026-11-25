<?php
namespace App\Services;
use App\Models\ArtistProfile;
use App\Models\BalanceTransaction;
use App\Models\Payment;
use App\Models\Withdrawal;
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
    public function readyAt($settledAt): CarbonImmutable
    {
        $date = CarbonImmutable::parse($settledAt)->setTimezone('Asia/Jakarta');
        $holidays = config('miseku.settlement_holidays', []);
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
        $payments = Payment::whereIn('customer_order_id', $ids)->where('payment_type', '!=', 'refund')->whereIn('payment_status', ['success', 'refunded'])->get()->groupBy('customer_order_id');
        $mature = 0; $immature = 0; $unverified = 0; $withdrawn = 0; $refunds = 0; $returned = 0; $missing = false; $calendarMissing = false; $nextAt = null;
        foreach ($txs as $tx) {
            $amount = $this->cents($tx->amount);
            if ($tx->mutation_type === 'credit' && in_array($tx->category, ['release', 'forfeiture'], true)) {
                $rows = $payments->get($tx->customer_order_id, collect());
                if (! $tx->customer_order_id || $rows->isEmpty() || $rows->contains(fn ($p) => ! $p->midtrans_settled_at)) { $missing = true; $unverified += $amount; continue; }
                $ready = $rows->map(fn ($p) => $this->readyAt($p->midtrans_settled_at))->max();
                $earliest = $rows->map(fn ($p) => CarbonImmutable::parse($p->midtrans_settled_at)->setTimezone('Asia/Jakarta')->toDateString())->min();
                if (! $this->calendarCovers($earliest, $ready->toDateString())) { $calendarMissing = true; $unverified += $amount; continue; }
                if (now()->gte($ready)) $mature += $amount;
                else {
                    $immature += $amount;
                    if ($nextAt === null || $ready->lt($nextAt)) $nextAt = $ready;
                }
            } elseif ($tx->mutation_type === 'debit' && $tx->category === 'withdrawal') $withdrawn += $amount;
            elseif ($tx->mutation_type === 'debit' && $tx->category === 'refund') $refunds += $amount;
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
