<?php

namespace App\Livewire\Studio;

use App\Models\EventPickup;
use App\Services\OrderService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/** Scan QR pick-up di booth (kamera HP/laptop via jsQR, atau ketik kode manual). */
#[Layout('components.layouts.app')]
#[Title('Scan QR pick-up')]
class Scan extends Component
{
    public string $code = '';

    public ?array $result = null;

    public function scan(?string $code = null): void
    {
        $code = trim($code ?? $this->code);
        if ($code === '') {
            return;
        }

        try {
            $order = app(OrderService::class)->scanPickup(Auth::user()->artistProfile, $code);
            $order->load(['buyer', 'items.product']);
            $this->result = ['ok' => true, 'title' => 'Valid — pesanan selesai', 'code' => $order->order_code, 'buyer' => $order->buyer->name,
                'items' => $order->items->map(fn ($i) => $i->quantity . '× ' . $i->product?->product_name)->implode(', ')];
            $this->dispatch('toast', message: "{$order->order_code} diambil — serahkan barang ke {$order->buyer->name}.");
        } catch (ValidationException $e) {
            $this->result = ['ok' => false, 'title' => $e->errors()['action'][0] ?? 'QR tidak valid.', 'code' => $code];
        }

        $this->code = '';
    }

    public function render()
    {
        $profile = Auth::user()->artistProfile;

        return view('livewire.studio.scan', [
            'waiting' => EventPickup::with(['order.buyer', 'booth.event'])->where('pickup_status', 'not_picked_up')
                ->whereHas('booth', fn ($q) => $q->where('artist_profile_id', $profile->id))->latest('id')->take(30)->get(),
            'recent'  => EventPickup::with(['order.buyer'])->where('pickup_status', 'picked_up')
                ->whereHas('booth', fn ($q) => $q->where('artist_profile_id', $profile->id))->latest('picked_up_at')->take(10)->get(),
        ]);
    }
}
