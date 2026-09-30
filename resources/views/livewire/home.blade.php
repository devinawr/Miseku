@php
    $rekomendasi = $this->rekomendasi;
@endphp

<div>
    {{-- ======================= SAMBUTAN + CARI ======================= --}}
    <section class="border-b border-miseku-200/70 bg-gradient-to-b from-miseku-100 to-miseku-50">
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <h1 class="max-w-3xl text-2xl font-extrabold leading-tight tracking-tight text-miseku-900 sm:text-3xl">
                Temukan merchandise
                <span class="text-miseku-500">fandom favoritmu</span>
                di satu tempat.
            </h1>

            <p class="mt-3 max-w-2xl text-sm text-miseku-600 sm:text-base">
                Jelajahi merchandise dari berbagai artis independen Indonesia, mulai dari ready stock
                hingga pre-order, sesuai dengan fandom favoritmu.
            </p>

            {{-- Form GET biasa: submit = pindah ke halaman katalog, bukan menyaring di sini --}}
            <form action="{{ route('catalog') }}" method="GET" class="mt-6 flex max-w-2xl gap-2">
                <div class="relative flex-1">
                    <svg class="pointer-events-none absolute left-3.5 top-1/2 h-5 w-5 -translate-y-1/2 text-miseku-400"
                         fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="m21 21-5.2-5.2m2.2-5.3a7.5 7.5 0 1 1-15 0 7.5 7.5 0 0 1 15 0Z" />
                    </svg>
                    <input type="search" name="q" placeholder="Cari karya, karakter, fandom, atau toko…"
                           class="ms-input !py-3 pl-11" aria-label="Cari karya">
                </div>
                <button type="submit" class="ms-btn-primary !px-6">Cari</button>
            </form>

            <p class="mt-2 text-xs text-miseku-400">
                Pencarian dan filter lengkap ada di
                <a href="{{ route('catalog') }}" wire:navigate class="font-semibold text-miseku-500 hover:text-miseku-700">
                    halaman katalog
                </a>.
            </p>
        </div>
    </section>

    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">

        {{-- ======================= JELAJAHI FANDOM ======================= --}}
        <section>
            <h2 class="text-sm font-bold uppercase tracking-wide text-miseku-400">Jelajahi per fandom</h2>
            <div class="mt-3 flex flex-wrap gap-2">
                @foreach ($this->fandomPopuler as $fandom)
                    <a href="{{ route('catalog', ['fandom' => $fandom->id]) }}" wire:navigate
                       class="ms-chip bg-white text-miseku-600 ring-1 ring-miseku-200 transition hover:bg-miseku-100">
                        {{ $fandom->fandom_name }}
                        <span class="text-miseku-400">{{ $fandom->karya_count }}</span>
                    </a>
                @endforeach
                <a href="{{ route('catalog') }}" wire:navigate
                   class="ms-chip bg-miseku-500 text-white transition hover:bg-miseku-600">
                    Lihat semua karya &rarr;
                </a>
            </div>
        </section>

        {{-- ======================= REKOMENDASI ======================= --}}
        <section class="mt-10">
            <div class="flex flex-wrap items-baseline justify-between gap-2">
                <div>
                    <h2 class="text-lg font-bold text-miseku-900">
                        {{ $rekomendasi['personal'] ? 'Rekomendasi untukmu' : 'Paling disukai pembeli' }}
                    </h2>
                    <p class="mt-0.5 text-xs text-miseku-400">
                        @if ($rekomendasi['personal'])
                            Disusun dari karya yang pernah kamu lihat — bobot karakter 0,5 · fandom 0,3 · jenis 0,2.
                        @elseif (auth()->check())
                            Belum ada jejak penelusuran. Buka beberapa karya, lalu kembali ke sini.
                        @else
                            <a href="{{ route('login') }}" wire:navigate class="font-semibold text-miseku-500 hover:text-miseku-700">Masuk</a>
                            untuk mendapat rekomendasi sesuai fandom yang kamu telusuri.
                        @endif
                    </p>
                </div>
                <a href="{{ route('catalog', ['urut' => 'rating']) }}" wire:navigate
                   class="text-sm font-semibold text-miseku-500 hover:text-miseku-700">Lihat semua</a>
            </div>

            @if ($rekomendasi['items']->isEmpty())
                <x-empty-state class="mt-4" title="Belum ada karya untuk direkomendasikan" />
            @else
                <div class="mt-4 grid grid-cols-2 gap-4 sm:gap-5 md:grid-cols-3 lg:grid-cols-4">
                    @foreach ($rekomendasi['items'] as $product)
                        <x-product-card :product="$product" :key="'rec-'.$product->id" />
                    @endforeach
                </div>
            @endif
        </section>

        {{-- ======================= PRE-ORDER ======================= --}}
        @if ($this->preOrderBuka->isNotEmpty())
            <section class="mt-12">
                <div class="flex flex-wrap items-baseline justify-between gap-2">
                    <div>
                        <h2 class="text-lg font-bold text-miseku-900">Pre-order sedang dibuka</h2>
                        <p class="mt-0.5 text-xs text-miseku-400">Ikut sebelum periode PO-nya ditutup.</p>
                    </div>
                    <a href="{{ route('catalog', ['status' => 'pre_order']) }}" wire:navigate
                       class="text-sm font-semibold text-miseku-500 hover:text-miseku-700">Lihat semua</a>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-4 sm:gap-5 md:grid-cols-4">
                    @foreach ($this->preOrderBuka as $product)
                        <x-product-card :product="$product" :key="'po-'.$product->id" />
                    @endforeach
                </div>
            </section>
        @endif

        {{-- ======================= TERBARU ======================= --}}
        <section class="mt-12">
            <div class="flex flex-wrap items-baseline justify-between gap-2">
                <div>
                    <h2 class="text-lg font-bold text-miseku-900">Baru dirilis</h2>
                    <p class="mt-0.5 text-xs text-miseku-400">Karya yang paling baru diunggah artis.</p>
                </div>
                <a href="{{ route('catalog') }}" wire:navigate
                   class="text-sm font-semibold text-miseku-500 hover:text-miseku-700">Lihat semua</a>
            </div>

            <div class="mt-4 grid grid-cols-2 gap-4 sm:gap-5 md:grid-cols-4">
                @foreach ($this->terbaru as $product)
                    <x-product-card :product="$product" :key="'new-'.$product->id" />
                @endforeach
            </div>
        </section>
    </div>
</div>
