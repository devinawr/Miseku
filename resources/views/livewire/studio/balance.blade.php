<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <x-studio-nav />
    <x-page-title title="Saldo & penarikan" subtitle="Dana pembeli ditahan sampai pesanan selesai, lalu dilepas ke saldo tersedia setelah dipotong admin fee." />

    <div class="grid gap-4 md:grid-cols-3">
        <div class="ms-card p-5">
            <p class="text-sm text-ink-500">Saldo tersedia</p>
            <p class="mt-1 text-3xl font-extrabold text-emerald-700"><x-rupiah :value="$profile->available_balance" /></p>
        </div>
        <div class="ms-card p-5">
            <p class="text-sm text-ink-500">Saldo tertahan (pesanan berjalan)</p>
            <p class="mt-1 text-3xl font-extrabold text-ink-900"><x-rupiah :value="$profile->held_balance" /></p>
        </div>
        <form wire:submit="withdraw" class="ms-card space-y-2 p-5">
            <p class="text-sm font-bold text-ink-900">Tarik dana ke {{ $profile->bank_name ?: '—' }} {{ $profile->bank_account_number }}</p>
            @if (! $profile->bank_name || ! $profile->bank_account_number)
                <p class="ms-hint text-amber-700">Lengkapi rekening di <a href="{{ route('account', ['tab' => 'toko']) }}" wire:navigate class="ms-link">Akun → Toko saya</a>.</p>
            @endif
            <div class="flex gap-2">
                <input type="number" wire:model="amount" min="{{ config('miseku.withdrawal_minimum') }}" step="1000" class="ms-input flex-1" placeholder="Jumlah">
                <button type="submit" class="ms-btn-primary">Tarik</button>
            </div>
            @error('amount')<p class="ms-error">{{ $message }}</p>@enderror
            <p class="ms-hint">Minimal <x-rupiah :value="config('miseku.withdrawal_minimum')" />. Diproses manual oleh admin.</p>
        </form>
    </div>

    <div class="mt-6 grid items-start gap-6 lg:grid-cols-12">
        <section class="ms-card overflow-x-auto lg:col-span-8">
            <h3 class="px-5 pt-4 font-extrabold text-ink-900">Mutasi saldo</h3>
            <table class="mt-2 w-full min-w-[680px] text-sm">
                <colgroup><col class="w-32"><col class="w-36"><col><col class="w-28"><col class="w-24"><col class="w-32"></colgroup>
                <thead class="bg-ink-50 text-left text-xs font-bold text-ink-500 uppercase"><tr><th class="px-4 py-2">Tanggal</th><th class="px-4 py-2">Jenis</th><th class="px-4 py-2">Keterangan</th><th class="px-4 py-2 text-right">Bruto</th><th class="px-4 py-2 text-right">Fee</th><th class="px-4 py-2 text-right">Jumlah</th></tr></thead>
                <tbody class="divide-y divide-ink-100">
                    @forelse ($mutations as $m)
                        <tr wire:key="mt-{{ $m->id }}" class="align-middle">
                            <td class="px-4 py-3 text-xs leading-snug text-ink-500">{{ $m->created_at->translatedFormat('d M Y') }}<br>{{ $m->created_at->format('H:i') }}</td>
                            <td class="px-4 py-3"><span class="ms-chip whitespace-nowrap {{ $m->mutation_type === 'credit' ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}">{{ $m->category_label }}</span></td>
                            <td class="px-4 py-3 leading-snug">
                                <span class="text-ink-800">{{ $m->description }}</span>
                                @if ($m->order)<span class="mt-0.5 block text-xs text-ink-400">{{ $m->order->order_code }}</span>@endif
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap"><x-rupiah :value="$m->gross_amount" /></td>
                            <td class="px-4 py-3 text-right whitespace-nowrap text-ink-500">{{ (float) $m->fee_amount ? \App\Support\Money::rp($m->fee_amount) : '-' }}</td>
                            <td class="px-4 py-3 text-right font-bold whitespace-nowrap {{ $m->mutation_type === 'credit' ? 'text-emerald-700' : 'text-red-600' }}">{{ $m->mutation_type === 'credit' ? '+' : '−' }}<x-rupiah :value="$m->amount" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-ink-500">Belum ada mutasi.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="p-4">{{ $mutations->links('components.pagination') }}</div>
        </section>
        <section class="ms-card p-5 lg:col-span-4">
            <h3 class="font-extrabold text-ink-900">Riwayat penarikan</h3>
            <ul class="mt-2 divide-y divide-ink-100 text-sm">
                @forelse ($withdrawals as $w)
                    <li wire:key="w-{{ $w->id }}">
                        <button type="button" wire:click="showWithdrawal({{ $w->id }})" class="-mx-2 block w-[calc(100%+1rem)] rounded-xl px-2 py-2.5 text-left hover:bg-brand-50">
                            <div class="flex items-center justify-between gap-2"><b><x-rupiah :value="$w->amount" /></b><span class="ms-chip whitespace-nowrap {{ $w->status_tone }}">{{ $w->status_label }}</span></div>
                            <p class="mt-0.5 text-xs text-ink-500">{{ $w->requested_at?->translatedFormat('d M Y H:i') }} · {{ $w->bank_name }} {{ $w->bank_account_number }}</p>
                            <p class="mt-0.5 text-xs font-bold text-brand-700">Lihat detail →</p>
                        </button>
                    </li>
                @empty
                    <li class="py-2 text-ink-500">Belum ada penarikan.</li>
                @endforelse
            </ul>
        </section>
    </div>

    {{-- ============ Detail penarikan (modal) ============ --}}
    @if ($detail)
        <div class="fixed inset-0 z-[60] flex items-end justify-center bg-black/50 p-0 sm:items-center sm:p-4" wire:click.self="$set('detailId', null)" wire:key="wd-detail-{{ $detail->id }}">
            <div class="max-h-[92vh] w-full max-w-lg overflow-y-auto rounded-t-3xl bg-white p-6 shadow-2xl sm:rounded-3xl">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-bold text-ink-500 uppercase">Penarikan #{{ $detail->id }}</p>
                        <p class="mt-1 text-2xl font-extrabold text-ink-900"><x-rupiah :value="$detail->amount" /></p>
                    </div>
                    <button type="button" wire:click="$set('detailId', null)" class="ms-icon-btn"><x-icon name="x" class="h-5 w-5" /></button>
                </div>
                <span class="ms-chip mt-2 {{ $detail->status_tone }}">{{ $detail->status_label }}</span>

                <dl class="mt-5 grid grid-cols-[8rem_1fr] gap-y-2 text-sm">
                    <dt class="text-ink-500">Rekening tujuan</dt><dd class="font-semibold text-ink-900">{{ $detail->bank_name }} {{ $detail->bank_account_number }}</dd>
                    <dt class="text-ink-500">Diajukan</dt><dd class="text-ink-900">{{ $detail->requested_at?->translatedFormat('d M Y H:i') }}</dd>
                    @if ($detail->processor)
                        <dt class="text-ink-500">Diproses oleh</dt><dd class="text-ink-900">{{ $detail->processor->name }}</dd>
                    @endif
                    @if ($detail->processed_at)
                        <dt class="text-ink-500">{{ $detail->withdrawal_status === 'rejected' ? 'Ditolak' : 'Ditransfer' }}</dt><dd class="text-ink-900">{{ $detail->processed_at->translatedFormat('d M Y H:i') }}</dd>
                    @endif
                </dl>

                @if ($detail->withdrawal_status === 'rejected')
                    <div class="mt-5 rounded-2xl bg-red-50 p-4 text-sm ring-1 ring-red-200">
                        <p class="font-extrabold text-red-700">Alasan ditolak</p>
                        <p class="mt-1 text-ink-800">{{ $detail->rejection_reason ?: 'Admin tidak menuliskan alasan. Cek notifikasi atau chat admin.' }}</p>
                        <p class="mt-2 text-xs text-ink-500">Dana <x-rupiah :value="$detail->amount" /> sudah dikembalikan ke saldo tersedia.</p>
                    </div>
                @endif

                @if ($detail->transfer_proof)
                    <div class="mt-5">
                        <p class="text-sm font-extrabold text-ink-900">Bukti transfer dari admin</p>
                        <a href="{{ $detail->transfer_proof }}" target="_blank" class="mt-2 block overflow-hidden rounded-2xl ring-1 ring-ink-200">
                            <img src="{{ $detail->transfer_proof }}" alt="Bukti transfer" class="max-h-80 w-full bg-ink-50 object-contain">
                        </a>
                        <p class="mt-1 text-xs text-ink-500">Klik gambar untuk membuka ukuran penuh.</p>
                    </div>
                @elseif (in_array($detail->withdrawal_status, ['submitted', 'processing'], true))
                    <p class="mt-5 rounded-2xl bg-amber-50 p-4 text-sm text-amber-800 ring-1 ring-amber-200">Sedang menunggu admin mentransfer. Bukti transfer akan muncul di sini.</p>
                @endif
            </div>
        </div>
    @endif
</div>
