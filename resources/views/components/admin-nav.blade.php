{{-- Navigasi panel admin --}}
@php
    $items = [
        ['admin.dashboard', 'Ringkasan', 'chart'],
        ['admin.approvals', 'Persetujuan', 'check-circle'],
        ['admin.users', 'Pengguna', 'users'],
        ['admin.products', 'Produk', 'box'],
        ['admin.transactions', 'Transaksi & refund', 'receipt'],
        ['admin.withdrawals', 'Penarikan dana', 'wallet'],
        ['admin.events', 'Event', 'calendar'],
        ['admin.themes', 'Tema fandom', 'palette'],
        ['admin.settings', 'Pengaturan & otomasi', 'settings'],
    ];
@endphp
<nav class="ms-scroll-x mb-6 flex gap-1.5 rounded-2xl border border-ink-200 bg-white p-1.5">
    @foreach ($items as [$route, $label, $icon])
        <a href="{{ route($route) }}" wire:navigate
           class="flex shrink-0 items-center gap-1.5 rounded-xl px-3.5 py-2 text-sm font-bold transition {{ \App\Support\CurrentRoute::is($route) ? 'bg-ink-900 text-white' : 'text-ink-600 hover:bg-ink-100 hover:text-ink-900' }}">
            <x-icon :name="$icon" class="h-4.5 w-4.5" /> {{ $label }}
        </a>
    @endforeach
</nav>
