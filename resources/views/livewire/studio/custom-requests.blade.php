<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <x-studio-nav />
    <x-page-title title="Permintaan custom" subtitle="Beri penawaran harga & estimasi. Pembeli memilih satu artis lalu membayar penuh; pesanan masuk ke menu Pesanan." />

    <div class="mb-4 flex gap-1.5">
        <button type="button" wire:click="$set('tab', 'terbuka')" @class(['ms-tab', 'ms-tab-active' => $tab === 'terbuka'])>Terbuka ({{ $open->count() }})</button>
        <button type="button" wire:click="$set('tab', 'penawaran')" @class(['ms-tab', 'ms-tab-active' => $tab === 'penawaran'])>Penawaranku ({{ $offers->count() }})</button>
    </div>

    @if ($tab === 'terbuka')
        <div class="grid gap-4 md:grid-cols-2">
            @forelse ($open as $r)
                <a href="{{ route('custom.show', $r) }}" wire:navigate class="ms-card block p-5 hover:border-brand-300" wire:key="r-{{ $r->id }}">
                    <div class="flex items-center gap-2">
                        <p class="font-bold text-ink-900">{{ $r->design_character }}</p>
                        @if ($r->mine)<span class="ms-chip ms-chip-ready ml-auto">Sudah kamu tawar</span>@endif
                    </div>
                    <p class="text-sm text-ink-500">{{ $r->merchandiseType?->type_name }} · {{ $r->quantity }} pcs · budget <x-rupiah :value="$r->budget_price" /></p>
                    <p class="mt-2 line-clamp-2 text-sm text-ink-600">{{ $r->specification }}</p>
                    <p class="mt-2 text-xs text-ink-400">{{ $r->buyer->name }} · {{ $r->created_at->diffForHumans() }} · {{ $r->offers_count }} penawaran</p>
                </a>
            @empty
                <x-empty-state icon="sparkles" title="Belum ada permintaan custom terbuka" class="md:col-span-2" />
            @endforelse
        </div>
    @else
        <div class="ms-card divide-y divide-ink-100">
            @forelse ($offers as $of)
                <a href="{{ route('custom.show', $of->custom_request_id) }}" wire:navigate class="flex flex-wrap items-center gap-3 px-5 py-4 hover:bg-brand-50" wire:key="of-{{ $of->id }}">
                    <div class="min-w-0 flex-1">
                        <p class="font-bold text-ink-900">{{ $of->request->design_character }} · {{ $of->request->merchandiseType?->type_name }}</p>
                        <p class="text-sm text-ink-500"><x-rupiah :value="$of->offer_price" /> · {{ $of->estimated_days }} hari · {{ $of->created_at->translatedFormat('d M Y') }}</p>
                    </div>
                    @if ($of->request->order && $of->offer_status === 'selected')<span class="ms-chip ms-chip-po">{{ $of->request->order->order_code }}</span>@endif
                    <span class="ms-chip ms-chip-muted">{{ $of->status_label }}</span>
                </a>
            @empty
                <p class="px-5 py-10 text-center text-sm text-ink-500">Belum ada penawaran.</p>
            @endforelse
        </div>
    @endif
</div>
