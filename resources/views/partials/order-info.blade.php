{{-- Rincian pesanan (dipakai halaman pembeli & Studio). Variabel: $o (CustomerOrder) --}}
@php
    $steps = match ($o->order_type) {
        'ready_stock' => ['awaiting_payment', 'paid', $o->isPickup() ? 'ready_for_pickup' : 'shipped', 'completed'],
        'ots_event'   => ['completed'],
        default       => array_values(array_filter(['awaiting_payment', 'paid', 'awaiting_production', 'in_production', $o->isDp() ? 'awaiting_settlement' : null, 'production_completed', $o->isPickup() ? 'ready_for_pickup' : 'shipped', 'completed'])),
    };
    $current = array_search($o->order_status, $steps, true);
    if ($o->order_status === 'production_completed' && $o->isDp()) { $current = array_search('production_completed', $steps, true); }
@endphp

{{-- Progres: lingkaran di atas, label di bawah, garis penghubung; kolom sama lebar (di HP bisa digeser) --}}
@if (! in_array($o->order_status, ['cancelled', 'failed'], true) && count($steps) > 1)
    <section class="ms-card overflow-x-auto px-3 py-5">
        <ol class="grid min-w-max sm:min-w-0" style="grid-template-columns: repeat({{ count($steps) }}, minmax(5.5rem, 1fr))">
            @foreach ($steps as $i => $st)
                @php
                    $done = $current !== false && $i <= $current;
                    $isNow = $current !== false && $i === $current;
                    $lineDone = $current !== false && $i < $current;
                @endphp
                <li class="relative flex flex-col items-center px-1 text-center">
                    @unless ($loop->last)
                        <span class="absolute top-3.5 left-1/2 h-0.5 w-full {{ $lineDone ? 'bg-brand-500' : 'bg-ink-200' }}"></span>
                    @endunless
                    <span class="relative z-10 flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-extrabold {{ $done ? 'bg-brand-600 text-white' : 'bg-ink-100 text-ink-400 ring-4 ring-white' }} {{ $isNow ? 'ring-4 ring-brand-100' : '' }}">
                        @if ($done) <x-icon name="check" class="h-4 w-4" stroke="2.6" /> @else {{ $i + 1 }} @endif
                    </span>
                    <span class="mt-2 text-[11px] leading-tight font-bold sm:text-xs {{ $isNow ? 'text-brand-700' : ($done ? 'text-ink-900' : 'text-ink-400') }}">{{ \App\Models\CustomerOrder::STATUS_LABELS[$st] }}</span>
                </li>
            @endforeach
        </ol>
    </section>
@endif

