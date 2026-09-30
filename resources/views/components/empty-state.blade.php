@props(['title' => 'Belum ada data', 'description' => null])

<div {{ $attributes->merge(['class' => 'ms-card flex flex-col items-center px-6 py-16 text-center']) }}>
    <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-miseku-100">
        <svg class="h-8 w-8 text-miseku-400" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M20.25 7.5 12 12l-8.25-4.5M12 12v9.75m8.25-14.25v9L12 21.75l-8.25-4.5v-9L12 3l8.25 4.5Z" />
        </svg>
    </div>
    <p class="mt-4 text-base font-semibold text-miseku-800">{{ $title }}</p>
    @if ($description)
        <p class="mt-1.5 max-w-md text-sm text-miseku-500">{{ $description }}</p>
    @endif
    @if (! $slot->isEmpty())
        <div class="mt-5">{{ $slot }}</div>
    @endif
</div>
