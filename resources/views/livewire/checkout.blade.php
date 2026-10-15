@php
    $groups = $this->groups;
    $totals = $this->totals;
    $couriers = config('miseku.couriers');
    $needsAddress = collect($ship)->contains(fn ($s) => ($s['method'] ?? 'courier') === 'courier');
@endphp

<div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
    <x-page-title title="Checkout" :subtitle="$this->offer ? 'Pesanan custom dari ' . $this->offer->artistProfile->shop_name : 'Pesanan dipisah per toko; tiap produk pre-order jadi pesanan tersendiri.'">
        <a href="{{ $this->offer ? route('custom.show', $this->offer->custom_request_id) : route('cart') }}" wire:navigate class="ms-link"><x-icon name="chevron-left" class="h-4 w-4" /> Kembali</a>
    </x-page-title>

    <div class="grid items-start gap-6 lg:grid-cols-12">
        <div class="space-y-5 lg:col-span-8">
            {{-- Alamat --}}
            <section class="ms-card p-5">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="flex items-center gap-2 font-extrabold text-ink-900"><x-icon name="map-pin" class="h-5 w-5 text-brand-600" /> Alamat pengiriman</h2>
                    <a href="{{ route('account', ['tab' => 'alamat']) }}" wire:navigate class="ms-link text-sm">Kelola alamat</a>
                </div>
                @if ($this->addresses->isEmpty())
                    <p class="mt-3 rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-800">
                        Kamu belum punya alamat. Tambahkan di <a href="{{ route('account', ['tab' => 'alamat']) }}" wire:navigate class="font-bold underline">Akun → Alamat</a>, atau pilih ambil di booth event (kalau tersedia).
                    </p>
                @else
                    <div class="mt-3 grid gap-2 sm:grid-cols-2">
                        @foreach ($this->addresses as $a)
                            <label wire:key="addr-{{ $a->id }}" class="flex cursor-pointer gap-3 rounded-xl border-2 p-3 text-sm transition {{ $addressId === $a->id ? 'border-brand-500 bg-brand-50/60' : 'border-ink-200 hover:border-brand-200' }}">
                                <input type="radio" class="mt-1" wire:model.live="addressId" value="{{ $a->id }}">
                                <span>
                                    <span class="font-bold text-ink-900">{{ $a->address_label }}</span>
                                    @if ($a->is_primary)<span class="ms-chip ms-chip-ready ml-1">Utama</span>@endif
                                    <span class="block text-ink-700">{{ $a->recipient_name }}</span>
                                    <span class="block text-xs text-ink-500">{{ $a->one_line }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                @endif
            </section>

            @foreach ($groups as $artistId => $g)
                @php $s = $ship[$artistId] ?? []; @endphp
                <section class="ms-card overflow-hidden" wire:key="grp-{{ $artistId }}">
                    <header class="flex items-center gap-2 border-b border-ink-100 px-5 py-3.5">
                        <x-icon name="store" class="h-4.5 w-4.5 text-brand-600" />
                        <span class="font-bold text-ink-900">{{ $g['artist']->shop_name }}</span>
                        <span class="ms-chip ms-chip-muted ml-auto">{{ count($g['packages']) }} pesanan</span>
                    </header>

                    @foreach ($g['packages'] as $pkg)
                        <div class="border-b border-ink-100 px-5 py-4">
                            <p class="text-xs font-extrabold tracking-wide text-accent-600 uppercase">{{ $pkg['label'] }}</p>
                            @if ($this->offer)
                                @php $req = $this->offer->request; @endphp
                                <p class="mt-1 font-bold text-ink-900">{{ $req->design_character }} · {{ $req->merchandiseType?->type_name }}</p>
                                <p class="text-sm text-ink-500">{{ $req->quantity }} pcs · estimasi {{ $this->offer->estimated_days }} hari pengerjaan</p>
                            @endif
                            @foreach ($pkg['rows'] as $row)
                                @php $p = $row['product']; $batch = $row['batch']; @endphp
                                <div class="mt-3 flex gap-3" wire:key="row-{{ $row['index'] }}">
                                    <img src="{{ $p->cover_url }}" class="h-16 w-16 shrink-0 rounded-xl object-cover" alt="">
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate font-bold text-ink-900">{{ $p->product_name }}</p>
                                        <p class="text-sm text-ink-500">{{ $row['qty'] }} × <x-rupiah :value="$row['price']" /></p>
                                        @if ($batch)
                                            <p class="text-xs text-ink-500">PO tutup {{ $batch->closes_at?->translatedFormat('d M Y') }} · estimasi {{ $batch->estimated_month?->translatedFormat('F Y') }}</p>
                                            @if ($batch->is_dp)
                                                <div class="mt-2 flex flex-wrap gap-2">
                                                    <button type="button" wire:click="setScheme({{ $row['index'] }}, 'dp')" @class(['ms-pill', 'ms-pill-active' => $row['scheme'] === 'dp'])>DP <x-rupiah :value="$batch->dp_amount" />/pcs</button>
                                                    <button type="button" wire:click="setScheme({{ $row['index'] }}, 'full')" @class(['ms-pill', 'ms-pill-active' => $row['scheme'] === 'full'])>Bayar penuh</button>
                                                </div>
                                                @if ($row['scheme'] === 'dp')
                                                    <p class="mt-1 text-xs text-amber-700">Sisa dilunasi setelah produksi selesai, maks. {{ config('miseku.settlement_days') }} hari. Lewat tenggat → DP hangus.</p>
                                                @endif
                                            @endif
                                        @endif
                                    </div>
                                    <p class="text-sm font-bold text-ink-900"><x-rupiah :value="$row['price'] * $row['qty']" /></p>
                                </div>
                            @endforeach
                            <div class="mt-3 flex flex-wrap justify-between gap-2 text-sm text-ink-600">
                                <span>Ongkir paket ini: <b class="text-ink-900"><x-rupiah :value="$pkg['shipping']" /></b></span>
                                <span>Total <b class="text-ink-900"><x-rupiah :value="$pkg['total']" /></b>
                                    @if ($pkg['first'] !== null) · bayar sekarang (DP) <b class="text-brand-700"><x-rupiah :value="$pkg['pay_now']" /></b>@endif
                                </span>
                            </div>
                        </div>
                    @endforeach

                    {{-- Pengiriman --}}
                    <div class="space-y-3 bg-ink-50/60 px-5 py-4">
                        <div class="flex flex-wrap gap-2">
                            <button type="button" wire:click="$set('ship.{{ $artistId }}.method', 'courier')" @class(['ms-pill', 'ms-pill-active' => ($s['method'] ?? 'courier') === 'courier'])>
                                <x-icon name="truck" class="h-4 w-4" /> Kirim kurir
                            </button>
                            @if ($g['pickupAllowed'] && $g['booths']->isNotEmpty())
                                <button type="button" wire:click="$set('ship.{{ $artistId }}.method', 'event_pickup')" @class(['ms-pill', 'ms-pill-active' => ($s['method'] ?? '') === 'event_pickup'])>
                                    <x-icon name="calendar" class="h-4 w-4" /> Ambil di booth event
                                </button>
                            @elseif ($g['booths']->isNotEmpty())
                                <span class="ms-hint self-center">Ada produk yang tidak bisa diambil di event.</span>
                            @endif
                        </div>

                        @if (($s['method'] ?? 'courier') === 'courier')
                            <div class="grid gap-3 sm:grid-cols-2">
                                <label class="block">
                                    <span class="ms-label">Kurir</span>
                                    <select class="ms-select" wire:model.live="ship.{{ $artistId }}.courier">
                                        @foreach ($couriers as $code => $name)<option value="{{ $code }}">{{ $name }}</option>@endforeach
                                    </select>
                                </label>
                                <label class="block">
                                    <span class="ms-label">Layanan</span>
                                    <select class="ms-select" wire:model.live="ship.{{ $artistId }}.service" @disabled(! $g['quotes'])>
                                        @forelse ($g['quotes'] as $q)
                                            <option value="{{ $q['service'] }}">{{ $q['service'] }} · {{ $q['etd'] }}</option>
                                        @empty
                                            <option>Pilih alamat dulu</option>
                                        @endforelse
                                    </select>
                                </label>
                            </div>
                            @if ($g['error'])<p class="ms-error">{{ $g['error'] }}</p>@endif
                            @unless (app(\App\Services\ShippingService::class)->isLive())
                                <p class="ms-hint">Ongkir simulasi (RAJAONGKIR_API_KEY belum diisi) — dihitung dari jarak kota asal artis ke kota tujuan.</p>
                            @endunless
                        @else
                            <label class="block">
                                <span class="ms-label">Booth pengambilan</span>
                                <select class="ms-select" wire:model.live="ship.{{ $artistId }}.booth_id">
                                    <option value="">— pilih booth —</option>
                                    @foreach ($g['booths'] as $b)
                                        <option value="{{ $b->id }}">{{ $b->label }} · {{ $b->event->period_label }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <p class="ms-hint">Tanpa ongkir. QR pengambilan muncul di halaman pesanan setelah artis menyiapkan barang. Tidak diambil sampai event berakhir → pesanan gagal & dana tidak kembali.</p>
                        @endif
                    </div>
                </section>
            @endforeach
        </div>

        {{-- Ringkasan --}}
        <aside class="ms-card sticky top-36 space-y-3 p-5 lg:col-span-4">
            <h2 class="font-extrabold text-ink-900">Ringkasan</h2>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-ink-500">Jumlah pesanan</dt><dd class="font-bold">{{ $totals['orders'] }}</dd></div>
                <div class="flex justify-between"><dt class="text-ink-500">Subtotal produk</dt><dd><x-rupiah :value="$totals['subtotal']" /></dd></div>
                <div class="flex justify-between"><dt class="text-ink-500">Ongkir</dt><dd><x-rupiah :value="$totals['shipping']" /></dd></div>
                <div class="flex justify-between border-t border-ink-100 pt-2 text-base"><dt class="font-bold">Total</dt><dd class="font-extrabold"><x-rupiah :value="$totals['total']" /></dd></div>
                @if ($totals['pay_now'] < $totals['total'])
                    <div class="flex justify-between rounded-xl bg-brand-50 px-3 py-2"><dt class="font-bold text-brand-700">Dibayar sekarang</dt><dd class="font-extrabold text-brand-700"><x-rupiah :value="$totals['pay_now']" /></dd></div>
                @endif
            </dl>
            <button type="button" wire:click="place" wire:loading.attr="disabled" class="ms-btn-primary w-full" @disabled($needsAddress && ! $addressId)>
                <span wire:loading.remove wire:target="place">Buat pesanan & bayar</span>
                <span wire:loading wire:target="place">Memproses…</span>
            </button>
            <p class="ms-hint">Tagihan berlaku {{ config('miseku.payment_expiry_hours') }} jam. Pembayaran lewat Midtrans (QRIS, e-wallet, virtual account).</p>
        </aside>
    </div>
</div>
