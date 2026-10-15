@props(['title', 'subtitle' => null])
<div class="mb-6 flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="text-2xl font-extrabold tracking-tight text-ink-900 sm:text-3xl">{{ $title }}</h1>
        @if ($subtitle)<p class="mt-1 text-sm text-ink-500">{{ $subtitle }}</p>@endif
    </div>
    @if (trim($slot))<div class="flex flex-wrap gap-2">{{ $slot }}</div>@endif
</div>
