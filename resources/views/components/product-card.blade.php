@props(['product', 'showArtist' => true, 'reason' => null])

@php
    $badge = $product->status_badge;
    $chip  = ['ready' => 'ms-chip-ready', 'po' => 'ms-chip-po', 'muted' => 'ms-chip-muted'][$badge['tone']];
@endphp

<article {{ $attributes->merge(['class' => 'group relative flex flex-col overflow-hidden rounded-2xl border border-ink-200/70 bg-white transition duration-200 hover:-translate-y-1 hover:border-brand-200 hover:shadow-[var(--shadow-lift)]']) }}>
    <a href="{{ route('product.detail', $product) }}" wire:navigate class="relative block aspect-square overflow-hidden bg-ink-100">
        <img src="{{ $product->cover_url }}" alt="{{ $product->product_name }}" loading="lazy"
             class="h-full w-full object-cover transition duration-500 group-hover:scale-105">

        <span class="{{ $chip }} absolute top-2.5 left-2.5 shadow-sm">{{ $badge['label'] }}</span>

        @if ($product->is_pre_order && $product->openBatch)
            <span class="absolute right-2.5 bottom-2.5 inline-flex items-center gap-1 rounded-full bg-ink-900/75 px-2 py-1 text-[10px] font-bold text-white backdrop-blur">
                <x-icon name="clock" class="h-3 w-3" stroke="2.2" />
                s.d. {{ $product->openBatch->closes_at->translatedFormat('d M') }}
            </span>
        @endif

        @if ($product->is_sold_out)
            <span class="absolute inset-0 flex items-center justify-center bg-white/70 text-sm font-extrabold tracking-wide text-ink-600 uppercase">
                Stok habis
            </span>
        @endif
    </a>

    <div class="flex flex-1 flex-col p-3.5">
        @if ($reason)
            <p class="mb-1.5 inline-flex items-center gap-1 self-start rounded-md bg-brand-50 px-1.5 py-0.5 text-[10px] font-bold text-brand-700">
                <x-icon name="sparkles" class="h-3 w-3" stroke="2" /> {{ $reason }}
            </p>
        @endif

        <p class="truncate text-[11px] font-bold tracking-wide text-accent-600 uppercase">
            {{ $product->fandom_name }}
        </p>

        <h3 class="mt-0.5 line-clamp-2 text-sm leading-snug font-bold text-ink-900">
            <a href="{{ route('product.detail', $product) }}" wire:navigate class="hover:text-brand-700">
                {{ $product->product_name }}
            </a>
        </h3>

        <p class="mt-1 truncate text-xs text-ink-500">
            {{ $product->character?->character_name }} · {{ $product->merchandiseType?->type_name }}
        </p>

        <div class="mt-auto pt-3">
            <x-rupiah :value="$product->display_price" class="text-base font-extrabold text-ink-900" />
            @if ($product->is_pre_order && $product->openBatch?->is_dp)
                <span class="ml-1 text-[11px] font-semibold text-accent-600">DP <x-rupiah :value="$product->openBatch->dp_amount" /></span>
            @endif

            <div class="mt-1.5 flex items-center justify-between gap-2 text-[11px] text-ink-500">
                @if ($showArtist && $product->artistProfile)
                    <a href="{{ route('artist.shop', $product->artistProfile) }}" wire:navigate
                       class="inline-flex min-w-0 items-center gap-1 font-semibold hover:text-brand-700">
                        <x-icon name="store" class="h-3.5 w-3.5 shrink-0" />
                        <span class="truncate">{{ $product->artistProfile->shop_name }}</span>
                    </a>
                @else
                    <span></span>
                @endif

                @if ($product->rating_count > 0)
                    <span class="inline-flex shrink-0 items-center gap-0.5 font-bold text-ink-700">
                        <svg class="h-3.5 w-3.5 text-amber-400" viewBox="0 0 20 20" fill="currentColor"><path d="M10 1.6l2.6 5.3 5.8.8-4.2 4.1 1 5.8-5.2-2.8-5.2 2.8 1-5.8L1.6 7.7l5.8-.8L10 1.6z"/></svg>
                        {{ number_format((float) $product->rating, 1, ',', '.') }}
                        <span class="font-medium text-ink-400">({{ $product->rating_count }})</span>
                    </span>
                @endif
            </div>
        </div>
    </div>
</article>
