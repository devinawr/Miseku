<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\RefundBankAccount;
use App\Models\User;
use App\Support\Fail;
use Illuminate\Support\Facades\DB;

/** Menyimpan tujuan refund manual, bukan menjadwalkan atau mengirim transfer. */
class RefundBankAccountService
{
    public function __construct(private NotificationService $notify) {}

    public function submit(Payment $refund, User $buyer, string $bank, string $accountName, string $accountNumber): void
    {
        $bank = trim($bank); $accountName = trim($accountName); $accountNumber = trim($accountNumber);
        Fail::unless(mb_strlen($bank) >= 2 && mb_strlen($bank) <= 100, 'Nama bank harus 2–100 karakter.');
        Fail::unless(mb_strlen($accountName) >= 2 && mb_strlen($accountName) <= 100, 'Nama pemilik rekening harus 2–100 karakter.');
        Fail::unless((bool) preg_match('/^[0-9]{6,20}$/', $accountNumber), 'Nomor rekening harus 6–20 digit.');
        DB::transaction(function () use ($refund, $buyer, $bank, $accountName, $accountNumber) {
            $refund = Payment::whereKey($refund->id)->lockForUpdate()->firstOrFail();
            $order = $refund->order;
            Fail::unless($order && $order->buyer_id === $buyer->id, 'Refund ini bukan milikmu.');
            Fail::unless($refund->payment_type === 'refund' && $refund->payment_status === 'pending' && $refund->refund_method === 'manual_transfer', 'Refund ini tidak memerlukan rekening manual.');
            Fail::unless(! RefundBankAccount::where('payment_id', $refund->id)->exists(), 'Rekening sudah dikirim. Jika salah, hubungi admin; jangan kirim ulang.');
            RefundBankAccount::create(['payment_id' => $refund->id, 'bank_name' => $bank, 'account_name' => $accountName, 'account_number' => $accountNumber]);
            $this->notify->toAdmins('payment', 'Rekening refund dikirim', "Pembeli mengirim rekening untuk refund {$order->order_code}. Periksa pada menu refund admin.");
        });
    }
}
