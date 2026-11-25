<?php

namespace App\Livewire\Studio;

use App\Livewire\Concerns\RunsActions;
use App\Models\CustomerOrder;
use App\Models\EventBooth;
use App\Models\Product;
use App\Models\OtsStockLog;
use App\Services\OtsService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Penjualan on-the-spot di booth (BPMN 5). Stok produk ready stock berkurang,
 * disertai riwayat stok, tanpa membuat pesanan atau pembayaran.
 */
#[Layout('components.layouts.app')]
#[Title('Penjualan OTS')]
class Ots extends Component
{
    use RunsActions;

    public ?int $boothId = null;

    /** product_id => qty */
    public array $qty = [];
    public array $variantQty = [];


    public function mount(): void
    {
        $this->boothId = $this->booths()->first()?->id;
    }

    private function booths()
    {
        return EventBooth::with('event')->where('artist_profile_id', Auth::user()->artistProfile->id)
            ->whereHas('event', fn ($q) => $q->whereDate('start_date', '<=', today())->whereDate('end_date', '>=', today()))->get();
    }

    public function sell(): void
    {
        $booth = $this->booths()->firstWhere('id', $this->boothId);
        if (! $booth) {
            $this->dispatch('toast', message: 'Pilih booth event yang sedang berlangsung.', tone: 'error');

            return;
        }

        $count = $this->attempt(fn () => app(OtsService::class)->sell(Auth::user()->artistProfile, $booth, $this->qty, $this->variantQty));
        if ($count) {
            $this->qty = [];
            $this->variantQty = [];
            $this->dispatch('toast', message: "Stok berkurang {$count} pcs dan tercatat di riwayat OTS.");
        }
    }

    public function render()
    {
        $profileId = Auth::user()->artistProfile->id;
        $products = Product::with(['primaryImage', 'variants'])->where('artist_profile_id', $profileId)->where('availability_type', 'ready_stock')
            ->where('product_status', '!=', 'deleted')->orderBy('product_name')->get();
        $total = collect($this->qty)->merge($this->variantQty)->sum(fn ($q) => max(0, (int) $q));

        return view('livewire.studio.ots', [
            'booths'   => $this->booths(),
            'products' => $products,
            'total'    => $total,
            'stockLogs' => OtsStockLog::where('artist_profile_id', $profileId)->latest('id')->take(30)->get(),
            'sales'    => CustomerOrder::with('items.product', 'booth.event')->where('artist_profile_id', $profileId)->where('order_type', 'ots_event')->latest('id')->take(15)->get(),
        ]);
    }
}
