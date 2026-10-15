<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <x-admin-nav />
    <x-page-title title="Tema fandom" subtitle="Skin tampilan per fandom: warna utama, gradasi, aksen & banner. Pengguna mendapat tema otomatis dari fandom favoritnya, atau memilih sendiri.">
        <button type="button" wire:click="edit" class="ms-btn-primary"><x-icon name="plus" class="h-4.5 w-4.5" /> Tema baru</button>
    </x-page-title>

    @if ($editingId !== null)
        <form wire:submit="save" class="ms-card mb-6 grid gap-6 p-5 lg:grid-cols-[1fr_1fr]">
            <div class="space-y-4">
                <p class="font-extrabold text-ink-900">{{ $editingId ? 'Ubah tema' : 'Tema baru' }}</p>
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="block"><span class="ms-label">Fandom</span>
                        <select wire:model="fandom_id" class="ms-input">
                            <option value="">— pilih fandom —</option>
                            @foreach ($fandoms as $f)<option value="{{ $f->id }}">{{ $f->fandom_name }}</option>@endforeach
                        </select>
                        @error('fandom_id')<p class="ms-error">{{ $message }}</p>@enderror
                    </label>
                    <label class="block"><span class="ms-label">Nama tema</span>
                        <input type="text" wire:model="theming_name" class="ms-input" placeholder="mis. Teyvat Night">
                        @error('theming_name')<p class="ms-error">{{ $message }}</p>@enderror
                    </label>
                </div>

                <div class="grid gap-4 sm:grid-cols-3">
                    @foreach (['primary_color' => ['Warna utama', 'tombol, tautan, ikon'], 'secondary_color' => ['Warna gradasi', 'ujung gradasi & lencana'], 'accent_color' => ['Warna aksen', 'sorotan kecil (opsional)']] as $field => [$label, $hint])
                        <div>
                            <span class="ms-label">{{ $label }}</span>
                            <div class="flex items-center gap-2">
                                <input type="color" wire:model.live.debounce.150ms="{{ $field }}" class="h-10 w-12 shrink-0 cursor-pointer rounded-lg border border-ink-200 bg-white p-1">
                                <input type="text" wire:model.live.debounce.400ms="{{ $field }}" class="ms-input !py-2 font-mono text-sm" placeholder="{{ $field === 'accent_color' ? 'opsional' : '#rrggbb' }}">
                            </div>
                            <p class="ms-hint">{{ $hint }}</p>
                            @error($field)<p class="ms-error">{{ $message }}</p>@enderror
                        </div>
                    @endforeach
                </div>

                {{-- Palet cepat: klik untuk mengisi warna utama + gradasi + aksen sekaligus (bisa diubah lagi) --}}
                <div>
                    <span class="ms-label">Palet cepat</span>
                    <div class="flex flex-wrap gap-2">
                        @foreach ([
                            'Ungu Miseku' => ['#7c3aed', '#db2777', '#f59e0b'],
                            'Hydro'       => ['#1d4ed8', '#38bdf8', '#facc15'],
                            'Teal Idol'   => ['#0f9488', '#22d3ee', '#f472b6'],
                            'Sakura'      => ['#db2777', '#f9a8d4', '#a78bfa'],
                            'Senja'       => ['#ea580c', '#f43f5e', '#fde047'],
                            'Hutan'       => ['#15803d', '#84cc16', '#fbbf24'],
                            'Malam'       => ['#312e81', '#7c3aed', '#22d3ee'],
                            'Merah Api'   => ['#b91c1c', '#f97316', '#fde68a'],
                        ] as $name => [$p1, $p2, $p3])
                            <button type="button" wire:click="applyPreset('{{ $p1 }}', '{{ $p2 }}', '{{ $p3 }}')"
                                    class="flex items-center gap-2 rounded-full border border-ink-200 py-1 pr-3 pl-1 text-xs font-bold text-ink-700 hover:border-brand-300">
                                <span class="h-6 w-6 rounded-full" style="background: linear-gradient(135deg, {{ $p1 }} 0%, {{ $p1 }} 45%, {{ $p2 }} 100%)"></span> {{ $name }}
                            </button>
                        @endforeach
                    </div>
                    <p class="ms-hint">Atau pilih warna bebas lewat kotak warna di atas.</p>
                </div>

                <div>
                    <span class="ms-label">Banner (opsional)</span>
                    <input type="file" wire:model="banner" accept="image/png,image/jpeg,image/webp" class="ms-input !py-2 text-sm">
                    <p class="ms-hint">JPG/PNG/WEBP maks. 3 MB, tampil di band tema beranda (ditumpuk gradasi warna tema).</p>
                    @error('banner')<p class="ms-error">{{ $message }}</p>@enderror
                    @if ($currentBanner && ! $banner)
                        <label class="mt-2 flex items-center gap-2 text-sm text-ink-600"><input type="checkbox" wire:model.live="removeBanner" class="rounded"> Hapus banner sekarang</label>
                    @endif
                </div>

                <label class="flex items-center gap-2 text-sm font-semibold text-ink-700"><input type="checkbox" wire:model="is_active" class="rounded"> Aktif (bisa dipakai pengguna)</label>

                <div class="flex gap-2">
                    <button type="submit" class="ms-btn-primary" wire:loading.attr="disabled">Simpan tema</button>
                    <button type="button" wire:click="cancel" class="ms-btn-ghost">Batal</button>
                </div>
            </div>

            {{-- Pratinjau (skala warna dibuat otomatis oleh ThemePalette) --}}
            <div class="space-y-3">
                <p class="text-sm font-extrabold text-ink-900">Pratinjau</p>
                <div class="relative overflow-hidden rounded-2xl text-white" style="background-image: linear-gradient(110deg, {{ $preview['brand'][500] }} 0%, {{ $preview['accent'][500] }} 100%)">
                    @if ($bannerPreview)<img src="{{ $bannerPreview }}" alt="" class="absolute inset-0 h-full w-full object-cover opacity-35 mix-blend-overlay">@endif
                    <div class="absolute inset-0" style="background-image: linear-gradient(90deg, {{ $preview['brand'][500] }}e6 0%, {{ $preview['brand'][500] }}80 45%, transparent 100%)"></div>
                    <div class="relative px-5 py-5">
                        <p class="text-xs font-bold text-white/80 uppercase">Band tema beranda</p>
                        <p class="text-xl font-extrabold">{{ $theming_name ?: 'Nama tema' }}</p>
                    </div>
                </div>
                <div class="rounded-2xl border border-ink-200 p-4" style="background: {{ $preview['brand'][50] }}">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-xl px-4 py-2 text-sm font-bold text-white" style="background-image: linear-gradient(120deg, {{ $preview['brand'][600] }}, {{ $preview['brand'][500] }} 35%, {{ $preview['accent'][500] }})">Tombol utama</span>
                        <span class="rounded-xl px-4 py-2 text-sm font-bold text-white" style="background: {{ $preview['brand'][600] }}">Tombol</span>
                        <span class="rounded-full px-2.5 py-1 text-xs font-bold" style="background: {{ $preview['brand'][100] }}; color: {{ $preview['brand'][700] }}">Chip</span>
                        <span class="rounded-full px-2.5 py-1 text-xs font-bold text-white" style="background: {{ $preview['accent'][500] }}">Pre-Order</span>
                        <span class="rounded-full px-2.5 py-1 text-xs font-bold" style="background: {{ $preview['pop'][100] }}; color: {{ $preview['pop'][700] }}">Aksen</span>
                    </div>
                    <p class="mt-3 text-sm font-bold" style="color: {{ $preview['brand'][700] }}">Tautan teks seperti ini →</p>
                </div>
                <div class="flex flex-wrap gap-1">
                    @foreach (['brand', 'accent', 'pop'] as $k)
                        <div class="flex overflow-hidden rounded-lg ring-1 ring-black/5">
                            @foreach ($preview[$k] as $step => $c)<span class="h-6 w-5" style="background: {{ $c }}" title="{{ $k }}-{{ $step }} {{ $c }}"></span>@endforeach
                        </div>
                    @endforeach
                </div>
                <p class="text-xs {{ $preview['button'] >= 4.5 ? 'text-emerald-700' : 'text-red-600' }}">
                    Kontras warna utama vs teks putih: {{ number_format($preview['contrast'], 2, ',', '.') }}:1.
                    @if ($preview['contrast'] < 4.5) Warna tombol otomatis digelapkan menjadi {{ number_format($preview['button'], 2, ',', '.') }}:1 supaya tetap terbaca. @else Aman untuk teks putih (≥ 4,5:1). @endif
                </p>
            </div>
        </form>
    @endif

    <div class="mb-4 flex flex-wrap items-center gap-3">
        <select wire:model.live="filterFandom" class="ms-input !w-auto !py-2 text-sm">
            <option value="">Semua fandom</option>
            @foreach ($fandoms as $f)<option value="{{ $f->id }}">{{ $f->fandom_name }}</option>@endforeach
        </select>
        <p class="text-sm text-ink-500">{{ $themes->count() }} tema · fandom dengan beberapa tema akan bergiliran per hari pada mode otomatis.</p>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($themes as $t)
            <div class="ms-card overflow-hidden {{ $t->is_active ? '' : 'opacity-60' }}" wire:key="at-{{ $t->id }}">
                <div class="relative h-24" style="background-image: linear-gradient(110deg, {{ $t->primary_color }} 0%, {{ $t->secondary_color }} 100%)">
                    @if ($t->banner_image)<img src="{{ $t->banner_image }}" alt="" class="absolute inset-0 h-full w-full object-cover opacity-40 mix-blend-overlay">@endif
                    <div class="absolute bottom-2 left-3 flex gap-1">
                        @foreach ([$t->primary_color, $t->secondary_color, $t->accent_color ?: '#06b6d4'] as $c)<span class="h-5 w-5 rounded-full ring-2 ring-white" style="background: {{ $c }}" title="{{ $c }}"></span>@endforeach
                    </div>
                    <span class="absolute top-2 right-2 rounded-full px-2 py-0.5 text-[11px] font-extrabold {{ $t->is_active ? 'bg-white text-emerald-700' : 'bg-ink-900 text-white' }}">{{ $t->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                </div>
                <div class="p-4">
                    <p class="truncate font-extrabold text-ink-900">{{ $t->theming_name }}</p>
                    <p class="truncate text-sm text-ink-500">{{ $t->fandom->fandom_name }}</p>
                    <p class="mt-1 text-xs text-ink-400">Dipilih manual oleh {{ $t->chosen_by_count }} pengguna · dibuat {{ $t->creator?->name ?? 'sistem' }}</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <button type="button" wire:click="edit({{ $t->id }})" class="ms-btn-ghost !py-1.5 text-xs">Ubah</button>
                        <button type="button" wire:click="toggle({{ $t->id }})" class="ms-btn-ghost !py-1.5 text-xs">{{ $t->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                        <button type="button" wire:click="delete({{ $t->id }})" wire:confirm="Hapus tema ini? Pengguna yang memakainya kembali ke tema otomatis." class="ms-btn-ghost !py-1.5 text-xs text-red-600">Hapus</button>
                    </div>
                </div>
            </div>
        @empty
            <div class="sm:col-span-2 lg:col-span-3"><x-empty-state icon="palette" title="Belum ada tema" /></div>
        @endforelse
    </div>
</div>
