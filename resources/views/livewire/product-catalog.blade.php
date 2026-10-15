<div x-data="{ filters: false }">
    {{-- ======================= JUDUL ======================= --}}
    <section class="ms-banner relative overflow-hidden">
        <div class="ms-dots absolute inset-0"></div>
        <div class="relative mx-auto max-w-7xl px-4 py-8 text-white sm:px-6 lg:px-8">
            @php
                $fandomAktif = $fandomId !== '' ? $this->fandoms->firstWhere('id', (int) $fandomId) : null;
            @endphp
            <p class="text-xs font-bold tracking-wider text-white/80 uppercase">Katalog</p>
            <h1 class="mt-1 text-3xl font-extrabold tracking-tight sm:text-4xl">
                @if ($search !== '')
                    Hasil untuk “{{ $search }}”
                @elseif ($fandomAktif)
                    {{ $fandomAktif->fandom_name }}
                @elseif ($availability === 'pre_order')
                    Pre-order dibuka
                @elseif ($availability === 'ready_stock')
                    Ready stock
                @else
                    Semua produk
                @endif
            </h1>
            <p class="mt-1 text-sm text-white/85">
                @if ($search !== '')
                    Produk yang cocok dengan kata kuncimu.
                @elseif ($fandomAktif)
                    Merchandise {{ $fandomAktif->fandom_name }} dari artis independen.
                @elseif ($availability === 'pre_order')
                    Pesan sekarang, dibuat khusus untukmu — bisa bayar DP.
                @elseif ($availability === 'ready_stock')
                    Barang sudah tersedia dan siap dikirim.
                @else
                    Temukan merchandise original dari artis independen Indonesia.
                @endif
            </p>
        </div>
    </section>

    <div class="mx-auto grid max-w-7xl gap-6 px-4 py-8 sm:px-6 lg:grid-cols-[260px_1fr] lg:px-8">
        {{-- ======================= FILTER (sidebar) ======================= --}}
        <aside :class="filters ? 'fixed inset-0 z-50 overflow-y-auto bg-ink-900/40 p-4 backdrop-blur-sm lg:static lg:bg-transparent lg:p-0' : 'hidden lg:block'">
            <div class="ms-card space-y-6 p-5 lg:sticky lg:top-36" @click.outside="filters = false">
                <div class="flex items-center justify-between">
                    <p class="flex items-center gap-2 font-extrabold text-ink-900"><x-icon name="filter" class="h-5 w-5 text-brand-600" /> Filter</p>
                    <div class="flex items-center gap-2">
                        @if ($this->activeFilterCount > 0)
                            <button type="button" wire:click="resetFilter" class="text-xs font-bold text-accent-600 hover:underline">Reset ({{ $this->activeFilterCount }})</button>
                        @endif
                        <button type="button" @click="filters = false" class="ms-icon-btn !h-8 !w-8 lg:hidden"><x-icon name="x" class="h-5 w-5" /></button>
                    </div>
                </div>

                {{-- Ketersediaan --}}
                <div>
                    <p class="mb-2 text-xs font-extrabold tracking-wide text-ink-500 uppercase">Ketersediaan</p>
                    <div class="grid grid-cols-3 gap-1 rounded-xl bg-ink-100 p-1">
                        @foreach (['semua' => 'Semua', 'ready_stock' => 'Ready', 'pre_order' => 'PO'] as $v => $l)
                            <button type="button" wire:click="$set('availability', '{{ $v }}')"
                                    @class(['rounded-lg py-1.5 text-xs font-bold transition', 'bg-white text-brand-700 shadow-sm' => $availability === $v, 'text-ink-500 hover:text-ink-800' => $availability !== $v])>
                                {{ $l }}
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- Fandom --}}
                <div>
                    <label class="mb-2 block text-xs font-extrabold tracking-wide text-ink-500 uppercase">Fandom</label>
                    <div class="relative">
                        <select wire:model.live="fandomId" class="ms-select">
                            <option value="">Semua fandom</option>
                            @foreach ($this->fandoms as $fandom)
                                <option value="{{ $fandom->id }}">{{ $fandom->fandom_name }}</option>
                            @endforeach
                        </select>
                        <x-chevron />
                    </div>
                </div>

                {{-- Karakter --}}
                <div>
                    <label class="mb-2 block text-xs font-extrabold tracking-wide text-ink-500 uppercase">Karakter</label>
                    <div class="relative">
                        <select wire:model.live="characterId" class="ms-select" @disabled($this->characters->isEmpty())>
                            <option value="">Semua karakter</option>
                            @foreach ($this->characters as $fandomName => $group)
                                <optgroup label="{{ $fandomName }}">
                                    @foreach ($group as $character)
                                        <option value="{{ $character->id }}">{{ $character->character_name }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        <x-chevron />
                    </div>
                </div>

                {{-- Jenis merchandise --}}
                <div>
                    <p class="mb-2 text-xs font-extrabold tracking-wide text-ink-500 uppercase">Jenis merchandise</p>
                    <div class="flex flex-wrap gap-1.5">
                        <button type="button" wire:click="$set('typeId', '')"
                                @class(['rounded-full border px-3 py-1 text-xs font-bold transition', 'border-brand-600 bg-brand-600 text-white' => $typeId === '', 'border-ink-200 text-ink-600 hover:border-brand-300' => $typeId !== ''])>
                            Semua
                        </button>
                        @foreach ($this->merchandiseTypes as $type)
                            <button type="button" wire:click="$set('typeId', '{{ $type->id }}')" wire:key="t-{{ $type->id }}"
                                    @class(['rounded-full border px-3 py-1 text-xs font-bold transition', 'border-brand-600 bg-brand-600 text-white' => $typeId === (string) $type->id, 'border-ink-200 text-ink-600 hover:border-brand-300' => $typeId !== (string) $type->id])>
                                {{ $type->type_name }}
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- Rentang harga --}}
                <div>
                    <p class="mb-2 text-xs font-extrabold tracking-wide text-ink-500 uppercase">Rentang harga</p>
                    <div class="flex items-center gap-2">
                        <input type="number" min="0" step="1000" wire:model.live.debounce.600ms="minPrice" placeholder="Min" class="ms-input !px-3 !py-2">
                        <span class="text-ink-400">–</span>
                        <input type="number" min="0" step="1000" wire:model.live.debounce.600ms="maxPrice" placeholder="Maks" class="ms-input !px-3 !py-2">
                    </div>
                    <div class="mt-2 flex flex-wrap gap-1.5">
                        @foreach ([['', '50000', '< 50rb'], ['50000', '150000', '50–150rb'], ['150000', '', '> 150rb']] as [$mn, $mx, $lb])
                            <button type="button" wire:click="$set('minPrice', '{{ $mn }}'); $set('maxPrice', '{{ $mx }}')"
                                    class="rounded-full bg-ink-100 px-2.5 py-1 text-[11px] font-bold text-ink-600 hover:bg-brand-50 hover:text-brand-700">{{ $lb }}</button>
                        @endforeach
                    </div>
                </div>

                <button type="button" @click="filters = false" class="ms-btn-primary w-full lg:hidden">Terapkan filter</button>
            </div>
        </aside>

        {{-- ======================= HASIL ======================= --}}
        <section>
            <div class="mb-5 flex flex-wrap items-center gap-3">
                <div class="relative min-w-60 flex-1">
                    <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3.5 h-5 w-5 -translate-y-1/2 text-ink-400" />
                    <input type="search" wire:model.live.debounce.500ms="search" placeholder="Cari produk, karakter, fandom, atau toko…" class="ms-input pr-10 pl-11">
                    <div wire:loading.delay wire:target="search" class="absolute top-1/2 right-3.5 -translate-y-1/2">
                        <span class="block h-4 w-4 animate-spin rounded-full border-2 border-brand-200 border-t-brand-600"></span>
                    </div>
                </div>

                <button type="button" @click="filters = true" class="ms-btn-ghost lg:hidden">
                    <x-icon name="filter" class="h-4.5 w-4.5" /> Filter
                    @if ($this->activeFilterCount) <span class="rounded-full bg-brand-600 px-1.5 text-[10px] text-white">{{ $this->activeFilterCount }}</span> @endif
                </button>

                <div class="relative">
                    <select wire:model.live="sort" class="ms-select !w-48">
                        <option value="terbaru">Terbaru</option>
                        <option value="termurah">Harga terendah</option>
                        <option value="termahal">Harga tertinggi</option>
                        <option value="rating">Rating tertinggi</option>
                    </select>
                    <x-chevron />
                </div>
            </div>

            {{-- Chip filter aktif --}}
            @if ($this->activeFilterCount > 0)
                <div class="mb-5 flex flex-wrap items-center gap-2">
                    @if ($search !== '')
                        <button wire:click="clearSearch" class="ms-chip bg-brand-50 text-brand-700 hover:bg-brand-100">“{{ $search }}” <x-icon name="x" class="h-3 w-3" stroke="2.6" /></button>
                    @endif
                    @if ($fandomAktif)
                        <button wire:click="$set('fandomId', '')" class="ms-chip bg-brand-50 text-brand-700 hover:bg-brand-100">{{ $fandomAktif->fandom_name }} <x-icon name="x" class="h-3 w-3" stroke="2.6" /></button>
                    @endif
                    @if ($characterId !== '')
                        <button wire:click="$set('characterId', '')" class="ms-chip bg-brand-50 text-brand-700 hover:bg-brand-100">
                            {{ $this->characters->flatten()->firstWhere('id', (int) $characterId)?->character_name }} <x-icon name="x" class="h-3 w-3" stroke="2.6" />
                        </button>
                    @endif
                    @if ($typeId !== '')
                        <button wire:click="$set('typeId', '')" class="ms-chip bg-brand-50 text-brand-700 hover:bg-brand-100">
                            {{ $this->merchandiseTypes->firstWhere('id', (int) $typeId)?->type_name }} <x-icon name="x" class="h-3 w-3" stroke="2.6" />
                        </button>
                    @endif
                    @if ($minPrice !== '' || $maxPrice !== '')
                        <button wire:click="$set('minPrice', ''); $set('maxPrice', '')" class="ms-chip bg-brand-50 text-brand-700 hover:bg-brand-100">
                            Rp{{ $minPrice !== '' ? number_format((float) $minPrice, 0, ',', '.') : '0' }} – {{ $maxPrice !== '' ? 'Rp' . number_format((float) $maxPrice, 0, ',', '.') : '∞' }}
                            <x-icon name="x" class="h-3 w-3" stroke="2.6" />
                        </button>
                    @endif
                </div>
            @endif

            <div wire:loading.class="opacity-50" wire:target="search,fandomId,characterId,typeId,availability,sort,minPrice,maxPrice,resetFilter,gotoPage,nextPage,previousPage" class="transition">
                @if ($products->isEmpty())
                    <x-empty-state title="Produk tidak ditemukan" icon="search"
                                   description="Coba kata kunci lain atau longgarkan filter. Mungkin artisnya belum mengunggah produk untuk kombinasi ini.">
                        <button wire:click="resetFilter" class="ms-btn-soft">Reset semua filter</button>
                    </x-empty-state>
                @else
                    <div class="grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3 xl:grid-cols-4">
                        @foreach ($products as $product)
                            <x-product-card :product="$product" wire:key="p-{{ $product->id }}" />
                        @endforeach
                    </div>

                    <div class="mt-8">{{ $products->onEachSide(1)->links('components.pagination') }}</div>
                @endif
            </div>
        </section>
    </div>
</div>
