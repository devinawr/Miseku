<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <x-admin-nav />
    <x-page-title title="Penarikan dana artis" subtitle="Transfer ke rekening artis, lalu unggah bukti. Ditolak → dana kembali ke saldo tersedia artis." />
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
