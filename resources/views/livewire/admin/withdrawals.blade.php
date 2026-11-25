<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <x-admin-nav />
    <x-page-title title="Penarikan dana artis" subtitle="Transfer ke rekening artis, lalu unggah bukti. Ditolak → dana kembali ke saldo tersedia artis." />
    @if ($demoSimulation)
        <p class="mb-4 rounded-xl bg-amber-50 p-3 text-sm font-semibold text-amber-800">Mode demo: saldo pembayaran SIM adalah simulasi, bukan dana Midtrans asli.</p>
    @endif
    <div class="mb-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="ms-card p-5"><p class="text-sm text-ink-500">Total saldo toko</p><p class="mt-1 text-2xl font-extrabold"><x-rupiah :value="$totals['available'] + $totals['held']" /></p><p class="mt-1 text-xs text-ink-500">Saldo tersedia + tertahan pesanan.</p></div>
        <div class="ms-card p-5"><p class="text-sm text-ink-500">Siap diajukan</p><p class="mt-1 text-2xl font-extrabold text-emerald-700"><x-rupiah :value="$totals['eligible']" /></p><p class="mt-1 text-xs text-ink-500">Total dari seluruh toko.</p></div>
        <div class="ms-card p-5"><p class="text-sm text-ink-500">Tertahan pesanan</p><p class="mt-1 text-2xl font-extrabold"><x-rupiah :value="$totals['held']" /></p><p class="mt-1 text-xs text-ink-500">Pesanan belum selesai.</p></div>
        <div class="ms-card p-5"><p class="text-sm text-ink-500">Pengajuan diproses</p><p class="mt-1 text-2xl font-extrabold"><x-rupiah :value="$totals['pending']" /></p><p class="mt-1 text-xs text-ink-500">Tidak termasuk total saldo toko.</p></div>
    </div>
    <p class="mb-4 text-xs text-ink-500">Total berasal dari catatan Miseku, bukan saldo bank atau Midtrans. Pengajuan yang sedang diproses sudah dikeluarkan dari saldo toko.</p>
    <section class="ms-card mb-6 divide-y divide-ink-100">
        <h2 class="px-5 py-4 font-extrabold text-ink-900">Rincian saldo setiap toko</h2>
        @forelse ($shops as $entry)
            @php $shop = $entry['profile']; $balance = $entry['eligibility']; @endphp
            <details class="px-5 py-4 text-sm" wire:key="shop-saldo-{{ $shop->id }}">
                <summary class="cursor-pointer font-bold text-ink-900">{{ $shop->shop_name }} · siap diajukan <x-rupiah :value="$balance['eligible']" /></summary>
                <div class="mt-3 grid gap-2 text-ink-700 sm:grid-cols-2 lg:grid-cols-3">
                    <p>Saldo tersedia: <b><x-rupiah :value="$shop->available_balance" /></b></p>
                    <p>Tertahan pesanan: <b><x-rupiah :value="$shop->held_balance" /></b></p>
                    <p>Menunggu 3 hari kerja: <b><x-rupiah :value="$balance['waiting_settlement']" /></b></p>
                    <p>Pengajuan diproses: <b><x-rupiah :value="$balance['pending_withdrawal']" /></b></p>
                    @if ($balance['waiting_other'] > 0)<p>Dana belum siap: <b><x-rupiah :value="$balance['waiting_other']" /></b></p>@endif
                    @if ($balance['next_at'])<p>Siap berikutnya sekitar: {{ $balance['next_at']->translatedFormat('d M Y H:i') }} WIB</p>@endif
                </div>
            </details>
        @empty <p class="px-5 py-6 text-sm text-ink-500">Belum ada toko.</p> @endforelse
    </section>
    <h2 class="mb-3 font-extrabold text-ink-900">Pengajuan penarikan</h2>
    <div class="ms-card divide-y divide-ink-100">
        @forelse ($rows as $w)
            @php $open = in_array($w->withdrawal_status, ['submitted', 'processing'], true); @endphp
            <div class="grid gap-4 px-5 py-4 text-sm {{ $open ? 'lg:grid-cols-[1fr_auto]' : '' }}" wire:key="wd-{{ $w->id }}">
                {{-- Info pengajuan --}}
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <b class="text-base text-ink-900"><x-rupiah :value="$w->amount" /></b>
                        <span class="text-ink-700">· {{ $w->artistProfile->shop_name }}</span>
                        <span class="ms-chip {{ $w->status_tone }}">{{ $w->status_label }}</span>
                    </div>
                    <p class="mt-1 text-xs text-ink-500">#{{ $w->id }} · {{ $w->bank_name }} {{ $w->bank_account_number }} · diajukan {{ $w->requested_at?->translatedFormat('d M Y H:i') }} @if ($w->processor)· oleh {{ $w->processor->name }}@endif</p>
                    @if ($w->transfer_proof)
                        <a href="{{ $w->transfer_proof }}" target="_blank" class="ms-link mt-1 text-xs"><x-icon name="photo" class="h-3.5 w-3.5" /> Lihat bukti transfer</a>
                    @endif
                    @if ($w->withdrawal_status === 'rejected' && $w->rejection_reason)
                        <p class="mt-1 text-xs text-red-700">Alasan ditolak: {{ $w->rejection_reason }}</p>
                    @endif
                    @if ($w->withdrawal_status === 'submitted')
                        <button type="button" wire:click="process({{ $w->id }})" class="ms-btn-ghost mt-3 !py-1.5 text-xs">Tandai sedang diproses</button>
                    @endif
                </div>

                {{-- Aksi: dua kotak sejajar, error tepat di bawah isian masing-masing --}}
                @if ($open)
                    <div class="grid gap-3 sm:grid-cols-2 lg:w-[34rem]">
                        <div class="rounded-xl border border-ink-200 p-3">
                            <p class="text-xs font-extrabold text-ink-900">Sudah ditransfer</p>
                            <label class="mt-2 flex cursor-pointer items-center gap-2 rounded-lg border border-dashed border-ink-300 px-3 py-2 text-xs text-ink-600 hover:border-brand-400">
                                <x-icon name="photo" class="h-4 w-4 shrink-0 text-ink-400" />
                                <span class="truncate">
                                    @if (! empty($proof[$w->id])) {{ $proof[$w->id]->getClientOriginalName() }} @else Pilih foto bukti transfer @endif
                                </span>
                                <input type="file" wire:model="proof.{{ $w->id }}" accept="image/*" class="hidden">
                            </label>
                            <div wire:loading wire:target="proof.{{ $w->id }}" class="mt-1 text-[11px] text-ink-500">Mengunggah…</div>
                            @error('proof.' . $w->id)<p class="ms-error">{{ $message }}</p>@enderror
                            <button type="button" wire:click="complete({{ $w->id }})" class="ms-btn-primary mt-2 w-full !py-1.5 text-xs">Selesai</button>
                        </div>
                        <div class="rounded-xl border border-ink-200 p-3">
                            <p class="text-xs font-extrabold text-ink-900">Tolak penarikan</p>
                            <input type="text" wire:model="reason.{{ $w->id }}" class="ms-input mt-2 !py-2 text-xs" placeholder="Alasan ditolak (dilihat artis)">
                            @error('reason.' . $w->id)<p class="ms-error">{{ $message }}</p>@enderror
                            <button type="button" wire:click="reject({{ $w->id }})" wire:confirm="Tolak penarikan ini? Saldo dikembalikan ke artis." class="ms-btn-danger mt-2 w-full !py-1.5 text-xs">Tolak</button>
                        </div>
                    </div>
                @endif
            </div>
        @empty
            <p class="px-5 py-10 text-center text-sm text-ink-500">Belum ada pengajuan penarikan.</p>
        @endforelse
    </div>
</div>
