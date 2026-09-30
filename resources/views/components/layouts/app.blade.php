<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Beranda' }} · Miseku</title>
    <meta name="description" content="Miseku — marketplace fan merchandise karya artis independen Indonesia.">

    {{-- PWA --}}
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#7a9bcf">
    <link rel="icon" href="/img/icons/icon-192.png" sizes="192x192">
    <link rel="apple-touch-icon" href="/img/icons/apple-touch-icon.png">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="Miseku">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full flex flex-col">

    {{-- ============================ HEADER ============================ --}}
    <header
        x-data="{ open: false }"
        class="sticky top-0 z-40 border-b border-miseku-200/70 bg-white/85 backdrop-blur-md"
    >
        <div class="mx-auto flex h-16 max-w-7xl items-center gap-4 px-4 sm:px-6 lg:px-8">
            <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-2.5" wire:navigate>
                <x-brand-mark class="h-9 w-9" />
                <span class="text-xl font-extrabold tracking-tight text-miseku-600">Miseku</span>
            </a>

            <nav class="ml-4 hidden items-center gap-1 md:flex">
                <a href="{{ route('home') }}" wire:navigate
                   class="rounded-lg px-3 py-2 text-sm font-semibold text-miseku-700 hover:bg-miseku-100">
                    Beranda
                </a>
                <a href="{{ route('catalog') }}" wire:navigate
                   class="rounded-lg px-3 py-2 text-sm font-semibold text-miseku-700 hover:bg-miseku-100">
                    Katalog
                </a>
                <a href="{{ route('catalog', ['status' => 'pre_order']) }}" wire:navigate
                   class="rounded-lg px-3 py-2 text-sm font-semibold text-miseku-700 hover:bg-miseku-100">
                    Pre-Order
                </a>
                <span class="cursor-not-allowed rounded-lg px-3 py-2 text-sm font-semibold text-miseku-300"
                      title="Modul belum dikerjakan">Event</span>
                <span class="cursor-not-allowed rounded-lg px-3 py-2 text-sm font-semibold text-miseku-300"
                      title="Modul belum dikerjakan">Custom</span>
            </nav>

            <div class="ml-auto flex items-center gap-2">
                <button type="button" class="ms-btn-ghost !px-3" title="Keranjang (modul belum aktif)">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 1 0 0 6 3 3 0 0 0 0-6Zm11.25 0a3 3 0 1 0 0 6 3 3 0 0 0 0-6ZM6.106 5.272 7.5 14.25h11.25l1.5-7.5H5.106Z" />
                    </svg>
                </button>

                @guest
                    {{-- Belum login --}}
                    <a href="{{ route('login') }}" wire:navigate class="ms-btn-ghost hidden sm:inline-flex">Masuk</a>
                    <a href="{{ route('register') }}" wire:navigate class="ms-btn-primary hidden sm:inline-flex">Daftar</a>
                @endguest

                @auth
                    {{-- Sudah login: dropdown akun, murni Alpine --}}
                    <div x-data="{ open: false }" @keydown.escape="open = false" class="relative">
                        <button type="button" @click="open = !open"
                                class="flex items-center gap-2 rounded-xl border border-miseku-200 bg-white py-1.5 pl-1.5 pr-3 transition hover:bg-miseku-100">
                            @if (auth()->user()->profile_photo)
                                <img src="{{ auth()->user()->avatar_url }}" alt=""
                                     class="h-8 w-8 rounded-lg object-cover">
                            @else
                                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-miseku-100 text-sm font-extrabold text-miseku-500">
                                    {{ auth()->user()->initials }}
                                </span>
                            @endif
                            <span class="hidden max-w-28 truncate text-sm font-semibold text-miseku-700 sm:inline">
                                {{ auth()->user()->name }}
                            </span>
                            <svg class="h-4 w-4 text-miseku-400 transition" :class="open && 'rotate-180'"
                                 fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                            </svg>
                        </button>

                        <div x-show="open" x-cloak @click.outside="open = false" x-transition.origin.top.right
                             class="absolute right-0 mt-2 w-60 overflow-hidden rounded-xl border border-miseku-200 bg-white shadow-[var(--shadow-lift)]">
                            <div class="border-b border-miseku-100 px-4 py-3">
                                <p class="truncate text-sm font-bold text-miseku-900">{{ auth()->user()->name }}</p>
                                <p class="truncate text-xs text-miseku-400">{{ auth()->user()->email }}</p>
                                <span class="ms-chip mt-1.5 bg-miseku-100 text-miseku-500">
                                    {{ ['admin' => 'Admin', 'artist' => 'Artis', 'buyer' => 'Pembeli'][auth()->user()->role] }}
                                </span>
                            </div>

                            <a href="{{ route('account') }}" wire:navigate
                               class="block px-4 py-2.5 text-sm font-medium text-miseku-700 hover:bg-miseku-100">
                                Akun saya
                            </a>

                            @if (auth()->user()->role === 'artist' && auth()->user()->artistProfile)
                                <a href="{{ route('artist.shop', auth()->user()->artistProfile) }}" wire:navigate
                                   class="block px-4 py-2.5 text-sm font-medium text-miseku-700 hover:bg-miseku-100">
                                    Halaman toko saya
                                </a>
                            @endif

                            <form method="POST" action="{{ route('logout') }}" class="border-t border-miseku-100">
                                @csrf
                                <button type="submit"
                                        class="w-full px-4 py-2.5 text-left text-sm font-medium text-miseku-500 hover:bg-miseku-100">
                                    Keluar
                                </button>
                            </form>
                        </div>
                    </div>
                @endauth

                <button type="button" @click="open = !open" class="ms-btn-ghost !px-3 md:hidden">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path x-show="!open" stroke-linecap="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
                        <path x-show="open" x-cloak stroke-linecap="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        {{-- Menu mobile: interaksi kecil murni Alpine, tanpa request ke server --}}
        <div x-show="open" x-cloak x-transition.origin.top
             class="border-t border-miseku-200/70 bg-white px-4 py-3 md:hidden">
            <a href="{{ route('home') }}" wire:navigate
               class="block rounded-lg px-3 py-2 text-sm font-semibold text-miseku-700 hover:bg-miseku-100">Beranda</a>
            <a href="{{ route('catalog') }}" wire:navigate
               class="block rounded-lg px-3 py-2 text-sm font-semibold text-miseku-700 hover:bg-miseku-100">Katalog</a>
            <a href="{{ route('catalog', ['status' => 'pre_order']) }}" wire:navigate
               class="block rounded-lg px-3 py-2 text-sm font-semibold text-miseku-700 hover:bg-miseku-100">Pre-Order</a>

            @guest
                <div class="mt-2 flex gap-2 border-t border-miseku-100 pt-3">
                    <a href="{{ route('login') }}" wire:navigate class="ms-btn-ghost flex-1">Masuk</a>
                    <a href="{{ route('register') }}" wire:navigate class="ms-btn-primary flex-1">Daftar</a>
                </div>
            @endguest

            @auth
                <a href="{{ route('account') }}" wire:navigate
                   class="mt-2 block border-t border-miseku-100 pt-3 text-sm font-semibold text-miseku-700">Akun saya</a>
            @endauth
        </div>
    </header>

    {{-- ============================ KONTEN ============================ --}}
    <main class="flex-1">
        {{ $slot }}
    </main>

    {{-- ============================ FOOTER ============================ --}}
    <footer class="mt-16 border-t border-miseku-200/70 bg-white">
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <div class="flex flex-col gap-6 md:flex-row md:items-start md:justify-between">
                <div class="max-w-sm">
                    <div class="flex items-center gap-2.5">
                        <x-brand-mark class="h-8 w-8" />
                        <span class="text-lg font-extrabold text-miseku-600">Miseku</span>
                    </div>
                    <p class="mt-3 text-sm text-miseku-600">
                        Marketplace fan merchandise untuk artis independen di Indonesia —
                        katalog karya, pre-order, custom, sampai penjualan di booth event.
                    </p>
                </div>
                <div class="grid grid-cols-2 gap-8 text-sm">
                    <div>
                        <p class="font-semibold text-miseku-800">Jelajahi</p>
                        <ul class="mt-2 space-y-1.5 text-miseku-600">
                            <li><a href="{{ route('home') }}" wire:navigate class="hover:text-miseku-800">Beranda</a></li>
                            <li><a href="{{ route('catalog') }}" wire:navigate class="hover:text-miseku-800">Semua karya</a></li>
                            <li><a href="{{ route('catalog', ['status' => 'pre_order']) }}" wire:navigate class="hover:text-miseku-800">Pre-order</a></li>
                            <li><a href="{{ route('catalog', ['urut' => 'rating']) }}" wire:navigate class="hover:text-miseku-800">Rating tertinggi</a></li>
                        </ul>
                    </div>
                    <div>
                        <p class="font-semibold text-miseku-800">Status modul</p>
                        <ul class="mt-2 space-y-1.5 text-miseku-600">
                            <li>Katalog · <span class="text-miseku-500 font-semibold">selesai</span></li>
                            <li>Detail produk · <span class="text-miseku-500 font-semibold">selesai</span></li>
                            <li>Toko artist · <span class="text-miseku-500 font-semibold">selesai</span></li>
                        </ul>
                    </div>
                </div>
            </div>
            <p class="mt-8 border-t border-miseku-100 pt-6 text-xs text-miseku-400">
                &copy; {{ date('Y') }} Miseku · Prototipe Tugas Akhir.
            </p>
        </div>
    </footer>

    {{-- ============ Toast: Alpine mendengarkan event dari Livewire ============ --}}
    <div
        x-data="{ show: false, message: '' }"
        x-on:toast.window="message = $event.detail.message; show = true; setTimeout(() => show = false, 3200)"
        x-show="show" x-cloak x-transition
        class="fixed bottom-6 left-1/2 z-50 -translate-x-1/2"
    >
        <div class="flex items-center gap-2 rounded-full bg-miseku-800 px-5 py-3 text-sm font-medium text-white shadow-[var(--shadow-lift)]">
            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            <span x-text="message"></span>
        </div>
    </div>

    {{-- Registrasi Service Worker (dukungan offline PWA) --}}
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js').catch(() => {});
            });
        }
    </script>
</body>
</html>
