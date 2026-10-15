@props([
    'rating'    => 0,
    'count'     => null,
    'size'      => 'sm',      // sm | md | lg
    'showValue' => true,
])

@php
    $rating = (float) $rating;
    $box    = ['sm' => 'h-3.5 w-3.5', 'md' => 'h-4.5 w-4.5', 'lg' => 'h-5 w-5'][$size] ?? 'h-4 w-4';
    $text   = ['sm' => 'text-xs', 'md' => 'text-sm', 'lg' => 'text-base'][$size] ?? 'text-sm';
@endphp

<div {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5']) }}>
    <div class="flex items-center gap-0.5">
        @for ($i = 1; $i <= 5; $i++)
            @php $fill = max(0, min(1, $rating - $i + 1)); @endphp
            <span class="relative inline-block {{ $box }}">
                {{-- bintang kosong --}}
                <svg class="absolute inset-0 {{ $box }} text-ink-200" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M10 1.6l2.6 5.3 5.8.8-4.2 4.1 1 5.8-5.2-2.8-5.2 2.8 1-5.8L1.6 7.7l5.8-.8L10 1.6z"/>
                </svg>
                {{-- bintang terisi, dipotong sesuai pecahan rating --}}
                <span class="absolute inset-0 overflow-hidden" style="width: {{ $fill * 100 }}%">
                    <svg class="{{ $box }} text-amber-400" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10 1.6l2.6 5.3 5.8.8-4.2 4.1 1 5.8-5.2-2.8-5.2 2.8 1-5.8L1.6 7.7l5.8-.8L10 1.6z"/>
                    </svg>
                </span>
            </span>
        @endfor
    </div>

    @if ($showValue)
        <span class="{{ $text }} font-semibold text-ink-800">{{ number_format($rating, 1, ',', '.') }}</span>
    @endif

    @if (! is_null($count))
        <span class="{{ $text }} text-ink-400">({{ $count }})</span>
    @endif
</div>
