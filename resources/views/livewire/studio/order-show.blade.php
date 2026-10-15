<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <x-studio-nav />
    <x-page-title :title="'Pesanan ' . $o->order_code" :subtitle="'Pembeli ' . $o->buyer->name . ' (rating ' . number_format((float) $o->buyer->rating_as_buyer, 1) . ') · ' . $o->created_at->translatedFormat('d M Y H:i')">
        <x-order-status :order="$o" class="!px-3 !py-1.5 text-sm" />
        <a href="{{ route('studio.orders') }}" wire:navigate class="ms-btn-ghost"><x-icon name="chevron-left" class="h-4 w-4" /> Pesanan</a>
    </x-page-title>

    <div class="grid items-start gap-6 lg:grid-cols-12">
        <div class="space-y-5 lg:col-span-8">
            @include('partials.order-info', ['o' => $o])
        </div>

        <aside class="space-y-5 lg:col-span-4">
            <section class="ms-card space-y-3 p-5">
                <h3 class="font-extrabold text-ink-900">Proses pesanan</h3>

                @if ($o->order_status === 'awaiting_payment')
                    <p class="text-sm text-ink-500">Menunggu pembeli membayar.</p>
                @endif

                {{-- Produksi (PO & custom) --}}
                @if (in_array($o->order_type, ['pre_order', 'custom'], true))
                    @if ($o->order_type === 'pre_order' && $o->order_status === 'paid')
                        <p class="ms-hint">Produksi PO dimulai per batch setelah PO ditutup (menu <a href="{{ route('studio.batches') }}" wire:navigate class="ms-link">Batch PO</a>).</p>
                    @endif
                    @if ($o->order_type === 'custom' && $o->order_status === 'paid')
                        <button type="button" wire:click="production('awaiting_production')" class="ms-btn-soft w-full">Tandai menunggu produksi</button>
                    @endif
                    @if (in_array($o->order_status, ['paid', 'awaiting_production'], true) && ($o->order_type === 'custom' || $o->order_status === 'awaiting_production'))
                        <button type="button" wire:click="production('in_production')" class="ms-btn-soft w-full">Mulai produksi</button>
                    @endif
                    @if (in_array($o->order_status, ['awaiting_production', 'in_production'], true))
                        <button type="button" wire:click="completeProduction" wire:confirm="Tandai produksi selesai?{{ $o->isDp() ? ' Tagihan pelunasan akan dikirim ke pembeli.' : '' }}" class="ms-btn-primary w-full">Produksi selesai</button>
                    @endif
                    @if ($o->order_status === 'awaiting_settlement')
                        <p class="rounded-xl bg-amber-50 px-3 py-2 text-sm text-amber-800">Menunggu pelunasan pembeli (≤ {{ $o->settlement_deadline?->translatedFormat('d M Y H:i') }}). Lewat tenggat → gagal & DP jadi milikmu.</p>
                    @endif
                @endif

                {{-- Kirim / pick-up --}}
                @if ($ready)
                    @if ($o->isCourier())
                        <div class="space-y-2 rounded-xl border border-ink-200 p-3">
                            <p class="text-sm font-bold text-ink-900">Kirim pesanan</p>
                            <input type="text" wire:model="courier" class="ms-input" placeholder="Kurir (mis. JNE REG)">
                            @error('courier')<p class="ms-error">{{ $message }}</p>@enderror
                            <input type="text" wire:model="tracking" class="ms-input" placeholder="Nomor resi">
                            @error('tracking')<p class="ms-error">{{ $message }}</p>@enderror
                            <button type="button" wire:click="ship" class="ms-btn-primary w-full"><x-icon name="truck" class="h-5 w-5" /> Simpan resi & kirim</button>
                            @if ($o->shipping_deadline)<p class="ms-hint">Batas kirim {{ $o->shipping_deadline->translatedFormat('d M Y H:i') }} — lewat → batal otomatis + penalti rating.</p>@endif
                        </div>
                    @else
                        <button type="button" wire:click="readyForPickup" class="ms-btn-primary w-full"><x-icon name="qr" class="h-5 w-5" /> Siap diambil di booth</button>
                        <p class="ms-hint">Pembeli menerima QR. Scan di menu <a href="{{ route('studio.scan') }}" wire:navigate class="ms-link">Scan QR</a> saat event berlangsung.</p>
                    @endif
                @endif

                @if ($o->order_status === 'shipped')
                    <p class="text-sm text-ink-600">Menunggu pembeli konfirmasi diterima.</p>
                @endif

                <a href="{{ route('chat', ['with' => $o->buyer_id, 'pesanan' => $o->id]) }}" wire:navigate class="ms-btn-ghost w-full"><x-icon name="chat" class="h-5 w-5" /> Chat pembeli</a>
            </section>

            {{-- Pengajuan pembatalan pembeli --}}
            @if ($openCase)
                <section class="ms-card space-y-3 border-red-200 p-5">
                    <h3 class="font-extrabold text-red-700">Pengajuan pembatalan</h3>
                    <p class="text-sm text-ink-700">"{{ $openCase->reason }}"</p>
                    @if ($openCase->cancellation_status === 'in_discussion')<p class="ms-hint">Pesanan custom — diskusikan dulu lewat chat sebelum memutuskan.</p>@endif
                    <textarea wire:model="note" rows="2" class="ms-input" placeholder="Tanggapan (wajib jika menolak)"></textarea>
                    @error('note')<p class="ms-error">{{ $message }}</p>@enderror
                    <div class="flex gap-2">
                        <button type="button" wire:click="decideCancel({{ $openCase->id }}, false)" class="ms-btn-ghost flex-1">Tolak</button>
                        <button type="button" wire:click="decideCancel({{ $openCase->id }}, true)" wire:confirm="Setujui pembatalan? Dana dikembalikan ke pembeli." class="ms-btn-danger flex-1">Setujui</button>
                    </div>
                </section>
            @endif

            {{-- Retur --}}
            @if ($activeReturn && in_array($activeReturn->return_status, ['submitted', 'processing'], true))
                <section class="ms-card space-y-3 p-5">
                    <h3 class="font-extrabold text-red-700">Retur — {{ $activeReturn->status_label }}</h3>
                    <p class="text-sm text-ink-700">"{{ $activeReturn->reason }}"</p>
                    <div class="flex flex-wrap gap-2">@foreach ($activeReturn->photos as $ph)<a href="{{ $ph->url }}" target="_blank"><img src="{{ $ph->url }}" class="h-16 w-16 rounded-lg object-cover" alt=""></a>@endforeach</div>
                    <textarea wire:model="note" rows="2" class="ms-input" placeholder="Catatan untuk pembeli"></textarea>
                    @error('note')<p class="ms-error">{{ $message }}</p>@enderror
                    @if ($activeReturn->return_status === 'submitted')
                        <div class="flex gap-2">
                            <button type="button" wire:click="reviewReturn({{ $activeReturn->id }}, false)" class="ms-btn-ghost flex-1">Tolak</button>
                            <button type="button" wire:click="reviewReturn({{ $activeReturn->id }}, true)" class="ms-btn-primary flex-1">Setujui retur</button>
                        </div>
                    @else
                        <p class="ms-hint">Setelah barang retur sampai, periksa lalu putuskan. Sesuai → refund penuh ke pembeli (dipotong dari saldo tersediamu).</p>
                        <div class="flex gap-2">
                            <button type="button" wire:click="inspectReturn({{ $activeReturn->id }}, false)" class="ms-btn-ghost flex-1">Tidak sesuai</button>
                            <button type="button" wire:click="inspectReturn({{ $activeReturn->id }}, true)" wire:confirm="Barang diterima & sesuai? Refund akan diproses." class="ms-btn-danger flex-1">Diterima, refund</button>
                        </div>
                    @endif
                </section>
            @endif

            {{-- Ulasan untuk pembeli --}}
            @if ($canReview)
                <section class="ms-card space-y-3 p-5">
                    <h3 class="font-extrabold text-ink-900">Nilai pembeli</h3>
                    <div class="flex gap-1">
                        @for ($s = 1; $s <= 5; $s++)
                            <button type="button" wire:click="$set('buyerRating', {{ $s }})" class="text-2xl {{ $s <= $buyerRating ? 'text-amber-400' : 'text-ink-200' }}">★</button>
                        @endfor
                    </div>
                    <textarea wire:model="buyerComment" rows="2" class="ms-input" placeholder="Komentar (opsional)"></textarea>
                    <button type="button" wire:click="reviewBuyer" class="ms-btn-primary w-full">Kirim ulasan</button>
                </section>
            @endif
            @if ($myReview || $buyerReview)
                <section class="ms-card space-y-2 p-5 text-sm">
                    <h3 class="font-extrabold text-ink-900">Ulasan</h3>
                    @if ($buyerReview)<p><b>Pembeli → toko:</b> <span class="text-amber-500">{{ str_repeat('★', $buyerReview->rating) }}</span> {{ $buyerReview->comment }}</p>@endif
                    @if ($myReview)<p><b>Kamu → pembeli:</b> <span class="text-amber-500">{{ str_repeat('★', $myReview->rating) }}</span> {{ $myReview->comment }}</p>@endif
                </section>
            @endif

            {{-- Batalkan oleh artis --}}
            @if ($canCancel)
                <section class="ms-card space-y-2 p-5" x-data="{ open: false }">
                    <button type="button" class="text-sm font-bold text-red-600" @click="open = !open">Batalkan pesanan (oleh artis)</button>
                    <div x-show="open" x-cloak class="space-y-2">
                        <p class="ms-hint">Dana dikembalikan penuh ke pembeli dan rating tokomu terkena penalti pembatalan.</p>
                        <textarea wire:model="cancelReason" rows="2" class="ms-input" placeholder="Alasan"></textarea>
                        @error('cancelReason')<p class="ms-error">{{ $message }}</p>@enderror
                        <button type="button" wire:click="cancelByArtist" wire:confirm="Yakin membatalkan pesanan ini?" class="ms-btn-danger w-full">Batalkan</button>
                    </div>
                </section>
            @endif
        </aside>
    </div>
</div>
