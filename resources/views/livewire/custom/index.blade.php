<div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
    <x-page-title title="Custom merch" subtitle="Ajukan desain/merch custom, terima penawaran dari beberapa artis, pilih yang paling cocok.">
        <a href="{{ route('custom.create') }}" wire:navigate class="ms-btn-primary"><x-icon name="plus" class="h-4.5 w-4.5" /> Ajukan custom</a>
    </x-page-title>

    <div class="space-y-3">
        @forelse ($requests as $r)
            <a href="{{ route('custom.show', $r) }}" wire:navigate wire:key="cr-{{ $r->id }}" class="ms-card flex flex-wrap items-center gap-3 p-4 hover:border-brand-300">
                <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-brand-50 text-brand-600"><x-icon name="sparkles" class="h-6 w-6" /></span>
                <div class="min-w-0 flex-1">
                    <p class="font-bold text-ink-900">{{ $r->design_character }} · {{ $r->merchandiseType?->type_name }}</p>
                    <p class="text-sm text-ink-500">{{ $r->quantity }} pcs · budget <x-rupiah :value="$r->budget_price" /> · {{ $r->created_at->translatedFormat('d M Y') }}</p>
                </div>
                @if ($r->offers_count)<span class="ms-chip ms-chip-po">{{ $r->offers_count }} penawaran baru</span>@endif
                <span class="ms-chip ms-chip-muted">{{ $r->status_label }}</span>
            </a>
        @empty
            <x-empty-state icon="sparkles" title="Belum ada permintaan custom" description="Punya ide merch karakter favorit yang belum dijual? Ajukan custom dan artis akan memberi penawaran.">
                <a href="{{ route('custom.create') }}" wire:navigate class="ms-btn-primary">Ajukan custom</a>
            </x-empty-state>
        @endforelse
    </div>
</div>
