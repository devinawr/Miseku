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

    public array $refundBank = [];
    public array $refundAccountName = [];
    public array $refundAccountNumber = [];

    public array $returnPhotos = [];

    public int $shopRating = 5;

    public string $shopComment = '';

    public array $shopPhotos = [];

    /** product_id => daftar foto untuk ulasan produk itu */
    public array $productPhotos = [];

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

    public function continueLateCustom(): void
    {
        abort_unless($this->mine(), 403);
        $this->attempt(fn () => app(\App\Services\CustomDelayService::class)->accept($this->order, Auth::user()), 'Estimasi baru disetujui.');
        $this->reload();
    }

    public function cancelLateCustom(): void
    {
        abort_unless($this->mine(), 403);
        $this->attempt(fn () => app(\App\Services\CustomDelayService::class)->cancel($this->order, Auth::user()), 'Dibatalkan tanpa penalti. Refund diproses.');
        $this->reload();
    }

    public function submitRefundBank(int $id): void
    {
        abort_unless($this->mine(), 403);
        $this->validate([
            "refundBank.$id" => 'required|string|min:2|max:100',
            "refundAccountName.$id" => 'required|string|min:2|max:100',
            "refundAccountNumber.$id" => ['required', 'string', 'regex:/^[0-9]{6,20}$/'],
        ]);
        $payment = $this->order->payments()->findOrFail($id);
        $saved = $this->attempt(fn () => app(\App\Services\RefundBankAccountService::class)->submit($payment, Auth::user(), $this->refundBank[$id], $this->refundAccountName[$id], $this->refundAccountNumber[$id]), 'Rekening refund dikirim ke admin.');
        if ($saved) { unset($this->refundBank[$id], $this->refundAccountName[$id], $this->refundAccountNumber[$id]); }
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
            'shopPhotos'               => 'array|max:4',
            'shopPhotos.*'             => 'image|mimes:jpg,jpeg,png,webp|max:4096',
            'productPhotos'            => 'array',
            'productPhotos.*'          => 'array|max:4',
            'productPhotos.*.*'        => 'image|mimes:jpg,jpeg,png,webp|max:4096',
        ], [
            'shopPhotos.max' => 'Maksimal 4 foto untuk ulasan toko.',
            'productPhotos.*.max' => 'Maksimal 4 foto untuk setiap ulasan produk.',
            'shopPhotos.*.image' => 'Foto ulasan toko harus berupa gambar.',
            'shopPhotos.*.mimes' => 'Foto ulasan toko harus berformat JPG, PNG, atau WEBP.',
            'shopPhotos.*.max' => 'Setiap foto ulasan toko maksimal 4 MB.',
            'productPhotos.*.*.image' => 'Foto ulasan produk harus berupa gambar.',
            'productPhotos.*.*.mimes' => 'Foto ulasan produk harus berformat JPG, PNG, atau WEBP.',
            'productPhotos.*.*.max' => 'Setiap foto ulasan produk maksimal 4 MB.',
        ]);

        $this->attempt(fn () => app(ReviewService::class)->submitBuyer($this->order, Auth::user(), $this->shopRating, $this->shopComment, $this->productReviews, $this->shopPhotos, $this->productPhotos), 'Ulasan terkirim. Terima kasih!');
        $this->reload();
    }

    public function render()
    {
        $o = $this->order->load([
            'items.product.primaryImage', 'items.batch', 'artistProfile.user', 'address', 'booth.event', 'customRequest.merchandiseType',
            'payments' => fn ($q) => $q->with('refundBankAccount')->orderBy('id'), 'pickup', 'cancellations.requester', 'returns.photos', 'reviews.photos', 'productReviews.photos',
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
