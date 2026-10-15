@php
    $me    = auth()->user();
    // Mode fokus: halaman onboarding (pilih fandom pertama kali) tanpa pencarian, menu akun, navigasi & footer
    $focus = request()->routeIs('onboarding');
    // Admin = hanya panel admin (tanpa beranda/belanja). Studio = mode kerja artis (navigasi belanja disembunyikan).
    $isAdmin  = (bool) $me?->isAdmin();
    $isStudio = request()->routeIs('studio.*');
    $themeColor = app(\App\Services\ThemeService::class)->current()['theme']?->primary_color ?? '#7C3AED';
    $item  = 'flex items-center gap-2.5 px-4 py-2 hover:bg-brand-50 hover:text-brand-700';
@endphp
<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Beranda' }} · Miseku</title>
    <meta name="description" content="Miseku — marketplace fan merchandise produk artis independen Indonesia.">

    {{-- PWA --}}
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="{{ $themeColor }}">
    <link rel="icon" href="/img/icons/icon-192.png" sizes="192x192">
    <link rel="apple-touch-icon" href="/img/icons/apple-touch-icon.png">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="Miseku">

    {{-- Font: Plus Jakarta Sans (fallback ke font sistem kalau offline) --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet">

    @include('partials.color-mode')
    @include('partials.pwa')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-full flex-col">
    <x-theme-style />

    {{-- ========================= HEADER ========================= --}}
    <header x-data="{ menu: false }" class="sticky top-0 z-40 border-b border-ink-200/70 bg-white/90 backdrop-blur-md">
        <div class="mx-auto flex h-18 max-w-7xl items-center gap-3 px-4 sm:gap-5 sm:px-6 lg:px-8">
            <a href="{{ $isAdmin ? route('admin.dashboard') : route('home') }}" wire:navigate class="flex shrink-0 items-center gap-2">
                <x-brand-mark class="h-11 w-11" />
                <span class="ms-logo-text hidden text-2xl font-extrabold tracking-tight sm:inline">Miseku</span>
            </a>
            @if ($isAdmin)
                <span class="ms-chip bg-ink-900 text-white">Panel admin</span>
            @elseif ($isStudio)
                <span class="ms-chip bg-brand-50 text-brand-700 ring-1 ring-brand-200">Studio artis</span>
                <a href="{{ route('home') }}" wire:navigate class="ms-btn-ghost hidden !py-2 text-sm md:inline-flex" title="Kembali belanja sebagai pembeli">
                    <x-icon name="home" class="h-4.5 w-4.5" /> Beranda
                </a>
            @endif

            @unless ($focus)
            {{-- Pencarian global desktop → hanya di beranda (di HP tetap ada lewat ikon & menu) --}}
            @if (request()->routeIs('home'))
            <form action="{{ route('catalog') }}" method="GET" class="hidden flex-1 md:block">
                <label class="group relative flex items-center">
                    <x-icon name="search" class="pointer-events-none absolute left-4 h-5 w-5 text-ink-400" />
                    <input type="search" name="q" value=""
                           placeholder="Cari karakter, fandom, atau jenis merch…"
                           class="h-11 w-full rounded-full border-2 border-brand-100 bg-ink-50 pr-28 pl-11 text-sm text-ink-900 placeholder:text-ink-400 transition focus:border-brand-400 focus:bg-white focus:outline-none">
                    <button type="submit" class="ms-btn-primary absolute right-1 !rounded-full !px-5 !py-2">Cari</button>
                </label>
            </form>
            @endif

            <div class="ml-auto flex items-center gap-1 sm:gap-2">
                {{-- HP: ikon cari tetap tampil di semua halaman --}}
                @unless ($isAdmin || $isStudio)
                <a href="{{ route('catalog') }}" wire:navigate class="ms-icon-btn md:hidden" title="Cari">
                    <x-icon name="search" class="h-6 w-6" />
                </a>
                @endunless

                <livewire:theme-picker />

                @auth
                    <a href="{{ route('chat') }}" wire:navigate class="ms-icon-btn hidden sm:inline-flex" title="Chat">
                        <x-icon name="chat" class="h-6 w-6" />
                    </a>
                    <livewire:notification-bell />
                @endauth

                @unless ($isAdmin)
                    <livewire:cart-badge />
                @endunless

                @guest
                    <a href="{{ route('login') }}" wire:navigate class="ms-btn-ghost hidden sm:inline-flex">Masuk</a>
                    <a href="{{ route('register') }}" wire:navigate class="ms-btn-primary hidden sm:inline-flex">Daftar</a>
                @endguest

                @auth
                    <div x-data="{ open: false }" @keydown.escape="open = false" class="relative">
                        <button type="button" @click="open = !open"
                                class="flex items-center gap-2 rounded-full border border-ink-200 bg-white py-1 pr-3 pl-1 transition hover:border-brand-300">
                            <x-avatar :user="$me" class="h-8 w-8 text-sm" />
                            <span class="hidden max-w-28 truncate text-sm font-bold text-ink-800 lg:inline">{{ $me->name }}</span>
                            <x-icon name="chevron-down" class="h-4 w-4 text-ink-400 transition" x-bind:class="open && 'rotate-180'" stroke="2" />
                        </button>

                        <div x-show="open" x-cloak @click.outside="open = false" x-transition.origin.top.right
                             class="absolute right-0 z-50 mt-2 max-h-[80vh] w-64 overflow-y-auto rounded-2xl border border-ink-200 bg-white shadow-[var(--shadow-lift)]">
                            <div class="ms-gradient-soft px-4 py-3">
                                <p class="truncate text-sm font-extrabold text-ink-900">{{ $me->name }}</p>
                                <p class="truncate text-xs text-ink-500">{{ $me->email }}</p>
                                <span class="ms-chip mt-2 bg-white text-brand-700">
                                    {{ ['admin' => 'Admin', 'artist' => 'Artis', 'buyer' => 'Pembeli'][$me->role] }}
                                </span>
                            </div>
                            <nav class="py-1.5 text-sm font-semibold text-ink-700">
                                <a href="{{ route('account') }}" wire:navigate class="{{ $item }}"><x-icon name="user" class="h-4.5 w-4.5" /> Akun saya</a>
                                @unless ($me->isAdmin())
                                    <a href="{{ route('orders.index') }}" wire:navigate class="{{ $item }}"><x-icon name="receipt" class="h-4.5 w-4.5" /> Pesanan saya</a>
                                    <a href="{{ route('custom.index') }}" wire:navigate class="{{ $item }}"><x-icon name="sparkles" class="h-4.5 w-4.5" /> Custom order</a>
                                @endunless
                                <a href="{{ route('chat') }}" wire:navigate class="{{ $item }}"><x-icon name="chat" class="h-4.5 w-4.5" /> Chat</a>
                                <a href="{{ route('notifications') }}" wire:navigate class="{{ $item }}"><x-icon name="bell" class="h-4.5 w-4.5" /> Notifikasi</a>
                                @unless ($isAdmin)
                                    <a href="{{ route('account', ['tab' => 'fandom']) }}" wire:navigate class="{{ $item }}"><x-icon name="heart" class="h-4.5 w-4.5" /> Fandom favorit</a>
                                    <a href="{{ route('account', ['tab' => 'alamat']) }}" wire:navigate class="{{ $item }}"><x-icon name="map-pin" class="h-4.5 w-4.5" /> Alamat</a>
                                @endunless
                                <a href="{{ route('account', ['tab' => 'tampilan']) }}" wire:navigate class="{{ $item }}"><x-icon name="palette" class="h-4.5 w-4.5" /> Tema tampilan</a>
                                <x-install-app />

                                @if ($me->isArtist() && $me->artistProfile)
                                    <div class="my-1.5 border-t border-ink-100"></div>
                                    <p class="px-4 pt-1 pb-0.5 text-[11px] font-extrabold tracking-wide text-ink-400 uppercase">Toko</p>
                                    @if ($isStudio)
                                        <a href="{{ route('home') }}" wire:navigate class="{{ $item }}"><x-icon name="home" class="h-4.5 w-4.5" /> Beranda (mode pembeli)</a>
                                    @endif
                                    <a href="{{ route('studio.dashboard') }}" wire:navigate class="{{ $item }}"><x-icon name="squares" class="h-4.5 w-4.5" /> Studio produk</a>
                                    <a href="{{ route('studio.orders') }}" wire:navigate class="{{ $item }}"><x-icon name="box" class="h-4.5 w-4.5" /> Pesanan toko</a>
                                    <a href="{{ route('studio.balance') }}" wire:navigate class="{{ $item }}"><x-icon name="wallet" class="h-4.5 w-4.5" /> Saldo</a>
                                    <a href="{{ route('artist.shop', $me->artistProfile) }}" wire:navigate class="{{ $item }}"><x-icon name="store" class="h-4.5 w-4.5" /> Halaman toko saya</a>
                                @elseif ($me->role === 'buyer')
                                    <div class="my-1.5 border-t border-ink-100"></div>
                                    <a href="{{ route('account', ['tab' => 'toko']) }}" wire:navigate class="{{ $item }}"><x-icon name="store" class="h-4.5 w-4.5" /> Buka toko</a>
                                @endif

                                @if ($me->isAdmin())
                                    <div class="my-1.5 border-t border-ink-100"></div>
                                    <a href="{{ route('admin.dashboard') }}" wire:navigate class="{{ $item }}"><x-icon name="chart" class="h-4.5 w-4.5" /> Panel admin</a>
                                    <a href="{{ route('admin.approvals') }}" wire:navigate class="{{ $item }}"><x-icon name="check-circle" class="h-4.5 w-4.5" /> Persetujuan master data</a>
                                    <a href="{{ route('admin.themes') }}" wire:navigate class="{{ $item }}"><x-icon name="palette" class="h-4.5 w-4.5" /> Kelola tema fandom</a>
                                    <a href="{{ route('community') }}" wire:navigate class="{{ $item }}"><x-icon name="chat" class="h-4.5 w-4.5" /> Pantau komunitas</a>
                                @endif
                            </nav>
                            <form method="POST" action="{{ route('logout') }}" class="border-t border-ink-100">
                                @csrf
                                <button type="submit" class="flex w-full items-center gap-2.5 px-4 py-2.5 text-left text-sm font-semibold text-ink-500 hover:bg-red-50 hover:text-red-600">
                                    <x-icon name="logout" class="h-4.5 w-4.5" /> Keluar
                                </button>
                            </form>
                        </div>
                    </div>
                @endauth

                <button type="button" @click="menu = !menu" class="ms-icon-btn md:hidden" aria-label="Menu">
                    <x-icon name="menu" class="h-6 w-6" x-show="!menu" />
                    <x-icon name="x" class="h-6 w-6" x-show="menu" x-cloak />
                </button>
            </div>
            @else
                <span class="ml-auto text-sm font-semibold text-ink-500">Langkah awal · pilih fandom favorit</span>
            @endunless
        </div>

        @unless ($focus || $isAdmin || $isStudio)
        {{-- Baris kategori (khusus mode belanja; disembunyikan di Studio & Panel admin) --}}
        <div class="border-t border-ink-100 bg-white">
            <div class="ms-scroll-x mx-auto max-w-7xl items-center !gap-1 px-4 !pb-0 sm:px-6 lg:px-8">
                @php
                    $links = [
                        ['Beranda', route('home'), request()->routeIs('home')],
                        ['Semua produk', route('catalog'), request()->routeIs('catalog') && ! request('status') && ! request('fandom')],
                        ['Pre-Order', route('catalog', ['status' => 'pre_order']), request('status') === 'pre_order'],
                        ['Ready Stock', route('catalog', ['status' => 'ready_stock']), request('status') === 'ready_stock'],
                        ['Voting desain', route('polls.index'), request()->routeIs('polls.*')],
                        ['Custom', route('custom.index'), request()->routeIs('custom.*')],
                        ['Komunitas', route('community'), request()->routeIs('community*')],
                    ];
                @endphp
                @foreach ($links as [$label, $href, $active])
                    <a href="{{ $href }}" wire:navigate
                       class="relative shrink-0 px-3 py-3 text-sm font-bold transition {{ $active ? 'text-brand-700' : 'text-ink-600 hover:text-brand-700' }}">
                        {{ $label }}
                        @if ($active)
                            <span class="ms-gradient absolute inset-x-3 bottom-0 h-0.5 rounded-full"></span>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>

        @endunless

        @unless ($focus)
        {{-- Menu mobile --}}
        <div x-show="menu" x-cloak x-transition.origin.top class="max-h-[75vh] overflow-y-auto border-t border-ink-200/70 bg-white px-4 py-3 md:hidden">
            @unless ($isAdmin)
            <form action="{{ route('catalog') }}" method="GET" class="mb-3">
                <input type="search" name="q" placeholder="Cari karakter, fandom, jenis merch…" class="ms-input">
            </form>
            @endunless
            @guest
                <div class="flex gap-2">
                    <a href="{{ route('login') }}" wire:navigate class="ms-btn-ghost flex-1">Masuk</a>
                    <a href="{{ route('register') }}" wire:navigate class="ms-btn-primary flex-1">Daftar</a>
                </div>
            @endguest
            @auth
                @php
                    $mobile = [['Akun saya', route('account')], ['Chat', route('chat')], ['Notifikasi', route('notifications')], ['Tema tampilan', route('account', ['tab' => 'tampilan'])]];
                    if ($isStudio) { array_unshift($mobile, ['← Beranda (mode pembeli)', route('home')]); }
                    if (! $me->isAdmin()) { array_splice($mobile, 1, 0, [['Pesanan saya', route('orders.index')], ['Custom order', route('custom.index')]]); }
                    if ($me->isArtist() && $me->artistProfile) {
                        $mobile = array_merge($mobile, [['Studio produk', route('studio.dashboard')], ['Pesanan toko', route('studio.orders')], ['Saldo', route('studio.balance')], ['Halaman toko saya', route('artist.shop', $me->artistProfile)]]);
                    } elseif ($me->role === 'buyer') {
                        $mobile[] = ['Buka toko', route('account', ['tab' => 'toko'])];
                    }
                    if ($me->isAdmin()) {
                        $mobile = array_merge($mobile, [['Panel admin', route('admin.dashboard')], ['Persetujuan master data', route('admin.approvals')], ['Kelola tema fandom', route('admin.themes')], ['Pantau komunitas', route('community')]]);
                    }
                @endphp
                @foreach ($mobile as [$label, $href])
                    <a href="{{ $href }}" wire:navigate class="block rounded-lg px-3 py-2 text-sm font-bold text-ink-700 hover:bg-brand-50">{{ $label }}</a>
                @endforeach
            @endauth
            <x-install-app class="mt-1 rounded-lg" />
        </div>
        @endunless
    </header>

    {{-- ========================= KONTEN ========================= --}}
    <main class="flex-1">
        {{ $slot }}
    </main>

    @unless ($focus)
    {{-- ========================= FOOTER ========================= --}}
    <footer class="mt-20 bg-ink-900 text-ink-300">
        <div class="ms-gradient h-1"></div>
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            @if ($isAdmin)
                <div class="flex items-center gap-2">
                    <x-brand-mark class="h-10 w-10 rounded-xl bg-white p-1" />
                    <span class="text-xl font-extrabold text-white">Miseku · Panel admin</span>
                </div>
            @else
            <div class="grid gap-10 md:grid-cols-[1.4fr_1fr_1fr_1fr]">
                <div>
                    <div class="flex items-center gap-2">
                        <x-brand-mark class="h-10 w-10 rounded-xl bg-white p-1" />
                        <span class="text-xl font-extrabold text-white">Miseku</span>
                    </div>
                    <p class="mt-4 max-w-sm text-sm leading-relaxed">
                        Marketplace fan merchandise untuk artis independen Indonesia — dari acrylic charm,
                        photocard, sampai plushie. Pre-order, custom, dan ambil di event dalam satu tempat.
                    </p>
                </div>
                <div>
                    <p class="text-sm font-extrabold text-white">Belanja</p>
                    <ul class="mt-3 space-y-2 text-sm">
                        <li><a href="{{ route('catalog') }}" wire:navigate class="hover:text-white">Semua produk</a></li>
                        <li><a href="{{ route('catalog', ['status' => 'pre_order']) }}" wire:navigate class="hover:text-white">Pre-order dibuka</a></li>
                        <li><a href="{{ route('catalog', ['urut' => 'rating']) }}" wire:navigate class="hover:text-white">Rating tertinggi</a></li>
                    </ul>
                </div>
                <div>
                    <p class="text-sm font-extrabold text-white">Untuk artis</p>
                    <ul class="mt-3 space-y-2 text-sm">
                        <li><a href="{{ auth()->check() ? route('account', ['tab' => 'toko']) : route('register') }}" wire:navigate class="hover:text-white">Buka toko</a></li>
                        <li><a href="{{ route('polls.index') }}" wire:navigate class="hover:text-white">Voting desain</a></li>
                        <li><a href="{{ route('community') }}" wire:navigate class="hover:text-white">Komunitas fandom</a></li>
                        <li><a href="{{ auth()->user()?->isArtist() ? route('studio.dashboard') : route('login') }}" wire:navigate class="hover:text-white">Studio produk</a></li>
                    </ul>
                </div>
                <div>
                    <p class="text-sm font-extrabold text-white">Fandom</p>
                    <ul class="mt-3 space-y-2 text-sm">
                        {{-- Cukup 3 fandom teratas + tautan ke katalog --}}
                        @foreach ($navFandoms->take(3) as $f)
                            <li><a href="{{ route('catalog', ['fandom' => $f->id]) }}" wire:navigate class="hover:text-white">{{ $f->fandom_name }}</a></li>
                        @endforeach
                        <li><a href="{{ route('catalog') }}" wire:navigate class="font-semibold text-brand-300 hover:text-white">Lihat semua fandom →</a></li>
                    </ul>
                </div>
            </div>
            @endif
            <p class="mt-10 border-t border-white/10 pt-6 text-xs text-ink-400">
                &copy; {{ date('Y') }} Miseku · Sistem Informasi Marketplace Fan Merchandise bagi Artis Independen di Indonesia.
            </p>
        </div>
    </footer>
    @endunless

    {{-- ============ Toast (event "toast" dari Livewire / flash session) ============ --}}
    <div x-data="{ show: false, message: '', tone: 'ok', timer: null }"
         x-on:toast.window="message = $event.detail.message; tone = $event.detail.tone || 'ok'; show = true; clearTimeout(timer); timer = setTimeout(() => show = false, 3500)"
         @if (session('status')) x-init="$nextTick(() => $dispatch('toast', { message: @js(session('status')) }))" @endif
         x-show="show" x-cloak x-transition
         class="fixed bottom-6 left-1/2 z-50 w-max max-w-[92vw] -translate-x-1/2">
        <div class="flex items-center gap-2 rounded-full px-5 py-3 text-sm font-semibold text-white shadow-[var(--shadow-lift)]"
             :class="tone === 'error' ? 'bg-red-600' : 'bg-ink-900'">
            <x-icon name="check-circle" class="h-5 w-5 shrink-0 text-pop-300" x-show="tone !== 'error'" />
            <x-icon name="info" class="h-5 w-5 shrink-0" x-show="tone === 'error'" />
            <span x-text="message"></span>
        </div>
    </div>

</body>
</html>
