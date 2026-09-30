<div>
    {{-- ======================= HEADER HALAMAN ======================= --}}
    <section class="border-b border-miseku-200/70 bg-gradient-to-b from-miseku-100 to-miseku-50">
        <div class="mx-auto flex max-w-7xl flex-wrap items-end justify-between gap-4 px-4 py-7 sm:px-6 lg:px-8">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-miseku-500">Katalog</p>
                <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-miseku-900 sm:text-3xl">
                    Jelajahi merchandise
                </h1>
                <p class="mt-1 text-sm text-miseku-600">
                    Saring berdasarkan fandom, karakter, jenis merchandise, dan ketersediaan.
                </p>
            </div>

            <dl class="flex gap-6 text-sm">
                <div>
                    <dt class="text-xs font-medium text-miseku-500">karya tampil</dt>
                    <dd class="text-xl font-extrabold text-miseku-700">{{ $products->total() }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-miseku-500">fandom</dt>
                    <dd class="text-xl font-extrabold text-miseku-700">{{ $this->fandoms->count() }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-miseku-500">jenis</dt>
                    <dd class="text-xl font-extrabold text-miseku-700">{{ $this->merchandiseTypes->count() }}</dd>
                </div>
            </dl>
        </div>
    </section>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">

        {{-- ======================= FILTER BAR ======================= --}}
        <section class="ms-card sticky top-[4.25rem] z-30 p-4 sm:p-5">
            <div class="grid gap-3 lg:grid-cols-12">
                {{-- Search --}}
                <div class="relative lg:col-span-4">
                    <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4.5 w-4.5 -translate-y-1/2 text-miseku-400"
                         fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="m21 21-5.2-5.2m2.2-5.3a7.5 7.5 0 1 1-15 0 7.5 7.5 0 0 1 15 0Z" />
                    </svg>
                    <input
                        type="search"
                        wire:model.live.debounce.400ms="search"
                        placeholder="Cari karya, karakter, fandom, atau toko…"
                        class="ms-input pl-10 pr-10"
                        aria-label="Cari karya"
                    >
                    <div wire:loading.delay wire:target="search"
                         class="absolute right-3.5 top-1/2 -translate-y-1/2">
                        <svg class="h-4 w-4 animate-spin text-miseku-400" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.4 0 0 5.4 0 12h4Z"/>
                        </svg>
                    </div>
                </div>

                {{-- Fandom --}}
                <div class="relative lg:col-span-2">
                    <select wire:model.live="fandomId" class="ms-select" aria-label="Filter fandom">
                        <option value="">Semua fandom</option>
                        @foreach ($this->fandoms as $fandom)
                            <option value="{{ $fandom->id }}">{{ $fandom->fandom_name }}</option>
                        @endforeach
                    </select>
                    <x-chevron />
                </div>

                {{-- Karakter (mengikuti fandom terpilih) --}}
                <div class="relative lg:col-span-2">
                    <select wire:model.live="characterId" class="ms-select" aria-label="Filter karakter"
                            @disabled($this->characters->isEmpty())>
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

                {{-- Jenis merchandise --}}
                <div class="relative lg:col-span-2">
                    <select wire:model.live="typeId" class="ms-select" aria-label="Filter jenis merchandise">
                        <option value="">Semua jenis</option>
                        @foreach ($this->merchandiseTypes as $type)
                            <option value="{{ $type->id }}">{{ $type->type_name }}</option>
                        @endforeach
                    </select>
                    <x-chevron />
                </div>

                {{-- Urutan --}}
                <div class="relative lg:col-span-2">
                    <select wire:model.live="sort" class="ms-select" aria-label="Urutkan">
                        <option value="terbaru">Urutkan: Terbaru</option>
                        <option value="termurah">Harga termurah</option>
                        <option value="termahal">Harga tertinggi</option>
                        <option value="rating">Rating tertinggi</option>
                    </select>
                    <x-chevron />
                </div>
            </div>

            {{-- Pil ketersediaan + reset --}}
            <div class="mt-3 flex flex-wrap items-center gap-2 border-t border-miseku-100 pt-3">
                @foreach ([
                    'semua'       => 'Semua',
                    'ready_stock' => 'Ready stock',
                    'pre_order'   => 'Pre-order',
                ] as $value => $label)
                    <button
                        type="button"
                        wire:click="$set('availability', '{{ $value }}')"
                        @class([
                            'ms-chip transition',
                            'bg-miseku-500 text-white shadow-[var(--shadow-soft)]' => $availability === $value,
                            'bg-miseku-100 text-miseku-600 hover:bg-miseku-200' => $availability !== $value,
                        ])
                    >{{ $label }}</button>
                @endforeach

                @if ($this->activeFilterCount > 0)
                    <button type="button" wire:click="resetFilter"
                            class="ms-chip ml-auto bg-white text-miseku-500 ring-1 ring-miseku-200 hover:bg-miseku-100">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                        Reset {{ $this->activeFilterCount }} filter
                    </button>
                @endif
            </div>
        </section>

        {{-- ======================= HASIL ======================= --}}
        <div class="mt-6 flex items-baseline justify-between gap-4">
            <p class="text-sm text-miseku-600">
                Menampilkan
                <span class="font-bold text-miseku-800">{{ $products->count() }}</span>
                dari
                <span class="font-bold text-miseku-800">{{ $products->total() }}</span>
                karya
                @if ($search !== '')
                    untuk <span class="font-semibold text-miseku-800">"{{ $search }}"</span>
                @endif
            </p>
        </div>

        {{-- Skeleton saat query berjalan --}}
        <div wire:loading.delay.longer class="mt-5 grid grid-cols-2 gap-4 sm:gap-5 md:grid-cols-3 lg:grid-cols-4">
            @for ($i = 0; $i < 8; $i++)
                <div class="ms-card overflow-hidden">
                    <div class="ms-skeleton aspect-square rounded-none"></div>
                    <div class="space-y-2 p-4">
                        <div class="ms-skeleton h-3 w-1/2"></div>
                        <div class="ms-skeleton h-4 w-full"></div>
                        <div class="ms-skeleton h-4 w-2/3"></div>
                    </div>
                </div>
            @endfor
        </div>

        <div wire:loading.delay.longer.remove>
            @if ($products->isEmpty())
                <x-empty-state
                    class="mt-5"
                    title="Karya yang kamu cari belum ada"
                    description="Coba ganti kata kunci, atau longgarkan filter fandom, karakter, dan jenis merchandise."
                >
                    <button type="button" wire:click="resetFilter" class="ms-btn-primary">Reset semua filter</button>
                </x-empty-state>
            @else
                <div class="mt-5 grid grid-cols-2 gap-4 sm:gap-5 md:grid-cols-3 lg:grid-cols-4">
                    @foreach ($products as $product)
                        <x-product-card :product="$product" :key="'p-'.$product->id" />
                    @endforeach
                </div>

                @if ($products->hasPages())
                    <div class="mt-10">
                        {{ $products->onEachSide(1)->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>
