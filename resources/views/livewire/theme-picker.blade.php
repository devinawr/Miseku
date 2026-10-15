<div>
    {{-- Pemilih tema tampilan. $current = ['theme', 'mode', 'source'] dari ThemeService --}}
@php
    $active = $current['theme'];
    $swatch = fn ($t) => "background-image: linear-gradient(135deg, {$t->primary_color} 0%, {$t->primary_color} 45%, {$t->secondary_color} 100%)";
    $isManual = fn ($t) => $current['mode'] === 'manual' && $active?->id === $t->id;
@endphp

@if ($variant === 'menu')
<div x-data="{ open: false }" @keydown.escape="open = false" class="relative">
    <button type="button" @click="open = !open" class="ms-icon-btn relative" title="Tema & mode tampilan">
        <x-icon name="palette" class="h-6 w-6" />
        @if ($active)
            <span class="absolute right-1 bottom-1 h-2.5 w-2.5 rounded-full ring-2 ring-white" style="{{ $swatch($active) }}"></span>
        @endif
    </button>

    <div x-show="open" x-cloak @click.outside="open = false" x-transition.origin.top.right
         class="absolute right-0 z-50 mt-2 w-80 overflow-hidden rounded-2xl border border-ink-200 bg-white shadow-[var(--shadow-lift)]">
        <div class="ms-gradient-soft px-4 py-3">
            <p class="text-sm font-extrabold text-ink-900">Tema tampilan</p>
            <p class="text-xs text-ink-500">{{ $active ? $active->label . ' · ' : '' }}{{ $sourceLabel }}</p>
        </div>

        <div class="border-b border-ink-100 px-3 py-2.5">
            <p class="mb-1.5 text-[11px] font-extrabold tracking-wide text-ink-400 uppercase">Mode</p>
            <x-color-mode-switch />
        </div>

        <div class="max-h-80 overflow-y-auto p-1.5 text-sm">
            <button type="button" wire:click="choose('auto')"
                    class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-left hover:bg-brand-50 {{ $current['mode'] === 'auto' ? 'bg-brand-50' : '' }}">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-ink-100 text-ink-600"><x-icon name="sparkles" class="h-4.5 w-4.5" /></span>
                <span class="min-w-0 flex-1">
                    <span class="block font-bold text-ink-900">Otomatis</span>
                    <span class="block truncate text-xs text-ink-500">
                        @if ($isGuest) Masuk & pilih fandom favorit supaya tema ikut fandommu
                        @elseif ($autoTheme) Sekarang: {{ $autoTheme->label }}
                        @else Belum ada tema untuk fandom favoritmu @endif
                    </span>
                </span>
                @if ($current['mode'] === 'auto')<x-icon name="check" class="h-4.5 w-4.5 text-brand-700" stroke="2.4" />@endif
            </button>

            <p class="px-3 pt-3 pb-1 text-[11px] font-extrabold tracking-wide text-ink-400 uppercase">Pilih tema fandom</p>
            @forelse ($options as $t)
                <button type="button" wire:key="tp-{{ $t->id }}" wire:click="choose('manual', {{ $t->id }})"
                        class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-left hover:bg-brand-50 {{ $isManual($t) ? 'bg-brand-50' : '' }}">
                    <span class="h-8 w-8 shrink-0 rounded-full ring-1 ring-black/5" style="{{ $swatch($t) }}"></span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate font-bold text-ink-900">{{ $t->theming_name }}</span>
                        <span class="block truncate text-xs text-ink-500">{{ $t->fandom->fandom_name }}@if (in_array($t->fandom_id, $favoriteIds, true)) · <span class="text-accent-600">♥ favoritmu</span>@endif</span>
                    </span>
                    @if ($isManual($t))<x-icon name="check" class="h-4.5 w-4.5 text-brand-700" stroke="2.4" />@endif
                </button>
            @empty
                <p class="px-3 py-2 text-xs text-ink-500">Belum ada tema dari admin.</p>
            @endforelse

            <div class="my-1.5 border-t border-ink-100"></div>
            <button type="button" wire:click="choose('off')"
                    class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-left hover:bg-ink-50 {{ $current['mode'] === 'off' ? 'bg-ink-100' : '' }}">
                <span class="h-8 w-8 shrink-0 rounded-full ring-1 ring-black/5" style="background-image: linear-gradient(135deg, #7c3aed 0%, #7c3aed 45%, #db2777 100%)"></span>
                <span class="min-w-0 flex-1">
                    <span class="block font-bold text-ink-900">Matikan tema</span>
                    <span class="block text-xs text-ink-500">Pakai tampilan default Miseku</span>
                </span>
                @if ($current['mode'] === 'off')<x-icon name="check" class="h-4.5 w-4.5 text-brand-700" stroke="2.4" />@endif
            </button>
        </div>
        @unless ($isGuest)
            <a href="{{ route('account', ['tab' => 'tampilan']) }}" wire:navigate class="block border-t border-ink-100 px-4 py-2.5 text-xs font-bold text-brand-700 hover:bg-brand-50">Atur di Akun → Tampilan</a>
        @endunless
    </div>
