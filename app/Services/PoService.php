<?php

namespace App\Services;

use App\Models\CustomerOrder;
use App\Models\PoBatch;
use App\Support\Fail;
use Illuminate\Support\Facades\DB;

/**
 * BPMN 3 (bagian PO): timer tutup PO → hitung total pesanan (order_item) →
 * tercapai: lanjut produksi · tidak: refund otomatis semua + batch dibatalkan.
 */
class PoService
{
    public function __construct(
        private OrderService $orders,
        private RefundService $refunds,
        private NotificationService $notify,
    ) {}

    /** Pesanan pada batch ini. */
    public function ordersOf(PoBatch $batch)
    {
        return CustomerOrder::whereHas('items', fn ($q) => $q->where('po_batch_id', $batch->id))->get();
    }

    /** Tutup PO lebih awal (artis) — langsung dievaluasi. */
    public function closeNow(PoBatch $batch): array
    {
        Fail::unless($batch->batch_status === 'open', 'Batch ini sudah ditutup.');
        $batch->update(['closes_at' => now()]);

        return $this->evaluate($batch->fresh());
    }

    /** @return array{reached: bool, total: int} */
    public function evaluate(PoBatch $batch): array
    {
        return DB::transaction(function () use ($batch) {
            $batch = PoBatch::whereKey($batch->id)->lockForUpdate()->with('product.artistProfile')->firstOrFail();

            if ($batch->batch_status !== 'open') {
                return ['reached' => $batch->batch_status !== 'cancelled', 'total' => $batch->orderedQuantity(true)];
            }

            // tagihan yang belum dibayar saat PO ditutup → dibatalkan
            foreach ($this->ordersOf($batch)->where('order_status', 'awaiting_payment') as $o) {
                $this->orders->cancelUnpaid($o, 'PO sudah ditutup sebelum tagihan dibayar.');
            }

            $total = $batch->orderedQuantity(true);
            $reached = ! $batch->min_order || $total >= $batch->min_order;
            $product = $batch->product;

            if ($reached) {
                $batch->update(['batch_status' => 'closed']);
                foreach ($this->ordersOf($batch)->where('order_status', 'paid') as $o) {
                    $o->update(['order_status' => 'awaiting_production']);
                    $this->notify->send($o->buyer_id, 'order', 'PO ditutup — target tercapai', "PO {$product->product_name} ditutup dengan {$total} pcs. Pesanan {$o->order_code} masuk antrean produksi.", $o);
                }
                $this->notify->send($product->artistProfile->user_id, 'order', 'PO ditutup — siap produksi', "PO {$product->product_name} ditutup: {$total} pcs" . ($batch->min_order ? " (target {$batch->min_order})" : '') . '. Mulai produksi dari menu Batch PO.');
            } else {
                $batch->update(['batch_status' => 'cancelled']);
                foreach ($this->ordersOf($batch)->where('order_status', 'paid') as $o) {
                    $o->update(['order_status' => 'cancelled']);
                    $this->refunds->refundOrder($o, 'PO tidak mencapai minimum produksi');
                    $this->notify->send($o->buyer_id, 'order', 'PO dibatalkan — target tidak tercapai', "PO {$product->product_name} hanya {$total}/{$batch->min_order} pcs. Pesanan {$o->order_code} dibatalkan dan refund diproses.", $o);
                }
                $this->notify->send($product->artistProfile->user_id, 'order', 'PO tidak mencapai target', "PO {$product->product_name} hanya {$total}/{$batch->min_order} pcs — batch dibatalkan dan semua refund pembeli diproses.");
            }

            return ['reached' => $reached, 'total' => $total];
        });
    }

    /** Ubah bulan estimasi dengan alasan dan beri tahu pembeli yang pesanannya masih aktif. */
    public function changeEstimate(PoBatch $batch, string $month, string $reason): void
    {
        $reason = trim($reason);
        Fail::unless(mb_strlen($reason) >= 10 && mb_strlen($reason) <= 1000, 'Alasan wajib diisi 10–1000 karakter.');
        try { $newDate = \Illuminate\Support\Carbon::createFromFormat('!Y-m', $month); }
        catch (\Throwable $e) { Fail::because('Bulan estimasi tidak valid.'); }
        Fail::unless($newDate && $newDate->format('Y-m') === $month && $newDate->gte(today()->startOfMonth()), 'Estimasi tidak boleh di bulan yang sudah lewat.');
        DB::transaction(function () use ($batch, $newDate, $reason) {
            $batch = PoBatch::whereKey($batch->id)->lockForUpdate()->with('product')->firstOrFail();
            Fail::unless(in_array($batch->batch_status, ['open', 'closed', 'in_production'], true), 'Estimasi batch ini tidak dapat diubah.');
            Fail::unless(! $batch->estimated_month || $batch->estimated_month->format('Y-m') !== $newDate->format('Y-m'), 'Bulan estimasi belum berubah.');
            $old = $batch->estimated_month?->translatedFormat('F Y') ?: '-';
            $batch->update(['estimated_month' => $newDate->toDateString(), 'estimate_change_reason' => $reason, 'estimate_reminded_for' => null]);
            foreach ($this->ordersOf($batch)->whereNotIn('order_status', ['cancelled', 'failed', 'completed']) as $order) {
                $this->notify->send($order->buyer_id, 'order', 'Estimasi PO berubah', "Estimasi {$batch->product->product_name} berubah dari {$old} menjadi " . $newDate->translatedFormat('F Y') . ". Alasan: {$reason}", $order);
            }
        });
    }

    /** Ingatkan artis sekali saat bulan estimasi tiba; perubahan bulan mengaktifkan pengingat baru. */
    public function remindEstimate(PoBatch $batch): bool
    {
        return DB::transaction(function () use ($batch) {
            $batch = PoBatch::whereKey($batch->id)->lockForUpdate()->with('product.artistProfile')->firstOrFail();
            if (! in_array($batch->batch_status, ['open', 'closed', 'in_production'], true) || ! $batch->estimated_month
                || $batch->estimated_month->isFuture() || ($batch->estimate_reminded_for && $batch->estimate_reminded_for->isSameDay($batch->estimated_month))) return false;
            $this->notify->send($batch->product->artistProfile->user_id, 'order', 'Bulan estimasi PO telah tiba',
                "Estimasi {$batch->product->product_name} sudah tiba. Perbarui status produksi atau ubah estimasi dengan alasan di Batch PO.");
            $batch->update(['estimate_reminded_for' => $batch->estimated_month->toDateString()]);
            return true;
        });
    }

    public function startProduction(PoBatch $batch): int
    {
        Fail::unless($batch->batch_status === 'closed', 'Produksi bisa dimulai setelah PO ditutup & target tercapai.');

        return DB::transaction(function () use ($batch) {
            $batch->update(['batch_status' => 'in_production']);
            $n = 0;
            foreach ($this->ordersOf($batch)->whereIn('order_status', ['paid', 'awaiting_production']) as $o) {
                $this->orders->setProductionStatus($o, 'in_production');
                $n++;
            }

            return $n;
        });
    }

    public function completeProduction(PoBatch $batch): int
    {
        Fail::unless($batch->batch_status === 'in_production', 'Batch belum dalam tahap produksi.');

        return DB::transaction(function () use ($batch) {
            $batch->update(['batch_status' => 'completed', 'completed_at' => now()]);
            $n = 0;
            foreach ($this->ordersOf($batch)->whereIn('order_status', ['awaiting_production', 'in_production']) as $o) {
                $this->orders->completeProduction($o);
                $n++;
            }

            return $n;
        });
    }
}
