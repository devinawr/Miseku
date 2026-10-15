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
            @php $first = $order->items->first(); $pending = $order->payments->firstWhere('payment_status', 'pending'); @endphp
            <a href="{{ route('orders.show', $order) }}" wire:navigate wire:key="o-{{ $order->id }}" class="ms-card flex gap-4 p-4 transition hover:border-brand-300">
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
        @empty
            <x-empty-state icon="receipt" title="Belum ada pesanan di sini" description="Pesanan yang kamu buat akan tampil di halaman ini.">
                <a href="{{ route('catalog') }}" wire:navigate class="ms-btn-primary">Belanja sekarang</a>
            </x-empty-state>
        @endforelse
    </div>

    <div class="mt-6">{{ $orders->links('components.pagination') }}</div>
</div>
