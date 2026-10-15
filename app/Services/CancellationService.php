<?php

namespace App\Services;

use App\Models\Cancellation;
use App\Models\ChatMessage;
use App\Models\CustomerOrder;
use App\Models\User;
use App\Support\Fail;
use Illuminate\Support\Facades\DB;

/**
 * BPMN 5 — cabang Pembatalan (barang belum diterima).
 * Syarat otomatis:
 *   ready stock : belum dikirim (status paid)
 *   pre-order   : produksi belum dimulai (paid / awaiting_production)
 *   custom      : lewat diskusi chat dulu (status in_discussion) lalu artis memutuskan
 * Gagal syarat → pengajuan tersimpan & langsung ditolak otomatis.
 * Disetujui → pesanan batal, stok kembali, refund dana terbayar, saldo disesuaikan,
 * penalti rating ke pihak yang mengajukan.
 */
class CancellationService
{
    public function __construct(
        private OrderService $orders,
        private RefundService $refunds,
        private RatingService $ratings,
        private NotificationService $notify,
    ) {}

    public function eligibility(CustomerOrder $order): ?string
    {
        return match ($order->order_type) {
            'ready_stock' => $order->order_status === 'paid' ? null : 'Pesanan ready stock hanya bisa dibatalkan sebelum dikirim/disiapkan.',
            'pre_order'   => in_array($order->order_status, ['paid', 'awaiting_production'], true) ? null : 'Pesanan pre-order hanya bisa dibatalkan sebelum produksi dimulai.',
            'custom'      => in_array($order->order_status, ['paid', 'awaiting_production', 'in_production', 'production_completed'], true) ? null : 'Pesanan custom yang sudah dikirim/diambil tidak bisa dibatalkan.',
            default       => 'Pesanan ini tidak bisa dibatalkan.',
        };
    }

    public function openCase(CustomerOrder $order): ?Cancellation
    {
        return $order->cancellations()->whereIn('cancellation_status', ['submitted', 'in_discussion'])->latest('id')->first();
    }

    /** Pembeli mengajukan pembatalan. */
    public function request(CustomerOrder $order, User $buyer, string $reason): Cancellation
    {
        Fail::unless($order->buyer_id === $buyer->id, 'Bukan pesananmu.');
        Fail::unless($order->order_status !== 'awaiting_payment', 'Pesanan belum dibayar — cukup tekan "Batalkan pesanan".');
        Fail::unless(! $this->openCase($order), 'Sudah ada pengajuan pembatalan yang sedang diproses.');

        return DB::transaction(function () use ($order, $buyer, $reason) {
            $problem = $this->eligibility($order);

            $case = Cancellation::create([
                'customer_order_id'   => $order->id,
                'requester_id'        => $buyer->id,
                'cancellation_type'   => 'voluntary',
                'reason'              => $reason,
                'cancellation_status' => $problem ? 'rejected' : ($order->order_type === 'custom' ? 'in_discussion' : 'submitted'),
                'artist_response'     => $problem ? 'Ditolak otomatis: ' . $problem : null,
                'resolved_at'         => $problem ? now() : null,
            ]);

            if ($problem) {
                $this->notify->send($buyer, 'cancellation', 'Pengajuan pembatalan ditolak otomatis', "{$order->order_code}: {$problem}", $order);

                return $case;
            }

            $artistUserId = $order->artistProfile->user_id;

            if ($order->order_type === 'custom') {
                // custom: diskusi lewat chat dulu
                ChatMessage::create([
                    'sender_id' => $buyer->id, 'receiver_id' => $artistUserId, 'customer_order_id' => $order->id,
                    'custom_request_id' => $order->custom_request_id,
                    'message_body' => "[Pengajuan pembatalan {$order->order_code}] {$reason}", 'sent_at' => now(),
                ]);
            }

            $this->notify->send($artistUserId, 'cancellation', 'Ada pengajuan pembatalan', "Pembeli mengajukan pembatalan {$order->order_code}: \"{$reason}\". Tinjau di Studio → Pesanan.", $order);

            return $case;
        });
    }

