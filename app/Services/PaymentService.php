<?php

namespace App\Services;

use App\Models\CustomerOrder;
use App\Models\Payment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Tagihan & pencatatan pembayaran (BPMN 1 & 2).
 * midtrans_order_id unik per tagihan: ORD-xxx-DP / ORD-xxx-LUNAS / ORD-xxx-FULL
 * (tagihan ulang setelah kedaluwarsa diberi akhiran -2, -3, ...).
 */
class PaymentService
{
    public function __construct(
        private BalanceService $balance,
        private MidtransGateway $midtrans,
    ) {}

    public function createInvoice(CustomerOrder $order, string $type, float $amount): Payment
    {
        $suffix = ['dp' => 'DP', 'settlement' => 'LUNAS', 'full' => 'FULL'][$type];
        $base = $order->order_code . '-' . $suffix;
        $tries = Payment::where('customer_order_id', $order->id)->where('payment_type', $type)->count();
        $midtransId = $tries === 0 ? $base : $base . '-' . ($tries + 1);

        return Payment::create([
            'customer_order_id' => $order->id,
            'payment_type'      => $type,
            'amount'            => round($amount, 2),
            'midtrans_order_id' => $midtransId,
            'payment_status'    => 'pending',
        ]);
    }

    /** Terbitkan ulang tagihan yang kedaluwarsa (mis. pelunasan yang belum lewat tenggat). */
    public function reissue(Payment $old): Payment
    {
        return $this->createInvoice($old->order, $old->payment_type, (float) $old->amount);
    }

    /** Webhook / simulasi → pembayaran berhasil. Idempoten. */
    public function markSuccess(Payment $payment, ?string $method, ?string $transactionId = null, ?Carbon $paidAt = null, ?Carbon $settledAt = null): Payment
    {
        return DB::transaction(function () use ($payment, $method, $transactionId, $paidAt, $settledAt) {
            $payment = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($payment->payment_type === 'refund') return $payment;
            // SIM hanya untuk mode simulasi, bukan bukti settlement provider.
            if (! $settledAt && str_starts_with((string) $transactionId, 'SIM-') && ! $this->midtrans->isLive()) $settledAt = now();
            if (in_array($payment->payment_status, ['success', 'refunded'], true)) {
                if ($settledAt && ! $payment->midtrans_settled_at) $payment->update(['midtrans_settled_at' => $settledAt]);
                return $payment;
            }

            $order = CustomerOrder::whereKey($payment->customer_order_id)->lockForUpdate()->firstOrFail();

            // Pesanan sudah dibatalkan/gagal (mis. kedaluwarsa duluan) → uang dicatat lalu langsung direfund.
            $payment->update([
                'payment_status'          => 'success',
                'payment_method'          => $method,
                'midtrans_transaction_id' => $transactionId,
                'paid_at'                 => $paidAt ?? now(),
                'midtrans_settled_at'     => $settledAt,
            ]);

            $this->balance->hold($order, $payment, (float) $payment->amount);

            if (in_array($order->order_status, ['cancelled', 'failed'], true)) {
                app(RefundService::class)->refundOrder($order->fresh('payments'), 'Pembayaran masuk setelah pesanan dibatalkan');

                return $payment;
            }

            app(OrderService::class)->afterPayment($order, $payment);

            return $payment->fresh();
        });
    }

    /** Pembayaran gagal / kedaluwarsa. Tagihan pertama yang gagal → pesanan dibatalkan & stok kembali. */
    public function markFailed(Payment $payment, string $status = 'expired'): void
    {
        DB::transaction(function () use ($payment, $status) {
            $payment = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($payment->payment_status !== 'pending') {
                return;
            }

            $payment->update(['payment_status' => $status]);
            $order = $payment->order;

            if (in_array($payment->payment_type, ['dp', 'full'], true) && $order->order_status === 'awaiting_payment') {
                app(OrderService::class)->cancelUnpaid($order, $status === 'expired'
                    ? 'Tagihan tidak dibayar sampai batas waktu.'
                    : 'Pembayaran gagal/dibatalkan.');
            }
        });
    }

    /** Proses notifikasi Midtrans (webhook atau hasil cek status). */
    public function handleMidtrans(array $payload): ?Payment
    {
        $payment = Payment::where('midtrans_order_id', $payload['order_id'] ?? '')->first();

        if (! $payment) {
            return null;
        }

        [$status, $method] = $this->midtrans->interpret($payload);

        if ($status === 'success') {
            // Jangan memproses pembayaran bila verifikasi API gagal atau data berbeda.
            try {
                if (! $this->midtrans->isLive()) return $payment->fresh();
                $verified = $this->midtrans->status($payment->midtrans_order_id);
                if (! is_array($verified) || ($verified['order_id'] ?? '') !== $payment->midtrans_order_id
                    || ! isset($verified['gross_amount']) || ! is_numeric($verified['gross_amount'])
                    || abs((float) $verified['gross_amount'] - (float) $payment->amount) >= 0.005) return $payment->fresh();
                [$verifiedStatus, $verifiedMethod] = $this->midtrans->interpret($verified);
                if ($verifiedStatus !== 'success') return $payment->fresh();
                $settledAt = ($verified['transaction_status'] ?? '') === 'settlement'
                    ? (! empty($verified['settlement_time']) ? Carbon::parse($verified['settlement_time'], 'Asia/Jakarta')->utc() : now()) : null;
                return $this->markSuccess($payment, $verifiedMethod, $verified['transaction_id'] ?? null, $settledAt, $settledAt);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Verifikasi status Midtrans gagal', ['payment_id' => $payment->id]);
                return $payment->fresh();
            }
        }

        if (in_array($status, ['failed', 'expired'], true)) {
            $this->markFailed($payment, $status);
        }

        return $payment->fresh();
    }
}
