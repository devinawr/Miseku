<div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
    <x-page-title :title="'Custom: ' . $r->design_character" :subtitle="($r->merchandiseType?->type_name) . ' · ' . $r->quantity . ' pcs · diajukan ' . $r->created_at->translatedFormat('d M Y')">
        <span class="ms-chip ms-chip-muted !px-3 !py-1.5 text-sm">{{ $r->status_label }}</span>
        <a href="{{ $isOwner ? route('custom.index') : route('studio.custom') }}" wire:navigate class="ms-btn-ghost"><x-icon name="chevron-left" class="h-4 w-4" /> Kembali</a>
    </x-page-title>

    <div class="grid items-start gap-6 lg:grid-cols-12">
        <section class="ms-card space-y-3 p-5 lg:col-span-5">
            <h2 class="font-extrabold text-ink-900">Detail permintaan</h2>
            <p class="text-sm text-ink-500">Pembeli: <b class="text-ink-800">{{ $r->buyer->name }}</b> · rating pembeli {{ number_format((float) $r->buyer->rating_as_buyer, 1) }}</p>
            <p class="text-sm text-ink-500">Budget: <b class="text-ink-800"><x-rupiah :value="$r->budget_price" /></b></p>
            <p class="whitespace-pre-line text-sm text-ink-700">{{ $r->specification }}</p>
            @if ($images->isNotEmpty())
                <div class="flex flex-wrap gap-2">@foreach ($images as $img)<a href="{{ $img->url }}" target="_blank"><img src="{{ $img->url }}" class="h-20 w-20 rounded-xl object-cover" alt=""></a>@endforeach</div>
            @endif
            @if ($r->order)
                <a href="{{ $isOwner ? route('orders.show', $r->order) : route('studio.orders.show', $r->order) }}" wire:navigate class="ms-btn-soft w-full">Lihat pesanan {{ $r->order->order_code }}</a>
            @endif
            @if ($isOwner && in_array($r->request_status, ['open', 'offered'], true))
                <button type="button" wire:click="cancelRequest" wire:confirm="Batalkan permintaan custom ini?" class="ms-btn-ghost w-full">Batalkan permintaan</button>
            @endif
        </section>

        <div class="space-y-4 lg:col-span-7">
            {{-- Form penawaran artis --}}
            @if ($profile && ! $isOwner && in_array($r->request_status, ['open', 'offered'], true) && ! ($myOffer && $myOffer->offer_status === 'pending'))
                <form wire:submit="submitOffer" class="ms-card space-y-3 p-5">
                    <h2 class="font-extrabold text-ink-900">Beri penawaran</h2>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <label class="block"><span class="ms-label">Harga total (Rp)</span><input type="number" step="1000" wire:model="offer_price" class="ms-input">@error('offer_price')<p class="ms-error">{{ $message }}</p>@enderror</label>
                        <label class="block"><span class="ms-label">Estimasi pengerjaan (hari)</span><input type="number" wire:model="estimated_days" class="ms-input">@error('estimated_days')<p class="ms-error">{{ $message }}</p>@enderror</label>
                    </div>
                    <textarea wire:model="note" rows="3" class="ms-input" placeholder="Catatan (bahan, revisi, dll.)"></textarea>
                    <p class="ms-hint">Harga sudah termasuk semua pcs (ongkir dihitung saat checkout). Pembeli membayar penuh di muka.</p>
                    <button type="submit" class="ms-btn-primary w-full">Kirim penawaran</button>
                </form>
            @endif

            <h2 class="ms-section-title">Penawaran ({{ $offers->count() }})</h2>
            @forelse ($offers as $of)
                <div class="ms-card p-5" wire:key="of-{{ $of->id }}">
                    <div class="flex flex-wrap items-center gap-2">
                        <a href="{{ route('artist.shop', $of->artistProfile) }}" wire:navigate class="font-bold text-ink-900 hover:text-brand-700">{{ $of->artistProfile->shop_name }}</a>
                        <span class="text-sm text-amber-500">★ {{ number_format((float) $of->artistProfile->rating, 1) }}</span>
                        <span class="ms-chip ms-chip-muted ml-auto">{{ $of->status_label }}</span>
                    </div>
                    <p class="mt-2 text-xl font-extrabold text-ink-900"><x-rupiah :value="$of->offer_price" /> <span class="text-sm font-normal text-ink-500">· {{ $of->estimated_days }} hari</span></p>
                    @if ($of->note)<p class="mt-1 text-sm text-ink-600">{{ $of->note }}</p>@endif
                    <div class="mt-3 flex flex-wrap gap-2">
                        @if ($isOwner)
                            <a href="{{ route('chat', ['with' => $of->artistProfile->user_id, 'custom' => $r->id]) }}" wire:navigate class="ms-btn-ghost"><x-icon name="chat" class="h-4.5 w-4.5" /> Diskusi</a>
                            @if ($of->offer_status === 'pending' && in_array($r->request_status, ['open', 'offered'], true))
                                <button type="button" wire:click="rejectOffer({{ $of->id }})" class="ms-btn-ghost">Tolak</button>
                                <button type="button" wire:click="choose({{ $of->id }})" class="ms-btn-primary">Pilih & checkout</button>
                            @endif
                        @elseif ($profile && $of->artist_profile_id === $profile->id)
                            <a href="{{ route('chat', ['with' => $r->buyer_id, 'custom' => $r->id]) }}" wire:navigate class="ms-btn-ghost"><x-icon name="chat" class="h-4.5 w-4.5" /> Chat pembeli</a>
                            @if ($of->offer_status === 'pending')<button type="button" wire:click="withdrawOffer({{ $of->id }})" class="ms-btn-ghost">Tarik penawaran</button>@endif
                        @endif
                    </div>
                </div>
            @empty
                <p class="ms-card p-6 text-center text-sm text-ink-500">Belum ada penawaran.</p>
            @endforelse
        </div>
    </div>
</div>