    public function approve(Cancellation $case, User $artist, ?string $response = null): void
    {
        $order = $case->order;
        Fail::unless($order->artistProfile->user_id === $artist->id, 'Bukan pesanan tokomu.');
        Fail::unless(in_array($case->cancellation_status, ['submitted', 'in_discussion'], true), 'Pengajuan ini sudah diputuskan.');
        Fail::unless($this->eligibility($order) === null, 'Status pesanan sudah berubah, pembatalan tidak bisa disetujui lagi.');

        DB::transaction(function () use ($case, $response) {
            $case->update(['cancellation_status' => 'approved', 'artist_response' => $response, 'resolved_at' => now()]);
            $this->executeCancel($case->order, 'Pembatalan disetujui artis', $case);
        });
    }

    public function reject(Cancellation $case, User $artist, string $response): void
    {
        $order = $case->order;
        Fail::unless($order->artistProfile->user_id === $artist->id, 'Bukan pesanan tokomu.');
        Fail::unless(in_array($case->cancellation_status, ['submitted', 'in_discussion'], true), 'Pengajuan ini sudah diputuskan.');

        $case->update(['cancellation_status' => 'rejected', 'artist_response' => $response, 'resolved_at' => now()]);
        $this->notify->send($order->buyer_id, 'cancellation', 'Pengajuan pembatalan ditolak', "Artis menolak pembatalan {$order->order_code}: \"{$response}\". Pesanan tetap diproses.", $order);
    }

    /** Artis membatalkan sendiri (mis. tidak sanggup memenuhi) → langsung disetujui, penalti ke artis. */
    public function cancelByArtist(CustomerOrder $order, User $artist, string $reason): void
    {
        Fail::unless($order->artistProfile->user_id === $artist->id, 'Bukan pesanan tokomu.');
        Fail::unless(in_array($order->order_status, ['paid', 'awaiting_production', 'in_production', 'production_completed', 'awaiting_settlement'], true),
            'Pesanan yang sudah dikirim/diambil/selesai tidak bisa dibatalkan.');

        DB::transaction(function () use ($order, $artist, $reason) {
            $case = Cancellation::create([
                'customer_order_id' => $order->id, 'requester_id' => $artist->id, 'cancellation_type' => 'voluntary',
                'reason' => $reason, 'cancellation_status' => 'approved', 'artist_response' => 'Dibatalkan oleh artis', 'resolved_at' => now(),
            ]);
            $this->executeCancel($order, 'Dibatalkan oleh artis', $case);
        });
    }

    /** Batal + stok kembali + refund + penalti + notifikasi. */
    private function executeCancel(CustomerOrder $order, string $label, Cancellation $case): void
    {
        $order = CustomerOrder::whereKey($order->id)->lockForUpdate()->firstOrFail();
        $order->update(['order_status' => 'cancelled']);
        $this->orders->restoreStock($order);
        $order->customRequest?->update(['request_status' => 'cancelled']);

        $refund = $this->refunds->refundOrder($order, $label);

        // penalti rating untuk pengaju
        if ($case->requester_id === $order->artistProfile->user_id) {
            $this->ratings->recalcArtist($order->artist_profile_id);
        } else {
            $this->ratings->recalcBuyer($order->buyer_id);
        }

        $this->notify->send($order->buyer_id, 'cancellation', 'Pesanan dibatalkan', "{$order->order_code} dibatalkan ({$label}). " . ($refund['total'] > 0 ? 'Dana yang sudah dibayar dikembalikan.' : ''), $order);
        $this->notify->send($order->artistProfile->user_id, 'cancellation', 'Pesanan dibatalkan', "{$order->order_code} dibatalkan ({$label}). Dana tertahan dikembalikan ke pembeli.", $order);
    }
}
