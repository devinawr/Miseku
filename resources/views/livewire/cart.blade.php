@php
    $summary   = $this->summary;
    $available = $this->items->filter(fn ($i) => $i->unavailable === null)->count();
@endphp

<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight text-ink-900 sm:text-3xl">Keranjang</h1>
            <p class="mt-1 text-sm text-ink-500">{{ $this->items->sum('quantity') }} barang dari {{ $this->groups->count() }} toko</p>
        </div>
        <a href="{{ route('catalog') }}" wire:navigate class="ms-link"><x-icon name="arrow-right" class="h-4 w-4 rotate-180" /> Lanjut belanja</a>
    </div>

    @if ($this->items->isEmpty())
        <x-empty-state icon="cart" class="mt-8" title="Keranjangmu masih kosong"
                       description="Yuk cari merch dari fandom favoritmu.">
            <a href="{{ route('catalog') }}" wire:navigate class="ms-btn-primary mt-5">Jelajahi katalog</a>
        </x-empty-state>
    @else
        <div class="mt-6 grid items-start gap-6 lg:grid-cols-12">
            <div class="space-y-4 lg:col-span-8">
                {{-- Bar pilih semua --}}
                <div class="ms-card flex items-center justify-between gap-3 px-5 py-3.5">
                    <label class="flex cursor-pointer items-center gap-3 text-sm font-bold text-ink-800">
                        <input type="checkbox" class="ms-check" wire:click="toggleAll" @checked($available && count($selected) === $available)>
                        Pilih semua ({{ $available }})
                    </label>
                    @if (count($selected))
                        <button type="button" wire:click="removeSelected" wire:confirm="Hapus item yang dipilih?" class="text-sm font-bold text-red-600 hover:underline">Hapus</button>
                    @endif
                </div>

                @foreach ($this->groups as $artistId => $items)
                    @php
                        $shop = $items->first()->product->artistProfile;
                        $shopIds = $items->filter(fn ($i) => $i->unavailable === null)->pluck('id')->map(fn ($id) => (string) $id)->all();
                        $shopOn = $shopIds && empty(array_diff($shopIds, $selected));
                    @endphp
                    <section class="ms-card overflow-hidden" wire:key="shop-{{ $artistId }}">
                        <header class="flex items-center gap-3 border-b border-ink-100 px-5 py-3.5">
                            <input type="checkbox" class="ms-check" wire:click="toggleShop({{ $artistId }})" @checked($shopOn) @disabled(! $shopIds)>
                            <x-icon name="store" class="h-4.5 w-4.5 text-brand-600" />
                            <a href="{{ route('artist.shop', $shop) }}" wire:navigate class="font-bold text-ink-900 hover:text-brand-700">{{ $shop->shop_name }}</a>
                        </header>

                        <ul class="divide-y divide-ink-100">
                            @foreach ($items as $item)
                                @php $p = $item->product; @endphp
                                <li class="flex gap-3 px-5 py-4 sm:gap-4" wire:key="item-{{ $item->id }}">
                                    <input type="checkbox" class="ms-check mt-8" value="{{ $item->id }}" wire:model.live="selected" @disabled($item->unavailable)>

                                    <a href="{{ route('product.detail', $p) }}" wire:navigate class="h-20 w-20 shrink-0 overflow-hidden rounded-xl bg-ink-100 sm:h-24 sm:w-24">
                                        <img src="{{ $p->cover_url }}" alt="" class="h-full w-full object-cover {{ $item->unavailable ? 'opacity-50 grayscale' : '' }}">
                                    </a>

                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <p class="text-[11px] font-bold tracking-wide text-accent-600 uppercase">{{ $p->fandom_name }}</p>
                                                <a href="{{ route('product.detail', $p) }}" wire:navigate class="line-clamp-2 text-sm font-bold text-ink-900 hover:text-brand-700">{{ $p->product_name }}</a>
                                                <p class="mt-0.5 text-xs text-ink-500">{{ $p->character?->character_name }} · {{ $p->merchandiseType?->type_name }}</p>
                                            </div>
                                            <button type="button" wire:click="remove({{ $item->id }})" class="ms-icon-btn h-8 w-8 shrink-0 text-ink-400 hover:text-red-600" aria-label="Hapus">
                                                <x-icon name="trash" class="h-4.5 w-4.5" />
                                            </button>
                                        </div>

                                        <div class="mt-1.5 flex flex-wrap gap-1.5">
                                            @if ($p->is_pre_order)
                                                <span class="ms-chip-po">Pre-order</span>
                                                @if ($p->openBatch)
                                                    <span class="text-[11px] font-semibold text-ink-500">tutup {{ $p->openBatch->closes_at->translatedFormat('d M') }}</span>
                                                @endif
                                            @else
                                                <span class="ms-chip-ready">Ready</span>
                                            @endif
                                        </div>

                                        @if ($item->unavailable)
                                            <p class="mt-2 inline-flex items-center gap-1.5 rounded-lg bg-red-50 px-2.5 py-1 text-xs font-bold text-red-700">
                                                <x-icon name="info" class="h-3.5 w-3.5" /> {{ $item->unavailable }}
                                            </p>
                                        @endif

                                        <div class="mt-2.5 flex flex-wrap items-center justify-between gap-3">
                                            <x-rupiah :value="$p->display_price" class="font-extrabold text-ink-900" />

                                            <div class="flex items-center rounded-lg border border-ink-200 bg-white">
                                                <button type="button" wire:click="changeQty({{ $item->id }}, -1)" class="flex h-8 w-8 items-center justify-center text-ink-600 hover:text-brand-700" aria-label="Kurangi">
                                                    <x-icon name="minus" class="h-3.5 w-3.5" stroke="2.4" />
                                                </button>
                                                <input type="number" value="{{ $item->quantity }}" min="1" wire:change="setQty({{ $item->id }}, $event.target.value)"
                                                       class="h-8 w-11 border-0 bg-transparent text-center text-sm font-bold focus:ring-0 focus:outline-none" aria-label="Jumlah">
                                                <button type="button" wire:click="changeQty({{ $item->id }}, 1)" class="flex h-8 w-8 items-center justify-center text-ink-600 hover:text-brand-700 disabled:opacity-40" @disabled($item->quantity >= $item->max) aria-label="Tambah">
                                                    <x-icon name="plus" class="h-3.5 w-3.5" stroke="2.4" />
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endforeach
            </div>

            {{-- Ringkasan --}}
            <aside class="ms-card sticky top-24 p-5 lg:col-span-4">
                <h2 class="font-extrabold text-ink-900">Ringkasan belanja</h2>
                <dl class="mt-4 space-y-2.5 text-sm">
                    <div class="flex justify-between"><dt class="text-ink-500">Total barang</dt><dd class="font-bold">{{ $summary['count'] }} pcs</dd></div>
                    <div class="flex justify-between"><dt class="text-ink-500">Subtotal harga</dt><dd class="font-bold"><x-rupiah :value="$summary['subtotal']" /></dd></div>
                    @if ($summary['dp'] < $summary['subtotal'])
                        <div class="flex justify-between"><dt class="text-ink-500">Dibayar sekarang (DP)</dt><dd class="font-bold text-accent-600"><x-rupiah :value="$summary['dp']" /></dd></div>
                    @endif
                    <div class="flex justify-between border-t border-ink-100 pt-3 text-base"><dt class="font-bold">{{ $summary['dp'] < $summary['subtotal'] ? 'Bayar sekarang' : 'Total' }}</dt><dd class="font-extrabold text-brand-700"><x-rupiah :value="$summary['dp']" /></dd></div>
                </dl>
                <p class="mt-2 text-xs text-ink-500">Ongkir dihitung saat checkout.</p>

                <button type="button" wire:click="checkout" wire:loading.attr="disabled" @disabled($summary['count'] < 1)
                        class="ms-btn-primary mt-5 w-full py-3 disabled:cursor-not-allowed disabled:opacity-60">
                    <x-icon name="lock" class="h-4.5 w-4.5" /> Checkout ({{ $summary['count'] }})
                </button>
                <p class="mt-2 text-center text-xs text-ink-500">Skema DP/lunas & pengiriman bisa diubah lagi di halaman checkout.</p>
            </aside>
        </div>
    @endif

    @if ($this->suggestions->isNotEmpty())
        <section class="mt-14">
            <x-section-heading title="Mungkin kamu suka juga" icon="sparkles" subtitle="Rekomendasi dari fandom favorit, pencarian, dan isi keranjangmu." />
            <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                @foreach ($this->suggestions as $s)
                    <x-product-card :product="$s" wire:key="sg-{{ $s->id }}" />
                @endforeach
            </div>
        </section>
    @endif
</div>