{{-- Item --}}
<section class="ms-card overflow-hidden">
    <header class="flex flex-wrap items-center gap-2 border-b border-ink-100 px-5 py-3.5">
        <x-icon name="store" class="h-4.5 w-4.5 text-brand-600" />
        <a href="{{ route('artist.shop', $o->artistProfile) }}" wire:navigate class="font-bold text-ink-900 hover:text-brand-700">{{ $o->artistProfile->shop_name }}</a>
        <span class="ms-chip ms-chip-muted">{{ $o->type_label }}</span>
        @if ($o->isDp())<span class="ms-chip ms-chip-po">Skema DP</span>@endif
    </header>
    @if ($o->order_type === 'custom' && $o->customRequest)
        @php $r = $o->customRequest; @endphp
        <div class="px-5 py-4 text-sm">
            <p class="font-bold text-ink-900">{{ $r->design_character }} · {{ $r->merchandiseType?->type_name }} · {{ $r->quantity }} pcs</p>
            <p class="mt-1 whitespace-pre-line text-ink-600">{{ $r->specification }}</p>
            <a href="{{ route('custom.show', $r) }}" wire:navigate class="ms-link mt-2 text-sm">Lihat permintaan custom</a>
        </div>
    @endif
    <ul class="divide-y divide-ink-100">
        @foreach ($o->items as $item)
            <li class="flex gap-3 px-5 py-3.5" wire:key="it-{{ $item->id }}">
                <img src="{{ $item->product?->cover_url }}" class="h-16 w-16 shrink-0 rounded-xl bg-ink-100 object-cover" alt="">
                <div class="min-w-0 flex-1">
                    <a href="{{ route('product.detail', $item->product_id) }}" wire:navigate class="font-bold text-ink-900 hover:text-brand-700">{{ $item->product?->product_name }}</a>
                    <p class="text-sm text-ink-500">{{ $item->quantity }} × <x-rupiah :value="$item->unit_price" /></p>
                    @if ($item->batch)
                        <p class="text-xs text-ink-500">Batch PO #{{ $item->batch->id }} · {{ $item->batch->status_label }} · tutup {{ $item->batch->closes_at?->translatedFormat('d M Y') }} · min {{ $item->batch->min_order }} pcs · estimasi {{ $item->batch->estimated_month?->translatedFormat('F Y') }}</p>
                    @endif
                </div>
                <p class="text-sm font-bold"><x-rupiah :value="$item->subtotal" /></p>
            </li>
        @endforeach
    </ul>
    <dl class="space-y-1.5 border-t border-ink-100 bg-ink-50/60 px-5 py-4 text-sm">
        <div class="flex justify-between"><dt class="text-ink-500">Subtotal produk</dt><dd><x-rupiah :value="$o->product_subtotal" /></dd></div>
        <div class="flex justify-between"><dt class="text-ink-500">Ongkir</dt><dd><x-rupiah :value="$o->shipping_cost" /></dd></div>
        <div class="flex justify-between text-base"><dt class="font-bold">Total</dt><dd class="font-extrabold"><x-rupiah :value="$o->total_amount" /></dd></div>
        <div class="flex justify-between"><dt class="text-ink-500">Sudah dibayar</dt><dd class="font-bold text-emerald-700"><x-rupiah :value="$o->paidAmount()" /></dd></div>
        @if ((float) $o->admin_fee > 0)
            <div class="flex justify-between"><dt class="text-ink-500">Admin fee (dipotong dari artis)</dt><dd><x-rupiah :value="$o->admin_fee" /></dd></div>
        @endif
    </dl>
</section>

{{-- Pengiriman / pengambilan --}}
<section class="ms-card p-5 text-sm">
    @if ($o->isCourier())
        <h3 class="flex items-center gap-2 font-extrabold text-ink-900"><x-icon name="truck" class="h-5 w-5 text-brand-600" /> Pengiriman kurir</h3>
        @if ($o->address)
            <p class="mt-2 font-bold">{{ $o->address->recipient_name }}</p>
            <p class="text-ink-600">{{ $o->address->one_line }}</p>
        @endif
        <p class="mt-2 text-ink-600">Kurir: <b>{{ $o->courier ?: '-' }}</b> @if ($o->tracking_number) · Resi <b class="font-mono">{{ $o->tracking_number }}</b>@endif</p>
        @if ($o->shipping_deadline && in_array($o->order_status, ['paid', 'production_completed'], true))
            <p class="mt-1 text-amber-700">Batas artis mengirim: {{ $o->shipping_deadline->translatedFormat('d M Y H:i') }}</p>
        @endif
    @elseif ($o->isPickup() && $o->booth)
        <h3 class="flex items-center gap-2 font-extrabold text-ink-900"><x-icon name="calendar" class="h-5 w-5 text-brand-600" /> Ambil di booth event</h3>
        <p class="mt-2 font-bold">{{ $o->booth->event->event_name }}</p>
        <p class="text-ink-600">{{ $o->booth->event->location }} · {{ $o->booth->event->period_label }}</p>
        <p class="text-ink-600">Booth {{ $o->booth->booth_number ?: '(belum ada nomor)' }} @if ($o->booth->days)· hadir hari ke-{{ implode(', ', $o->booth->days) }}@endif @if ($o->booth->operating_hours)· jam {{ $o->booth->operating_hours }}@endif</p>
        @if ($o->pickup)
            <p class="mt-1">Status pengambilan: <b>{{ ['not_picked_up' => 'Belum diambil', 'picked_up' => 'Sudah diambil', 'forfeited' => 'Hangus (tidak diambil)'][$o->pickup->pickup_status] }}</b>
                @if ($o->pickup->picked_up_at) · {{ $o->pickup->picked_up_at->translatedFormat('d M Y H:i') }}@endif</p>
        @endif
    @elseif ($o->order_type === 'ots_event')
        <h3 class="font-extrabold text-ink-900">Penjualan langsung di booth</h3>
        <p class="mt-1 text-ink-600">{{ $o->booth?->label }}</p>
    @endif
    @if ($o->settlement_deadline && $o->order_status === 'awaiting_settlement')
        <p class="mt-2 rounded-xl bg-amber-50 px-3 py-2 font-bold text-amber-800">Tenggat pelunasan: {{ $o->settlement_deadline->translatedFormat('d M Y H:i') }}</p>
    @endif
