@php
    $user = $this->user;
    $roleLabel = ['admin' => 'Admin', 'artist' => 'Artis', 'buyer' => 'Pembeli'][$user->role] ?? $user->role;
@endphp

<div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">

    @if (session('status'))
        <div class="mb-6 flex items-start gap-3 rounded-xl bg-miseku-100 px-4 py-3.5 text-sm font-medium text-miseku-700">
            <svg class="mt-0.5 h-4.5 w-4.5 shrink-0 text-miseku-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            {{ session('status') }}
        </div>
    @endif

    {{-- ============ Identitas ============ --}}
    <section class="ms-card p-6">
        <div class="flex flex-col gap-5 sm:flex-row sm:items-center">
            @if ($user->profile_photo)
                <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}"
                     class="h-20 w-20 shrink-0 rounded-2xl object-cover ring-2 ring-miseku-200">
            @else
                <div class="flex h-20 w-20 shrink-0 items-center justify-center rounded-2xl bg-miseku-100 text-3xl font-extrabold text-miseku-500">
                    {{ $user->initials }}
                </div>
            @endif

            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-xl font-extrabold tracking-tight text-miseku-900">{{ $user->name }}</h1>
                    <span class="ms-chip bg-miseku-100 text-miseku-600">{{ $roleLabel }}</span>
                </div>
                <p class="mt-1 text-sm text-miseku-500">{{ $user->email }}</p>
                <p class="mt-0.5 text-xs text-miseku-400">
                    Bergabung {{ $user->created_at->translatedFormat('d F Y') }}
                    @if ($user->phone) · {{ $user->phone }} @endif
                </p>
            </div>

            <button type="button" wire:click="logout" wire:confirm="Keluar dari akun ini?" class="ms-btn-ghost shrink-0">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                </svg>
                Keluar
            </button>
        </div>
    </section>

    {{-- ============ Kartu khusus artis ============ --}}
    @if ($user->role === 'artist' && $user->artistProfile)
        <section class="ms-card mt-6 overflow-hidden">
            <div class="h-28 w-full overflow-hidden bg-miseku-200">
                <img src="{{ $user->artistProfile->banner_url }}" alt="" class="h-full w-full object-cover">
            </div>
            <div class="flex flex-wrap items-center gap-4 p-5">
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-bold uppercase tracking-wide text-miseku-400">Tokomu</p>
                    <p class="mt-0.5 text-lg font-extrabold text-miseku-900">{{ $user->artistProfile->shop_name }}</p>
                    <div class="mt-1.5">
                        <x-star-rating :rating="$user->artistProfile->rating"
                                       :count="$user->artistProfile->rating_count" size="sm" />
                    </div>
                </div>
                <a href="{{ route('artist.shop', $user->artistProfile) }}" wire:navigate class="ms-btn-primary">
                    Lihat halaman toko
                </a>
            </div>
        </section>
    @endif

    {{-- ============ Pintasan sesuai peran ============ --}}
    <section class="mt-6">
        <h2 class="text-sm font-bold uppercase tracking-wide text-miseku-400">Pintasan</h2>
        <div class="mt-3 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @php
                $shortcuts = match ($user->role) {
                    'artist' => [
                        ['Kelola karya', 'Unggah dan atur produk tokomu.', false],
                        ['Batch pre-order', 'Buka periode PO dengan skema DP.', false],
                        ['Penarikan dana', 'Tarik saldo hasil penjualan.', false],
                    ],
                    'admin' => [
                        ['Tinjau ajuan atribut', 'Setujui fandom & jenis merch baru.', false],
                        ['Proses penarikan dana', 'Verifikasi dan unggah bukti transfer.', false],
                        ['Pengaturan platform', 'Atur tarif fee dan penalti rating.', false],
                    ],
                    default => [
                        ['Pesanan saya', 'Lacak status pesanan dan pelunasan.', false],
                        ['Permintaan custom', 'Ajukan merchandise custom ke artis.', false],
                        ['Fandom yang diikuti', 'Atur notifikasi karya baru.', false],
                    ],
                };
            @endphp

            <a href="{{ route('catalog') }}" wire:navigate
               class="ms-card group flex flex-col p-5 transition hover:-translate-y-0.5 hover:shadow-[var(--shadow-lift)]">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-miseku-100 text-miseku-500">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 8.25V6Zm0 9.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 8.25 20.25H6A2.25 2.25 0 0 1 3.75 18v-2.25Zm9.75-9.75A2.25 2.25 0 0 1 15.75 3.75H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6Zm0 9.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
                    </svg>
                </span>
                <p class="mt-3 text-sm font-bold text-miseku-900 group-hover:text-miseku-600">Jelajahi katalog</p>
                <p class="mt-1 text-xs leading-relaxed text-miseku-500">Lihat semua karya ready stock & pre-order.</p>
                <span class="ms-chip mt-3 self-start bg-miseku-100 text-miseku-500">Aktif</span>
            </a>

            @foreach ($shortcuts as [$label, $desc, $ready])
                <div class="ms-card flex cursor-not-allowed flex-col p-5 opacity-70">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-miseku-100 text-miseku-300">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75A2.25 2.25 0 0 0 4.5 12.75v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                        </svg>
                    </span>
                    <p class="mt-3 text-sm font-bold text-miseku-700">{{ $label }}</p>
                    <p class="mt-1 text-xs leading-relaxed text-miseku-400">{{ $desc }}</p>
                    <span class="ms-chip mt-3 self-start bg-miseku-100 text-miseku-400">Modul menyusul</span>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ============ Riwayat dilihat (tabel product_view) ============ --}}
    <section class="mt-8">
        <div class="flex items-baseline justify-between gap-4">
            <h2 class="text-sm font-bold uppercase tracking-wide text-miseku-400">Terakhir kamu lihat</h2>
            <span class="text-xs text-miseku-300">dari tabel <span class="font-mono">product_view</span></span>
        </div>

        @if ($this->recentlyViewed->isEmpty())
            <p class="ms-card mt-3 px-5 py-8 text-center text-sm text-miseku-400">
                Belum ada riwayat. Buka beberapa halaman produk, lalu kembali ke sini.
            </p>
        @else
            <div class="mt-3 grid grid-cols-2 gap-4 sm:gap-5 md:grid-cols-4">
                @foreach ($this->recentlyViewed as $product)
                    <x-product-card :product="$product" :key="'rv-'.$product->id" />
                @endforeach
            </div>
        @endif
    </section>
</div>
