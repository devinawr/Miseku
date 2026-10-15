{{-- Pratinjau foto yang akan dikirim + status unggah + pesan error. --}}
@props(['photos' => []])
<div wire:loading.flex wire:target="photos" class="mb-2 items-center gap-2 text-xs font-semibold text-ink-500">
    <span class="h-3 w-3 animate-spin rounded-full border-2 border-brand-500 border-t-transparent"></span> Mengunggah foto…
</div>
@if (count($photos))
    <div class="mb-2 flex flex-wrap gap-2">
        @foreach ($photos as $i => $ph)
            @php $preview = (is_object($ph) && method_exists($ph, 'isPreviewable') && $ph->isPreviewable()) ? $ph->temporaryUrl() : null; @endphp
            <div class="relative h-16 w-16 overflow-hidden rounded-xl ring-1 ring-ink-200" wire:key="pp-{{ $i }}">
                @if ($preview)
                    <img src="{{ $preview }}" alt="" class="h-full w-full object-cover">
                @else
                    <div class="flex h-full items-center justify-center bg-ink-100 text-[10px] text-ink-500">foto</div>
                @endif
                <button type="button" wire:click="removePhoto({{ $i }})" class="absolute top-0.5 right-0.5 rounded-full bg-black/60 p-0.5 text-white" title="Batal lampirkan">
                    <x-icon name="x" class="h-3.5 w-3.5" />
                </button>
            </div>
        @endforeach
    </div>
@endif
@error('photos')<p class="ms-error mb-2">{{ $message }}</p>@enderror
@foreach ($errors->get('photos.*') as $msgs)
    <p class="ms-error mb-2">{{ $msgs[0] }}</p>
@endforeach