<?php

namespace App\Services;

use App\Models\CustomerOrder;
use App\Models\Payment;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Integrasi Midtrans Snap (tanpa SDK — cukup HTTP client Laravel).
 * Mode LIVE aktif bila MIDTRANS_SERVER_KEY diisi (sandbox: SB-Mid-server-...).
 * Bila kosong, aplikasi memakai halaman pembayaran simulasi (lihat PaymentPage).
 */
class MidtransGateway
{
    public function isLive(): bool
    {
        return filled(config('services.midtrans.server_key'));
    }

    public function clientKey(): ?string
    {
        return config('services.midtrans.client_key');
    }

    public function snapJsUrl(): string
    {
        return config('services.midtrans.is_production')
            ? 'https://app.midtrans.com/snap/snap.js'
            : 'https://app.sandbox.midtrans.com/snap/snap.js';
    }

    private function snapUrl(): string
    {
        return config('services.midtrans.is_production')
            ? 'https://app.midtrans.com/snap/v1/transactions'
            : 'https://app.sandbox.midtrans.com/snap/v1/transactions';
    }

    private function apiUrl(): string
    {
        return config('services.midtrans.is_production') ? 'https://api.midtrans.com' : 'https://api.sandbox.midtrans.com';
    }

    private function http()
    {
        return Http::withBasicAuth((string) config('services.midtrans.server_key'), '')->acceptJson()->timeout(20);
    }

    /** Token Snap untuk satu tagihan. Disimpan di cache agar tidak membuat transaksi ganda. */
    public function snapToken(Payment $payment): ?string
    {
        return Cache::remember('snap_' . $payment->midtrans_order_id, now()->addHours((int) config('miseku.payment_expiry_hours', 24)), function () use ($payment) {
            /** @var CustomerOrder $order */
            $order = $payment->order()->with('buyer')->first();

            $res = $this->http()->post($this->snapUrl(), [
                'transaction_details' => ['order_id' => $payment->midtrans_order_id, 'gross_amount' => (int) round((float) $payment->amount)],
                'customer_details'    => [
                    'first_name' => $order->buyer->name, 'email' => $order->buyer->email, 'phone' => $order->buyer->phone,
                ],
                'item_details' => [[
                    'id' => $order->order_code, 'price' => (int) round((float) $payment->amount), 'quantity' => 1,
                    'name' => mb_substr($payment->type_label . ' ' . $order->order_code, 0, 50),
                ]],
                'enabled_payments' => ['bca_va', 'bni_va', 'bri_va', 'permata_va', 'echannel', 'other_va', 'qris', 'gopay', 'shopeepay'],
                'expiry'           => ['unit' => 'hour', 'duration' => (int) config('miseku.payment_expiry_hours', 24)],
                'callbacks'        => ['finish' => route('payments.show', $payment)],
            ]);

            if (! $res->successful()) {
                Log::error('Midtrans Snap gagal', ['body' => $res->body()]);

                return null;
            }

            return $res->json('token');
        });
    }

    /** Status transaksi dari API (dipakai tombol "Cek status" — berguna saat webhook belum bisa diakses dari localhost). */
    public function status(string $midtransOrderId): ?array
    {
        $res = $this->http()->get($this->apiUrl() . '/v2/' . urlencode($midtransOrderId) . '/status');

        return $res->successful() ? $res->json() : null;
    }

    /** Verifikasi signature_key webhook: sha512(order_id + status_code + gross_amount + server_key). */
    public function verifySignature(array $payload): bool
    {
        $expected = hash('sha512', ($payload['order_id'] ?? '') . ($payload['status_code'] ?? '') . ($payload['gross_amount'] ?? '') . config('services.midtrans.server_key'));

        return hash_equals($expected, (string) ($payload['signature_key'] ?? ''));
    }

    /** Refund lewat API (hanya metode yang didukung Midtrans, mis. QRIS/GoPay/ShopeePay). */
    public function refund(Payment $original, float $amount, string $reason): bool
    {
        try {
            $res = $this->http()->post($this->apiUrl() . '/v2/' . urlencode($original->midtrans_order_id) . '/refund', [
                'refund_key' => $original->midtrans_order_id . '-RF-' . now()->format('YmdHis'),
                'amount'     => (int) round($amount),
                'reason'     => mb_substr($reason, 0, 255),
            ]);

            return $res->successful() && in_array((string) $res->json('status_code'), ['200', '201'], true);
        } catch (\Throwable $e) {
            Log::warning('Refund Midtrans gagal: ' . $e->getMessage());

            return false;
        }
    }

    /** Ubah payload Midtrans → [status internal, label metode]. */
    public function interpret(array $payload): array
    {
        $trx = $payload['transaction_status'] ?? '';
        $fraud = $payload['fraud_status'] ?? 'accept';

        $status = match ($trx) {
            'settlement'                 => 'success',
            'capture'                    => $fraud === 'accept' ? 'success' : 'pending',
            'pending'                    => 'pending',
            'expire'                     => 'expired',
            'deny', 'cancel', 'failure'  => 'failed',
            default                      => 'pending',
        };

        $type = $payload['payment_type'] ?? null;
        $method = match ($type) {
            'bank_transfer' => 'VA ' . strtoupper($payload['va_numbers'][0]['bank'] ?? ($payload['permata_va_number'] ?? false ? 'permata' : 'bank')),
            'echannel'      => 'VA Mandiri',
            'qris'          => 'QRIS',
            'gopay'         => 'GoPay',
            'shopeepay'     => 'ShopeePay',
            'credit_card'   => 'Kartu kredit',
            null            => null,
            default         => ucwords(str_replace('_', ' ', $type)),
        };

        return [$status, $method];
    }
}
