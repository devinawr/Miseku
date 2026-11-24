<div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
    <x-page-title title="Pesanan saya" subtitle="Lacak status pembayaran, produksi, pengiriman, dan pengambilan di event." />

    <div class="ms-scroll-x mb-5 flex gap-1.5">
        @foreach (\App\Livewire\Orders\Index::TABS as $key => [$label, $st])
            @php $n = $st ? collect($st)->sum(fn ($s) => $counts[$s] ?? 0) : $counts->sum(); @endphp
            <button type="button" wire:click="$set('tab', '{{ $key }}')" @class(['ms-tab', 'ms-tab-active' => $tab === $key])>
                {{ $label }} @if ($n)<span class="ml-1 rounded-full bg-white/70 px-1.5 text-xs">{{ $n }}</span>@endif
            </button>
        @endforeach
    </div>

    <div class="space-y-3">
        @forelse ($orders as $order)
            @php
                $first = $order->items->first();
                $pending = $order->pendingPayment();
                $statusMessage = match ($order->order_status) {
                    'awaiting_payment' => 'Menunggu pembayaran. Selesaikan pembayaran agar pesanan dapat diproses.',
                    'awaiting_settlement' => 'Menunggu pelunasan. Buka detail pesanan untuk melihat tagihan.',
                    'paid' => $order->order_type === 'pre_order' ? 'Pembayaran diterima. Menunggu pre-order ditutup.' : 'Pembayaran diterima. Pesanan menunggu diproses.',
                    'awaiting_production' => 'Pesanan menunggu proses produksi.',
                    'in_production' => 'Pesanan sedang diproduksi oleh artis.',
                    'production_completed' => $order->isDp() && ! $order->isFullyPaid() ? 'Produksi selesai. Lihat detail untuk informasi pelunasan.' : ($order->isPickup() ? 'Produksi selesai. Pesanan menunggu disiapkan untuk pengambilan.' : 'Produksi selesai. Pesanan menunggu pengiriman.'),
                    'shipped' => 'Paket sudah dikirim' . ($order->courier ? ' (' . $order->courier . ')' : '') . '. ' . ($order->delivered_at && ! $order->auto_complete_hold ? 'Jika ada kendala, segera chat CS. Pesanan dapat selesai otomatis setelah ' . $order->delivered_at->copy()->addDays(config('miseku.auto_complete_days'))->translatedFormat('d M Y H:i') . '.' : 'Jika ada kendala, silakan chat CS. Waktu tiba belum diverifikasi atau penyelesaian sedang ditunda.'),
                    'ready_for_pickup' => 'Pesanan siap diambil di booth. Lihat detail pesanan untuk informasi pengambilan.',
                    'completed' => $order->returns->whereIn('return_status', ['submitted', 'processing', 'approved', 'completed'])->isNotEmpty() ? 'Pesanan selesai. Pengajuan retur sudah tercatat; lihat statusnya di detail pesanan. Jika ada kendala, silakan chat CS.' : ($order->order_type !== 'ots_event' && $order->completed_at && now()->lte($order->completed_at->copy()->addDays(config('miseku.return_days'))) ? 'Pesanan selesai. Ajukan retur dengan bukti paling lambat ' . $order->completed_at->copy()->addDays(config('miseku.return_days'))->translatedFormat('d M Y H:i') . '. Jika ada kendala, silakan chat CS.' : 'Pesanan selesai. Jika ada kendala, silakan chat CS.'),
                    'cancelled' => 'Pesanan dibatalkan. Lihat detail pesanan untuk informasi selengkapnya.',
                    'failed' => 'Pesanan gagal. Lihat detail pesanan untuk informasi selengkapnya.',
                    default => 'Lihat detail pesanan untuk informasi terbaru.',
                };
            @endphp
            <article wire:key="o-{{ $order->id }}" class="ms-card overflow-hidden">
            <a href="{{ route('orders.show', $order) }}" wire:navigate class="flex gap-4 p-4 transition hover:bg-brand-50/40">
                <img src="{{ $first?->product?->cover_url ?? '/img/logo-mark.png' }}" class="h-20 w-20 shrink-0 rounded-xl bg-ink-100 object-cover" alt="">
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="font-mono text-xs text-ink-500">{{ $order->order_code }}</span>
                        <span class="ms-chip ms-chip-muted">{{ $order->type_label }}</span>
                        @if ($order->isDp())<span class="ms-chip ms-chip-po">DP</span>@endif
                        <x-order-status :order="$order" class="ml-auto" />
                    </div>
                    <p class="mt-1 truncate font-bold text-ink-900">
                        @if ($order->order_type === 'custom') Custom: {{ $order->customRequest?->design_character }}
                        @else {{ $first?->product?->product_name }} @if ($order->items->count() > 1)<span class="font-normal text-ink-500">+{{ $order->items->count() - 1 }} produk</span>@endif
                        @endif
                    </p>
                    <p class="text-sm text-ink-500">{{ $order->artistProfile->shop_name }} · {{ $order->created_at->translatedFormat('d M Y') }}</p>
                    <div class="mt-1 flex flex-wrap items-center justify-between gap-2">
                        <span class="font-extrabold text-ink-900"><x-rupiah :value="$order->total_amount" /></span>
                        @if ($pending)<span class="ms-chip bg-amber-100 text-amber-800">Tagihan {{ $pending->type_label }} <x-rupiah :value="$pending->amount" /></span>@endif
                    </div>
                </div>
            </a>
            <div class="border-t border-ink-100 bg-brand-50/40 px-4 py-3 text-sm text-ink-700">{{ $statusMessage }}</div>
            <div class="flex flex-wrap items-center justify-end gap-2 border-t border-ink-100 px-4 py-3">
                @if ($csAdmin)
                    <a href="{{ route('chat', ['with' => $csAdmin->id, 'pesanan' => $order->id]) }}" wire:navigate class="ms-btn-ghost !py-2 text-sm"><x-icon name="chat" class="h-4 w-4" /> Hubungi CS</a>
                @else
                    <span class="text-xs text-ink-500">CS belum tersedia</span>
                @endif
                <a href="{{ route('orders.show', $order) }}" wire:navigate class="ms-btn-primary !py-2 text-sm">Lihat detail pesanan</a>
            </div>
            </article>
        @empty
            <x-empty-state icon="receipt" title="Belum ada pesanan di sini" description="Pesanan yang kamu buat akan tampil di halaman ini.">
                <a href="{{ route('catalog') }}" wire:navigate class="ms-btn-primary">Belanja sekarang</a>
            </x-empty-state>
        @endforelse
    </div>

    <div class="mt-6">{{ $orders->links('components.pagination') }}</div>
</div>
