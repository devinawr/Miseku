<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <x-studio-nav />
    <x-page-title title="Batch pre-order" subtitle="PO otomatis ditutup di tanggal tutup. Target minimum tidak tercapai → batch batal & pembeli direfund otomatis." />

    <div class="grid gap-4 lg:grid-cols-2">
        @forelse ($batches as $b)
            @php $pct = $b->min_order ? min(100, round($b->paid_qty / $b->min_order * 100)) : 100; @endphp
            <div class="ms-card p-5" wire:key="b-{{ $b->id }}">
                <div class="flex gap-3">
                    <img src="{{ $b->product->cover_url }}" class="h-16 w-16 rounded-xl object-cover" alt="">
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-bold text-ink-900">{{ $b->product->product_name }}</p>
                        <p class="text-xs text-ink-500">Batch #{{ $b->id }} · <x-rupiah :value="$b->price_po" /> {{ $b->is_dp ? '· DP ' . \App\Support\Money::rp($b->dp_amount) : '· bayar penuh' }}</p>
                        <p class="text-xs text-ink-500">{{ $b->opens_at->translatedFormat('d M') }} – {{ $b->closes_at->translatedFormat('d M Y H:i') }} · estimasi {{ $b->estimated_month?->translatedFormat('F Y') }}</p>
                    </div>
                    <span class="ms-chip ms-chip-po h-fit">{{ $b->status_label }}</span>
                </div>
                <div class="mt-3">
                    <div class="flex justify-between text-xs font-bold text-ink-600">
                        <span>Terbayar {{ $b->paid_qty }} pcs @if ($b->all_qty > $b->paid_qty)(+{{ $b->all_qty - $b->paid_qty }} belum bayar)@endif</span>
                        <span>Min {{ $b->min_order ?: '-' }} · Maks {{ $b->max_slot ?: '∞' }}</span>
                    </div>
                    <div class="mt-1 h-2 overflow-hidden rounded-full bg-ink-100"><div class="h-full {{ $pct >= 100 ? 'bg-emerald-500' : 'ms-gradient' }}" style="width: {{ $pct }}%"></div></div>
                </div>
                <div class="mt-4 flex flex-wrap gap-2">
                    @if ($b->batch_status === 'open')
                        <button type="button" wire:click="closeNow({{ $b->id }})" wire:confirm="Tutup PO sekarang? Tagihan yang belum dibayar akan dibatalkan." class="ms-btn-ghost">Tutup PO sekarang</button>
                    @elseif ($b->batch_status === 'closed')
                        <button type="button" wire:click="start({{ $b->id }})" class="ms-btn-primary">Mulai produksi</button>
                    @elseif ($b->batch_status === 'in_production')
                        <button type="button" wire:click="complete({{ $b->id }})" wire:confirm="Tandai produksi batch ini selesai? Pembeli DP menerima tagihan pelunasan ({{ config('miseku.settlement_days') }} hari)." class="ms-btn-primary">Produksi selesai</button>
                    @endif
                    <a href="{{ route('studio.orders', ['tab' => 'semua', 'q' => '']) }}" wire:navigate class="ms-btn-ghost">Pesanan</a>
                </div>
            </div>
        @empty
            <x-empty-state icon="clock" title="Belum ada batch PO" description="Buat produk dengan status Pre-order dari halaman Upload produk." class="lg:col-span-2" />
        @endforelse
    </div>
</div>
