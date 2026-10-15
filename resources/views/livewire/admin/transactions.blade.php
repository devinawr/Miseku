<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <x-admin-nav />
    <x-page-title title="Transaksi & refund" subtitle="Pantau pesanan dan pembayaran Midtrans. Refund yang tidak didukung API (mis. virtual account) ditransfer manual lalu unggah buktinya.">
        @if ($tab !== 'refund')<input type="search" wire:model.live.debounce.300ms="q" class="ms-input w-64" placeholder="Cari kode / pembeli…">@endif
    </x-page-title>
    <div class="mb-4 flex flex-wrap gap-1.5">
        @foreach (['pesanan' => 'Pesanan', 'pembayaran' => 'Pembayaran', 'refund' => 'Refund'] as $k => $label)
            <button type="button" wire:click="$set('tab', '{{ $k }}')" @class(['ms-tab', 'ms-tab-active' => $tab === $k])>{{ $label }}</button>
        @endforeach
        @if ($tab === 'pesanan')
            <select wire:model.live="status" class="ms-select ml-auto w-52"><option value="">Semua status</option>@foreach ($statuses as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select>
        @endif
    </div>

    <div class="ms-card overflow-x-auto">
        @if ($tab === 'pesanan')
            <table class="w-full min-w-[800px] text-sm">
                <thead class="bg-ink-50 text-left text-xs font-bold text-ink-500 uppercase"><tr><th class="px-4 py-3">Kode</th><th class="px-4 py-3">Pembeli → Toko</th><th class="px-4 py-3">Jenis</th><th class="px-4 py-3 text-right">Total</th><th class="px-4 py-3 text-right">Admin fee</th><th class="px-4 py-3">Status</th></tr></thead>
                <tbody class="divide-y divide-ink-100">
                    @foreach ($orders as $o)
                        <tr wire:key="ao-{{ $o->id }}">
                            <td class="px-4 py-3"><a href="{{ route('orders.show', $o) }}" wire:navigate class="font-mono text-xs font-bold text-brand-700 hover:underline">{{ $o->order_code }}</a><br><span class="text-xs text-ink-400">{{ $o->created_at->translatedFormat('d M Y H:i') }}</span></td>
                            <td class="px-4 py-3">{{ $o->buyer->name }} → {{ $o->artistProfile->shop_name }}</td>
                            <td class="px-4 py-3">{{ $o->type_label }}{{ $o->isDp() ? ' · DP' : '' }}</td>
                            <td class="px-4 py-3 text-right font-bold"><x-rupiah :value="$o->total_amount" /></td>
                            <td class="px-4 py-3 text-right"><x-rupiah :value="$o->admin_fee" /></td>
                            <td class="px-4 py-3"><x-order-status :order="$o" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="p-4">{{ $orders->links('components.pagination') }}</div>
        @elseif ($tab === 'pembayaran')
            <table class="w-full min-w-[760px] text-sm">
                <thead class="bg-ink-50 text-left text-xs font-bold text-ink-500 uppercase"><tr><th class="px-4 py-3">Midtrans order ID</th><th class="px-4 py-3">Pembeli</th><th class="px-4 py-3">Jenis</th><th class="px-4 py-3">Metode</th><th class="px-4 py-3 text-right">Jumlah</th><th class="px-4 py-3">Status</th></tr></thead>
                <tbody class="divide-y divide-ink-100">
                    @foreach ($payments as $p)
                        <tr wire:key="pp-{{ $p->id }}">
                            <td class="px-4 py-3 font-mono text-xs">{{ $p->midtrans_order_id }}<br><span class="text-ink-400">{{ $p->midtrans_transaction_id }}</span></td>
                            <td class="px-4 py-3">{{ $p->order?->buyer?->name }}</td>
                            <td class="px-4 py-3">{{ $p->type_label }}</td>
                            <td class="px-4 py-3">{{ $p->payment_method ?: '-' }}</td>
                            <td class="px-4 py-3 text-right font-bold"><x-rupiah :value="$p->amount" /></td>
                            <td class="px-4 py-3">{{ $p->status_label }}<br><span class="text-xs text-ink-400">{{ ($p->paid_at ?? $p->created_at)->translatedFormat('d M Y H:i') }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="p-4">{{ $payments->links('components.pagination') }}</div>
        @else
            <div class="divide-y divide-ink-100">
                @forelse ($refunds as $r)
                    <div class="flex flex-wrap items-center gap-3 px-5 py-4 text-sm" wire:key="rf-{{ $r->id }}">
                        <div class="min-w-0 flex-1">
                            <p><b><x-rupiah :value="$r->amount" /></b> ke {{ $r->order?->buyer?->name }} ({{ $r->order?->buyer?->email }}) · <a href="{{ route('orders.show', $r->order) }}" wire:navigate class="ms-link font-mono text-xs">{{ $r->order?->order_code }}</a></p>
                            <p class="text-xs text-ink-500">{{ $r->refund_method === 'manual_transfer' ? 'Transfer manual' : 'Midtrans API' }} · {{ $r->created_at->translatedFormat('d M Y H:i') }} · {{ $r->status_label }}</p>
                            @if ($r->refund_proof)<a href="{{ $r->refund_proof }}" target="_blank" class="ms-link text-xs">Bukti transfer</a>@endif
                        </div>
                        @if ($r->payment_status === 'pending')
                            <div class="flex flex-wrap items-center gap-2">
                                <a href="{{ route('chat', ['with' => $r->order->buyer_id, 'pesanan' => $r->customer_order_id]) }}" wire:navigate class="ms-btn-ghost !py-1.5 text-xs">Minta rekening pembeli</a>
                                <input type="file" wire:model="proof.{{ $r->id }}" accept="image/*" class="w-48 text-xs">
                                <button type="button" wire:click="completeRefund({{ $r->id }})" class="ms-btn-primary !py-1.5 text-xs">Tandai sudah ditransfer</button>
                                @error('proof.' . $r->id)<p class="ms-error w-full">{{ $message }}</p>@enderror
                            </div>
                        @endif
                    </div>
                @empty
                    <p class="px-5 py-10 text-center text-sm text-ink-500">Belum ada refund.</p>
                @endforelse
            </div>
            <div class="p-4">{{ $refunds->links('components.pagination') }}</div>
        @endif
    </div>
</div>
