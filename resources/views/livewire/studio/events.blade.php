<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <x-studio-nav />
    <x-page-title title="Event & booth" subtitle="Daftarkan booth di event (dibuat admin) agar pembeli bisa memilih ambil di booth, lalu jual OTS & scan QR saat event berlangsung." />

    <div class="grid gap-4 lg:grid-cols-2">
        @forelse ($events as $e)
            @php $booth = $booths->get($e->id); $past = $e->end_date->lt(today()); @endphp
            <div class="ms-card p-5" wire:key="ev-{{ $e->id }}">
                <div class="flex flex-wrap items-start gap-2">
                    <div class="min-w-0 flex-1">
                        <p class="font-extrabold text-ink-900">{{ $e->event_name }}</p>
                        <p class="text-sm text-ink-500"><x-icon name="map-pin" class="inline h-4 w-4" /> {{ $e->location }} · {{ $e->period_label }}</p>
                    </div>
                    @if ($e->is_ongoing)<span class="ms-chip bg-emerald-50 text-emerald-700">Berlangsung</span>
                    @elseif ($past)<span class="ms-chip ms-chip-muted">Selesai</span>
                    @else<span class="ms-chip ms-chip-po">Akan datang</span>@endif
                </div>

                @if ($booth)
                    <div class="mt-3 rounded-xl bg-brand-50 px-4 py-3 text-sm">
                        <p class="font-bold text-brand-800">Booth {{ $booth->booth_number ?: '(nomor belum ada)' }} · {{ $booth->operating_hours }} · hari ke-{{ implode(', ', $booth->days) }}</p>
                        @php $pk = $pickups->get($booth->id, collect()); @endphp
                        @if ($pk->isNotEmpty())
                            <p class="mt-1 text-xs text-brand-700">Pesanan pick-up: {{ $pk->map(fn ($r) => $r->n . ' ' . strtolower(\App\Models\CustomerOrder::STATUS_LABELS[$r->order_status]))->implode(', ') }}</p>
                        @endif
                    </div>
                @endif

                @if ($editingEvent === $e->id)
                    <form wire:submit="save" class="mt-3 space-y-3 rounded-xl border border-ink-200 p-4">
                        <div class="grid gap-3 sm:grid-cols-2">
                            <label class="block"><span class="ms-label">Nomor booth</span><input type="text" wire:model="booth_number" class="ms-input" placeholder="mis. B-12"></label>
                            <label class="block"><span class="ms-label">Jam operasional</span><input type="text" wire:model="operating_hours" class="ms-input"></label>
                        </div>
                        <div>
                            <span class="ms-label">Hari hadir</span>
                            <div class="flex flex-wrap gap-3">
                                @for ($d = 1; $d <= min(7, $e->duration_days); $d++)
                                    <label class="flex items-center gap-1.5 text-sm"><input type="checkbox" class="ms-check" wire:model="attend_days" value="{{ $d }}"> Day {{ $d }} <span class="text-xs text-ink-400">({{ $e->start_date->copy()->addDays($d - 1)->translatedFormat('d M') }})</span></label>
                                @endfor
                            </div>
                            @error('attend_days')<p class="ms-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="flex gap-2"><button type="submit" class="ms-btn-primary">Simpan booth</button><button type="button" wire:click="$set('editingEvent', null)" class="ms-btn-ghost">Batal</button></div>
                    </form>
                @elseif (! $past)
                    <div class="mt-3 flex flex-wrap gap-2">
                        <button type="button" wire:click="edit({{ $e->id }})" class="{{ $booth ? 'ms-btn-ghost' : 'ms-btn-primary' }}">{{ $booth ? 'Ubah booth' : 'Daftarkan booth' }}</button>
                        @if ($booth)
                            <button type="button" wire:click="remove({{ $e->id }})" wire:confirm="Hapus booth dari event ini?" class="ms-btn-ghost text-red-600">Hapus</button>
                            @if ($e->is_ongoing)
                                <a href="{{ route('studio.scan') }}" wire:navigate class="ms-btn-soft"><x-icon name="qr" class="h-4.5 w-4.5" /> Scan QR</a>
                                <a href="{{ route('studio.ots') }}" wire:navigate class="ms-btn-soft"><x-icon name="ticket" class="h-4.5 w-4.5" /> Jual OTS</a>
                            @endif
                        @endif
                    </div>
                @endif
            </div>
        @empty
            <x-empty-state icon="calendar" title="Belum ada event" description="Admin belum membuat event. Event yang dibuat admin akan muncul di sini." class="lg:col-span-2" />
        @endforelse
    </div>
</div>
