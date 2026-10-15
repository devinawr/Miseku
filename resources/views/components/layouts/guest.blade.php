<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Masuk' }} · Miseku</title>

    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="{{ app(\App\Services\ThemeService::class)->current()['theme']?->primary_color ?? '#6b32cc' }}">
    <link rel="icon" href="/img/icons/icon-192.png" sizes="192x192">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet">

    @include('partials.color-mode')
    @include('partials.pwa')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-white">
    <x-theme-style />
    <div class="grid min-h-screen lg:grid-cols-[1.05fr_1fr]">
        {{-- ============ Panel kiri: identitas & suasana fandom ============ --}}
        <aside class="ms-gradient relative hidden overflow-hidden lg:flex lg:flex-col lg:justify-between lg:p-12">
            <div class="ms-dots absolute inset-0"></div>
            <div class="absolute -top-24 -right-24 h-80 w-80 rounded-full bg-white/10 blur-2xl"></div>
            <div class="absolute -bottom-32 -left-16 h-96 w-96 rounded-full bg-pop-400/25 blur-3xl"></div>

            <a href="{{ route('home') }}" wire:navigate class="relative flex items-center gap-3">
                <span class="rounded-2xl bg-white p-1.5 shadow-lg"><x-brand-mark class="h-11 w-11" /></span>
                <span class="text-2xl font-extrabold text-white">Miseku</span>
            </a>

            {{-- Kolase produk (gambar contoh) --}}
            <div class="relative mx-auto my-10 grid w-full max-w-md grid-cols-3 gap-3">
                @foreach ([1, 4, 7, 2, 9, 5] as $i => $n)
                    <div class="overflow-hidden rounded-2xl border-4 border-white/80 shadow-2xl {{ $i % 2 ? 'translate-y-6' : '' }} {{ $i % 3 === 1 ? 'rotate-2' : '-rotate-2' }}">
                        <img src="/img/dummy/merch-{{ $n }}.jpg" alt="" class="aspect-[4/5] w-full object-cover">
                    </div>
                @endforeach
            </div>

            <div class="relative max-w-md text-white">
                <h2 class="text-3xl leading-tight font-extrabold">Rumah buat produk fandom-mu.</h2>
                <p class="mt-3 text-sm leading-relaxed text-white/85">
                    Temukan merch original dari artis independen — anime, game, idol, sampai
                    fandom yang jarang ada di toko resmi. Pre-order bisa DP, ambil di event pakai QR.
                </p>
            </div>
        </aside>

        {{-- ============ Panel kanan: form ============ --}}
        <main class="flex flex-col px-5 py-8 sm:px-10">
            <div class="flex items-center justify-between lg:hidden">
                <a href="{{ route('home') }}" wire:navigate class="flex items-center gap-2">
                    <x-brand-mark class="h-10 w-10" />
                    <span class="ms-logo-text text-xl font-extrabold">Miseku</span>
                </a>
            </div>
            <div class="hidden justify-end lg:flex">
                <a href="{{ route('home') }}" wire:navigate class="ms-link">← Kembali ke beranda</a>
            </div>

            <div class="mx-auto flex w-full max-w-md flex-1 flex-col justify-center py-10">
                {{ $slot }}
            </div>
        </main>
    </div>
</body>
</html>
