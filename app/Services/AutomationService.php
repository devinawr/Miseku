<?php

namespace App\Services;

use App\Models\Cancellation;
use App\Models\CustomerOrder;
use App\Models\DesignPoll;
use App\Models\EventPickup;
use App\Models\Payment;
use App\Models\PoBatch;
use App\Support\Money;
use Illuminate\Support\Facades\DB;

/**
 * Pekerjaan terjadwal (scheduler) — semua timer di BPMN:
 *  1. Tagihan kedaluwarsa (tidak dibayar dalam N jam)
 *  2. Timer tutup PO → cek minimum (BPMN 3)
 *  3. Voting berakhir → pemenang (BPMN 3)
 *  4. Pengingat pelunasan H-7/H-3/H-1 & tenggat 14 hari → gagal + DP hangus (BPMN 2)
 *  5. Pengingat kirim H-7/H-3/H-1 & tenggat 14 hari → batal otomatis + refund (BPMN 2)
 *  6. Event selesai → QR yang tidak diambil hangus (forfeited)
 * Idempoten: aman dijalankan berkali-kali (pakai kolom *_reminder_stage & status).
 */
class AutomationService
{
    public function __construct(
        private PaymentService $payments,
        private PoService $po,
        private PollService $polls,
        private OrderService $orders,
        private RefundService $refunds,
        private BalanceService $balance,
        private RatingService $ratings,
        private NotificationService $notify,
    ) {}

    public function run(): array
    {
        $log = ['expired' => 0, 'po_closed' => 0, 'polls' => 0, 'settle_remind' => 0, 'settle_fail' => 0, 'ship_remind' => 0, 'ship_cancel' => 0, 'pickup_forfeit' => 0];

        // 1) tagihan kedaluwarsa
        Payment::where('payment_status', 'pending')->where('payment_type', '!=', 'refund')
            ->where('created_at', '<', now()->subHours((int) config('miseku.payment_expiry_hours')))
            ->get()->each(function (Payment $p) use (&$log) {
                $this->payments->markFailed($p, 'expired');
                $log['expired']++;
            });

        // 2) PO lewat tanggal tutup
        PoBatch::where('batch_status', 'open')->where('closes_at', '<', now())->get()->each(function (PoBatch $b) use (&$log) {
            $this->po->evaluate($b);
            $log['po_closed']++;
        });

        // 3) voting berakhir
        DesignPoll::where('poll_status', 'running')->where('end_date', '<', now())->get()->each(function (DesignPoll $poll) use (&$log) {
            $this->polls->finish($poll);
            $log['polls']++;
        });

        // 4) pelunasan
        CustomerOrder::where('order_status', 'awaiting_settlement')->whereNotNull('settlement_deadline')->get()->each(function (CustomerOrder $o) use (&$log) {
            if (now()->gte($o->settlement_deadline)) {
                $this->settlementTimeout($o);
                $log['settle_fail']++;
            } elseif ($this->remind($o, 'settlement')) {
                $log['settle_remind']++;
            }
        });

        // 5) tenggat kirim (kurir saja; pick-up mengikuti tanggal event)
        CustomerOrder::whereIn('order_status', ['paid', 'production_completed'])->whereNotNull('shipping_deadline')
            ->where('shipping_method', 'courier')->get()->each(function (CustomerOrder $o) use (&$log) {
                if (! $this->orders->isReadyToShip($o)) {
                    return;
                }
                if (now()->gte($o->shipping_deadline)) {
                    $this->shippingTimeout($o, 'Artis tidak mengirim pesanan sampai tenggat ' . config('miseku.shipping_days') . ' hari.');
                    $log['ship_cancel']++;
                } elseif ($this->remind($o, 'shipping')) {
                    $log['ship_remind']++;
                }
            });

        // 6) event pick-up berakhir
        CustomerOrder::where('shipping_method', 'event_pickup')->whereIn('order_status', ['paid', 'awaiting_production', 'in_production', 'production_completed', 'ready_for_pickup'])
            ->whereHas('booth.event', fn ($q) => $q->whereDate('end_date', '<', today()))
            ->get()->each(function (CustomerOrder $o) use (&$log) {
                $this->eventEnded($o);
                $log['pickup_forfeit']++;
            });

        return $log;
    }

    /** Kirim pengingat H-7/H-3/H-1 sekali per tahap. */
    private function remind(CustomerOrder $o, string $kind): bool
    {
        $deadline = $kind === 'settlement' ? $o->settlement_deadline : $o->shipping_deadline;
        $stageCol = $kind === 'settlement' ? 'settlement_reminder_stage' : 'shipping_reminder_stage';
        $hoursLeft = now()->diffInHours($deadline, false);

        $target = 0;
        foreach (config('miseku.reminder_days') as $i => $days) {
            if ($hoursLeft <= $days * 24) {
                $target = $i + 1;
            }
        }

        if ($target <= $o->{$stageCol}) {
            return false;
        }

        $o->update([$stageCol => $target]);
        $daysLeft = max(1, (int) ceil($hoursLeft / 24));

        if ($kind === 'settlement') {
            $rest = (float) $o->total_amount - $o->load('payments')->paidAmount();
            $this->notify->send($o->buyer_id, 'po_settlement', "Pengingat pelunasan (H-{$daysLeft})",
                "Sisa pembayaran {$o->order_code} " . Money::rp($rest) . ' jatuh tempo ' . $deadline->translatedFormat('d M Y H:i') . '. Lewat tenggat, pesanan gagal dan DP hangus.', $o);
        } else {
            $this->notify->send($o->artistProfile->user_id, 'order', "Pengingat kirim pesanan (H-{$daysLeft})",
                "{$o->order_code} wajib dikirim paling lambat " . $deadline->translatedFormat('d M Y H:i') . '. Lewat tenggat, pesanan batal otomatis dan dana dikembalikan ke pembeli.', $o);
        }

        return true;
    }

