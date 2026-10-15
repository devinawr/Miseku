{{-- Navigasi Studio artis (dipakai di semua halaman /studio) --}}
@php
    $items = [
        ['studio.dashboard', 'Produk', 'squares'],
        ['studio.orders', 'Pesanan', 'receipt'],
        ['studio.batches', 'Batch PO', 'clock'],
        ['studio.custom', 'Custom', 'sparkles'],
        ['studio.polls', 'Voting', 'chart'],
        ['studio.events', 'Event & booth', 'calendar'],
        ['studio.scan', 'Scan QR', 'qr'],
        ['studio.ots', 'Jual OTS', 'ticket'],
        ['studio.balance', 'Saldo', 'wallet'],
    ];
@endphp
<nav class="ms-scroll-x mb-6 flex gap-1.5 rounded-2xl border border-ink-200 bg-white p-1.5">
    {{-- Artis tetap pembeli: tombol kembali ke mode belanja --}}
    <a href="{{ route('home') }}" wire:navigate title="Kembali ke Beranda (mode pembeli)"
       class="flex shrink-0 items-center gap-1.5 rounded-xl px-3.5 py-2 text-sm font-bold text-ink-600 ring-1 ring-ink-200 transition hover:bg-brand-50 hover:text-brand-700">
        <x-icon name="home" class="h-4.5 w-4.5" /> Beranda
    </a>
    <span class="my-1.5 w-px shrink-0 bg-ink-200"></span>
    @foreach ($items as [$route, $label, $icon])
        @php $active = \App\Support\CurrentRoute::is($route) || ($route !== 'studio.dashboard' && \App\Support\CurrentRoute::is($route . '.*')) || ($route === 'studio.dashboard' && \App\Support\CurrentRoute::is('studio.product.*')); @endphp
        <a href="{{ route($route) }}" wire:navigate
           class="flex shrink-0 items-center gap-1.5 rounded-xl px-3.5 py-2 text-sm font-bold transition {{ $active ? 'bg-brand-600 text-white' : 'text-ink-600 hover:bg-brand-50 hover:text-brand-700' }}">
            <x-icon :name="$icon" class="h-4.5 w-4.5" /> {{ $label }}
        </a>
    @endforeach
</nav>
