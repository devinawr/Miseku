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
                    $this->notify->send($o->buyer_id, 'order', 'PO dibatalkan — target tidak tercapai', "PO {$product->product_name} hanya {$total}/{$batch->min_order} pcs. Pesanan {$o->order_code} dibatalkan dan dana dikembalikan otomatis.", $o);
                }
                $this->notify->send($product->artistProfile->user_id, 'order', 'PO tidak mencapai target', "PO {$product->product_name} hanya {$total}/{$batch->min_order} pcs — batch dibatalkan dan semua pembeli direfund otomatis.");
            }

            return ['reached' => $reached, 'total' => $total];
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
