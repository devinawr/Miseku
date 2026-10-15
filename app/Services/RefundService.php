<?php

namespace App\Services;

use App\Models\CustomerOrder;
use App\Models\Payment;
use App\Support\Money;
use Illuminate\Support\Facades\DB;

/**
 * Refund dana yang sudah dibayar (BPMN 3 PO gagal, BPMN 5 batal/retur, BPMN 2 timeout kirim).
 *
 * Tiap pembayaran sukses direfund dengan 1 baris payment bertipe "refund":
 *  - Mode simulasi        → refund_method midtrans_api, langsung success.
 *  - Mode Midtrans (live) → coba API refund (QRIS/GoPay/ShopeePay). VA tidak bisa
 *    direfund lewat API → refund_method manual_transfer, status pending sampai admin
 *    mengunggah bukti transfer (menu Admin → Refund manual).
 * Saldo artis disesuaikan saat itu juga (dana tertahan / tersedia berkurang).
 */
class RefundService
{
    public function __construct(
        private BalanceService $balance,
        private MidtransGateway $midtrans,
        private NotificationService $notify,
    ) {}

    /** @return array{total: float, manual: bool} */
    public function refundOrder(CustomerOrder $order, string $reason): array
    {
        return DB::transaction(function () use ($order, $reason) {
            $order->load('payments');
            $total = 0.0;
            $manual = false;

            // tagihan yang masih menunggu ditutup
            $order->payments()->where('payment_status', 'pending')->where('payment_type', '!=', 'refund')->update(['payment_status' => 'failed', 'updated_at' => now()]);

            $alreadyRefunded = (float) $order->payments->where('payment_type', 'refund')->whereIn('payment_status', ['success', 'pending'])->sum('amount');

            foreach ($order->payments->where('payment_type', '!=', 'refund')->where('payment_status', 'success') as $paid) {
                $amount = (float) $paid->amount;

                // jangan merefund dua kali bila sebagian sudah pernah direfund
                if ($alreadyRefunded > 0) {
                    $skip = min($alreadyRefunded, $amount);
                    $alreadyRefunded -= $skip;
                    $amount -= $skip;
                }

                if ($amount <= 0) {
                    continue;
                }

                [$method, $status] = $this->executeRefund($paid, $amount, $reason);
                $manual = $manual || $method === 'manual_transfer';

                $refund = Payment::create([
                    'customer_order_id' => $order->id,
                    'payment_type'      => 'refund',
                    'amount'            => $amount,
                    'payment_method'    => $paid->payment_method,
                    'refund_method'     => $method,
                    'payment_status'    => $status,
                    'paid_at'           => $status === 'success' ? now() : null,
                ]);

                if ($status === 'success') {
                    $paid->update(['payment_status' => 'refunded']);
                }

                $this->balance->refund($order, $refund, $amount, "Refund {$order->order_code} — {$reason}");
                $total += $amount;
            }

            if ($total > 0) {
                $this->notify->send($order->buyer_id, 'payment', 'Dana dikembalikan', "Refund " . Money::rp($total) . " untuk {$order->order_code} ({$reason}). "
                    . ($manual ? 'Refund dikirim manual oleh admin ke rekening/e-wallet-mu — admin akan menghubungimu.' : 'Dana kembali ke metode pembayaran semula.'), $order);
            }

            return ['total' => $total, 'manual' => $manual];
        });
    }

    /** @return array{0: string, 1: string} [refund_method, payment_status] */
    private function executeRefund(Payment $paid, float $amount, string $reason): array
    {
        if (! $this->midtrans->isLive() || str_starts_with((string) $paid->midtrans_transaction_id, 'SIM-')) {
            return ['midtrans_api', 'success']; // simulasi
        }

        $refundable = in_array($paid->payment_method, ['QRIS', 'GoPay', 'ShopeePay', 'Kartu kredit'], true);

        if ($refundable && $this->midtrans->refund($paid, $amount, $reason)) {
            return ['midtrans_api', 'success'];
        }

        return ['manual_transfer', 'pending'];
    }

    /** Admin menyelesaikan refund manual + unggah bukti transfer. */
    public function completeManual(Payment $refund, string $proofUrl): void
    {
        DB::transaction(function () use ($refund, $proofUrl) {
            $refund->update(['payment_status' => 'success', 'refund_proof' => $proofUrl, 'paid_at' => now()]);
            $order = $refund->order()->with('payments')->first();

            // tandai pembayaran asal sebagai refunded bila seluruh dana sudah kembali
            if ($order->payments->where('payment_type', 'refund')->where('payment_status', 'pending')->isEmpty()) {
                $order->payments()->where('payment_type', '!=', 'refund')->where('payment_status', 'success')->update(['payment_status' => 'refunded', 'updated_at' => now()]);
                $order->returns()->where('return_status', 'approved')->update(['return_status' => 'completed', 'updated_at' => now()]);
            }

            $this->notify->send($order->buyer_id, 'payment', 'Refund manual sudah ditransfer', 'Admin sudah mentransfer refund ' . Money::rp($refund->amount) . " untuk {$order->order_code}. Bukti transfer bisa dilihat di detail pesanan.", $order);
        });
    }
}
