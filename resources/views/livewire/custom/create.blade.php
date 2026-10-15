<div class="mx-auto max-w-2xl px-4 py-8 sm:px-6">
    <x-page-title title="Ajukan custom merch" subtitle="Jelaskan sedetail mungkin supaya artis bisa memberi harga & estimasi yang tepat." />
    <form wire:submit="save" class="ms-card space-y-4 p-6">
        <label class="block">
            <span class="ms-label">Jenis merchandise</span>
            <select wire:model="merchandise_type_id" class="ms-select">
                <option value="">— pilih —</option>
                @foreach ($types as $t)<option value="{{ $t->id }}">{{ $t->type_name }}</option>@endforeach
            </select>
            @error('merchandise_type_id')<p class="ms-error">{{ $message }}</p>@enderror
        </label>
        <label class="block">
            <span class="ms-label">Karakter / desain</span>
            <input type="text" wire:model="design_character" class="ms-input" placeholder="mis. Gojo Satoru versi chibi">
            @error('design_character')<p class="ms-error">{{ $message }}</p>@enderror
        </label>
        <div class="grid gap-4 sm:grid-cols-2">
            <label class="block">
                <span class="ms-label">Jumlah (pcs)</span>
                <input type="number" min="1" wire:model="quantity" class="ms-input">
                @error('quantity')<p class="ms-error">{{ $message }}</p>@enderror
            </label>
            <label class="block">
                <span class="ms-label">Budget total (Rp)</span>
                <input type="number" min="10000" step="1000" wire:model="budget_price" class="ms-input">
                @error('budget_price')<p class="ms-error">{{ $message }}</p>@enderror
            </label>
        </div>
        <label class="block">
            <span class="ms-label">Spesifikasi</span>
            <textarea wire:model="specification" rows="5" class="ms-input" placeholder="Ukuran, bahan, warna, pose, tenggat yang diinginkan, dll."></textarea>
            @error('specification')<p class="ms-error">{{ $message }}</p>@enderror
        </label>
        <div>
            <span class="ms-label">Gambar referensi (opsional, maks 5)</span>
            <input type="file" wire:model="references" multiple accept="image/*" class="block w-full text-sm">
            <div wire:loading wire:target="references" class="ms-hint">Mengunggah…</div>
            @error('references.*')<p class="ms-error">{{ $message }}</p>@enderror
            @if ($references)
                <div class="mt-2 flex flex-wrap gap-2">@foreach ($references as $f)<img src="{{ $f->temporaryUrl() }}" class="h-16 w-16 rounded-lg object-cover" alt="">@endforeach</div>
            @endif
        </div>
        <button type="submit" class="ms-btn-primary w-full">Kirim permintaan</button>
    </form>
</div>
