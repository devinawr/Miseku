<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <x-page-title title="Komunitas fandom" subtitle="Ngobrol bareng sesama fans, bagikan rekomendasi merch & artis favorit.">
        <input type="search" wire:model.live.debounce.300ms="q" class="ms-input w-64" placeholder="Cari fandom…">
    </x-page-title>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($fandoms as $f)
            @php $joined = in_array($f->id, $mine); @endphp
            <div class="ms-card flex flex-col p-5" wire:key="f-{{ $f->id }}">
                <a href="{{ route('community.fandom', $f) }}" wire:navigate class="text-lg font-extrabold text-ink-900 hover:text-brand-700">{{ $f->fandom_name }}</a>
                <p class="mt-1 text-sm text-ink-500">{{ $f->members_count }} anggota · {{ $f->topics_count }} topik</p>
                <div class="mt-4 flex gap-2">
                    <a href="{{ route('community.fandom', $f) }}" wire:navigate class="ms-btn-soft flex-1">Lihat topik</a>
                    <button type="button" wire:click="toggle({{ $f->id }})" class="{{ $joined ? 'ms-btn-ghost' : 'ms-btn-primary' }} flex-1">{{ $joined ? 'Keluar' : 'Gabung' }}</button>
                </div>
            </div>
        @endforeach
    </div>
</div>
