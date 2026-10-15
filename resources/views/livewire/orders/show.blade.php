<div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
    <x-page-title :title="'Pesanan ' . $o->order_code" :subtitle="'Dibuat ' . $o->created_at->translatedFormat('d M Y H:i')">
        <x-order-status :order="$o" class="!px-3 !py-1.5 text-sm" />
        <a href="{{ route('orders.index') }}" wire:navigate class="ms-btn-ghost"><x-icon name="chevron-left" class="h-4 w-4" /> Semua pesanan</a>
    </x-page-title>

    <div class="grid items-start gap-6 lg:grid-cols-12">
        <div class="space-y-5 lg:col-span-8">
            @include('partials.order-info', ['o' => $o])
        </div>

        <aside class="space-y-5 lg:col-span-4">
            @if ($mine)
                {{-- Aksi utama --}}
                <section class="ms-card space-y-3 p-5">
                    <h3 class="font-extrabold text-ink-900">Tindakan</h3>

                    @if ($pending)
                        <a href="{{ route('payments.show', $pending) }}" class="ms-btn-primary w-full">Bayar {{ $pending->type_label }} <x-rupiah :value="$pending->amount" /></a>
                    @elseif ($o->order_status === 'awaiting_settlement')
                        <button type="button" wire:click="reissueSettlement" class="ms-btn-primary w-full">Terbitkan ulang tagihan pelunasan</button>
                    @endif

                    @if ($o->order_status === 'awaiting_payment')
                        <button type="button" wire:click="cancelUnpaid" wire:confirm="Batalkan pesanan yang belum dibayar ini?" class="ms-btn-ghost w-full">Batalkan pesanan</button>
                    @endif

                    @if ($o->order_status === 'shipped')
                        <button type="button" wire:click="confirmReceived" wire:confirm="Konfirmasi barang sudah diterima? Dana akan diteruskan ke artis." class="ms-btn-primary w-full"><x-icon name="check-circle" class="h-5 w-5" /> Pesanan diterima</button>
                    @endif

                    @if ($o->order_status === 'ready_for_pickup' && $o->pickup)
                        <div class="rounded-2xl border-2 border-brand-200 bg-white p-4 text-center" wire:ignore>
                            <p class="text-sm font-bold text-ink-900">Tunjukkan QR ini ke artis di booth</p>
                            <canvas x-data="qrCanvas(@js($o->pickup->qr_code))" class="mx-auto mt-3"></canvas>
                            <p class="mt-2 font-mono text-sm tracking-wider text-ink-600">{{ $o->pickup->qr_code }}</p>
                        </div>
                    @endif

                    <a href="{{ route('chat', ['with' => $o->artistProfile->user_id, 'pesanan' => $o->id]) }}" wire:navigate class="ms-btn-ghost w-full"><x-icon name="chat" class="h-5 w-5" /> Chat artis</a>
                </section>

                {{-- Pembatalan --}}
                @if ($o->order_status !== 'awaiting_payment' && ! in_array($o->order_status, ['completed', 'cancelled', 'failed'], true))
                    <section class="ms-card space-y-3 p-5" x-data="{ open: false }">
                        <div class="flex items-center justify-between">
                            <h3 class="font-extrabold text-ink-900">Ajukan pembatalan</h3>
                            <button type="button" class="ms-link text-sm" @click="open = !open" x-text="open ? 'Tutup' : 'Buka'"></button>
                        </div>
                        @if ($openCase)
                            <p class="rounded-xl bg-amber-50 px-3 py-2 text-sm text-amber-800">Pengajuan sedang ditinjau artis{{ $openCase->cancellation_status === 'in_discussion' ? ' (diskusikan lewat chat)' : '' }}.</p>
                        @else
                            <div x-show="open" x-cloak class="space-y-2">
                                @if ($cancelProblem)<p class="ms-hint text-amber-700">{{ $cancelProblem }} Pengajuan tetap bisa dikirim, tapi akan ditolak otomatis.</p>@endif
                                <p class="ms-hint">Disetujui → dana dikembalikan penuh & rating akunmu terkena penalti pembatalan.</p>
                                <textarea wire:model="cancelReason" rows="3" class="ms-input" placeholder="Alasan pembatalan"></textarea>
                                @error('cancelReason')<p class="ms-error">{{ $message }}</p>@enderror
                                <button type="button" wire:click="requestCancel" class="ms-btn-danger w-full">Kirim pengajuan</button>
                            </div>
                        @endif
                    </section>
                @endif

                {{-- Retur --}}
                @if ($o->order_status === 'completed' && $o->order_type !== 'ots_event')
                    <section class="ms-card space-y-3 p-5" x-data="{ open: false }">
                        <div class="flex items-center justify-between">
                            <h3 class="font-extrabold text-ink-900">Retur barang</h3>
                            @if (! $activeReturn && ! $returnProblem)<button type="button" class="ms-link text-sm" @click="open = !open" x-text="open ? 'Tutup' : 'Ajukan'"></button>@endif
                        </div>
                        @if ($activeReturn)
                            <p class="text-sm text-ink-600">Status retur: <b>{{ $activeReturn->status_label }}</b>@if ($activeReturn->return_status === 'processing') — kirim barang kembali ke artis & kabari resinya lewat chat.@endif</p>
                        @elseif ($returnProblem)
                            <p class="ms-hint">{{ $returnProblem }}</p>
                        @else
                            <p class="ms-hint">Bisa diajukan sampai {{ $returnDeadline?->translatedFormat('d M Y H:i') }}. Wajib foto bukti.</p>
                            <div x-show="open" x-cloak class="space-y-2">
                                <textarea wire:model="returnReason" rows="3" class="ms-input" placeholder="Jelaskan masalahnya (rusak, salah kirim, dll.)"></textarea>
                                @error('returnReason')<p class="ms-error">{{ $message }}</p>@enderror
                                <input type="file" wire:model="returnPhotos" multiple accept="image/*" class="block w-full text-sm">
                                <div wire:loading wire:target="returnPhotos" class="ms-hint">Mengunggah…</div>
                                @error('returnPhotos')<p class="ms-error">{{ $message }}</p>@enderror
                                @error('returnPhotos.*')<p class="ms-error">{{ $message }}</p>@enderror
                                <button type="button" wire:click="requestReturn" class="ms-btn-danger w-full">Kirim pengajuan retur</button>
                            </div>
                        @endif
                    </section>
                @endif

                {{-- Ulasan --}}
                @if ($canReview)
                    <section class="ms-card space-y-3 p-5">
                        <h3 class="font-extrabold text-ink-900">Beri ulasan</h3>
                        <div>
                            <p class="ms-label">Toko {{ $o->artistProfile->shop_name }}</p>
                            <div class="flex gap-1">
                                @for ($s = 1; $s <= 5; $s++)
                                    <button type="button" wire:click="$set('shopRating', {{ $s }})" class="text-2xl {{ $s <= $shopRating ? 'text-amber-400' : 'text-ink-200' }}">★</button>
                                @endfor
                            </div>
                            <textarea wire:model="shopComment" rows="2" class="ms-input mt-1" placeholder="Pelayanan artis (opsional)"></textarea>
                        </div>
                        @foreach ($o->items as $item)
                            <div wire:key="rv-{{ $item->product_id }}">
                                <p class="ms-label">{{ $item->product?->product_name }}</p>
                                <div class="flex gap-1">
                                    @for ($s = 1; $s <= 5; $s++)
                                        <button type="button" wire:click="$set('productReviews.{{ $item->product_id }}.rating', {{ $s }})" class="text-2xl {{ $s <= ($productReviews[$item->product_id]['rating'] ?? 0) ? 'text-amber-400' : 'text-ink-200' }}">★</button>
                                    @endfor
                                </div>
                                <textarea wire:model="productReviews.{{ $item->product_id }}.comment" rows="2" class="ms-input mt-1" placeholder="Kualitas produk (opsional)"></textarea>
                            </div>
                        @endforeach
                        <button type="button" wire:click="submitReview" class="ms-btn-primary w-full">Kirim ulasan</button>
                    </section>
                @endif
            @endif

            @if ($myReview || $artistReview)
                <section class="ms-card space-y-2 p-5 text-sm">
                    <h3 class="font-extrabold text-ink-900">Ulasan</h3>
                    @if ($myReview)<p><b>Pembeli → toko:</b> <span class="text-amber-500">{{ str_repeat('★', $myReview->rating) }}</span> {{ $myReview->comment }}</p>@endif
                    @foreach ($o->productReviews as $pr)<p><b>Produk:</b> <span class="text-amber-500">{{ str_repeat('★', $pr->rating) }}</span> {{ $pr->comment }}</p>@endforeach
                    @if ($artistReview)<p><b>Artis → pembeli:</b> <span class="text-amber-500">{{ str_repeat('★', $artistReview->rating) }}</span> {{ $artistReview->comment }}</p>@endif
                </section>
            @endif

            @if (! $mine)
                <p class="ms-hint">Kamu melihat pesanan ini sebagai admin (hanya baca).</p>
            @endif
        </aside>
    </div>
</div>
