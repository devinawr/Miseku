<?php

namespace App\Services;

use App\Models\ArtistProfile;
use App\Models\User;
use App\Models\Withdrawal;
use App\Support\Fail;
use App\Support\Money;
use Illuminate\Support\Facades\DB;

/**
 * BPMN 6 — Penarikan dana. Validasi otomatis di server (tanpa API tambahan):
 * jumlah ≤ saldo tersedia, ≥ minimum, rekening lengkap. Pencairan manual oleh admin.
 */
class WithdrawalService
{
    public function __construct(
        private BalanceService $balance,
        private NotificationService $notify,
    ) {}

    public function request(ArtistProfile $artist, float $amount): Withdrawal
    {
        return DB::transaction(function () use ($artist, $amount) {
            $artist = ArtistProfile::whereKey($artist->id)->lockForUpdate()->firstOrFail();
            $min = (float) config('miseku.withdrawal_minimum');

            Fail::unless(filled($artist->bank_name) && filled($artist->bank_account_number), 'Lengkapi nama bank & nomor rekening dulu.');
            Fail::unless($amount >= $min, 'Minimal penarikan ' . Money::rp($min) . '.');
            Fail::unless($amount <= (float) $artist->available_balance, 'Jumlah melebihi saldo tersedia (' . Money::rp($artist->available_balance) . ').');
            Fail::unless(! Withdrawal::where('artist_profile_id', $artist->id)->whereIn('withdrawal_status', ['submitted', 'processing'])->exists(),
                'Masih ada penarikan yang sedang diproses. Tunggu sampai selesai.');

            $w = Withdrawal::create([
                'artist_profile_id'   => $artist->id,
                'amount'              => $amount,
                'bank_name'           => $artist->bank_name,
                'bank_account_number' => $artist->bank_account_number,
                'withdrawal_status'   => 'submitted',
            ]);

            $this->balance->withdrawalHold($w);
            $this->notify->toAdmins('withdrawal', 'Pengajuan penarikan dana', "{$artist->shop_name} mengajukan penarikan " . Money::rp($amount) . " ke {$artist->bank_name} {$artist->bank_account_number}.");

            return $w;
        });
    }

    public function markProcessing(Withdrawal $w, User $admin): void
    {
        Fail::unless($w->withdrawal_status === 'submitted', 'Status penarikan sudah berubah.');
        $w->update(['withdrawal_status' => 'processing', 'processed_by' => $admin->id]);
        $this->notify->send($w->artistProfile->user_id, 'withdrawal', 'Penarikan sedang diproses', 'Admin sedang memproses penarikan ' . Money::rp($w->amount) . '.');
    }

    public function complete(Withdrawal $w, User $admin, string $proofUrl): void
    {
        Fail::unless(in_array($w->withdrawal_status, ['submitted', 'processing'], true), 'Penarikan ini sudah diputuskan.');

        $w->update(['withdrawal_status' => 'completed', 'processed_by' => $admin->id, 'transfer_proof' => $proofUrl, 'processed_at' => now()]);
        $this->notify->send($w->artistProfile->user_id, 'withdrawal', 'Dana sudah ditransfer', 'Penarikan ' . Money::rp($w->amount) . " ke {$w->bank_name} {$w->bank_account_number} sudah ditransfer. Bukti transfer ada di menu Saldo.");
    }

    public function reject(Withdrawal $w, User $admin, string $reason): void
    {
        Fail::unless(in_array($w->withdrawal_status, ['submitted', 'processing'], true), 'Penarikan ini sudah diputuskan.');

        DB::transaction(function () use ($w, $admin, $reason) {
            $values = ['withdrawal_status' => 'rejected', 'processed_by' => $admin->id, 'processed_at' => now()];
            // kolom baru (PATCH_withdrawal_reason.sql) — dicek dulu supaya DB lama tidak error
            if (\Illuminate\Support\Facades\Schema::hasColumn('withdrawal', 'rejection_reason')) {
                $values['rejection_reason'] = mb_substr($reason, 0, 255);
            }
            $w->update($values);
            $this->balance->withdrawalReturn($w);
            $this->notify->send($w->artistProfile->user_id, 'withdrawal', 'Penarikan ditolak', 'Penarikan ' . Money::rp($w->amount) . " ditolak: {$reason}. Saldo sudah dikembalikan.");
        });
    }
}
