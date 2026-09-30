@props(['product', 'showArtist' => true])

@php
    $badge = $product->status_badge;
    $tone  = [
        'ready'  => 'bg-miseku-100 text-miseku-700',
        'po'     => 'bg-sakura-400/25 text-sakura-500',
        'muted'  => 'bg-miseku-100 text-miseku-400',
    ][$badge['tone']];
@endphp

<article class="ms-card group flex flex-col overflow-hidden transition duration-200 hover:-translate-y-1 hover:shadow-[var(--shadow-lift)]">
    <a href="{{ route('product.detail', $product) }}" wire:navigate class="relative block aspect-square overflow-hidden bg-miseku-100">
        <img src="{{ $product->cover_url }}" alt="{{ $product->product_name }}" loading="lazy"
             class="h-full w-full object-cover transition duration-300 group-hover:scale-105">

        <span class="ms-chip absolute left-3 top-3 {{ $tone }} backdrop-blur-sm">{{ $badge['label'] }}</span>

        @if ($product->is_sold_out)
            <span class="absolute inset-0 flex items-center justify-center bg-white/65 text-sm font-bold uppercase tracking-wide text-miseku-600">
                Stok habis
            </span>
        @endif
    </a>

    <div class="flex flex-1 flex-col p-4">
        <p class="text-[11px] font-semibold uppercase tracking-wide text-miseku-400">
            {{ $product->merchandiseType?->type_name }}
            @if ($product->character?->fandom)
                · {{ $product->character->fandom->fandom_name }}
            @endif
        </p>

        <h3 class="mt-1 line-clamp-2 text-sm font-bold leading-snug text-miseku-900">
            <a href="{{ route('product.detail', $product) }}" wire:navigate class="hover:text-miseku-600">
                {{ $product->product_name }}
            </a>
        </h3>

        @if ($showArtist && $product->artistProfile)
            <a href="{{ route('artist.shop', $product->artistProfile) }}" wire:navigate
               class="mt-1.5 inline-flex items-center gap-1 text-xs font-medium text-miseku-500 hover:text-miseku-700">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M13.5 21v-7.5h-3V21M3 9.75 12 3l9 6.75M4.5 9.75V21h15V9.75" />
                </svg>
                {{ $product->artistProfile->shop_name }}
            </a>
        @endif

        <div class="mt-3 flex items-end justify-between gap-2 pt-1">
            <div>
                <x-rupiah :value="$product->display_price" class="text-base font-extrabold text-miseku-700" />
                @if ($product->is_pre_order && $product->openBatch?->is_dp)
                    <p class="text-[11px] font-medium text-miseku-400">
                        DP <x-rupiah :value="$product->openBatch->dp_amount" />
                    </p>
                @endif
            </div>
            @if ($product->rating_count > 0)
                <x-star-rating :rating="$product->rating" :count="$product->rating_count" size="sm" :show-value="false" />
            @else
                <span class="text-[11px] text-miseku-300">Belum ada ulasan</span>
            @endif
        </div>
    </div>
</article>
