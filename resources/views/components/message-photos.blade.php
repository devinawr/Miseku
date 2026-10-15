{{-- Foto terlampir di sebuah pesan. Klik → lihat besar (lightbox sederhana). --}}
@props(['images'])
@if ($images && $images->isNotEmpty())
    <div x-data="{ zoom: null }" {{ $attributes->merge(['class' => 'mt-1.5 grid gap-1.5 ' . ($images->count() === 1 ? 'grid-cols-1 justify-items-start' : 'grid-cols-2 max-w-sm')]) }}>
        @foreach ($images as $img)
            <button type="button" @click="zoom = @js($img->url)" class="block overflow-hidden rounded-xl">
                <img src="{{ $img->url }}" alt="Foto lampiran" loading="lazy" class="{{ $images->count() === 1 ? 'h-48 w-64 max-w-full' : 'h-28 w-full' }} object-cover">
            </button>
        @endforeach
        <template x-teleport="body">
            <div x-show="zoom" x-cloak x-transition.opacity @click="zoom = null" @keydown.escape.window="zoom = null"
                 class="fixed inset-0 z-[60] flex items-center justify-center bg-black/80 p-4">
                <img :src="zoom" alt="" class="max-h-[90vh] max-w-[95vw] rounded-xl shadow-2xl">
                <span class="absolute top-4 right-4 rounded-full bg-white/15 p-2 text-white"><x-icon name="x" class="h-6 w-6" /></span>
            </div>
        </template>
    </div>
@endif