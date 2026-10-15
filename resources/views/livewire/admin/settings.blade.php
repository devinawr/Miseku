@php
    $runLabels = ['expired' => 'Tagihan kedaluwarsa', 'po_closed' => 'PO ditutup otomatis', 'polls' => 'Voting direkap', 'settle_remind' => 'Pengingat pelunasan', 'settle_fail' => 'Gagal pelunasan (DP hangus)', 'ship_remind' => 'Pengingat kirim', 'ship_cancel' => 'Batal karena tidak dikirim', 'pickup_forfeit' => 'Pick-up event berakhir'];
@endphp
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <x-admin-nav />
    <x-page-title title="Pengaturan & otomasi" />

    <div class="grid items-start gap-6 lg:grid-cols-12">
        <form wire:submit="save" class="ms-card space-y-4 p-5 lg:col-span-5">
            <h3 class="font-extrabold text-ink-900">Admin fee & penalti rating</h3>
            <div class="grid gap-3 sm:grid-cols-2">
                <label class="block"><span class="ms-label">Fee (%)</span><input type="number" step="0.01" wire:model="fee_percentage" class="ms-input"></label>
                <label class="block"><span class="ms-label">Fee minimum (Rp)</span><input type="number" step="100" wire:model="fee_minimum" class="ms-input"></label>
                <label class="block"><span class="ms-label">Penalti batal sukarela</span><input type="number" step="0.01" wire:model="penalty_voluntary" class="ms-input"></label>
                <label class="block"><span class="ms-label">Penalti tidak melunasi</span><input type="number" step="0.01" wire:model="penalty_settlement_timeout" class="ms-input"></label>
                <label class="block"><span class="ms-label">Penalti tidak mengirim</span><input type="number" step="0.01" wire:model="penalty_shipping_timeout" class="ms-input"></label>
                <label class="block"><span class="ms-label">Berlaku mulai</span><input type="datetime-local" wire:model="effective_from" class="ms-input"></label>
            </div>
            @if ($errors->any())<p class="ms-error">{{ $errors->first() }}</p>@endif
            <p class="ms-hint">Fee = maks(fee% × subtotal produk, fee minimum), tidak melebihi subtotal; dipotong saat dana dilepas ke artis. Penalti mengurangi rating rata-rata per kejadian.</p>
            <button type="submit" class="ms-btn-primary w-full">Simpan sebagai pengaturan baru</button>
        </form>

        <div class="space-y-6 lg:col-span-7">
            <section class="ms-card p-5">
                <h3 class="font-extrabold text-ink-900">Proses otomatis (timer BPMN)</h3>
                <p class="mt-1 text-sm text-ink-600">Berjalan tiap 15 menit lewat <code class="rounded bg-ink-100 px-1">php artisan schedule:work</code>. Tombol ini menjalankannya sekarang (sama dengan <code class="rounded bg-ink-100 px-1">php artisan miseku:tick</code>).</p>
                <button type="button" wire:click="runAutomation" class="ms-btn-soft mt-3"><x-icon name="refresh" class="h-4.5 w-4.5" /> Jalankan sekarang</button>
                @if ($lastRun)
                    <ul class="mt-3 grid gap-1 text-sm sm:grid-cols-2">
                        @foreach ($lastRun as $k => $n)<li class="flex justify-between rounded-lg bg-ink-50 px-3 py-1.5"><span>{{ $runLabels[$k] ?? $k }}</span><b>{{ $n }}</b></li>@endforeach
                    </ul>
                @endif
                <div class="mt-4 grid gap-1 text-xs text-ink-500 sm:grid-cols-2">
                    <p>Tagihan kedaluwarsa: {{ config('miseku.payment_expiry_hours') }} jam</p>
                    <p>Tenggat pelunasan DP: {{ config('miseku.settlement_days') }} hari</p>
                    <p>Tenggat kirim: {{ config('miseku.shipping_days') }} hari</p>
                    <p>Pengingat: H-{{ implode(', H-', config('miseku.reminder_days')) }}</p>
                    <p>Masa retur: {{ config('miseku.return_days') }} hari</p>
                    <p>Minimal penarikan: <x-rupiah :value="config('miseku.withdrawal_minimum')" /></p>
                </div>
                <button type="button" wire:click="recalc" class="ms-link mt-3 text-sm">Hitung ulang semua rating</button>
            </section>

            <section class="ms-card overflow-x-auto p-5">
                <h3 class="font-extrabold text-ink-900">Riwayat pengaturan</h3>
                <table class="mt-2 w-full min-w-[560px] text-sm">
                    <thead class="text-left text-xs font-bold text-ink-500 uppercase"><tr><th class="py-2">Berlaku</th><th>Fee</th><th>Min</th><th>Penalti (sukarela / lunas / kirim)</th><th>Oleh</th></tr></thead>
                    <tbody class="divide-y divide-ink-100">
                        @foreach ($history as $h)
                            <tr class="{{ $h->id === $current->id ? 'font-bold text-brand-700' : '' }}">
                                <td class="py-2">{{ $h->effective_from?->translatedFormat('d M Y H:i') }}</td>
                                <td>{{ rtrim(rtrim((string) $h->fee_percentage, '0'), '.') }}%</td>
                                <td><x-rupiah :value="$h->fee_minimum" /></td>
                                <td>{{ $h->penalty_voluntary }} / {{ $h->penalty_settlement_timeout }} / {{ $h->penalty_shipping_timeout }}</td>
                                <td>{{ $h->changer?->name ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </section>
        </div>
    </div>
</div>