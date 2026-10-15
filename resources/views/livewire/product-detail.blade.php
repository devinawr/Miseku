@php
    $batch   = $product->openBatch;
    $badge   = $product->status_badge;
    $chip    = ['ready' => 'ms-chip-ready', 'po' => 'ms-chip-po', 'muted' => 'ms-chip-muted'][$badge['tone']];
    $images  = $product->images->isNotEmpty() ? $product->images->pluck('url')->all() : [$product->cover_url];
    $blocked = $this->unavailableReason;
    $isOwn   = $this->isOwnProduct();
    $artist  = $product->artistProfile;
@endphp

<div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">

    {{-- Breadcrumb --}}
    <nav class="flex flex-wrap items-center gap-1.5 text-xs font-medium text-ink-500" aria-label="Breadcrumb">
        <a href="{{ route('home') }}" wire:navigate class="hover:text-brand-700">Beranda</a>
        <x-icon name="chevron-right" class="h-3 w-3" />
        <a href="{{ route('catalog', ['fandom' => $product->character->fandom_id]) }}" wire:navigate class="hover:text-brand-700">
            {{ $product->character->fandom->fandom_name }}
        </a>
        <x-icon name="chevron-right" class="h-3 w-3" />
        <a href="{{ route('catalog', ['fandom' => $product->character->fandom_id, 'karakter' => $product->fandom_character_id]) }}" wire:navigate class="hover:text-brand-700">
            {{ $product->character->character_name }}
        </a>
    </nav>

    <div class="mt-5 grid gap-8 lg:grid-cols-12">

        {{-- ============ Galeri ============ --}}
        <div class="lg:col-span-6" x-data="{ active: 0, images: @js($images) }">
            <div class="relative overflow-hidden rounded-[var(--radius-card)] border border-ink-200/70 bg-white">
                <div class="aspect-square bg-ink-100">
                    <template x-for="(img, i) in images" :key="i">
                        <img x-show="active === i" :src="img" alt="{{ $product->product_name }}" class="h-full w-full object-cover">
                    </template>
                </div>

                <span class="{{ $chip }} absolute top-4 left-4 shadow-sm">{{ $badge['label'] }}</span>

                <template x-if="images.length > 1">
                    <div>
                        <button type="button" @click="active = (active - 1 + images.length) % images.length" class="ms-icon-btn absolute top-1/2 left-3 -translate-y-1/2 bg-white/90 shadow" aria-label="Sebelumnya">
                            <x-icon name="chevron-left" class="h-5 w-5" />
                        </button>
                        <button type="button" @click="active = (active + 1) % images.length" class="ms-icon-btn absolute top-1/2 right-3 -translate-y-1/2 bg-white/90 shadow" aria-label="Berikutnya">
                            <x-icon name="chevron-right" class="h-5 w-5" />
                        </button>
                        <span class="absolute right-4 bottom-4 rounded-full bg-ink-900/70 px-2.5 py-1 text-xs font-bold text-white" x-text="(active + 1) + ' / ' + images.length"></span>
                    </div>
                </template>
            </div>

            <div class="ms-scroll-x mt-3 flex gap-2.5 pb-1" x-show="images.length > 1">
                <template x-for="(img, i) in images" :key="'t-'+i">
                    <button type="button" @click="active = i" class="h-18 w-18 shrink-0 overflow-hidden rounded-xl border-2 transition"
                            :class="active === i ? 'border-brand-500' : 'border-transparent opacity-70 hover:opacity-100'">
                        <img :src="img" alt="" class="h-full w-full object-cover">
                    </button>
                </template>
            </div>
        </div>

        {{-- ============ Info & aksi ============ --}}
        <div class="lg:col-span-6">
            <div class="flex flex-wrap gap-1.5">
                <a href="{{ route('catalog', ['fandom' => $product->character->fandom_id]) }}" wire:navigate class="ms-pill">{{ $product->character->fandom->fandom_name }}</a>
                <span class="ms-pill">{{ $product->character->character_name }}</span>
                <a href="{{ route('catalog', ['tipe' => $product->merchandise_type_id]) }}" wire:navigate class="ms-pill">{{ $product->merchandiseType->type_name }}</a>
            </div>

            <h1 class="mt-3 text-2xl leading-tight font-extrabold tracking-tight text-ink-900 sm:text-3xl">{{ $product->product_name }}</h1>

            <div class="mt-2.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-ink-500">
                @if ($product->rating_count > 0)
                    <x-star-rating :rating="$product->rating" :count="$product->rating_count" size="md" />
                @else
                    <span>Belum ada ulasan</span>
                @endif
                <span class="text-ink-300">•</span>
                @if ($product->is_pre_order)
                    <span>Sistem pre-order</span>
                @else
                    <span>Stok <b class="text-ink-800">{{ $product->stock }}</b></span>
                @endif
            </div>

            {{-- Harga --}}
            <div class="ms-gradient-soft mt-5 rounded-[var(--radius-card)] p-5 ring-1 ring-brand-100">
                <p class="text-xs font-bold tracking-wide text-brand-700 uppercase">{{ $product->is_pre_order ? 'Harga pre-order' : 'Harga' }}</p>
                <div class="mt-1 flex flex-wrap items-end gap-3">
                    <x-rupiah :value="$product->display_price" class="text-3xl font-extrabold text-ink-900" />
                    @if ($product->is_pre_order && $batch && $batch->price_po != $product->price)
                        <x-rupiah :value="$product->price" class="pb-1 text-sm text-ink-400 line-through" />
                    @endif
                </div>

                @if ($product->is_pre_order && $batch)
                    @php $daysLeft = (int) ceil(now()->diffInDays($batch->closes_at, false)); @endphp
                    <dl class="mt-4 grid gap-3 border-t border-brand-200/60 pt-4 text-sm sm:grid-cols-2">
                        <div class="flex gap-2.5">
                            <x-icon name="clock" class="mt-0.5 h-4 w-4 shrink-0 text-accent-600" />
                            <div>
                                <dt class="text-xs text-ink-500">PO ditutup</dt>
                                <dd class="font-bold text-ink-900">{{ $batch->closes_at->translatedFormat('d M Y') }}
                                    <span class="text-xs font-semibold text-accent-600">· {{ $daysLeft <= 0 ? 'hari ini' : $daysLeft.' hari lagi' }}</span></dd>
                            </div>
                        </div>
                        <div class="flex gap-2.5">
                            <x-icon name="shield" class="mt-0.5 h-4 w-4 shrink-0 text-accent-600" />
                            <div>
                                <dt class="text-xs text-ink-500">Skema bayar</dt>
                                <dd class="font-bold text-ink-900">
                                    @if ($batch->is_dp) DP <x-rupiah :value="$batch->dp_amount" /> <span class="text-xs font-medium text-ink-500">· sisa dilunasi nanti</span>
                                    @else Bayar penuh @endif
                                </dd>
                            </div>
                        </div>
                        @if ($batch->estimated_month)
                            <div class="flex gap-2.5">
                                <x-icon name="calendar" class="mt-0.5 h-4 w-4 shrink-0 text-accent-600" />
                                <div>
                                    <dt class="text-xs text-ink-500">Estimasi jadi</dt>
                                    <dd class="font-bold text-ink-900">{{ $batch->estimated_month->translatedFormat('F Y') }}</dd>
                                </div>
                            </div>
                        @endif
                        @if ($batch->min_order || $batch->max_slot)
                            <div class="flex gap-2.5">
                                <x-icon name="squares" class="mt-0.5 h-4 w-4 shrink-0 text-accent-600" />
                                <div>
                                    <dt class="text-xs text-ink-500">Kuota produksi</dt>
                                    <dd class="font-bold text-ink-900">
                                        @if ($batch->min_order) min. {{ $batch->min_order }} pcs @endif
                                        @if ($batch->min_order && $batch->max_slot) · @endif
                                        @if ($batch->max_slot) maks. {{ $batch->max_slot }} slot @endif
                                    </dd>
                                </div>
                            </div>
                        @endif
                    </dl>
                @endif
            </div>

            {{-- Kuantitas + tombol --}}
            @if ($isOwn)
                <div class="mt-5 flex flex-wrap items-center gap-3 rounded-2xl border border-dashed border-brand-300 bg-brand-50 p-4 text-sm text-brand-800">
                    <x-icon name="store" class="h-5 w-5" />
                    <span class="flex-1">Ini produk dari tokomu sendiri.</span>
                    <a href="{{ route('studio.product.edit', $product) }}" wire:navigate class="ms-btn-solid">
                        <x-icon name="pencil" class="h-4 w-4" /> Ubah produk
                    </a>
                </div>
            @elseif ($blocked)
                <div class="mt-5 flex items-center gap-2 rounded-2xl bg-ink-100 p-4 text-sm font-semibold text-ink-600">
                    <x-icon name="info" class="h-5 w-5" /> {{ $blocked }}
                </div>
            @else
                <div class="mt-5">
                    <p class="ms-label">Jumlah</p>
                    <div class="flex flex-wrap items-center gap-3">
                        <div class="flex items-center rounded-xl border border-ink-200 bg-white">
                            <button type="button" wire:click="decrement" class="flex h-11 w-11 items-center justify-center text-ink-600 hover:text-brand-700 disabled:opacity-40" @disabled($quantity <= 1) aria-label="Kurangi">
                                <x-icon name="minus" class="h-4 w-4" stroke="2.4" />
                            </button>
                            <input type="number" wire:model.live.debounce.500ms="quantity" min="1" max="{{ $this->maxQuantity }}"
                                   class="h-11 w-14 border-0 bg-transparent text-center text-sm font-bold text-ink-900 focus:ring-0 focus:outline-none" aria-label="Jumlah">
                            <button type="button" wire:click="increment" class="flex h-11 w-11 items-center justify-center text-ink-600 hover:text-brand-700 disabled:opacity-40" @disabled($quantity >= $this->maxQuantity) aria-label="Tambah">
                                <x-icon name="plus" class="h-4 w-4" stroke="2.4" />
                            </button>
                        </div>
                        <span class="text-xs text-ink-500">
                            {{ $product->is_pre_order ? 'Maks. '.$this->maxQuantity.' pcs per pesanan' : 'Tersisa '.$product->stock.' pcs' }}
                        </span>
                    </div>

                    @if ($product->is_pre_order && $batch?->is_dp)
                        <p class="ms-label mt-4">Skema bayar</p>
                        <div class="grid gap-2 sm:grid-cols-2">
                            @foreach (['dp' => ['Bayar DP dulu', 'DP '.\App\Support\Money::rp($batch->dp_amount).'/pcs, sisa dilunasi saat produksi selesai'], 'full' => ['Bayar lunas', 'Langsung bayar penuh '.\App\Support\Money::rp($batch->price_po ?: $product->price).'/pcs']] as $key => [$label, $hint])
                                <label wire:key="scheme-{{ $key }}" class="flex cursor-pointer items-start gap-2.5 rounded-xl border-2 p-3 text-sm transition {{ $scheme === $key ? 'border-brand-500 bg-brand-50' : 'border-ink-200 hover:border-brand-300' }}">
                                    <input type="radio" wire:model.live="scheme" value="{{ $key }}" class="mt-0.5 text-brand-600">
                                    <span><span class="block font-bold text-ink-900">{{ $label }}</span><span class="block text-xs text-ink-500">{{ $hint }}</span></span>
                                </label>
                            @endforeach
                        </div>
                    @endif

                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <button type="button" wire:click="addToCart" wire:loading.attr="disabled" class="ms-btn-soft py-3">
                            <x-icon name="cart" class="h-5 w-5" /> + Keranjang
                        </button>
                        <button type="button" wire:click="buyNow" wire:loading.attr="disabled" class="ms-btn-primary py-3">
                            {{ $product->is_pre_order ? 'Ikut pre-order' : 'Beli sekarang' }}
                        </button>
                    </div>
                </div>
            @endif

            <div class="mt-4 flex flex-wrap gap-2 text-xs">
                @if ($product->size)
                    <span class="ms-chip-muted"><x-icon name="squares" class="h-3.5 w-3.5" /> Ukuran {{ $product->size }}</span>
                @endif
                @if ($product->allow_event_pickup)
                    <span class="ms-chip-muted"><x-icon name="calendar" class="h-3.5 w-3.5" /> Bisa ambil di booth event</span>
                @endif
                <span class="ms-chip-muted"><x-icon name="truck" class="h-3.5 w-3.5" /> Dikirim dari toko artis</span>
            </div>

            {{-- Kartu toko --}}
            <div class="ms-card mt-6 flex items-center gap-4 p-4">
                <x-avatar :user="$artist->user" class="h-14 w-14 text-lg" />
                <div class="min-w-0 flex-1">
                    <p class="truncate font-bold text-ink-900">{{ $artist->shop_name }}</p>
                    @if ($artist->rating_count > 0)
                        <x-star-rating :rating="$artist->rating" :count="$artist->rating_count" size="sm" />
                    @else
                        <p class="text-xs text-ink-500">Toko baru di Miseku</p>
                    @endif
                </div>
                <div class="flex shrink-0 flex-wrap justify-end gap-2">
                    @unless ($isOwn)
                        <a href="{{ auth()->check() ? route('chat', ['with' => $artist->user_id, 'produk' => $product->id]) : route('login') }}" wire:navigate class="ms-btn-soft">
                            <x-icon name="chat" class="h-4 w-4" /> Chat artis
                        </a>
                    @endunless
                    <a href="{{ route('artist.shop', $artist) }}" wire:navigate class="ms-btn-ghost">
                        <x-icon name="store" class="h-4 w-4" /> Kunjungi
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- ============ Deskripsi & ulasan ============ --}}
    <div class="mt-12 grid gap-6 lg:grid-cols-12">
        <section class="ms-card p-6 lg:col-span-7">
            <h2 class="ms-section-title">Deskripsi produk</h2>
            <div class="mt-3 text-sm leading-relaxed whitespace-pre-line text-ink-600">{{ $product->description ?: 'Artis belum menambahkan deskripsi.' }}</div>

            <dl class="mt-6 grid gap-x-6 gap-y-4 border-t border-ink-100 pt-5 text-sm sm:grid-cols-2">
                @foreach ([
                    'Jenis merchandise' => $product->merchandiseType->type_name,
                    'Fandom'            => $product->character->fandom->fandom_name,
                    'Karakter'          => $product->character->character_name,
                    'Ukuran'            => $product->size ?: '—',
                    'Ketersediaan'      => $product->is_pre_order ? 'Pre-order' : 'Ready stock',
                    'Dirilis'           => $product->created_at?->translatedFormat('d F Y'),
                ] as $label => $value)
                    <div>
                        <dt class="text-xs text-ink-500">{{ $label }}</dt>
                        <dd class="font-semibold text-ink-900">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>

        <section class="ms-card p-6 lg:col-span-5">
            <h2 class="ms-section-title">Ulasan produk</h2>

            <div class="mt-4 flex items-center gap-5">
                <div class="text-center">
                    <p class="text-4xl leading-none font-extrabold text-ink-900">{{ number_format((float) $product->rating, 1, ',', '.') }}</p>
                    <x-star-rating :rating="$product->rating" size="sm" class="mt-1.5" :show-value="false" />
                    <p class="mt-1 text-xs text-ink-500">{{ $product->rating_count }} ulasan</p>
                </div>
                <div class="flex-1 space-y-1.5">
                    @foreach ($this->ratingBreakdown as $star => $row)
                        <div class="flex items-center gap-2 text-xs">
                            <span class="w-3 text-right font-bold text-ink-600">{{ $star }}</span>
                            <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-ink-100">
                                <div class="ms-gradient h-full rounded-full" style="width: {{ $row['percent'] }}%"></div>
                            </div>
                            <span class="w-6 text-ink-400">{{ $row['count'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="mt-6 space-y-5 border-t border-ink-100 pt-5">
                @forelse ($this->reviews as $review)
                    <article class="flex gap-3">
                        <x-avatar :user="$review->reviewer" class="h-9 w-9 text-sm" />
                        <div class="min-w-0">
                            <p class="text-sm font-bold text-ink-900">{{ $review->reviewer->name }}
                                <span class="ml-1 text-xs font-normal text-ink-400">{{ $review->created_at->diffForHumans() }}</span></p>
                            <x-star-rating :rating="$review->rating" size="sm" class="mt-0.5" :show-value="false" />
                            @if ($review->comment)
                                <p class="mt-1.5 text-sm leading-relaxed text-ink-600">{{ $review->comment }}</p>
                            @endif
                        </div>
                    </article>
                @empty
                    <p class="text-sm text-ink-500">Belum ada ulasan. Ulasan ditulis pembeli setelah pesanan selesai.</p>
                @endforelse
            </div>
        </section>
    </div>

    {{-- ============ Produk serupa (CBF item-to-item) ============ --}}
    @if ($this->similar->isNotEmpty())
        <section class="mt-12">
            {{-- Urutan tetap dihitung CBF (cosine similarity), tapi angka % tidak ditampilkan ke pengguna --}}
            <x-section-heading title="Produk serupa" icon="sparkles" />
            <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                @foreach ($this->similar as $item)
                    <x-product-card :product="$item" wire:key="s-{{ $item->id }}" />
                @endforeach
            </div>
        </section>
    @endif

    @if ($this->moreFromArtist->isNotEmpty())
        <section class="mt-12">
            <x-section-heading :title="'Produk lain dari '.$artist->shop_name" :href="route('artist.shop', $artist)" link-label="Lihat toko" />
            <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                @foreach ($this->moreFromArtist as $other)
                    <x-product-card :product="$other" :show-artist="false" wire:key="o-{{ $other->id }}" />
                @endforeach
            </div>
        </section>
    @endif
</div>
