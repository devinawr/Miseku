<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <x-studio-nav />
    <x-page-title title="Pesanan toko" subtitle="Proses pesanan: produksi, kirim (maks. {{ config('miseku.shipping_days') }} hari), siapkan pick-up, tanggapi pembatalan & retur.">
        <input type="search" wire:model.live.debounce.300ms="q" class="ms-input w-64" placeholder="Cari kode / pembeli…">
    </x-page-title>

    <div class="ms-scroll-x mb-4 flex gap-1.5">
        @foreach (\App\Livewire\Studio\Orders::TABS as $k => [$label])
            <button type="button" wire:click="$set('tab', '{{ $k }}')" @class(['ms-tab', 'ms-tab-active' => $tab === $k])>{{ $label }}</button>
        @endforeach
    </div>

    <div class="ms-card overflow-x-auto">
        <table class="w-full min-w-[720px] text-sm">
            <thead class="bg-ink-50 text-left text-xs font-bold tracking-wide text-ink-500 uppercase">
                <tr><th class="px-4 py-3">Pesanan</th><th class="px-4 py-3">Pembeli</th><th class="px-4 py-3">Jenis</th><th class="px-4 py-3">Total</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Tenggat</th></tr>
            </thead>
            <tbody class="divide-y divide-ink-100">
                @forelse ($orders as $o)
                    <tr wire:key="so-{{ $o->id }}" class="hover:bg-brand-50/40">
                        <td class="px-4 py-3">
                            <a href="{{ route('studio.orders.show', $o) }}" wire:navigate class="font-mono text-xs font-bold text-brand-700 hover:underline">{{ $o->order_code }}</a>
                            <p class="max-w-56 truncate text-ink-600">{{ $o->order_type === 'custom' ? 'Custom: ' . $o->customRequest?->design_character : $o->items->map(fn ($i) => $i->quantity . '× ' . $i->product?->product_name)->implode(', ') }}</p>
                            @if ($o->cancellations->whereIn('cancellation_status', ['submitted', 'in_discussion'])->isNotEmpty())<span class="ms-chip bg-red-50 text-red-600">Pengajuan batal</span>@endif
                            @if ($o->returns->whereIn('return_status', ['submitted', 'processing'])->isNotEmpty())<span class="ms-chip bg-red-50 text-red-600">Retur</span>@endif
                        </td>
                        <td class="px-4 py-3">{{ $o->buyer->name }}</td>
                        <td class="px-4 py-3">{{ $o->type_label }}{{ $o->isDp() ? ' · DP' : '' }}<br><span class="text-xs text-ink-500">{{ $o->isPickup() ? 'Pick-up event' : 'Kurir' }}</span></td>
                        <td class="px-4 py-3 font-bold"><x-rupiah :value="$o->total_amount" /></td>
                        <td class="px-4 py-3"><x-order-status :order="$o" /></td>
                        <td class="px-4 py-3 text-xs">
                            @if ($o->shipping_deadline && in_array($o->order_status, ['paid', 'production_completed'], true))
                                <span class="{{ $o->shipping_deadline->lt(now()->addDays(3)) ? 'font-bold text-red-600' : 'text-ink-600' }}">Kirim ≤ {{ $o->shipping_deadline->translatedFormat('d M') }}</span>
                            @elseif ($o->settlement_deadline && $o->order_status === 'awaiting_settlement')
                                Lunas ≤ {{ $o->settlement_deadline->translatedFormat('d M') }}
                            @else — @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-ink-500">Tidak ada pesanan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-5">{{ $orders->links('components.pagination') }}</div>
</div>