    /** Pelunasan lewat tenggat → gagal, DP hangus jadi milik artis, penalti pembeli. */
    public function settlementTimeout(CustomerOrder $o): void
    {
        DB::transaction(function () use ($o) {
            $o = CustomerOrder::whereKey($o->id)->lockForUpdate()->firstOrFail();
            if ($o->order_status !== 'awaiting_settlement') {
                return;
            }

            $o->update(['order_status' => 'failed']);
            $o->payments()->where('payment_type', 'settlement')->where('payment_status', 'pending')->update(['payment_status' => 'expired', 'updated_at' => now()]);

            Cancellation::create([
                'customer_order_id' => $o->id, 'requester_id' => null, 'cancellation_type' => 'settlement_timeout',
                'reason' => 'Pelunasan tidak dibayar sampai tenggat ' . config('miseku.settlement_days') . ' hari.',
                'cancellation_status' => 'approved', 'resolved_at' => now(),
            ]);

            $tx = $this->balance->forfeit($o, "DP {$o->order_code} hangus (lewat tenggat pelunasan)");
            $this->ratings->recalcBuyer($o->buyer_id);

            $this->notify->send($o->buyer_id, 'po_settlement', 'Pesanan gagal — DP hangus', "Pelunasan {$o->order_code} tidak dibayar sampai tenggat. Pesanan gagal dan DP menjadi milik artis.", $o);
            $this->notify->send($o->artistProfile->user_id, 'po_settlement', 'Pembeli tidak melunasi — DP jadi milikmu',
                "{$o->order_code} gagal karena lewat tenggat pelunasan. DP " . Money::rp($tx?->amount ?? 0) . ' masuk ke saldo tersedia.', $o);
        });
    }

    /** Artis lewat tenggat kirim → batal otomatis, refund penuh, penalti artis. */
    public function shippingTimeout(CustomerOrder $o, string $reason): void
    {
        DB::transaction(function () use ($o, $reason) {
            $o = CustomerOrder::whereKey($o->id)->lockForUpdate()->firstOrFail();
            if (! in_array($o->order_status, CustomerOrder::ACTIVE, true) || $o->order_status === 'shipped') {
                return;
            }

            $o->update(['order_status' => 'cancelled']);
            // pengajuan pembatalan pembeli yang masih terbuka ikut ditutup
            $o->cancellations()->whereIn('cancellation_status', ['submitted', 'in_discussion'])->update([
                'cancellation_status' => 'rejected', 'artist_response' => 'Ditutup otomatis — pesanan sudah dibatalkan sistem karena lewat tenggat.',
                'resolved_at' => now(), 'updated_at' => now(),
            ]);
            Cancellation::create([
                'customer_order_id' => $o->id, 'requester_id' => null, 'cancellation_type' => 'shipping_timeout',
                'reason' => $reason, 'cancellation_status' => 'approved', 'resolved_at' => now(),
            ]);

            $this->orders->restoreStock($o);
            $this->refunds->refundOrder($o, 'Batal otomatis — lewat tenggat kirim');
            $o->customRequest?->update(['request_status' => 'cancelled']);
            $this->ratings->recalcArtist($o->artist_profile_id);

            $this->notify->send($o->artistProfile->user_id, 'cancellation', 'Pesanan batal otomatis', "{$o->order_code} dibatalkan sistem: {$reason} Dana dikembalikan ke pembeli dan rating tokomu terkena penalti.", $o);
            $this->notify->send($o->buyer_id, 'cancellation', 'Pesanan dibatalkan otomatis', "{$o->order_code} dibatalkan karena artis tidak menyerahkan barang tepat waktu. Dana dikembalikan penuh.", $o);
        });
    }

    /** Event berakhir: QR tidak diambil → hangus; pesanan yang belum siap → kesalahan artis (refund). */
    private function eventEnded(CustomerOrder $o): void
    {
        if ($o->order_status === 'ready_for_pickup') {
            DB::transaction(function () use ($o) {
                EventPickup::where('customer_order_id', $o->id)->where('pickup_status', 'not_picked_up')->update(['pickup_status' => 'forfeited', 'updated_at' => now()]);
                $o->update(['order_status' => 'failed']);
                $this->balance->forfeit($o, "Pesanan {$o->order_code} tidak diambil sampai event berakhir");

                $this->notify->send($o->buyer_id, 'event_pickup', 'Pengambilan hangus', "{$o->order_code} tidak diambil sampai event berakhir. Sesuai ketentuan, pesanan hangus.", $o);
                $this->notify->send($o->artistProfile->user_id, 'event_pickup', 'Pesanan tidak diambil', "{$o->order_code} tidak diambil sampai event berakhir. Dana pesanan masuk ke saldo tersedia.", $o);
            });

            return;
        }

        $this->shippingTimeout($o, 'Pesanan belum disiapkan artis sampai event pengambilan berakhir.');
    }
}