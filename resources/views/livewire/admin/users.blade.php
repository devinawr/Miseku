<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <x-admin-nav />
    <x-page-title title="Pengguna">
        <select wire:model.live="role" class="ms-select w-40"><option value="">Semua peran</option><option value="buyer">Pembeli</option><option value="artist">Artis</option><option value="admin">Admin</option></select>
        <input type="search" wire:model.live.debounce.300ms="q" class="ms-input w-64" placeholder="Cari nama / email…">
    </x-page-title>
    <div class="ms-card overflow-x-auto">
        <table class="w-full min-w-[760px] text-sm">
            <thead class="bg-ink-50 text-left text-xs font-bold text-ink-500 uppercase"><tr><th class="px-4 py-3">Nama</th><th class="px-4 py-3">Peran</th><th class="px-4 py-3">Rating</th><th class="px-4 py-3">Pesanan</th><th class="px-4 py-3">Status</th><th></th></tr></thead>
            <tbody class="divide-y divide-ink-100">
                @foreach ($users as $u)
                    <tr wire:key="u-{{ $u->id }}">
                        <td class="px-4 py-3"><b>{{ $u->name }}</b><br><span class="text-xs text-ink-500">{{ $u->email }}</span></td>
                        <td class="px-4 py-3">{{ ['admin' => 'Admin', 'artist' => 'Artis', 'buyer' => 'Pembeli'][$u->role] }}@if ($u->artistProfile)<br><a href="{{ route('artist.shop', $u->artistProfile) }}" wire:navigate class="ms-link text-xs">{{ $u->artistProfile->shop_name }}</a>@endif</td>
                        <td class="px-4 py-3 text-xs">
                            Pembeli ★ {{ number_format((float) $u->rating_as_buyer, 2) }} ({{ $u->rating_count_as_buyer }})
                            @if ($u->artistProfile)<br>Toko ★ {{ number_format((float) $u->artistProfile->rating, 2) }} ({{ $u->artistProfile->rating_count }})@endif
                        </td>
                        <td class="px-4 py-3">{{ $u->orders_count }}</td>
                        <td class="px-4 py-3"><span class="ms-chip {{ $u->is_active ? 'ms-chip-ready' : 'bg-red-50 text-red-600' }}">{{ $u->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                        <td class="px-4 py-3 text-right">@if ($u->id !== auth()->id())<button type="button" wire:click="toggle({{ $u->id }})" wire:confirm="Ubah status akun {{ $u->name }}?" class="ms-btn-ghost !py-1.5 text-xs">{{ $u->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>@endif</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-5">{{ $users->links('components.pagination') }}</div>
</div>
