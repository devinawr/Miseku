<div class="mt-2 space-y-1">
    <label class="block text-xs font-semibold text-ink-600">Tambah foto (opsional, maks. 4 foto)</label>
    <input type="file" wire:model="{{ $field }}" multiple accept="image/jpeg,image/png,image/webp" class="block w-full text-xs text-ink-600" aria-label="{{ $label }}">
    <p class="text-xs text-ink-500">JPG, PNG, atau WEBP · maks. 4 MB per foto. Pilih semua foto sekaligus.</p>
    <p wire:loading wire:target="{{ $field }}" class="text-xs text-brand-700">Mengunggah foto…</p>
    @if (count($photos))
        <p class="text-xs text-ink-600">{{ count($photos) }} foto dipilih.
            <button type="button" wire:click="$set('{{ $field }}', [])" class="font-bold text-brand-700 underline">Hapus pilihan</button>
        </p>
    @endif
    @error($field)<p class="ms-error">{{ $message }}</p>@enderror
    @error($field . '.*')<p class="ms-error">{{ $message }}</p>@enderror
</div>
