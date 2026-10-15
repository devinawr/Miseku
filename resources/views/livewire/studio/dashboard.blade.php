@php $stats = $this->stats; @endphp

<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <x-studio-nav />
    {{-- Header toko --}}
    <div class="ms-banner relative overflow-hidden rounded-[var(--radius-card)] p-6 text-white sm:p-8">
        <div class="ms-dots absolute inset-0 opacity-20"></div>
        <div class="relative flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-xs font-bold tracking-widest text-white/80 uppercase">Studio artis</p>
                <h1 class="mt-1 text-2xl font-extrabold sm:text-3xl">{{ $this->shop->shop_name }}</h1>
                <p class="mt-1 text-sm text-white/85">Kelola produk yang tampil di katalog Miseku.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('artist.shop', $this->shop) }}" wire:navigate class="ms-btn bg-white/15 text-white ring-1 ring-white/40 hover:bg-white/25">
                    <x-icon name="eye" class="h-4.5 w-4.5" /> Lihat toko
                </a>
                <a href="{{ route('studio.product.create') }}" wire:navigate class="ms-btn bg-white text-brand-700 hover:bg-brand-50">
                    <x-icon name="plus" class="h-4.5 w-4.5" stroke="2.4" /> Upload produk
                </a>
            </div>
        </div>
    </div>

    {{-- Ringkasan kecil --}}
    <div class="mt-5 grid grid-cols-2 gap-3 lg:grid-cols-4">
        @foreach ([
            ['Tayang di katalog', $stats['active'], 'check-circle', 'text-emerald-600 bg-emerald-50'],
            ['Disembunyikan', $stats['inactive'], 'eye-off', 'text-ink-600 bg-ink-100'],
            ['Dilihat (30 hari)', $stats['views'], 'eye', 'text-brand-700 bg-brand-50'],
            ['Di keranjang pembeli', $stats['carts'], 'cart', 'text-accent-600 bg-accent-50'],
        ] as [$label, $value, $icon, $tone])
            <div class="ms-card flex items-center gap-3 p-4">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl {{ $tone }}"><x-icon :name="$icon" class="h-5 w-5" /></span>
                <div>
                    <p class="text-xl font-extrabold text-ink-900">{{ number_format($value, 0, ',', '.') }}</p>
                    <p class="text-xs text-ink-500">{{ $label }}</p>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Toolbar --}}
    <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
        <div class="ms-scroll-x flex gap-1.5">
            @foreach (['semua' => 'Semua', 'aktif' => 'Tayang', 'nonaktif' => 'Disembunyikan', 'po' => 'Pre-order', 'habis' => 'Stok habis'] as $key => $label)
                <button type="button" wire:click="$set('status', '{{ $key }}')" @class(['ms-tab', 'ms-tab-active' => $status === $key])>{{ $label }}</button>
            @endforeach
        </div>
        <div class="relative w-full sm:w-72">
            <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3.5 h-4.5 w-4.5 -translate-y-1/2 text-ink-400" />
            <input type="search" wire:model.live.debounce.400ms="search" placeholder="Cari produkmu…" class="ms-input pl-10">
        </div>
    </div>

    @if ($stats['waiting'])
        <div class="mt-4 flex items-start gap-2.5 rounded-2xl bg-amber-50 px-4 py-3 text-sm text-amber-800 ring-1 ring-amber-200">
            <x-icon name="clock" class="mt-0.5 h-4.5 w-4.5 shrink-0" />
            <span><b>{{ $stats['waiting'] }} produk</b> belum tayang karena memakai fandom/karakter/jenis usulan yang belum disetujui admin.</span>
        </div>
    @endif

    {{-- Daftar --}}
    <div class="ms-card mt-4 overflow-hidden">
        @forelse ($this->products as $p)
            @php $waiting = $p->unapprovedMasters(); @endphp
            <div class="flex flex-wrap items-center gap-4 border-b border-ink-100 px-4 py-4 last:border-0 sm:px-5" wire:key="p-{{ $p->id }}">
                <img src="{{ $p->cover_url }}" alt="" class="h-16 w-16 shrink-0 rounded-xl object-cover {{ $p->product_status !== 'active' ? 'opacity-50 grayscale' : '' }}">

                <div class="min-w-0 flex-1 basis-56">
                    <div class="flex flex-wrap items-center gap-1.5">
                        @if ($p->product_status === 'active' && $waiting)
                            <span class="ms-chip-muted">Belum tayang</span>
                        @elseif ($p->product_status === 'active')
                            <span class="ms-chip-ready">Tayang</span>
                        @else
                            <span class="ms-chip-muted">Disembunyikan</span>
                        @endif
                        <span class="{{ $p->is_pre_order ? 'ms-chip-po' : 'ms-chip-muted' }}">{{ $p->is_pre_order ? 'Pre-order' : 'Ready stock' }}</span>
                    </div>
                    <a href="{{ route('studio.product.edit', $p) }}" wire:navigate class="mt-1 block truncate font-bold text-ink-900 hover:text-brand-700">{{ $p->product_name }}</a>
                    <p class="truncate text-xs text-ink-500">{{ $p->fandom_name }} · {{ $p->character?->character_name }} · {{ $p->merchandiseType?->type_name }}</p>
                    @foreach ($waiting as $w)
                        <p @class(['mt-1 text-xs font-semibold', 'text-amber-700' => $w['status'] === 'pending', 'text-red-600' => $w['status'] === 'rejected'])>
                            @if ($w['status'] === 'pending')
                                {{ $w['label'] }} “{{ $w['name'] }}” menunggu persetujuan admin
                            @else
                                {{ $w['label'] }} “{{ $w['name'] }}” ditolak admin — ubah produk dan pilih yang lain
                            @endif
                        </p>
                    @endforeach
                </div>

                <div class="grid grid-cols-3 gap-5 text-xs text-ink-500 sm:w-80">
                    <div><p class="font-extrabold text-ink-900"><x-rupiah :value="$p->display_price" /></p>Harga</div>
                    <div>
                        @if ($p->is_pre_order)
                            <p class="font-extrabold text-ink-900">{{ $p->openBatch ? $p->openBatch->closes_at->translatedFormat('d M') : 'Tutup' }}</p>PO s.d.
                        @else
                            <p @class(['font-extrabold', 'text-red-600' => $p->stock < 1, 'text-ink-900' => $p->stock >= 1])>{{ $p->stock }}</p>Stok
                        @endif
                    </div>
                    <div><p class="font-extrabold text-ink-900">{{ $p->views_30d }} / {{ $p->in_carts }}</p>Dilihat / keranjang</div>
                </div>

                <div class="flex items-center gap-1">
                    <button type="button" wire:click="toggleStatus({{ $p->id }})" class="ms-icon-btn" title="{{ $p->product_status === 'active' ? 'Sembunyikan' : 'Tayangkan' }}">
                        <x-icon :name="$p->product_status === 'active' ? 'eye-off' : 'eye'" class="h-4.5 w-4.5" />
                    </button>
                    <a href="{{ route('studio.product.edit', $p) }}" wire:navigate class="ms-icon-btn" title="Ubah"><x-icon name="pencil" class="h-4.5 w-4.5" /></a>
                    <button type="button" wire:click="delete({{ $p->id }})" wire:confirm="Hapus produk ini? Produk akan hilang dari katalog dan keranjang pembeli." class="ms-icon-btn hover:text-red-600" title="Hapus">
                        <x-icon name="trash" class="h-4.5 w-4.5" />
                    </button>
                </div>
            </div>
        @empty
            <x-empty-state icon="photo" class="border-0 shadow-none" title="Belum ada produk di sini"
                           description="Upload produk pertamamu — foto, harga, dan pilih ready stock atau pre-order.">
                <a href="{{ route('studio.product.create') }}" wire:navigate class="ms-btn-primary"><x-icon name="plus" class="h-4.5 w-4.5" /> Upload produk</a>
            </x-empty-state>
        @endforelse
    </div>

    <div class="mt-5">{{ $this->products->links('components.pagination') }}</div>
</div>
