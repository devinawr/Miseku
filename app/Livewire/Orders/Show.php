<?php

namespace App\Livewire\Orders;

use App\Livewire\Concerns\RunsActions;
use App\Models\CustomerOrder;
use App\Models\Payment;
use App\Services\CancellationService;
use App\Services\OrderService;
use App\Services\PaymentService;
use App\Services\ReturnService;
use App\Services\ReviewService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Detail pesanan untuk pembeli (admin juga bisa melihat, hanya baca):
 * bayar/pelunasan, QR pick-up, konfirmasi diterima, pembatalan, retur, ulasan.
 */
#[Layout('components.layouts.app')]
class Show extends Component
{
    use RunsActions, WithFileUploads;

    public CustomerOrder $order;

    public string $cancelReason = '';

    public string $returnReason = '';

    public array $returnPhotos = [];

    public int $shopRating = 5;

    public string $shopComment = '';

    /** product_id => ['rating' => int, 'comment' => string] */
    public array $productReviews = [];

    public function mount(CustomerOrder $order): void
    {
        $me = Auth::user();
        abort_unless($order->buyer_id === $me->id || $me->isAdmin(), 404);
        $this->order = $order;

        foreach ($order->items as $item) {
            $this->productReviews[$item->product_id] = ['rating' => 5, 'comment' => ''];
        }
    }

    private function mine(): bool
    {
        return $this->order->buyer_id === Auth::id();
    }

    private function reload(): void
    {
        $this->order = $this->order->fresh();
    }

    public function cancelUnpaid(): void
    {
        abort_unless($this->mine(), 403);
        $this->attempt(fn () => app(OrderService::class)->cancelUnpaid($this->order, 'Dibatalkan oleh pembeli sebelum membayar.'), 'Pesanan dibatalkan.');
        $this->reload();
    }

    public function requestCancel(): void
    {
        abort_unless($this->mine(), 403);
        $this->validate(['cancelReason' => 'required|string|min:5|max:500'], [], ['cancelReason' => 'alasan']);

        $case = $this->attempt(fn () => app(CancellationService::class)->request($this->order, Auth::user(), $this->cancelReason));
        if ($case) {
            $this->dispatch('toast', message: $case->cancellation_status === 'rejected' ? 'Pengajuan ditolak otomatis: ' . $case->artist_response : 'Pengajuan pembatalan dikirim ke artis.', tone: $case->cancellation_status === 'rejected' ? 'error' : 'ok');
            $this->cancelReason = '';
        }
        $this->reload();
    }

    public function confirmReceived(): void
    {
        abort_unless($this->mine(), 403);
        $this->attempt(fn () => app(OrderService::class)->confirmReceived($this->order, Auth::user()), 'Terima kasih! Pesanan selesai.');
        $this->reload();
    }

    public function reissueSettlement(): void
    {
        abort_unless($this->mine(), 403);
        $o = $this->order;
        if ($o->order_status !== 'awaiting_settlement' || $o->pendingPayment()) {
            return;
        }
        $last = $o->payments()->where('payment_type', 'settlement')->latest('id')->first();
        $new = $last ? app(PaymentService::class)->reissue($last) : app(PaymentService::class)->createInvoice($o, 'settlement', (float) $o->total_amount - $o->paidAmount());
        $this->redirectRoute('payments.show', ['payment' => $new->id]);
    }

    public function requestReturn(): void
    {
        abort_unless($this->mine(), 403);
        $this->validate([
            'returnReason'   => 'required|string|min:10|max:1000',
            'returnPhotos'   => 'required|array|min:1|max:5',
            'returnPhotos.*' => 'image|max:4096',
        ], [], ['returnReason' => 'alasan retur', 'returnPhotos' => 'foto bukti']);

        $urls = collect($this->returnPhotos)->map(fn ($f) => '/uploads/' . $f->store('returns', 'uploads'))->all();
        $ret = $this->attempt(fn () => app(ReturnService::class)->request($this->order, Auth::user(), $this->returnReason, $urls));
        if ($ret) {
            $this->dispatch('toast', message: $ret->return_status === 'rejected' ? 'Retur ditolak otomatis (di luar ketentuan).' : 'Pengajuan retur dikirim ke artis.');
            $this->reset('returnReason', 'returnPhotos');
        }
        $this->reload();
    }

    public function submitReview(): void
    {
        abort_unless($this->mine(), 403);
        $this->validate([
            'shopRating'               => 'required|integer|min:1|max:5',
            'shopComment'              => 'nullable|string|max:1000',
            'productReviews.*.rating'  => 'nullable|integer|min:1|max:5',
            'productReviews.*.comment' => 'nullable|string|max:1000',
        ]);

        $this->attempt(fn () => app(ReviewService::class)->submitBuyer($this->order, Auth::user(), $this->shopRating, $this->shopComment, $this->productReviews), 'Ulasan terkirim. Terima kasih!');
        $this->reload();
    }

    public function render()
    {
        $o = $this->order->load([
            'items.product.primaryImage', 'items.batch', 'artistProfile.user', 'address', 'booth.event', 'customRequest.merchandiseType',
            'payments' => fn ($q) => $q->orderBy('id'), 'pickup', 'cancellations.requester', 'returns.photos', 'reviews', 'productReviews',
        ]);

        $returns = app(ReturnService::class);

        return view('livewire.orders.show', [
            'o'              => $o,
            'mine'           => $this->mine(),
            'pending'        => $o->payments->firstWhere(fn (Payment $p) => $p->payment_status === 'pending' && $p->payment_type !== 'refund'),
            'cancelProblem'  => app(CancellationService::class)->eligibility($o),
            'openCase'       => app(CancellationService::class)->openCase($o),
            'returnProblem'  => $returns->eligibility($o),
            'returnDeadline' => $returns->deadline($o),
            'activeReturn'   => $returns->activeReturn($o),
            'canReview'      => $this->mine() && app(ReviewService::class)->buyerCanReview($o, Auth::user()) && ! $o->reviews->firstWhere('reviewer_id', Auth::id()),
            'myReview'       => $o->reviews->firstWhere('direction', 'buyer_to_artist'),
            'artistReview'   => $o->reviews->firstWhere('direction', 'artist_to_buyer'),
        ])->title('Pesanan ' . $o->order_code);
    }
}
