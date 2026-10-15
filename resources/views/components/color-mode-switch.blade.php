{{-- Pilihan mode terang / gelap / ikuti perangkat (disimpan di perangkat ini). --}}
@props(['size' => 'sm'])
<div x-data="{ m: window.msGetMode ? msGetMode() : 'light' }" x-on:ms-mode.window="m = $event.detail" {{ $attributes->merge(['class' => 'grid grid-cols-3 gap-1 rounded-xl bg-ink-100 p-1']) }}>
    @foreach (['light' => ['Terang', 'sun'], 'dark' => ['Gelap', 'moon'], 'system' => ['Ikuti perangkat', 'device']] as $key => [$label, $icon])
        <button type="button" @click="m = '{{ $key }}'; msSetMode('{{ $key }}')"
                class="flex items-center justify-center gap-1.5 rounded-lg px-2 font-bold transition {{ $size === 'lg' ? 'py-2.5 text-sm' : 'py-1.5 text-xs' }}"
                :class="m === '{{ $key }}' ? 'bg-brand-600 text-white shadow-sm' : 'text-ink-500 hover:text-ink-800'">
            <x-icon :name="$icon" class="h-4 w-4 shrink-0" /> <span class="truncate">{{ $size === 'lg' ? $label : ($key === 'system' ? 'Sistem' : $label) }}</span>
        </button>
    @endforeach
</div>
