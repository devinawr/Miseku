<?php

namespace App\Services;

use App\Models\ArtistProfile;
use App\Models\BalanceTransaction;
use App\Models\CustomerOrder;
use App\Models\Payment;
use App\Models\PlatformSetting;
use App\Models\Withdrawal;

/**
 * Alur dana (kamus data v3/v4):
 *   bayar sukses        → hold        (held_balance naik)
 *   pesanan selesai     → release     (held → available, dipotong admin fee)
 *   pelunasan timeout   → forfeiture  (held DP → available artis)
 *   batal sebelum selesai → refund    (held keluar ke pembeli)
 *   retur setelah selesai → refund    (available berkurang)
 *   penarikan           → withdrawal  (available berkurang; ditolak → dikembalikan)
 *
 * Setiap mutasi = 1 baris balance_transaction + update cache di artist_profile
 * dalam transaksi DB yang sama (pemanggil wajib membungkus dengan DB::transaction).
 */
class BalanceService
{
    public function hold(CustomerOrder $order, Payment $payment, float $amount): BalanceTransaction
    {
        $artist = $this->lock($order->artist_profile_id);
        $artist->held_balance = (float) $artist->held_balance + $amount;
        $artist->save();

        return $this->journal($artist, 'credit', 'hold', $amount, 0, $amount, "Dana {$payment->type_label} {$order->order_code} ditahan", $order, $payment);
    }

    /** Dana yang masih ditahan untuk pesanan ini. */
    public function heldFor(CustomerOrder $order): float
    {
        $rows = BalanceTransaction::where('customer_order_id', $order->id)->get();

        if ($rows->whereIn('category', ['release', 'forfeiture'])->isNotEmpty()) {
            return 0;
        }

        return max(0, (float) $rows->where('category', 'hold')->sum('amount') - (float) $rows->where('category', 'refund')->sum('amount'));
    }

    public function isReleased(CustomerOrder $order): bool
    {
        return BalanceTransaction::where('customer_order_id', $order->id)->whereIn('category', ['release', 'forfeiture'])->exists();
    }

    /** Pesanan selesai → dana tertahan pindah ke saldo tersedia dikurangi admin fee. */
    public function release(CustomerOrder $order): ?BalanceTransaction
    {
        $held = $this->heldFor($order);

        if ($held <= 0 || $this->isReleased($order)) {
            return null;
        }

        $fee = min($held, PlatformSetting::current()->feeFor((float) $order->product_subtotal));
        $net = $held - $fee;

        $artist = $this->lock($order->artist_profile_id);
        $artist->held_balance = max(0, (float) $artist->held_balance - $held);
        $artist->available_balance = (float) $artist->available_balance + $net;
        $artist->save();

        $order->forceFill(['admin_fee' => $fee])->save();

        return $this->journal($artist, 'credit', 'release', $held, $fee, $net, "Pesanan {$order->order_code} selesai — dana dilepas", $order);
    }

    /** Pelunasan lewat tenggat / no-show pickup → dana tertahan jadi milik artis. */
    public function forfeit(CustomerOrder $order, string $description): ?BalanceTransaction
    {
        $held = $this->heldFor($order);

        if ($held <= 0) {
            return null;
        }

        $artist = $this->lock($order->artist_profile_id);
        $artist->held_balance = max(0, (float) $artist->held_balance - $held);
        $artist->available_balance = (float) $artist->available_balance + $held;
        $artist->save();

        return $this->journal($artist, 'credit', 'forfeiture', $held, 0, $held, $description, $order);
    }

    /** Refund ke pembeli. Sebelum selesai diambil dari dana tertahan, sesudah selesai dari saldo tersedia. */
    public function refund(CustomerOrder $order, ?Payment $refundPayment, float $amount, string $description): BalanceTransaction
    {
        $artist = $this->lock($order->artist_profile_id);

        if ($this->isReleased($order)) {
            $artist->available_balance = (float) $artist->available_balance - $amount;
            $description .= ' (dari saldo tersedia)';
        } else {
            $artist->held_balance = max(0, (float) $artist->held_balance - $amount);
        }

        $artist->save();

        return $this->journal($artist, 'debit', 'refund', $amount, 0, $amount, $description, $order, $refundPayment);
    }

    public function withdrawalHold(Withdrawal $w): BalanceTransaction
    {
        $artist = $this->lock($w->artist_profile_id);
        $artist->available_balance = (float) $artist->available_balance - (float) $w->amount;
        $artist->save();

        return $this->journal($artist, 'debit', 'withdrawal', (float) $w->amount, 0, (float) $w->amount, 'Pengajuan penarikan dana #' . $w->id, null, null, $w);
    }

    public function withdrawalReturn(Withdrawal $w): BalanceTransaction
    {
        $artist = $this->lock($w->artist_profile_id);
        $artist->available_balance = (float) $artist->available_balance + (float) $w->amount;
        $artist->save();

        return $this->journal($artist, 'credit', 'withdrawal', (float) $w->amount, 0, (float) $w->amount, 'Penarikan #' . $w->id . ' ditolak — saldo dikembalikan', null, null, $w);
    }

    private function lock(int $artistProfileId): ArtistProfile
    {
        return ArtistProfile::whereKey($artistProfileId)->lockForUpdate()->firstOrFail();
    }

    private function journal(ArtistProfile $artist, string $type, string $category, float $gross, float $fee, float $amount, string $desc, ?CustomerOrder $order = null, ?Payment $payment = null, ?Withdrawal $w = null): BalanceTransaction
    {
        return BalanceTransaction::create([
            'artist_profile_id' => $artist->id,
            'customer_order_id' => $order?->id,
            'payment_id'        => $payment?->id,
            'withdrawal_id'     => $w?->id,
            'mutation_type'     => $type,
            'category'          => $category,
            'gross_amount'      => round($gross, 2),
            'fee_amount'        => round($fee, 2),
            'amount'            => round($amount, 2),
            'description'       => mb_substr($desc, 0, 255),
        ]);
    }
}
