<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <a href="{{ route('home') }}" wire:navigate class="ms-link text-sm"><x-icon name="chevron-left" class="h-4 w-4" /> Beranda</a>

    <x-section-heading class="mt-3" title="Rekomendasi untukmu" icon="sparkles"
        subtitle="Diurutkan dari yang paling cocok dengan fandom favorit, pencarian, produk yang kamu lihat, keranjang, dan pembelianmu." />

    @php $items = $this->items; @endphp

    @guest
        <div class="ms-card flex flex-col items-center p-10 text-center">
            <x-icon name="sparkles" class="h-10 w-10 text-brand-500" />
            <p class="mt-3 font-extrabold text-ink-900">Masuk untuk melihat rekomendasimu</p>
            <p class="mt-1 text-sm text-ink-500">Rekomendasi dihitung dari fandom favorit dan aktivitas belanjamu.</p>
            <a href="{{ route('login') }}" wire:navigate class="ms-btn-primary mt-5">Masuk</a>
        </div>
    @else
        @if ($items->isEmpty())
            <div class="ms-card flex flex-col items-center p-10 text-center">
                <x-icon name="sparkles" class="h-10 w-10 text-brand-500" />
                <p class="mt-3 font-extrabold text-ink-900">Rekomendasimu belum terbentuk</p>
                <p class="mt-1 text-sm text-ink-500">Pilih fandom favorit atau lihat-lihat beberapa produk dulu, ya.</p>
                <div class="mt-5 flex gap-2">
                    <a href="{{ route('account', ['tab' => 'fandom']) }}" wire:navigate class="ms-btn-ghost">Pilih fandom favorit</a>
                    <a href="{{ route('catalog') }}" wire:navigate class="ms-btn-primary">Jelajahi katalog</a>
                </div>
            </div>
        @else
            <div class="grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3 lg:grid-cols-5">
                @foreach ($items->take($limit) as $p)
                    <x-product-card :product="$p" wire:key="rec-{{ $p->id }}" />
                @endforeach
            </div>

            @if ($items->count() > $limit)
                <div class="mt-8 text-center">
                    <button type="button" wire:click="more" class="ms-btn-ghost">
                        <span wire:loading.remove wire:target="more">Muat lebih banyak</span>
                        <span wire:loading wire:target="more">Memuat…</span>
                    </button>
                </div>
            @endif
        @endif
    @endguest
</div>
