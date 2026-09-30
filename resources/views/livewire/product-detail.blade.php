@php
    $batch  = $product->openBatch;
    $badge  = $product->status_badge;
    $images = $product->images->isNotEmpty()
        ? $product->images->pluck('url')->all()
        : [$product->cover_url];
@endphp

<div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">

    {{-- ======================= BREADCRUMB ======================= --}}
    <nav class="flex flex-wrap items-center gap-1.5 text-xs text-miseku-400" aria-label="Breadcrumb">
        <a href="{{ route('catalog') }}" wire:navigate class="hover:text-miseku-600">Katalog</a>
        <span>/</span>
        <a href="{{ route('catalog', ['fandom' => $product->character->fandom_id]) }}" wire:navigate
           class="hover:text-miseku-600">{{ $product->character->fandom->fandom_name }}</a>
        <span>/</span>
        <span class="font-medium text-miseku-600">{{ $product->character->character_name }}</span>
    </nav>

    <div class="mt-5 grid gap-8 lg:grid-cols-12">

        {{-- ======================= GALERI (Alpine) ======================= --}}
        <div class="lg:col-span-6" x-data="{ active: 0, images: {{ Js::from($images) }}, zoom: false }">
            <div class="ms-card relative overflow-hidden">
                <div class="aspect-square bg-miseku-100">
                    <template x-for="(img, i) in images" :key="i">
                        <img x-show="active === i" x-transition.opacity :src="img"
                             alt="{{ $product->product_name }}"
                             class="h-full w-full object-cover transition-transform duration-300"
                             :class="zoom && 'scale-125'"
                             @click="zoom = !zoom">
                    </template>
                </div>

                <span @class([
                    'ms-chip absolute left-4 top-4',
                    'bg-miseku-100 text-miseku-700' => $badge['tone'] === 'ready',
                    'bg-sakura-400/25 text-sakura-500' => $badge['tone'] === 'po',
                    'bg-miseku-100 text-miseku-400' => $badge['tone'] === 'muted',
                ])>{{ $badge['label'] }}</span>

                {{-- Navigasi kiri/kanan, hanya tampil bila gambar lebih dari satu --}}
                <template x-if="images.length > 1">
                    <div>
                        <button type="button" @click="active = (active - 1 + images.length) % images.length"
                                class="absolute left-3 top-1/2 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 text-miseku-600 shadow-[var(--shadow-soft)] hover:bg-white">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                            </svg>
                        </button>
                        <button type="button" @click="active = (active + 1) % images.length"
                                class="absolute right-3 top-1/2 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 text-miseku-600 shadow-[var(--shadow-soft)] hover:bg-white">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                            </svg>
                        </button>
                    </div>
                </template>
            </div>

            {{-- Thumbnail --}}
            <div class="mt-3 flex gap-3 overflow-x-auto pb-1">
                <template x-for="(img, i) in images" :key="'t-'+i">
                    <button type="button" @click="active = i; zoom = false"
                            class="h-20 w-20 shrink-0 overflow-hidden rounded-xl border-2 transition"
                            :class="active === i ? 'border-miseku-500' : 'border-miseku-200 hover:border-miseku-300'">
                        <img :src="img" alt="" class="h-full w-full object-cover">
                    </button>
                </template>
            </div>
        </div>

        {{-- ======================= INFO & AKSI ======================= --}}
        <div class="lg:col-span-6">
            <p class="text-xs font-semibold uppercase tracking-wide text-miseku-400">
                {{ $product->merchandiseType->type_name }} ·
                {{ $product->character->fandom->fandom_name }} ·
                {{ $product->character->character_name }}
            </p>

            <h1 class="mt-2 text-2xl font-extrabold leading-snug tracking-tight text-miseku-900 sm:text-3xl">
                {{ $product->product_name }}
            </h1>

            <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2">
                @if ($product->rating_count > 0)
                    <x-star-rating :rating="$product->rating" :count="$product->rating_count" size="md" />
                @else
                    <span class="text-sm text-miseku-400">Belum ada ulasan produk</span>
                @endif
                <span class="text-miseku-200">|</span>
                <span class="text-sm text-miseku-500">
                    @if ($product->is_pre_order)
                        Sistem pre-order
                    @else
                        Stok tersedia: <span class="font-semibold text-miseku-700">{{ $product->stock }}</span>
                    @endif
                </span>
            </div>

            {{-- Harga --}}
            <div class="mt-5 rounded-[var(--radius-card)] bg-gradient-to-br from-miseku-100 to-miseku-50 p-5 ring-1 ring-miseku-200/70">
                <p class="text-xs font-semibold uppercase tracking-wide text-miseku-500">
                    {{ $product->is_pre_order ? 'Harga pre-order' : 'Harga' }}
                </p>
                <div class="mt-1 flex flex-wrap items-end gap-3">
                    <x-rupiah :value="$product->display_price" class="text-3xl font-extrabold text-miseku-700" />
                    @if ($product->is_pre_order && $batch && $batch->price_po != $product->price)
                        <x-rupiah :value="$product->price" class="text-sm text-miseku-400 line-through" />
                    @endif
                </div>

                @if ($batch)
                    <dl class="mt-4 grid gap-x-6 gap-y-3 border-t border-miseku-200/70 pt-4 text-sm sm:grid-cols-2">
                        @if ($batch->is_dp)
                            <div>
                                <dt class="text-xs font-medium text-miseku-500">Bayar di muka (DP)</dt>
                                <dd class="font-bold text-miseku-800">
                                    <x-rupiah :value="$batch->dp_amount" />
                                    <span class="text-xs font-medium text-miseku-500">
                                        · sisa dilunasi setelah produksi
                                    </span>
                                </dd>
                            </div>
                        @else
                            <div>
                                <dt class="text-xs font-medium text-miseku-500">Skema pembayaran</dt>
                                <dd class="font-bold text-miseku-800">Bayar penuh</dd>
                            </div>
                        @endif

                        <div>
                            <dt class="text-xs font-medium text-miseku-500">PO ditutup</dt>
                            <dd class="font-bold text-miseku-800">
                                {{ $batch->closes_at->translatedFormat('d M Y') }}
                                <span class="text-xs font-medium text-miseku-500">
                                    · {{ $batch->closes_at->diffForHumans(['short' => true]) }}
                                </span>
                            </dd>
                        </div>

                        @if ($batch->estimated_month)
                            <div>
                                <dt class="text-xs font-medium text-miseku-500">Estimasi barang jadi</dt>
                                <dd class="font-bold text-miseku-800">{{ $batch->estimated_month->translatedFormat('F Y') }}</dd>
                            </div>
                        @endif

                        @if ($batch->min_order)
                            <div>
                                <dt class="text-xs font-medium text-miseku-500">Minimum produksi</dt>
                                <dd class="font-bold text-miseku-800">{{ $batch->min_order }} pcs</dd>
                            </div>
                        @endif
                    </dl>
                @endif
            </div>

            {{-- Kuantitas + tombol --}}
            <div class="mt-5 flex flex-wrap items-center gap-3">
                <div class="flex items-center rounded-xl border border-miseku-200 bg-white">
                    <button type="button" wire:click="decrement"
                            class="flex h-11 w-11 items-center justify-center rounded-l-xl text-miseku-600 hover:bg-miseku-100 disabled:text-miseku-300"
                            @disabled($quantity <= 1)>
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" d="M5 12h14" />
                        </svg>
                    </button>
                    <input type="number" wire:model.live.debounce.600ms="quantity" min="1"
                           class="h-11 w-14 border-0 bg-transparent text-center text-sm font-bold text-miseku-800 focus:outline-none"
                           aria-label="Jumlah">
                    <button type="button" wire:click="increment"
                            class="flex h-11 w-11 items-center justify-center rounded-r-xl text-miseku-600 hover:bg-miseku-100">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" d="M12 5v14M5 12h14" />
                        </svg>
                    </button>
                </div>

                <button type="button" wire:click="addToCart" class="ms-btn-primary flex-1 sm:flex-none sm:px-7"
                        @disabled($product->is_sold_out || ($product->is_pre_order && ! $batch))>
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 1 0 0 6 3 3 0 0 0 0-6Zm11.25 0a3 3 0 1 0 0 6 3 3 0 0 0 0-6ZM6.106 5.272 7.5 14.25h11.25l1.5-7.5H5.106Z" />
                    </svg>
                    @if ($product->is_sold_out)
                        Stok habis
                    @elseif ($product->is_pre_order && ! $batch)
                        Periode PO ditutup
                    @elseif ($product->is_pre_order)
                        Ikut pre-order
                    @else
                        Masukkan keranjang
                    @endif
                </button>

                <button type="button" wire:click="addToCart" class="ms-btn-ghost">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M8.625 9.75h.008v.008h-.008V9.75Zm3.375 0h.008v.008H12V9.75Zm3.375 0h.008v.008h-.008V9.75ZM21 12c0 4.14-4.03 7.5-9 7.5a9.7 9.7 0 0 1-2.6-.35l-3.9 1.35 1.28-3.1C3.66 16.1 3 14.14 3 12c0-4.14 4.03-7.5 9-7.5s9 3.36 9 7.5Z" />
                    </svg>
                    Chat artis
                </button>
            </div>

            <div class="mt-3 flex flex-wrap gap-2 text-xs text-miseku-500">
                @if ($product->size)
                    <span class="ms-chip bg-miseku-100 text-miseku-600">Ukuran {{ $product->size }}</span>
                @endif
                @if ($product->allow_event_pickup)
                    <span class="ms-chip bg-miseku-100 text-miseku-600">Bisa diambil di booth event</span>
                @endif
                <span class="ms-chip bg-miseku-100 text-miseku-600">Dikirim dari toko artis</span>
            </div>

            {{-- Kartu artis --}}
            <div class="ms-card mt-6 flex items-center gap-4 p-4">
                <img src="{{ $product->artistProfile->user->avatar_url }}"
                     alt="{{ $product->artistProfile->shop_name }}"
                     class="h-14 w-14 shrink-0 rounded-full object-cover ring-2 ring-miseku-200">
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-bold text-miseku-900">{{ $product->artistProfile->shop_name }}</p>
                    <div class="mt-0.5 flex items-center gap-2">
                        <x-star-rating :rating="$product->artistProfile->rating"
                                       :count="$product->artistProfile->rating_count" size="sm" />
                    </div>
                </div>
                <a href="{{ route('artist.shop', $product->artistProfile) }}" wire:navigate class="ms-btn-ghost shrink-0">
                    Kunjungi toko
                </a>
            </div>
        </div>
    </div>

    {{-- ======================= DESKRIPSI & ULASAN ======================= --}}
    <div class="mt-12 grid gap-8 lg:grid-cols-12">
        <section class="ms-card p-6 lg:col-span-7">
            <h2 class="text-lg font-bold text-miseku-900">Deskripsi karya</h2>
            <div class="prose-sm mt-3 whitespace-pre-line text-sm leading-relaxed text-miseku-600">
                {{ $product->description ?: 'Artis belum menambahkan deskripsi untuk karya ini.' }}
            </div>

            <h3 class="mt-6 border-t border-miseku-100 pt-5 text-sm font-bold text-miseku-900">Informasi produk</h3>

            <dl class="mt-3 grid gap-4 text-sm sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-medium text-miseku-400">Nama produk</dt>
                    <dd class="font-semibold text-miseku-800">{{ $product->product_name }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-miseku-400">Jenis merchandise</dt>
                    <dd class="font-semibold text-miseku-800">{{ $product->merchandiseType->type_name }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-miseku-400">Fandom</dt>
                    <dd class="font-semibold text-miseku-800">{{ $product->character->fandom->fandom_name }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-miseku-400">Karakter</dt>
                    <dd class="font-semibold text-miseku-800">{{ $product->character->character_name }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-miseku-400">Ukuran produk</dt>
                    <dd class="font-semibold text-miseku-800">{{ $product->size ?: 'Tidak dicantumkan artis' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-miseku-400">Harga</dt>
                    <dd class="font-semibold text-miseku-800">
                        <x-rupiah :value="$product->price" />
                        @if ($product->is_pre_order && $batch && $batch->price_po != $product->price)
                            <span class="text-xs font-medium text-miseku-500">
                                (harga PO <x-rupiah :value="$batch->price_po" />)
                            </span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-miseku-400">Ketersediaan</dt>
                    <dd class="font-semibold text-miseku-800">
                        {{ $product->is_pre_order ? 'Pre-order' : 'Ready stock' }}
                        @unless ($product->is_pre_order)
                            <span class="text-xs font-medium text-miseku-500">· sisa {{ $product->stock }} pcs</span>
                        @endunless
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-miseku-400">Dirilis</dt>
                    <dd class="font-semibold text-miseku-800">{{ $product->created_at->translatedFormat('d F Y') }}</dd>
                </div>

                @if ($product->is_pre_order)
                    <div>
                        <dt class="text-xs font-medium text-miseku-400">PO dibuka sampai</dt>
                        <dd class="font-semibold text-miseku-800">
                            {{ $batch?->closes_at?->translatedFormat('d F Y') ?? 'Belum ada batch PO yang dibuka' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-miseku-400">Estimasi barang jadi</dt>
                        <dd class="font-semibold text-miseku-800">
                            {{ $batch?->estimated_month?->translatedFormat('F Y') ?? 'Belum ditentukan' }}
                        </dd>
                    </div>
                @endif
            </dl>
        </section>

        <section class="lg:col-span-5">
            <div class="ms-card p-6">
                <h2 class="text-lg font-bold text-miseku-900">Ulasan produk</h2>

                <div class="mt-4 flex items-center gap-5">
                    <div class="text-center">
                        <p class="text-4xl font-extrabold leading-none text-miseku-700">
                            {{ number_format((float) $product->rating, 1, ',', '.') }}
                        </p>
                        <x-star-rating :rating="$product->rating" size="sm" class="mt-1.5" :show-value="false" />
                        <p class="mt-1 text-xs text-miseku-400">{{ $product->rating_count }} ulasan</p>
                    </div>

                    <div class="flex-1 space-y-1.5">
                        @foreach ($this->ratingBreakdown as $star => $row)
                            <div class="flex items-center gap-2">
                                <span class="w-3 text-right text-xs font-semibold text-miseku-500">{{ $star }}</span>
                                <svg class="h-3 w-3 text-miseku-300" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M10 1.6l2.6 5.3 5.8.8-4.2 4.1 1 5.8-5.2-2.8-5.2 2.8 1-5.8L1.6 7.7l5.8-.8L10 1.6z"/>
                                </svg>
                                <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-miseku-100">
                                    <div class="h-full rounded-full bg-miseku-400" style="width: {{ $row['percent'] }}%"></div>
                                </div>
                                <span class="w-6 text-xs text-miseku-400">{{ $row['count'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="mt-6 space-y-5 border-t border-miseku-100 pt-5">
                    @forelse ($this->reviews as $review)
                        <article class="flex gap-3">
                            <img src="{{ $review->reviewer->avatar_url }}" alt=""
                                 class="h-9 w-9 shrink-0 rounded-full object-cover ring-1 ring-miseku-200">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-x-2">
                                    <p class="text-sm font-semibold text-miseku-800">{{ $review->reviewer->name }}</p>
                                    <span class="text-xs text-miseku-300">{{ $review->created_at->diffForHumans() }}</span>
                                </div>
                                <x-star-rating :rating="$review->rating" size="sm" class="mt-0.5" :show-value="false" />
                                @if ($review->comment)
                                    <p class="mt-1.5 text-sm leading-relaxed text-miseku-600">{{ $review->comment }}</p>
                                @endif
                            </div>
                        </article>
                    @empty
                        <p class="text-sm text-miseku-400">
                            Belum ada ulasan. Ulasan hanya bisa ditulis pembeli setelah pesanannya selesai.
                        </p>
                    @endforelse
                </div>
            </div>
        </section>
    </div>

    {{-- ======================= KARYA LAIN ======================= --}}
    @if ($this->moreFromArtist->isNotEmpty())
        <section class="mt-12">
            <div class="flex items-baseline justify-between gap-4">
                <h2 class="text-lg font-bold text-miseku-900">
                    Karya lain dari {{ $product->artistProfile->shop_name }}
                </h2>
                <a href="{{ route('artist.shop', $product->artistProfile) }}" wire:navigate
                   class="text-sm font-semibold text-miseku-500 hover:text-miseku-700">Lihat semua</a>
            </div>
            <div class="mt-4 grid grid-cols-2 gap-4 sm:gap-5 md:grid-cols-4">
                @foreach ($this->moreFromArtist as $other)
                    <x-product-card :product="$other" :show-artist="false" :key="'o-'.$other->id" />
                @endforeach
            </div>
        </section>
    @endif
</div>
