<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Masuk' }} · Miseku</title>

    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#7a9bcf">
    <link rel="icon" href="/img/icons/icon-192.png" sizes="192x192">
    <link rel="apple-touch-icon" href="/img/icons/apple-touch-icon.png">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full">
    <div class="flex min-h-screen">

        {{-- ============ Panel kiri: branding (sembunyi di mobile) ============ --}}
        <aside class="relative hidden w-1/2 overflow-hidden bg-gradient-to-br from-miseku-200 via-miseku-100 to-miseku-50 lg:flex lg:flex-col lg:justify-between">
            <div class="pointer-events-none absolute -left-20 -top-24 h-80 w-80 rounded-full bg-white/50 blur-3xl"></div>
            <div class="pointer-events-none absolute -bottom-24 -right-16 h-96 w-96 rounded-full bg-sakura-400/15 blur-3xl"></div>

            <div class="relative p-10">
                <a href="{{ route('home') }}" class="inline-flex items-center gap-3" wire:navigate>
                    <x-brand-mark class="h-11 w-11" />
                    <span class="text-2xl font-extrabold tracking-tight text-miseku-600">Miseku</span>
                </a>
            </div>

            <div class="relative px-10">
                <h2 class="max-w-md text-3xl font-extrabold leading-tight tracking-tight text-miseku-900">
                    Satu tempat untuk karya artis independen Indonesia.
                </h2>
                <p class="mt-4 max-w-sm text-sm leading-relaxed text-miseku-600">
                    Kelola katalog, buka pre-order, terima pesanan custom, sampai jualan di booth event —
                    semuanya dari satu akun.
                </p>

                <ul class="mt-8 space-y-3 text-sm text-miseku-700">
                    @foreach ([
                        'Buka pre-order dengan skema DP atau bayar penuh',
                        'Terima permintaan custom merchandise dari pembeli',
                        'Rekomendasi karya sesuai fandom yang kamu ikuti',
                    ] as $point)
                        <li class="flex items-start gap-2.5">
                            <svg class="mt-0.5 h-4.5 w-4.5 shrink-0 text-miseku-500" fill="none" stroke="currentColor"
                                 stroke-width="2.2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                            {{ $point }}
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="relative flex items-center gap-4 p-10">
                <img src="/img/dummy/avatar-1.jpg" alt="" class="h-10 w-10 rounded-full ring-2 ring-white">
                <img src="/img/dummy/avatar-2.jpg" alt="" class="-ml-7 h-10 w-10 rounded-full ring-2 ring-white">
                <img src="/img/dummy/avatar-3.jpg" alt="" class="-ml-7 h-10 w-10 rounded-full ring-2 ring-white">
                <p class="text-xs font-medium text-miseku-600">Sudah ada artis yang membuka tokonya di Miseku.</p>
            </div>
        </aside>

        {{-- ============ Panel kanan: form ============ --}}
        <main class="flex w-full items-center justify-center px-4 py-10 sm:px-8 lg:w-1/2">
            <div class="w-full max-w-md">
                <a href="{{ route('home') }}" class="mb-8 inline-flex items-center gap-2.5 lg:hidden" wire:navigate>
                    <x-brand-mark class="h-9 w-9" />
                    <span class="text-xl font-extrabold tracking-tight text-miseku-600">Miseku</span>
                </a>

                {{ $slot }}

                <p class="mt-8 text-center text-xs text-miseku-400">
                    <a href="{{ route('home') }}" wire:navigate class="hover:text-miseku-600">
                        &larr; Kembali ke katalog
                    </a>
                </p>
            </div>
        </main>
    </div>
</body>
</html>
