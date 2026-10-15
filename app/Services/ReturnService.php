<?php

namespace App\Services;

use App\Models\ChatMessage;
use App\Models\CustomerOrder;
use App\Models\FileUpload;
use App\Models\OrderReturn;
use App\Models\User;
use App\Support\Fail;
use Illuminate\Support\Facades\DB;

/**
 * BPMN 5 — cabang Retur (barang SUDAH diterima).
 * Syarat otomatis: pesanan selesai & masih dalam masa retur (config miseku.return_days),
 * wajib bukti foto. Gagal syarat → tersimpan & ditolak otomatis.
 */
class ReturnService
{
    public function __construct(
        private RefundService $refunds,
        private NotificationService $notify,
    ) {}

    public function deadline(CustomerOrder $order): ?\Illuminate\Support\Carbon
    {
        return $order->completed_at?->copy()->addDays(config('miseku.return_days'));
    }

    public function eligibility(CustomerOrder $order): ?string
    {
        if ($order->order_status !== 'completed' || $order->order_type === 'ots_event') {
            return 'Retur hanya untuk pesanan yang sudah diterima.';
        }

        if (now()->gt($this->deadline($order))) {
            return 'Masa retur (' . config('miseku.return_days') . ' hari sejak diterima) sudah lewat.';
        }

        return null;
    }

    public function activeReturn(CustomerOrder $order): ?OrderReturn
    {
        return $order->returns()->whereIn('return_status', ['submitted', 'processing', 'approved', 'completed'])->latest('id')->first();
    }

    /** @param  array<int, string>  $photoUrls */
    public function request(CustomerOrder $order, User $buyer, string $reason, array $photoUrls): OrderReturn
    {
        Fail::unless($order->buyer_id === $buyer->id, 'Bukan pesananmu.');
        Fail::unless(count($photoUrls) > 0, 'Bukti foto wajib diunggah.');
        Fail::unless(! $this->activeReturn($order), 'Pesanan ini sudah pernah diajukan retur.');

        return DB::transaction(function () use ($order, $buyer, $reason, $photoUrls) {
            $problem = $this->eligibility($order);

            $return = OrderReturn::create([
                'customer_order_id' => $order->id,
                'buyer_id'          => $buyer->id,
                'reason'            => $reason,
                'photo_evidence'    => $photoUrls[0],
                'return_status'     => $problem ? 'rejected' : 'submitted',
            ]);

            foreach ($photoUrls as $i => $url) {
                FileUpload::create(['url' => $url, 'source_table' => 'order_return', 'source_id' => $return->id, 'uploaded_by' => $buyer->id, 'sort_order' => $i + 1]);
            }

            if ($problem) {
                $this->notify->send($buyer, 'order', 'Retur ditolak otomatis', "{$order->order_code}: {$problem}", $order);
            } else {
                $this->notify->send($order->artistProfile->user_id, 'order', 'Ada pengajuan retur', "Pembeli mengajukan retur {$order->order_code}: \"{$reason}\". Tinjau bukti foto di Studio → Pesanan.", $order);
            }

            return $return;
        });
    }

    /** Artis meninjau pengajuan + bukti. Setuju → pembeli kirim barang kembali. */
    public function review(OrderReturn $return, User $artist, bool $approve, string $note): void
    {
        $order = $return->order;
        Fail::unless($order->artistProfile->user_id === $artist->id, 'Bukan pesanan tokomu.');
        Fail::unless($return->return_status === 'submitted', 'Pengajuan retur ini sudah ditinjau.');

        DB::transaction(function () use ($return, $order, $artist, $approve, $note) {
            $return->update(['return_status' => $approve ? 'processing' : 'rejected']);
            $this->chat($artist, $order, ($approve ? '[Retur disetujui] ' : '[Retur ditolak] ') . $note);

            $this->notify->send($order->buyer_id, 'order', $approve ? 'Retur disetujui — kirim barang kembali' : 'Retur ditolak',
                $approve ? "Artis menyetujui retur {$order->order_code}. Kirim barang kembali ke artis lalu kabari nomor resinya lewat chat. Catatan artis: {$note}"
                         : "Artis menolak retur {$order->order_code}: {$note}", $order);
        });
    }

    /** Artis menerima & memeriksa barang retur. Sesuai → refund. */
    public function inspect(OrderReturn $return, User $artist, bool $ok, string $note): void
    {
        $order = $return->order;
        Fail::unless($order->artistProfile->user_id === $artist->id, 'Bukan pesanan tokomu.');
        Fail::unless($return->return_status === 'processing', 'Barang retur belum dalam tahap pengiriman kembali.');

        DB::transaction(function () use ($return, $order, $artist, $ok, $note) {
            $this->chat($artist, $order, ($ok ? '[Barang retur diterima & sesuai] ' : '[Barang retur tidak sesuai] ') . $note);

            if (! $ok) {
                $return->update(['return_status' => 'rejected']);
                $this->notify->send($order->buyer_id, 'order', 'Retur ditolak setelah pemeriksaan', "Barang retur {$order->order_code} tidak sesuai: {$note}", $order);

                return;
            }

            $return->update(['return_status' => 'approved']);
            $result = $this->refunds->refundOrder($order, 'Retur disetujui');

            if (! $result['manual']) {
                $return->update(['return_status' => 'completed']);
            }

            $this->notify->send($artist, 'order', 'Retur selesai diproses', "Refund retur {$order->order_code} diproses; saldo tersedia disesuaikan.", $order);
        });
    }

    private function chat(User $artist, CustomerOrder $order, string $body): void
    {
        ChatMessage::create([
            'sender_id' => $artist->id, 'receiver_id' => $order->buyer_id, 'customer_order_id' => $order->id,
            'message_body' => $body, 'sent_at' => now(),
        ]);
    }
}
