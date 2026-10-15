@php
    $tabs = ['pending' => 'Menunggu', 'rejected' => 'Ditolak', 'active' => 'Disetujui'];
    $total = array_sum($this->pendingCounts);
    $sections = [
        ['kind' => 'fandom', 'title' => 'Fandom', 'icon' => 'sparkles', 'rows' => $this->fandoms,
            'name' => fn ($r) => $r->fandom_name, 'meta' => fn ($r) => $r->characters_count . ' karakter'],
        ['kind' => 'character', 'title' => 'Karakter', 'icon' => 'user', 'rows' => $this->characters,
            'name' => fn ($r) => $r->character_name,
            'meta' => fn ($r) => 'Fandom ' . $r->fandom?->fandom_name . ($r->fandom?->status === 'pending' ? ' (fandom juga masih usulan)' : '')],
        ['kind' => 'type', 'title' => 'Jenis merchandise', 'icon' => 'squares', 'rows' => $this->types,
            'name' => fn ($r) => $r->type_name, 'meta' => fn ($r) => null],
    ];
@endphp

<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <x-admin-nav />
    <h1 class="text-2xl font-extrabold tracking-tight text-ink-900 sm:text-3xl">Persetujuan master data</h1>
    <p class="mt-1 text-sm text-ink-500">
        Usulan fandom, karakter, dan jenis merchandise dari artis. Produk yang memakai usulan baru
        tampil di katalog setelah usulannya disetujui.
    </p>

    <div class="mt-6 flex flex-wrap gap-2">
        @foreach ($tabs as $key => $label)
            <button type="button" wire:click="$set('status', '{{ $key }}')" @class([
                'inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-bold transition',
                'ms-gradient text-white' => $status === $key,
                'bg-white text-ink-700 ring-1 ring-ink-200 hover:text-brand-700' => $status !== $key,
            ])>
                {{ $label }}
                @if ($key === 'pending' && $total)
                    <span class="rounded-full bg-white px-2 text-xs text-brand-700">{{ $total }}</span>
                @endif
            </button>
        @endforeach
    </div>

    <div class="mt-6 space-y-6">
        @foreach ($sections as $sec)
            <section class="ms-card overflow-hidden" wire:key="sec-{{ $sec['kind'] }}">
                <div class="flex items-center gap-2 border-b border-ink-100 px-5 py-4">
                    <x-icon :name="$sec['icon']" class="h-5 w-5 text-brand-600" />
                    <h2 class="font-extrabold text-ink-900">{{ $sec['title'] }}</h2>
                    <span class="ms-pill ml-auto">{{ $sec['rows']->count() }}</span>
                </div>

                @forelse ($sec['rows'] as $row)
                    @php $used = $this->productsUsing($sec['kind'], $row->id); @endphp
                    <div class="flex flex-wrap items-center gap-4 border-b border-ink-100 px-5 py-3.5 last:border-0" wire:key="{{ $sec['kind'] }}-{{ $row->id }}">
                        <div class="min-w-0 flex-1 basis-60">
                            <p class="font-bold text-ink-900">{{ ($sec['name'])($row) }}</p>
                            <p class="text-xs text-ink-500">
                                @if ($m = ($sec['meta'])($row)) {{ $m }} · @endif
                                Diusulkan oleh {{ $row->proposer?->name ?? 'admin / data awal' }} ·
                                dipakai {{ $used }} produk
                            </p>
                        </div>
                        <div class="flex gap-2">
                            @if ($row->status !== 'active')
                                <button type="button" wire:click="approve('{{ $sec['kind'] }}', {{ $row->id }})" class="ms-btn-primary !px-4 !py-2 text-sm">
                                    <x-icon name="check" class="h-4 w-4" stroke="2.4" /> Setujui
                                </button>
                            @endif
                            @if ($row->status !== 'rejected')
                                <button type="button" wire:click="reject('{{ $sec['kind'] }}', {{ $row->id }})"
                                        wire:confirm="Tolak usulan ini?{{ $used ? ' ' . $used . ' produk yang memakainya akan tetap tersembunyi.' : '' }}"
                                        class="ms-btn-ghost !px-4 !py-2 text-sm hover:text-red-600">
                                    <x-icon name="x" class="h-4 w-4" stroke="2.4" /> Tolak
                                </button>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="px-5 py-6 text-sm text-ink-500">Tidak ada data.</p>
                @endforelse
            </section>
        @endforeach
    </div>
</div>