</section>

{{-- Riwayat pembayaran --}}
<section class="ms-card p-5">
    <h3 class="flex items-center gap-2 font-extrabold text-ink-900"><x-icon name="wallet" class="h-5 w-5 text-brand-600" /> Pembayaran & refund</h3>
    <ul class="mt-3 divide-y divide-ink-100 text-sm">
        @forelse ($o->payments as $p)
            <li class="flex flex-wrap items-center justify-between gap-2 py-2.5" wire:key="pay-{{ $p->id }}">
                <span>
                    <b>{{ $p->type_label }}</b>
                    <span class="font-mono text-xs text-ink-400">{{ $p->midtrans_order_id }}</span>
                    <span class="block text-xs text-ink-500">{{ $p->payment_method ?: ($p->refund_method === 'manual_transfer' ? 'Transfer manual oleh admin' : ($p->refund_method === 'midtrans_api' ? 'Refund via Midtrans' : '-')) }} · {{ ($p->paid_at ?? $p->created_at)->translatedFormat('d M Y H:i') }}</span>
                    @if ($p->refund_proof)<a href="{{ $p->refund_proof }}" target="_blank" class="ms-link text-xs">Bukti transfer refund</a>@endif
                </span>
                <span class="text-right">
                    <span class="font-bold {{ $p->payment_type === 'refund' ? 'text-emerald-700' : '' }}">{{ $p->payment_type === 'refund' ? '+' : '' }}<x-rupiah :value="$p->amount" /></span>
                    <span class="block text-xs {{ $p->payment_status === 'success' ? 'text-emerald-600' : ($p->payment_status === 'pending' ? 'text-amber-600' : 'text-ink-400') }}">{{ $p->status_label }}</span>
                </span>
            </li>
        @empty
            <li class="py-2 text-ink-500">Belum ada pembayaran.</li>
        @endforelse
    </ul>
</section>

{{-- Pembatalan & retur --}}
@if ($o->cancellations->isNotEmpty() || $o->returns->isNotEmpty())
    <section class="ms-card space-y-3 p-5 text-sm">
        <h3 class="font-extrabold text-ink-900">Riwayat pembatalan / retur</h3>
        @foreach ($o->cancellations as $c)
            <div class="rounded-xl bg-ink-50 px-4 py-3" wire:key="c-{{ $c->id }}">
                <p><b>Pembatalan</b> ({{ ['voluntary' => 'diajukan', 'settlement_timeout' => 'tidak dilunasi', 'shipping_timeout' => 'tidak dikirim'][$c->cancellation_type] }}) oleh {{ $c->requester?->name ?? 'sistem' }} — <b>{{ ['submitted' => 'Menunggu artis', 'in_discussion' => 'Diskusi lewat chat', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'][$c->cancellation_status] }}</b></p>
                <p class="text-ink-600">Alasan: {{ $c->reason }}</p>
                @if ($c->artist_response)<p class="text-ink-600">Tanggapan: {{ $c->artist_response }}</p>@endif
            </div>
        @endforeach
        @foreach ($o->returns as $r)
            <div class="rounded-xl bg-ink-50 px-4 py-3" wire:key="r-{{ $r->id }}">
                <p><b>Retur</b> — <b>{{ $r->status_label }}</b> · {{ $r->created_at->translatedFormat('d M Y') }}</p>
                <p class="text-ink-600">Alasan: {{ $r->reason }}</p>
                <div class="mt-2 flex flex-wrap gap-2">
                    @foreach ($r->photos as $ph)<a href="{{ $ph->url }}" target="_blank"><img src="{{ $ph->url }}" class="h-16 w-16 rounded-lg object-cover" alt=""></a>@endforeach
                </div>
            </div>
        @endforeach
    </section>
@endif
