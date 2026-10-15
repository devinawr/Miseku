<?php

namespace App\Livewire;

use App\Livewire\Concerns\RunsActions;
use App\Models\Payment;
use App\Services\MidtransGateway;
use App\Services\PaymentService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Halaman bayar satu tagihan (DP / pelunasan / penuh).
 * - MIDTRANS_SERVER_KEY terisi → Snap popup + tombol "Cek status" (webhook juga didukung).
 * - Kosong → mode simulasi (tombol bayar berhasil / gagal) supaya alur bisa diuji offline.
 */
#[Layout('components.layouts.app')]
#[Title('Pembayaran')]
class PaymentPage extends Component
{
    use RunsActions;

    public Payment $payment;

    public ?string $snapToken = null;

    public ?string $gatewayError = null;

    public string $simMethod = 'QRIS';

    public function mount(Payment $payment): void
    {
        $payment->load('order.artistProfile', 'order.items.product');
        abort_unless($payment->order->buyer_id === Auth::id() && $payment->payment_type !== 'refund', 404);
        $this->payment = $payment;

        $gateway = app(MidtransGateway::class);
        if ($payment->payment_status === 'pending' && $gateway->isLive()) {
            try {
                $this->snapToken = $gateway->snapToken($payment);
            } catch (\Throwable $e) {
                $this->snapToken = null;
            }
            if (! $this->snapToken) {
                $this->gatewayError = 'Gagal membuat transaksi Midtrans. Periksa MIDTRANS_SERVER_KEY / koneksi, lalu muat ulang.';
            }
        }
    }

    /** Dipanggil setelah popup Snap ditutup / sukses: tanya status ke Midtrans (berguna di localhost tanpa webhook). */
    public function checkStatus(): void
    {
        $gateway = app(MidtransGateway::class);
        if (! $gateway->isLive()) {
            return;
        }

        $payload = $gateway->status($this->payment->midtrans_order_id);
        if (! $payload || ($payload['status_code'] ?? '') === '404') {
            $this->dispatch('toast', message: 'Belum ada transaksi di Midtrans untuk tagihan ini.', tone: 'error');

            return;
        }

        app(PaymentService::class)->handleMidtrans($payload);
        $this->afterChange();
    }

    public function simulatePay(): void
    {
        abort_if(app(MidtransGateway::class)->isLive(), 403);

        $ok = $this->attempt(fn () => app(PaymentService::class)->markSuccess($this->payment, $this->simMethod . ' (simulasi)', 'SIM-' . strtoupper(Str::random(10))));
        if ($ok) {
            $this->afterChange();
        }
    }

    public function simulateFail(): void
    {
        abort_if(app(MidtransGateway::class)->isLive(), 403);

        app(PaymentService::class)->markFailed($this->payment, 'failed');
        $this->afterChange();
    }

    private function afterChange(): void
    {
        $this->payment->refresh();

        if ($this->payment->payment_status === 'success') {
            session()->flash('status', 'Pembayaran berhasil 🎉');
            $this->redirectRoute('orders.show', ['order' => $this->payment->customer_order_id], navigate: false);
        } elseif ($this->payment->payment_status !== 'pending') {
            session()->flash('status', 'Pembayaran ' . $this->payment->status_label . '.');
            $this->redirectRoute('orders.show', ['order' => $this->payment->customer_order_id], navigate: false);
        } else {
            $this->dispatch('toast', message: 'Pembayaran masih menunggu. Selesaikan di aplikasi pembayaranmu lalu cek lagi.');
        }
    }

    public function render()
    {
        return view('livewire.payment-page', [
            'live'     => app(MidtransGateway::class)->isLive(),
            'gateway'  => app(MidtransGateway::class),
            'expires'  => $this->payment->created_at->copy()->addHours((int) config('miseku.payment_expiry_hours')),
        ]);
    }
}
