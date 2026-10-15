<?php

namespace App\Livewire;

use App\Livewire\Concerns\HasPhotoAttachments;
use App\Livewire\Concerns\RunsActions;
use App\Models\CustomerOrder;
use App\Models\CustomRequest;
use App\Models\Product;
use App\Models\User;
use App\Services\ChatService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Chat 1:1 pembeli ↔ artis. Konteks opsional (produk / pesanan / custom) ikut tersimpan
 * di chat_message supaya artis tahu pesan ini tentang apa.
 */
#[Layout('components.layouts.app')]
#[Title('Chat')]
class Chat extends Component
{
    use RunsActions, WithFileUploads, HasPhotoAttachments;

    #[Url(as: 'with')]
    public ?int $partnerId = null;

    #[Url(as: 'produk')]
    public ?int $productId = null;

    #[Url(as: 'pesanan')]
    public ?int $orderId = null;

    #[Url(as: 'custom')]
    public ?int $customId = null;

    public string $body = '';

    public function select(int $id): void
    {
        $this->partnerId = $id;
        $this->productId = $this->orderId = $this->customId = null;
    }

    public function clearContext(): void
    {
        $this->productId = $this->orderId = $this->customId = null;
    }

    public function send(): void
    {
        $this->validate(
            ['body' => (count($this->photos) ? 'nullable' : 'required') . '|string|max:2000'] + $this->photoRules(),
            $this->photoMessages() + ['body.required' => 'Tulis pesan atau lampirkan foto.'],
            ['body' => 'pesan']
        );
        abort_unless($this->partnerId, 422);

        $ok = $this->attempt(function () {
            $msg = app(ChatService::class)->send(Auth::user(), $this->partnerId, (string) $this->body, [
                'product' => $this->productId, 'order' => $this->orderId, 'custom' => $this->customId,
            ], count($this->photos) > 0);
            $this->storePhotos('chat_message', $msg->id, 'chat');

            return $msg;
        });

        if ($ok) {
            $this->body = '';
            $this->clearContext();
        }
    }

    public function render()
    {
        $me = Auth::user();
        $service = app(ChatService::class);
        $partner = $this->partnerId ? User::with('artistProfile')->find($this->partnerId) : null;

        return view('livewire.chat', [
            'me'            => $me,
            'conversations' => $service->conversations($me),
            'partner'       => $partner,
            'thread'        => $partner ? $service->thread($me, $partner->id) : collect(),
            'ctxProduct'    => $this->productId ? Product::find($this->productId) : null,
            'ctxOrder'      => $this->orderId ? CustomerOrder::where(fn ($q) => $q->where('buyer_id', $me->id)->orWhereHas('artistProfile', fn ($a) => $a->where('user_id', $me->id)))->find($this->orderId) : null,
            'ctxCustom'     => $this->customId ? CustomRequest::find($this->customId) : null,
        ]);
    }
}
