<div>
    {{-- Urutan beranda mengikuti survei pengguna: banner promo → rekomendasi → produk terbaru → pre-order → fandom --}}
    {{-- ======================= BANNER SLIDER =======================
         Banner = gambar jadi (public/img/banner/*.jpg), 1 per layar di HP, 2 per layar di desktop. --}}
    @php $slides = $this->slides; @endphp
    @if ($slides)
    <section class="mx-auto max-w-7xl px-4 pt-6 sm:px-6 lg:px-8"
             x-data="{ i: 0, n: {{ count($slides) }}, per: 1, t: null,
                       get last() { return Math.max(0, this.n - this.per) },
                       fit() { this.per = window.innerWidth >= 768 ? 2 : 1; this.i = Math.min(this.i, this.last) },
                       go(k) { this.i = k > this.last ? 0 : (k < 0 ? this.last : k) },
                       play() { clearInterval(this.t); this.t = setInterval(() => this.go(this.i + 1), 5000) } }"
             x-init="fit(); play()" @resize.window="fit()" @mouseenter="clearInterval(t)" @mouseleave="play()">
        <div class="relative">
            <div class="-mx-2 overflow-hidden">
                <div class="flex transition-transform duration-700 ease-out" :style="`transform: translateX(-${i * (100 / per)}%)`">
                    @foreach ($slides as $k => $s)
                        <a href="{{ $s['href'] }}" wire:navigate wire:key="slide-{{ $k }}" class="group block w-full shrink-0 px-2 md:w-1/2">
                            <div class="relative aspect-[20/11] overflow-hidden rounded-2xl bg-ink-100 shadow-[var(--shadow-lift)]">
                                <img src="{{ $s['image'] }}" alt="{{ $s['alt'] }}" class="h-full w-full object-cover transition duration-700 group-hover:scale-[1.03]" @if ($k > 1) loading="lazy" @endif>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>

            <template x-if="n > per">
                <div>
                    <button type="button" @click="go(i - 1)" class="absolute top-1/2 -left-2 z-10 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 text-ink-800 shadow-lg backdrop-blur hover:bg-white sm:-left-4" aria-label="Sebelumnya">
                        <x-icon name="chevron-left" class="h-5 w-5" stroke="2.4" />
                    </button>
                    <button type="button" @click="go(i + 1)" class="absolute top-1/2 -right-2 z-10 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 text-ink-800 shadow-lg backdrop-blur hover:bg-white sm:-right-4" aria-label="Berikutnya">
                        <x-icon name="chevron-right" class="h-5 w-5" stroke="2.4" />
                    </button>
                </div>
            </template>
        </div>
        <div class="mt-3 flex justify-center gap-1.5" x-show="n > per">
            @foreach ($slides as $k => $s)
                <button type="button" @click="go({{ $k }})" x-show="{{ $k }} <= last" class="h-2 rounded-full bg-brand-500 transition-all"
                        :class="i === {{ $k }} ? 'w-7 opacity-100' : 'w-2 opacity-30'" aria-label="Slide {{ $k + 1 }}"></button>
            @endforeach
        </div>
    </section>
    @endif

    {{-- ======================= BAND TEMA FANDOM (skin aktif) =======================
         Hanya tampil kalau ada tema yang dipakai. Isi beranda (slider & rekomendasi CBF) tidak berubah. --}}
    @php $themeNow = app(\App\Services\ThemeService::class)->current(); $tm = $themeNow['theme']; @endphp
    @if ($tm)
        <section class="mx-auto max-w-7xl px-4 pt-6 sm:px-6 lg:px-8">
            <div class="relative overflow-hidden rounded-3xl text-white shadow-[var(--shadow-glow)]"
                 style="background-image: linear-gradient(110deg, {{ $tm->primary_color }} 0%, {{ $tm->secondary_color }} 100%)">
                @if ($tm->banner_image)
                    <img src="{{ $tm->banner_image }}" alt="" class="absolute inset-0 h-full w-full object-cover opacity-35 mix-blend-overlay">
                @endif
                <div class="absolute inset-0" style="background-image: linear-gradient(90deg, {{ $tm->primary_color }}e6 0%, {{ $tm->primary_color }}80 45%, transparent 100%)"></div>
                <div class="ms-dots absolute inset-0 opacity-40"></div>
                <div class="relative flex flex-wrap items-center gap-x-6 gap-y-3 px-6 py-5 sm:px-8">
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-bold tracking-wide text-white/80 uppercase">
                            {{ $themeNow['source'] === 'manual' ? 'Tema pilihanmu' : 'Tema dari fandom favoritmu' }}
                        </p>
                        <p class="truncate text-xl font-extrabold sm:text-2xl">{{ $tm->theming_name }} <span class="font-semibold text-white/80">· {{ $tm->fandom->fandom_name }}</span></p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('catalog', ['fandom' => $tm->fandom_id]) }}" wire:navigate class="rounded-full bg-white px-4 py-2 text-sm font-extrabold text-ink-900 transition hover:bg-white/90">Merch {{ $tm->fandom->fandom_name }}</a>
                        <a href="{{ auth()->check() ? route('account', ['tab' => 'tampilan']) : route('home') }}" wire:navigate class="rounded-full border border-white/60 px-4 py-2 text-sm font-bold text-white transition hover:bg-white/15">Ganti tema</a>
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- ======================= NUDGE ONBOARDING ======================= --}}
    @if ($this->needsOnboarding)
        <section class="mx-auto mt-6 max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="ms-gradient-soft flex flex-col items-start justify-between gap-4 rounded-2xl border border-brand-100 p-5 sm:flex-row sm:items-center">
                <div class="flex items-center gap-3">
                    <span class="ms-gradient flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-white"><x-icon name="heart" class="h-5 w-5" /></span>
                    <div>
                        <p class="font-extrabold text-ink-900">Kamu belum memilih fandom favorit</p>
                        <p class="text-sm text-ink-600">Pilih dulu supaya rekomendasi di bawah benar-benar sesuai seleramu.</p>
                    </div>
                </div>
                <a href="{{ route('onboarding') }}" wire:navigate class="ms-btn-primary shrink-0">Pilih fandom</a>
            </div>
        </section>
    @endif

    {{-- Ajak pasang aplikasi (PWA) — hanya muncul di HP/browser yang mendukung & belum terpasang --}}
    <div class="mx-auto mt-6 max-w-7xl px-4 sm:px-6 md:hidden lg:px-8"><x-install-app variant="banner" /></div>

    {{-- ======================= REKOMENDASI ======================= --}}
    @php $rek = $this->rekomendasi; @endphp
    <section class="mx-auto mt-12 max-w-7xl px-4 sm:px-6 lg:px-8">
        <x-section-heading
            :title="$rek['personal'] ? 'Rekomendasi untukmu' : 'Paling disukai kolektor'"
            :subtitle="$rek['personal']
                ? 'Dihitung dari fandom favorit, pencarian, produk yang kamu lihat, keranjang, dan pembelianmu.'
                : (auth()->check() ? 'Mulai lihat-lihat produk — rekomendasimu akan terbentuk otomatis.' : 'Masuk untuk mendapat rekomendasi sesuai fandom favoritmu.')"
            icon="sparkles" :href="$rek['personal'] ? route('recommendations') : route('catalog', ['urut' => 'rating'])" />

        <div class="grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3 lg:grid-cols-5">
            @foreach ($rek['items'] as $p)
                <x-product-card :product="$p" wire:key="rek-{{ $p->id }}" :class="$loop->index >= 6 ? 'max-md:hidden' : ''" />
            @endforeach
        </div>
    </section>

    {{-- ======================= TERBARU ======================= --}}
    <section class="mx-auto mt-14 max-w-7xl px-4 sm:px-6 lg:px-8">
        <x-section-heading title="Baru rilis" subtitle="Produk terbaru dari artis independen." icon="clock" :href="route('catalog')" />
        <div class="grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3 lg:grid-cols-5">
            @foreach ($this->terbaru as $p)
                <x-product-card :product="$p" wire:key="new-{{ $p->id }}" :class="$loop->index >= 6 ? 'max-md:hidden' : ''" />
            @endforeach
        </div>
    </section>

    {{-- ======================= PRE-ORDER ======================= --}}
    @if ($this->preOrderBuka->isNotEmpty())
        {{-- Latar putih, sama seperti section lain (hasil konsul UI) --}}
        <section class="mx-auto mt-14 max-w-7xl px-4 sm:px-6 lg:px-8">
            <div>
                <x-section-heading title="Pre-order segera ditutup"
                    subtitle="Amankan slot sebelum batch ditutup. Bisa DP dulu untuk produk tertentu."
                    icon="fire" link-label="Semua pre-order" :href="route('catalog', ['status' => 'pre_order'])" />
                <div class="grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3 lg:grid-cols-5">
                    @foreach ($this->preOrderBuka as $p)
                        @php $days = (int) ceil(now()->diffInHours($p->openBatch->closes_at) / 24); @endphp
                        <div class="relative" wire:key="po-{{ $p->id }}">
                            <x-product-card :product="$p" />
                            <span class="pointer-events-none absolute -top-2 -right-2 z-10 rounded-full bg-pop-400 px-2.5 py-1 text-[11px] font-extrabold text-ink-900 shadow-lg">
                                {{ $days <= 1 ? 'Hari terakhir!' : $days . ' hari lagi' }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ======================= FANDOM ======================= --}}
    <section class="mx-auto mt-12 max-w-7xl px-4 sm:px-6 lg:px-8">
        <x-section-heading title="Jelajahi per fandom" subtitle="Anime, game, idol — pilih dunia favoritmu." :href="route('catalog')" link-label="Semua produk" />
        <div class="ms-scroll-x">
            @php
                // kartu tanpa gambar memakai gradasi dekoratif dari palet tema
                $grad = [
                    'from-brand-600 to-pop-500', 'from-accent-500 to-brand-600',
                    'from-pop-500 to-brand-700', 'from-brand-500 to-accent-500',
                ];
            @endphp
            @foreach ($this->fandomPopuler as $f)
                <a href="{{ route('catalog', ['fandom' => $f->id]) }}" wire:navigate wire:key="fd-{{ $f->id }}"
                   class="group relative aspect-[4/3] w-44 shrink-0 snap-start overflow-hidden rounded-2xl bg-gradient-to-br {{ $grad[$loop->index % 4] }} text-white transition hover:-translate-y-1 hover:shadow-[var(--shadow-lift)]">
                    @if ($img = $f->image_url)
                        {{-- gambar fandom: public/img/fandom/{slug}.jpg --}}
                        <img src="{{ $img }}" alt="{{ $f->fandom_name }}" loading="lazy" class="absolute inset-0 h-full w-full object-cover transition duration-500 group-hover:scale-105">
                    @else
                        <div class="ms-dots absolute inset-0"></div>
                        <span class="absolute top-3 left-4 text-5xl font-extrabold opacity-30">{{ mb_substr($f->fandom_name, 0, 1) }}</span>
                    @endif
                    <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-ink-900/90 via-ink-900/50 to-transparent px-3.5 pt-8 pb-2.5">
                        <span class="block text-sm leading-tight font-extrabold">{{ $f->fandom_name }}</span>
                        <span class="mt-0.5 block text-xs text-white/80">{{ (int) $f->product_count }} produk</span>
                    </div>
                </a>
            @endforeach
        </div>
    </section>

    {{-- ======================= TOKO ======================= --}}
    <section class="mx-auto mt-14 max-w-7xl px-4 sm:px-6 lg:px-8">
        <x-section-heading title="Toko artis pilihan" subtitle="Kenalan dengan kreator di balik produknya." icon="store" />
        <div class="grid gap-4 md:grid-cols-3">
            @foreach ($this->tokoPilihan as $shop)
                <a href="{{ route('artist.shop', $shop) }}" wire:navigate wire:key="shop-{{ $shop->id }}"
                   class="ms-card group overflow-hidden transition hover:-translate-y-1 hover:shadow-[var(--shadow-lift)]">
                    <div class="ms-gradient relative h-24">
                        @if ($shop->banner)
                            <img src="{{ $shop->banner->url }}" alt="" class="h-full w-full object-cover">
                        @endif
                    </div>
                    <div class="px-5 pb-5">
                        {{-- Avatar saja yang naik menimpa banner; nama & rating tetap di area putih --}}
                        <div class="relative z-10 flex items-start gap-3">
                            <x-avatar :user="$shop->user" class="relative z-10 -mt-8 h-16 w-16 shrink-0 border-4 border-white text-xl" />
                            <div class="min-w-0 pt-2">
                                <p class="truncate font-extrabold text-ink-900 group-hover:text-brand-700">{{ $shop->shop_name }}</p>
                                <p class="text-xs text-ink-500">
                                    ★ {{ number_format((float) $shop->rating, 1, ',', '.') }} · {{ $shop->product_count }} produk
                                </p>
                            </div>
                        </div>
                        <div class="mt-4 grid grid-cols-3 gap-2">
                            @foreach ($shop->products as $p)
                                <img src="{{ $p->cover_url }}" alt="" class="aspect-square w-full rounded-xl object-cover">
                            @endforeach
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </section>

    {{-- ======================= KENAPA MISEKU ======================= --}}
    <section class="mx-auto mt-14 max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="grid gap-4 md:grid-cols-3">
            @foreach ([
                ['calendar', 'Pre-order bisa DP', 'Bayar DP dulu, pelunasan tercatat di order ID yang sama.'],
                ['truck', 'Kirim atau ambil di event', 'Ongkir dihitung otomatis, atau ambil di booth pakai kode QR.'],
                ['shield', 'Langsung dari artisnya', 'Setiap produk diunggah oleh artis independen pemiliknya.'],
            ] as [$icon, $title, $text])
                <div class="flex items-start gap-4 rounded-2xl border border-ink-200/70 bg-white p-5">
                    <span class="ms-gradient-soft flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-brand-700"><x-icon :name="$icon" class="h-5.5 w-5.5" /></span>
                    <div>
                        <p class="font-extrabold text-ink-900">{{ $title }}</p>
                        <p class="mt-0.5 text-sm text-ink-500">{{ $text }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
</div>
