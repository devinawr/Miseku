<div class="flex gap-3">
    <x-avatar :user="$m->user" class="h-9 w-9 shrink-0 text-sm" />
    <div class="min-w-0 flex-1">
        <p class="text-sm"><b class="text-ink-900">{{ $m->user?->name }}</b> <span class="text-xs text-ink-400">{{ $m->sent_at?->diffForHumans() }}</span></p>
        @if ($m->message_body !== '')<p class="mt-1 text-sm whitespace-pre-line text-ink-700">{{ $m->message_body }}</p>@endif
        <x-message-photos :images="$m->images" class="max-w-md" />
        @if ($m->attachedProduct)
            <a href="{{ route('product.detail', $m->attachedProduct) }}" wire:navigate class="mt-2 flex max-w-sm items-center gap-3 rounded-xl border border-ink-200 p-2 hover:border-brand-300">
                <img src="{{ $m->attachedProduct->cover_url }}" class="h-12 w-12 rounded-lg object-cover" alt="">
                <span class="text-sm font-bold text-ink-900">{{ $m->attachedProduct->product_name }}</span>
            </a>
        @endif
        @if ($m->attachedArtist)
            <a href="{{ route('artist.shop', $m->attachedArtist) }}" wire:navigate class="ms-chip mt-2 bg-brand-50 text-brand-700"><x-icon name="store" class="h-3.5 w-3.5" /> {{ $m->attachedArtist->shop_name }}</a>
        @endif
        {{-- $mode: 'public' (halaman komunitas) atau 'admin' (Panel admin → Komunitas) --}}
        @php $mode = $mode ?? 'public'; $mine = auth()->id() === $m->user_id; @endphp
        <div class="mt-1 flex gap-3 text-xs font-bold">
            @if ($mode === 'admin')
                <button type="button" wire:click="askDelete('message', {{ $m->id }})" class="text-red-600 hover:underline">Hapus</button>
            @else
                @if ($canReply ?? false)<button type="button" wire:click="reply({{ $m->id }})" class="text-brand-700 hover:underline">Balas</button>@endif
                @if ($mine)
                    <button type="button" wire:click="deleteMessage({{ $m->id }})" wire:confirm="Hapus pesan ini?" class="text-red-600 hover:underline">Hapus</button>
                @elseif (auth()->check() && ! auth()->user()->isAdmin())
                    <button type="button" wire:click="openReport({{ $m->id }})" class="text-ink-400 hover:text-red-600 hover:underline">Laporkan</button>
                @endif
            @endif
        </div>
    </div>
</div>