</div>
@else
<div class="space-y-6">
    <div class="ms-card p-5 sm:p-6">
        <h2 class="text-lg font-extrabold text-ink-900">Tema tampilan</h2>
        <p class="mt-1 text-sm text-ink-500">
            Tema mengubah warna seluruh halaman (tombol, gradasi, aksen) dan banner beranda sesuai fandom.
            Isi rekomendasi & produk tetap sama.
        </p>
        <div class="mt-4 flex flex-wrap items-center gap-3 rounded-2xl bg-ink-50 p-4">
            <span class="h-10 w-10 shrink-0 rounded-full ring-1 ring-black/5" style="{{ $active ? $swatch($active) : 'background-image: linear-gradient(135deg,#7c3aed 0%,#7c3aed 45%,#db2777 100%)' }}"></span>
            <div class="min-w-0 flex-1">
                <p class="font-extrabold text-ink-900">{{ $active ? $active->label : 'Default Miseku' }}</p>
                <p class="text-xs text-ink-500">{{ $sourceLabel }}</p>
            </div>
        </div>

        <div class="mt-5 grid gap-3 sm:grid-cols-2">
            <button type="button" wire:click="choose('auto')"
                    class="rounded-2xl border-2 p-4 text-left transition {{ $current['mode'] === 'auto' ? 'border-brand-500 bg-brand-50' : 'border-ink-200 hover:border-brand-300' }}">
                <p class="flex items-center gap-2 font-extrabold text-ink-900"><x-icon name="sparkles" class="h-5 w-5 text-brand-600" /> Otomatis</p>
                <p class="mt-1 text-xs text-ink-500">
                    Ikut fandom favoritmu. Kalau punya beberapa tema, berganti tiap hari.
                    @if ($autoTheme) Sekarang: <b>{{ $autoTheme->label }}</b>.
                    @else Belum ada tema untuk fandom favoritmu — <a href="{{ route('account', ['tab' => 'fandom']) }}" wire:navigate class="font-bold text-brand-700 underline">atur fandom favorit</a>. @endif
                </p>
            </button>
            <button type="button" wire:click="choose('off')"
                    class="rounded-2xl border-2 p-4 text-left transition {{ $current['mode'] === 'off' ? 'border-brand-500 bg-brand-50' : 'border-ink-200 hover:border-brand-300' }}">
                <p class="flex items-center gap-2 font-extrabold text-ink-900"><x-icon name="eye-off" class="h-5 w-5 text-ink-500" /> Matikan tema</p>
                <p class="mt-1 text-xs text-ink-500">Selalu pakai tampilan default Miseku (ungu–magenta).</p>
            </button>
        </div>
    </div>

    <div class="ms-card p-5 sm:p-6">
        <h3 class="font-extrabold text-ink-900">Mode terang / gelap</h3>
        <p class="mt-1 text-xs text-ink-500">Disimpan di perangkat ini. "Ikuti perangkat" mengikuti pengaturan HP/laptop. Warna tema tetap dipakai di kedua mode.</p>
        <x-color-mode-switch size="lg" class="mt-4 max-w-lg" />
    </div>

    <div class="ms-card p-5 sm:p-6">
        <h3 class="font-extrabold text-ink-900">Atau pilih tema sendiri</h3>
        <p class="mt-1 text-xs text-ink-500">Tema dari fandom favoritmu ditaruh paling atas.</p>
        <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($options as $t)
                <button type="button" wire:key="tpp-{{ $t->id }}" wire:click="choose('manual', {{ $t->id }})"
                        class="overflow-hidden rounded-2xl border-2 text-left transition {{ $isManual($t) ? 'border-brand-500 ring-4 ring-brand-100' : 'border-ink-200 hover:border-brand-300' }}">
                    <div class="relative h-20" style="{{ $swatch($t) }}">
                        @if ($t->banner_image)
                            <img src="{{ $t->banner_image }}" alt="" class="absolute inset-0 h-full w-full object-cover opacity-45 mix-blend-overlay">
                        @endif
                        <div class="absolute bottom-2 left-3 flex gap-1">
                            @foreach ([$t->primary_color, $t->secondary_color, $t->accent_color ?: '#06b6d4'] as $c)
                                <span class="h-4 w-4 rounded-full ring-2 ring-white" style="background: {{ $c }}"></span>
                            @endforeach
                        </div>
                        @if ($isManual($t))
                            <span class="absolute top-2 right-2 rounded-full bg-white px-2 py-0.5 text-[11px] font-extrabold text-ink-900">Dipakai</span>
                        @endif
                    </div>
                    <div class="p-3">
                        <p class="truncate text-sm font-extrabold text-ink-900">{{ $t->theming_name }}</p>
                        <p class="truncate text-xs text-ink-500">{{ $t->fandom->fandom_name }}@if (in_array($t->fandom_id, $favoriteIds, true)) · <span class="text-accent-600">♥ favoritmu</span>@endif</p>
                    </div>
                </button>
            @empty
                <p class="text-sm text-ink-500">Belum ada tema dari admin.</p>
            @endforelse
        </div>
    </div>
</div>
@endif
</div>
