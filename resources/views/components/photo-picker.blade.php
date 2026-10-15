{{-- Tombol lampirkan foto. Pakai di dalam komponen Livewire yang memakai HasPhotoAttachments. --}}
@props(['max' => 4])
<label {{ $attributes->merge(['class' => 'ms-icon-btn shrink-0 cursor-pointer']) }} title="Lampirkan foto (maks. {{ $max }})">
    <x-icon name="photo" class="h-5.5 w-5.5" />
    <input type="file" wire:model="photos" multiple accept="image/jpeg,image/png,image/webp" class="hidden">
</label>