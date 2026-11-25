<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <x-studio-nav />
    <x-page-title title="Penjualan OTS di booth" subtitle="Stok asli ready stock berkurang dan dicatat menurut produk dan event. Tidak membuat pesanan atau pembayaran." />

    @if ($booths->isEmpty())
        <x-empty-state icon="ticket" title="Tidak ada event yang sedang berlangsung" description="Penjualan OTS hanya bisa saat event berlangsung dan kamu terdaftar punya booth.">
            <a href="{{ route('studio.events') }}" wire:navigate class="ms-btn-primary">Atur booth</a>
        </x-empty-state>
    @else
        <div class="grid items-start gap-6 lg:grid-cols-12">
            <section class="ms-card p-5 lg:col-span-8">
                <label class="block max-w-md"><span class="ms-label">Booth</span>
                    <select wire:model.live="boothId" class="ms-select">@foreach ($booths as $b)<option value="{{ $b->id }}">{{ $b->label }}</option>@endforeach</select>
                </label>
                <ul class="mt-4 divide-y divide-ink-100">
                    @foreach ($products as $p)
                        @if ($p->variants->isNotEmpty())
                            @foreach ($p->variants as $v)
                                <li class="flex items-center gap-3 py-3" wire:key="ot-v-{{ $v->id }}">
                                    <img src="{{ $v->image_url }}" class="h-12 w-12 rounded-lg object-cover" alt="">
                                    <div class="min-w-0 flex-1"><p class="truncate font-bold text-ink-900">{{ $p->product_name }} · {{ $v->name }}</p><p class="text-xs text-ink-500">Stok {{ $v->stock }} pcs</p></div>
                                    <input type="number" min="0" max="{{ $v->stock }}" wire:model.live.debounce.300ms="variantQty.{{ $v->id }}" class="ms-input w-20 text-center" placeholder="0" @disabled($v->stock < 1)>
                                </li>
                            @endforeach
                        @else
                            <li class="flex items-center gap-3 py-3" wire:key="ot-{{ $p->id }}">
                                <img src="{{ $p->cover_url }}" class="h-12 w-12 rounded-lg object-cover" alt="">
                                <div class="min-w-0 flex-1"><p class="truncate font-bold text-ink-900">{{ $p->product_name }}</p><p class="text-xs text-ink-500">Stok {{ $p->stock }} pcs</p></div>
                                <input type="number" min="0" max="{{ $p->stock }}" wire:model.live.debounce.300ms="qty.{{ $p->id }}" class="ms-input w-20 text-center" placeholder="0" @disabled($p->stock < 1)>
                            </li>
                        @endif
                    @endforeach
                </ul>
            </section>
            <aside class="ms-card space-y-3 p-5 lg:col-span-4">
                <p class="text-sm text-ink-500">Total pengurangan</p>
                <p class="text-3xl font-extrabold text-ink-900">{{ $total }} pcs</p>
                <button type="button" wire:click="sell" wire:confirm="Kurangi stok asli produk yang dipilih?" class="ms-btn-primary w-full" @disabled($total <= 0)>Catat pengurangan stok</button>
            </aside>
        </div>
    @endif

    <h2 class="ms-section-title mt-8 mb-3">Riwayat OTS</h2>
    <div class="ms-card divide-y divide-ink-100">
        @foreach ($stockLogs as $log)
            <div class="flex flex-wrap justify-between gap-2 px-5 py-3 text-sm" wire:key="stock-log-{{ $log->id }}">
                <span><b>{{ $log->quantity }}× {{ $log->product_name }}@if($log->variant_name) · {{ $log->variant_name }}@endif</b> · {{ $log->event_name }} @if($log->booth_name) · Booth {{ $log->booth_name }} @endif</span>
                <span>{{ $log->created_at->translatedFormat('d M Y H:i') }}</span>
            </div>
        @endforeach
        @forelse ($sales as $s)
            <div class="flex flex-wrap justify-between gap-2 px-5 py-3 text-sm" wire:key="sale-{{ $s->id }}">
                <span><b class="font-mono">{{ $s->order_code }}</b> · {{ $s->items->map(fn ($i) => $i->quantity . '× ' . $i->product?->product_name)->implode(', ') }}</span>
                <span class="font-bold"><x-rupiah :value="$s->total_amount" /> <span class="font-normal text-ink-500">· {{ $s->created_at->translatedFormat('d M H:i') }}</span></span>
            </div>
        @empty
            @if ($stockLogs->isEmpty()) <p class="px-5 py-6 text-center text-sm text-ink-500">Belum ada penjualan OTS.</p> @endif
        @endforelse
    </div>
</div>
