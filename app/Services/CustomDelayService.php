<?php

namespace App\Services;

use App\Models\Cancellation;
use App\Models\CustomerOrder;
use App\Models\User;
use App\Support\Fail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/** Keterlambatan custom: pembeli menentukan lanjut atau batal tanpa penalti. */
class CustomDelayService
{
    public function __construct(private NotificationService $notify, private RefundService $refunds) {}

    public function isPending(CustomerOrder $order): bool
    {
        return $order->order_type === 'custom'
            && in_array($order->order_status, ['paid', 'awaiting_production', 'in_production'], true)
            && ($order->custom_late_at !== null || ($order->custom_estimate_at && now()->gte($order->custom_estimate_at)));
    }

    /** Caller harus sudah mengunci row order. */
    private function markLocked(CustomerOrder $order): bool
    {
        if (! $this->isPending($order) || $order->custom_late_at) return false;
        $order->update(['custom_late_at' => now()]);
        $this->notify->send($order->buyer_id, 'custom', 'Estimasi custom terlewati', "{$order->order_code} melewati estimasi. Chat artis; kamu dapat membatalkan tanpa penalti atau menunggu estimasi baru.", $order);
        $this->notify->send($order->artistProfile->user_id, 'custom', 'Estimasi custom terlewati', "{$order->order_code} terlambat. Jelaskan alasannya dan usulkan estimasi baru; pembeli dapat memilih lanjut atau batal tanpa penalti.", $order);
        return true;
    }

    public function markLate(CustomerOrder $order): bool
    {
        return DB::transaction(function () use ($order) {
            $order = CustomerOrder::whereKey($order->id)->lockForUpdate()->firstOrFail();
            return $this->markLocked($order);
        });
    }

    public function revise(CustomerOrder $order, User $artist, string $date, string $reason): void
    {
        $reason = trim($reason);
        Fail::unless(mb_strlen($reason) >= 10 && mb_strlen($reason) <= 1000, 'Alasan wajib diisi 10–1000 karakter.');
        try { $newDate = Carbon::createFromFormat('!Y-m-d', $date); }
        catch (\Throwable $e) { Fail::because('Tanggal estimasi tidak valid.'); }
        Fail::unless($newDate && $newDate->format('Y-m-d') === $date && $newDate->isAfter(today()), 'Pilih tanggal estimasi setelah hari ini.');
        DB::transaction(function () use ($order, $artist, $newDate, $reason) {
            $order = CustomerOrder::whereKey($order->id)->lockForUpdate()->firstOrFail();
            Fail::unless($order->artistProfile->user_id === $artist->id, 'Bukan pesanan tokomu.');
            Fail::unless($order->order_type === 'custom' && in_array($order->order_status, ['paid', 'awaiting_production', 'in_production'], true), 'Estimasi tidak bisa diubah pada tahap ini.');
            $this->markLocked($order);
            $order->update(['custom_estimate_at' => $newDate->endOfDay(), 'custom_estimate_reason' => $reason]);
            $choice = $order->custom_late_at ? ' Pilih lanjut atau batal tanpa penalti di detail pesanan.' : '';
            $this->notify->send($order->buyer_id, 'custom', 'Estimasi custom diubah', "{$order->order_code}: estimasi baru {$newDate->translatedFormat('d M Y')}. Alasan: {$reason}.{$choice}", $order);
        });
    }

    public function accept(CustomerOrder $order, User $buyer): void
    {
        DB::transaction(function () use ($order, $buyer) {
            $order = CustomerOrder::whereKey($order->id)->lockForUpdate()->firstOrFail();
            Fail::unless($order->buyer_id === $buyer->id, 'Bukan pesananmu.');
            Fail::unless($this->isPending($order), 'Tidak ada keterlambatan yang menunggu keputusan.');
            Fail::unless($order->custom_late_at && $order->custom_estimate_at && $order->custom_estimate_at->isFuture() && filled($order->custom_estimate_reason), 'Tunggu artis memberikan estimasi baru dan alasan.');
            $order->update(['custom_late_at' => null]);
            $this->notify->send($order->artistProfile->user_id, 'custom', 'Pembeli setuju melanjutkan', "Pembeli menyetujui estimasi baru {$order->order_code}.", $order);
        });
    }

    public function cancel(CustomerOrder $order, User $buyer): void
    {
        DB::transaction(function () use ($order, $buyer) {
            $order = CustomerOrder::whereKey($order->id)->lockForUpdate()->firstOrFail();
            Fail::unless($order->buyer_id === $buyer->id && $this->isPending($order), 'Pesanan tidak memenuhi syarat pembatalan keterlambatan.');
            $this->markLocked($order);
            $order->cancellations()->whereIn('cancellation_status', ['submitted', 'in_discussion'])->update(['cancellation_status' => 'rejected', 'artist_response' => 'Ditutup karena pembatalan keterlambatan.', 'resolved_at' => now(), 'updated_at' => now()]);
            Cancellation::create(['customer_order_id' => $order->id, 'requester_id' => $buyer->id, 'cancellation_type' => 'voluntary', 'penalty_exempt' => true, 'reason' => 'Pembeli tidak melanjutkan karena estimasi custom terlewati.', 'cancellation_status' => 'approved', 'resolved_at' => now()]);
            $order->update(['order_status' => 'cancelled']);
            $order->customRequest?->update(['request_status' => 'cancelled']);
            $this->refunds->refundOrder($order, 'Custom terlambat — pembeli tidak melanjutkan');
            $this->notify->send($order->buyer_id, 'custom', 'Custom dibatalkan tanpa penalti', "{$order->order_code} dibatalkan. Refund seluruh dana terbayar diproses; cek statusnya di pesanan.", $order);
            $this->notify->send($order->artistProfile->user_id, 'custom', 'Custom dibatalkan tanpa penalti', "Pembeli membatalkan {$order->order_code} karena estimasi terlewati. Refund diproses tanpa penalti kedua pihak.", $order);
        });
    }
}
