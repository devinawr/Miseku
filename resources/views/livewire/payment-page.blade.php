@php $order = $payment->order; @endphp
<div class="mx-auto max-w-xl px-4 py-10 sm:px-6">
    <div class="ms-card overflow-hidden">
        <div class="ms-banner relative p-6 text-white">
            <div class="ms-dots absolute inset-0 opacity-20"></div>
            <div class="relative">
                <p class="text-xs font-bold tracking-widest text-white/80 uppercase">{{ $payment->type_label }} · {{ $order->order_code }}</p>
                <p class="mt-2 text-3xl font-extrabold"><x-rupiah :value="$payment->amount" /></p>
                <p class="mt-1 text-sm text-white/85">{{ $order->artistProfile->shop_name }} · {{ $order->type_label }}</p>
            </div>
        </div>

        <div class="space-y-4 p-6">
            <dl class="space-y-1.5 text-sm">
                <div class="flex justify-between"><dt class="text-ink-500">Kode tagihan</dt><dd class="font-mono text-xs">{{ $payment->midtrans_order_id }}</dd></div>
                <div class="flex justify-between"><dt class="text-ink-500">Status</dt><dd class="font-bold">{{ $payment->status_label }}</dd></div>
                @if ($payment->payment_status === 'pending')
                    <div class="flex justify-between"><dt class="text-ink-500">Bayar sebelum</dt><dd class="font-bold text-amber-700">{{ $expires->translatedFormat('d M Y H:i') }}</dd></div>
                @endif
                <div class="flex justify-between"><dt class="text-ink-500">Total pesanan</dt><dd><x-rupiah :value="$order->total_amount" /></dd></div>
            </dl>

            @if ($payment->payment_status !== 'pending')
                <a href="{{ route('orders.show', $order) }}" wire:navigate class="ms-btn-primary w-full">Lihat pesanan</a>
            @elseif ($live)
                @if ($gatewayError)
                    <p class="rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700">{{ $gatewayError }}</p>
                @else
                    <div x-data="{ pay() { window.snap.pay(@js($snapToken), { onSuccess: () => $wire.checkStatus(), onPending: () => $wire.checkStatus(), onError: () => $wire.checkStatus(), onClose: () => $wire.checkStatus() }) } }">
                        <button type="button" class="ms-btn-primary w-full" @click="pay()">Bayar sekarang (Midtrans)</button>
                    </div>
                    <button type="button" wire:click="checkStatus" class="ms-btn-ghost w-full"><x-icon name="refresh" class="h-4.5 w-4.5" /> Saya sudah bayar — cek status</button>
                    <script src="{{ $gateway->snapJsUrl() }}" data-client-key="{{ $gateway->clientKey() }}"></script>
                @endif
            @else
                <div class="rounded-xl border-2 border-dashed border-amber-300 bg-amber-50 p-4 text-sm text-amber-800">
                    <p class="font-bold">Mode simulasi pembayaran</p>
                    <p class="mt-1">MIDTRANS_SERVER_KEY belum diisi di .env, jadi pembayaran disimulasikan. Isi kunci sandbox Midtrans untuk memakai Snap asli.</p>
                </div>
                <label class="block">
                    <span class="ms-label">Metode (simulasi)</span>
                    <select class="ms-select" wire:model="simMethod">
                        @foreach (['QRIS', 'GoPay', 'ShopeePay', 'VA BCA', 'VA BNI', 'VA Mandiri'] as $m)<option>{{ $m }}</option>@endforeach
                    </select>
                </label>
                <button type="button" wire:click="simulatePay" class="ms-btn-primary w-full">Bayar <x-rupiah :value="$payment->amount" /> (simulasi berhasil)</button>
                <button type="button" wire:click="simulateFail" wire:confirm="Simulasikan pembayaran gagal? Tagihan pertama yang gagal membatalkan pesanan." class="ms-btn-ghost w-full">Simulasikan gagal</button>
            @endif

            <a href="{{ route('orders.show', $order) }}" wire:navigate class="ms-link block text-center text-sm">Bayar nanti — ke detail pesanan</a>
        </div>
    </div>
</div>
