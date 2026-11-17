<?php

namespace App\Livewire\Studio;

use App\Models\CustomerOrder;
use App\Models\PoBatch;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/** Pesanan masuk ke toko artis (BPMN 2). */
#[Layout('components.layouts.app')]
#[Title('Pesanan toko')]
class Orders extends Component
{
    use WithPagination;

    public const TABS = [
        'tindakan' => ['Perlu tindakan', null],
        'proses'   => ['Diproses', ['paid', 'awaiting_production', 'in_production', 'production_completed', 'awaiting_settlement']],
        'kirim'    => ['Dikirim / pick-up', ['shipped', 'ready_for_pickup']],
        'selesai'  => ['Selesai', ['completed']],
        'batal'    => ['Batal / gagal', ['cancelled', 'failed']],
        'bayar'    => ['Belum dibayar', ['awaiting_payment']],
        'po'       => ['Pre-order (PO)', null],
        'semua'    => ['Semua', null],
    ];

    #[Url]
    public string $tab = 'tindakan';

    #[Url]
    public string $q = '';

    /** Filter pesanan PO per batch (dari tombol "Pesanan" di menu Batch PO). Kosong = semua PO. */
    #[Url]
    public ?int $batch = null;

    public function mount(): void
    {
        if ($this->batch) {
            $this->tab = 'po';
        }
    }

    public function updatedTab(): void
    {
        if ($this->tab !== 'po') {
            $this->batch = null;
        }
        $this->resetPage();
    }

    public function updatedBatch(): void
    {
        $this->batch = $this->batch ?: null;
        $this->resetPage();
    }

    public function updatedQ(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $profile = Auth::user()->artistProfile;
        $statuses = self::TABS[$this->tab][1] ?? null;

        $orders = CustomerOrder::where('artist_profile_id', $profile->id)
            ->where('order_type', '!=', 'ots_event')
            ->with(['buyer', 'items.product.primaryImage', 'items.batch', 'customRequest', 'cancellations', 'returns'])
            ->when($this->tab === 'po', fn ($w) => $w->where('order_type', 'pre_order')
                ->when($this->batch, fn ($x) => $x->whereHas('items', fn ($i) => $i->where('po_batch_id', $this->batch))))
            ->when($this->tab === 'tindakan', fn ($w) => $w->where(function ($x) {
                $x->where(fn ($r) => $r->where('order_type', 'ready_stock')->where('order_status', 'paid'))
                    ->orWhere(fn ($r) => $r->where('order_type', 'custom')->whereIn('order_status', ['paid', 'awaiting_production', 'in_production']))
                    ->orWhere('order_status', 'production_completed')
                    ->orWhere(fn ($r) => $r->where('order_type', 'pre_order')->where('order_status', 'awaiting_production'))
                    ->orWhereHas('cancellations', fn ($c) => $c->whereIn('cancellation_status', ['submitted', 'in_discussion']))
                    ->orWhereHas('returns', fn ($c) => $c->whereIn('return_status', ['submitted', 'processing']));
            }))
            ->when($statuses, fn ($w) => $w->whereIn('order_status', $statuses))
            ->when($this->q, fn ($w) => $w->where(fn ($x) => $x->where('order_code', 'like', "%{$this->q}%")->orWhereHas('buyer', fn ($b) => $b->where('name', 'like', "%{$this->q}%"))))
            ->orderByRaw('shipping_deadline IS NULL, shipping_deadline')->latest('created_at')->latest('id')
            ->paginate(15);

        // pilihan batch untuk filter di tab PO (hanya batch milik toko ini)
        $batches = $this->tab === 'po'
            ? PoBatch::whereHas('product', fn ($q) => $q->where('artist_profile_id', $profile->id))->with('product:id,product_name')->latest('id')->get()
            : collect();
        $currentBatch = $this->batch ? $batches->firstWhere('id', $this->batch) : null;

        return view('livewire.studio.orders', compact('orders', 'batches', 'currentBatch'));
    }
}
