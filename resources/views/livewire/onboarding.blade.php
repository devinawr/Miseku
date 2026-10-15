<div class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
    <div class="text-center">
        <h1 class="text-3xl font-extrabold tracking-tight text-ink-900 sm:text-4xl">
            Kamu suka fandom <span class="ms-gradient-text">apa saja?</span>
        </h1>
        <p class="mx-auto mt-3 max-w-xl text-sm text-ink-500">
            Pilih satu atau lebih. Pilihanmu dipakai untuk rekomendasi produk di beranda.
            Ini <b>bukan</b> gabung grup diskusi — itu terpisah dan terserah kamu nanti.
        </p>
    </div>

    <div class="mx-auto mt-8 max-w-md">
        <div class="relative">
            <x-icon name="search" class="pointer-events-none absolute top-1/2 left-4 h-5 w-5 -translate-y-1/2 text-ink-400" />
            <input type="search" wire:model.live.debounce.300ms="filter" placeholder="Cari fandom…" class="ms-input !rounded-full pl-11">
        </div>
    </div>

    <div class="mt-8 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
        @forelse ($this->fandoms as $f)
            @php
                $on   = in_array($f->id, $selected, true);
            @endphp
            <button type="button" wire:click="toggle({{ $f->id }})" wire:key="f-{{ $f->id }}"
                    @class([
                        'group relative overflow-hidden rounded-2xl border-2 p-4 text-left transition',
                        'ms-gradient border-transparent shadow-[var(--shadow-lift)] ring-4 ring-brand-200' => $on,
                        'border-ink-200 bg-white hover:-translate-y-0.5 hover:border-brand-200' => ! $on,
                    ])>
                <span class="absolute top-3 right-3 flex h-6 w-6 items-center justify-center rounded-full border-2 {{ $on ? 'border-white bg-white text-brand-600' : 'border-ink-300' }}">
                    @if ($on) <x-icon name="check" class="h-3.5 w-3.5" stroke="3" /> @endif
                </span>
                <span class="flex h-10 w-10 items-center justify-center rounded-xl text-base font-extrabold {{ $on ? 'bg-white/25 text-white' : 'ms-gradient text-white' }}">{{ mb_strtoupper(mb_substr($f->fandom_name, 0, 1)) }}</span>
                <span class="mt-3 block text-sm font-extrabold {{ $on ? 'text-white' : 'text-ink-900' }}">{{ $f->fandom_name }}</span>
                <span class="mt-0.5 block text-xs {{ $on ? 'text-white/80' : 'text-ink-500' }}">
                    {{ $f->karakter_count }} karakter
                </span>
            </button>
        @empty
            <p class="col-span-full py-10 text-center text-sm text-ink-500">Fandom tidak ditemukan.</p>
        @endforelse
    </div>

    @error('selected') <p class="ms-error mt-4 text-center">{{ $message }}</p> @enderror

    <div class="sticky bottom-4 mt-10 flex flex-col items-center justify-between gap-3 rounded-2xl border border-ink-200 bg-white/95 p-4 shadow-[var(--shadow-lift)] backdrop-blur sm:flex-row">
        <p class="text-sm font-semibold text-ink-600">
            <b class="text-brand-700">{{ count($selected) }}</b> fandom dipilih
        </p>
        <div class="flex gap-2">
            <button type="button" wire:click="skip" class="ms-btn-ghost">Lewati dulu</button>
            <button type="button" wire:click="save" class="ms-btn-primary" @disabled(empty($selected))>
                Simpan & lanjut <x-icon name="arrow-right" class="h-4 w-4" stroke="2.2" />
            </button>
        </div>
    </div>
</div>
