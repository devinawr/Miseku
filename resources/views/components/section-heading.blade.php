@props(['title', 'subtitle' => null, 'href' => null, 'linkLabel' => 'Lihat semua', 'icon' => null])

<div {{ $attributes->merge(['class' => 'mb-5 flex items-end justify-between gap-4']) }}>
    <div>
        <h2 class="ms-section-title flex items-center gap-2">
            @if ($icon)
                <span class="ms-gradient inline-flex h-8 w-8 items-center justify-center rounded-lg text-white">
                    <x-icon :name="$icon" class="h-4.5 w-4.5" stroke="2" />
                </span>
            @endif
            {{ $title }}
        </h2>
        @if ($subtitle)
            <p class="mt-1 text-sm text-ink-500">{{ $subtitle }}</p>
        @endif
    </div>
    @if ($href)
        <a href="{{ $href }}" wire:navigate class="ms-link shrink-0">
            {{ $linkLabel }} <x-icon name="arrow-right" class="h-4 w-4" stroke="2.2" />
        </a>
    @endif
</div>
