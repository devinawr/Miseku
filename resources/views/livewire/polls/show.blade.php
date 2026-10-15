<div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
    <x-page-title :title="$poll->poll_title" :subtitle="$poll->artistProfile->shop_name . ' · ' . $poll->start_date->translatedFormat('d M') . ' – ' . $poll->end_date->translatedFormat('d M Y H:i')">
        <span class="ms-chip ms-chip-po !px-3 !py-1.5 text-sm">{{ $poll->status_label }}</span>
        <a href="{{ route('polls.index') }}" wire:navigate class="ms-btn-ghost"><x-icon name="chevron-left" class="h-4 w-4" /> Semua voting</a>
    </x-page-title>
    @if ($poll->description)<p class="mb-6 max-w-3xl text-ink-600">{{ $poll->description }}</p>@endif

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($poll->options as $op)
            @php $pct = round($op->votes_count / $total * 100); $isWin = $winner && $winner->id === $op->id; @endphp
            <div class="ms-card overflow-hidden {{ $myVote === $op->id ? 'ring-2 ring-brand-500' : '' }} {{ $isWin ? 'ring-2 ring-pop-400' : '' }}" wire:key="op-{{ $op->id }}">
                <img src="{{ $op->design_image }}" class="aspect-square w-full object-cover" alt="">
                <div class="space-y-2 p-4">
                    <p class="font-bold text-ink-900">{{ $op->description ?: 'Opsi #' . $loop->iteration }} @if ($isWin)<span class="ms-chip bg-pop-100 text-pop-700">Pemenang</span>@endif</p>
                    @if ($showResult)
                        <div class="h-2 overflow-hidden rounded-full bg-ink-100"><div class="ms-gradient h-full" style="width: {{ $pct }}%"></div></div>
                        <p class="text-xs text-ink-500">{{ $op->votes_count }} suara ({{ $pct }}%)</p>
                    @endif
                    @if ($poll->is_open && ! $myVote)
                        <button type="button" wire:click="vote({{ $op->id }})" wire:confirm="Pilih desain ini? Suara tidak bisa diubah." class="ms-btn-primary w-full">Pilih desain ini</button>
                    @elseif ($myVote === $op->id)
                        <p class="text-sm font-bold text-brand-700">✓ Pilihanmu</p>
                    @endif
                    @if ($op->product)
                        <a href="{{ route('product.detail', $op->product) }}" wire:navigate class="ms-btn-soft w-full">Lihat PO desain ini</a>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>
