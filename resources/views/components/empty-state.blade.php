@props(['title' => 'Belum ada data', 'description' => null, 'icon' => 'squares'])

<div {{ $attributes->merge(['class' => 'ms-card flex flex-col items-center px-6 py-14 text-center']) }}>
    <div class="ms-gradient-soft flex h-16 w-16 items-center justify-center rounded-2xl">
        <x-icon :name="$icon" class="h-8 w-8 text-brand-600" />
    </div>
    <p class="mt-4 text-base font-extrabold text-ink-900">{{ $title }}</p>
    @if ($description)
        <p class="mt-1.5 max-w-md text-sm text-ink-500">{{ $description }}</p>
    @endif
    @if (! $slot->isEmpty())
        <div class="mt-5">{{ $slot }}</div>
    @endif
</div>
