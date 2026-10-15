<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <x-admin-nav />
    <x-page-title title="Event" subtitle="Event offline tempat artis membuka booth (pick-up pesanan & penjualan OTS).">
        <button type="button" wire:click="edit" class="ms-btn-primary"><x-icon name="plus" class="h-4.5 w-4.5" /> Event baru</button>
    </x-page-title>

    @if ($editingId !== null)
        <form wire:submit="save" class="ms-card mb-6 grid gap-4 p-5 md:grid-cols-2">
            <label class="block"><span class="ms-label">Nama event</span><input type="text" wire:model="event_name" class="ms-input">@error('event_name')<p class="ms-error">{{ $message }}</p>@enderror</label>
            <label class="block"><span class="ms-label">Lokasi</span><input type="text" wire:model="location" class="ms-input">@error('location')<p class="ms-error">{{ $message }}</p>@enderror</label>
            <label class="block"><span class="ms-label">Mulai</span><input type="date" wire:model="start_date" class="ms-input"></label>
            <label class="block"><span class="ms-label">Selesai</span><input type="date" wire:model="end_date" class="ms-input">@error('end_date')<p class="ms-error">{{ $message }}</p>@enderror</label>
            <div class="flex gap-2 md:col-span-2"><button type="submit" class="ms-btn-primary">Simpan</button><button type="button" wire:click="$set('editingId', null)" class="ms-btn-ghost">Batal</button></div>
        </form>
    @endif

    <div class="space-y-3">
        @forelse ($events as $e)
            <div class="ms-card p-5" wire:key="ae-{{ $e->id }}">
                <div class="flex flex-wrap items-center gap-2">
                    <div class="min-w-0 flex-1">
                        <p class="font-extrabold text-ink-900">{{ $e->event_name }}</p>
                        <p class="text-sm text-ink-500">{{ $e->location }} · {{ $e->period_label }} @if ($e->is_ongoing)<span class="ms-chip bg-emerald-50 text-emerald-700">Berlangsung</span>@endif</p>
                    </div>
                    <button type="button" wire:click="edit({{ $e->id }})" class="ms-btn-ghost !py-1.5 text-xs">Ubah</button>
                    <button type="button" wire:click="delete({{ $e->id }})" wire:confirm="Hapus event?" class="ms-btn-ghost !py-1.5 text-xs text-red-600">Hapus</button>
                </div>
                @if ($e->booths->isNotEmpty())
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach ($e->booths as $b)<span class="ms-chip bg-brand-50 text-brand-700">{{ $b->artistProfile->shop_name }} · Booth {{ $b->booth_number ?: '-' }}</span>@endforeach
                    </div>
                @endif
            </div>
        @empty
            <x-empty-state icon="calendar" title="Belum ada event" />
        @endforelse
    </div>
</div>
