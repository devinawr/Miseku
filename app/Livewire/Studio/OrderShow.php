<?php

namespace App\Livewire\Studio;

use App\Livewire\Concerns\RunsActions;
use App\Models\Cancellation;
use App\Models\CustomerOrder;
use App\Models\OrderReturn;
use App\Services\CancellationService;
use App\Services\OrderService;
use App\Services\ReturnService;
use App\Services\ReviewService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/** Detail pesanan untuk artis: produksi, kirim/pick-up, pembatalan, retur, ulasan pembeli. */
#[Layout('components.layouts.app')]
class OrderShow extends Component
{
    use RunsActions;

    public CustomerOrder $order;

    public string $courier = '';

    public string $tracking = '';

    public string $note = '';

    public string $cancelReason = '';

    public int $buyerRating = 5;

    public string $buyerComment = '';

    public function mount(CustomerOrder $order): void
    {
        abort_unless($order->artist_profile_id === Auth::user()->artistProfile->id, 404);
        $this->order = $order;
        $this->courier = (string) $order->courier;
    }

    private function svc(): OrderService
    {
        return app(OrderService::class);
    }

    private function done(): void
    {
        $this->order = $this->order->fresh();
        $this->reset('note', 'cancelReason');
    }

    public function production(string $status): void
    {
        $this->attempt(fn () => $this->svc()->setProductionStatus($this->order, $status), 'Status produksi diperbarui.');
        $this->done();
    }

    public function completeProduction(): void
    {
        $this->attempt(fn () => $this->svc()->completeProduction($this->order), 'Produksi ditandai selesai.');
        $this->done();
    }

    public function ship(): void
    {
        $this->validate(['courier' => 'required|string|max:40', 'tracking' => 'required|string|min:4|max:60'], [], ['courier' => 'kurir', 'tracking' => 'nomor resi']);
        $this->attempt(fn () => $this->svc()->ship($this->order, $this->courier, $this->tracking), 'Resi tersimpan, pembeli dikabari.');
        $this->done();
    }

    public function readyForPickup(): void
    {
        $this->attempt(fn () => $this->svc()->readyForPickup($this->order), 'Pesanan siap diambil — QR dikirim ke pembeli.');
        $this->done();
    }

    public function cancelByArtist(): void
    {
        $this->validate(['cancelReason' => 'required|string|min:5|max:500'], [], ['cancelReason' => 'alasan']);
        $this->attempt(fn () => app(CancellationService::class)->cancelByArtist($this->order, Auth::user(), $this->cancelReason), 'Pesanan dibatalkan & dana dikembalikan ke pembeli.');
        $this->done();
    }

    public function decideCancel(int $caseId, bool $approve): void
    {
        $case = Cancellation::where('customer_order_id', $this->order->id)->findOrFail($caseId);
        if (! $approve) {
            $this->validate(['note' => 'required|string|min:3|max:500'], [], ['note' => 'tanggapan']);
        }
        $this->attempt(fn () => $approve
            ? app(CancellationService::class)->approve($case, Auth::user(), $this->note ?: null)
            : app(CancellationService::class)->reject($case, Auth::user(), $this->note), $approve ? 'Pembatalan disetujui, dana dikembalikan.' : 'Pengajuan ditolak.');
        $this->done();
    }

    public function reviewReturn(int $id, bool $approve): void
    {
        $this->validate(['note' => 'required|string|min:3|max:500'], [], ['note' => 'catatan']);
        $ret = OrderReturn::where('customer_order_id', $this->order->id)->findOrFail($id);
        $this->attempt(fn () => app(ReturnService::class)->review($ret, Auth::user(), $approve, $this->note), $approve ? 'Retur disetujui — tunggu barang dikirim balik.' : 'Retur ditolak.');
        $this->done();
    }

    public function inspectReturn(int $id, bool $ok): void
    {
        $this->validate(['note' => 'required|string|min:3|max:500'], [], ['note' => 'catatan']);
        $ret = OrderReturn::where('customer_order_id', $this->order->id)->findOrFail($id);
        $this->attempt(fn () => app(ReturnService::class)->inspect($ret, Auth::user(), $ok, $this->note), $ok ? 'Barang retur diterima — refund diproses.' : 'Retur ditolak setelah pemeriksaan.');
        $this->done();
    }

    public function reviewBuyer(): void
    {
        $this->validate(['buyerRating' => 'required|integer|min:1|max:5', 'buyerComment' => 'nullable|string|max:1000']);
        $this->attempt(fn () => app(ReviewService::class)->submitArtist($this->order, Auth::user(), $this->buyerRating, $this->buyerComment), 'Ulasan untuk pembeli tersimpan.');
        $this->done();
    }

    public function render()
    {
        $o = $this->order->load([
            'items.product.primaryImage', 'items.batch', 'artistProfile', 'buyer', 'address', 'booth.event', 'customRequest.merchandiseType',
            'payments' => fn ($q) => $q->orderBy('id'), 'pickup', 'cancellations.requester', 'returns.photos', 'reviews', 'productReviews',
        ]);

        return view('livewire.studio.order-show', [
            'o'           => $o,
            'ready'       => $this->svc()->isReadyToShip($o),
            'openCase'    => app(CancellationService::class)->openCase($o),
            'activeReturn'=> app(ReturnService::class)->activeReturn($o),
            'canReview'   => app(ReviewService::class)->artistCanReview($o, Auth::user()) && ! $o->reviews->firstWhere('reviewer_id', Auth::id()),
            'myReview'    => $o->reviews->firstWhere('direction', 'artist_to_buyer'),
            'buyerReview' => $o->reviews->firstWhere('direction', 'buyer_to_artist'),
            'canCancel'   => in_array($o->order_status, ['paid', 'awaiting_production', 'in_production', 'production_completed', 'awaiting_settlement'], true),
        ])->title('Pesanan ' . $o->order_code);
    }
}
