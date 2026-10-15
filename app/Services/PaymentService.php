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
    public function markSuccess(Payment $payment, ?string $method, ?string $transactionId = null, ?Carbon $paidAt = null): Payment
    {
        return DB::transaction(function () use ($payment, $method, $transactionId, $paidAt) {
            $payment = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($payment->payment_status === 'success' || $payment->payment_type === 'refund') {
                return $payment;
            }

            $order = CustomerOrder::whereKey($payment->customer_order_id)->lockForUpdate()->firstOrFail();

            // Pesanan sudah dibatalkan/gagal (mis. kedaluwarsa duluan) → uang dicatat lalu langsung direfund.
            $payment->update([
                'payment_status'          => 'success',
                'payment_method'          => $method,
                'midtrans_transaction_id' => $transactionId,
                'paid_at'                 => $paidAt ?? now(),
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
            return $this->markSuccess($payment, $method, $payload['transaction_id'] ?? null,
                isset($payload['settlement_time']) ? Carbon::parse($payload['settlement_time']) : null);
        }

        if (in_array($status, ['failed', 'expired'], true)) {
            $this->markFailed($payment, $status);
        }

        return $payment->fresh();
    }
}
