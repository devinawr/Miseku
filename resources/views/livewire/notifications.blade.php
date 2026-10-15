<div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
    <x-page-title title="Notifikasi" subtitle="Update pesanan, pembayaran, pelunasan, pick-up, pembatalan, custom, voting, dan penarikan dana. Salinan dikirim ke email.">
        <button type="button" wire:click="readAll" class="ms-btn-ghost"><x-icon name="check" class="h-4.5 w-4.5" /> Tandai semua dibaca</button>
    </x-page-title>

    @if ($newProducts->isNotEmpty())
        <section class="mb-8">
            <h2 class="ms-section-title mb-3 flex items-center gap-2"><x-icon name="sparkles" class="h-5 w-5 text-accent-500" /> Produk baru dari fandom favoritmu</h2>
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                @foreach ($newProducts->take(4) as $p)
                    <x-product-card :product="$p" wire:key="np-{{ $p->id }}" />
                @endforeach
            </div>
        </section>
    @endif

    <div class="ms-scroll-x mb-4 flex gap-1.5">
        @foreach (\App\Livewire\Notifications::TYPES as $k => $label)
            <button type="button" wire:click="$set('jenis', '{{ $k }}')" @class(['ms-tab', 'ms-tab-active' => $jenis === $k])>{{ $label }}</button>
        @endforeach
    </div>

    <div class="ms-card divide-y divide-ink-100 overflow-hidden">
        @forelse ($notes as $n)
            <button type="button" wire:click="open({{ $n->id }})" wire:key="n-{{ $n->id }}" class="flex w-full gap-3 px-5 py-4 text-left hover:bg-brand-50 {{ $n->is_read ? '' : 'bg-brand-50/40' }}">
                <span class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full {{ $n->is_read ? 'bg-ink-200' : 'bg-accent-500' }}"></span>
                <span class="min-w-0 flex-1">
                    <span class="flex flex-wrap items-center gap-2">
                        <span class="font-bold text-ink-900">{{ $n->title }}</span>
                        <span class="ms-chip ms-chip-muted">{{ \App\Livewire\Notifications::TYPES[$n->notification_type] ?? $n->notification_type }}</span>
                    </span>
                    <span class="mt-0.5 block text-sm text-ink-600">{{ $n->body }}</span>
                    <span class="mt-1 block text-xs text-ink-400">{{ $n->created_at->translatedFormat('d M Y H:i') }}</span>
                </span>
            </button>
        @empty
            <p class="px-5 py-10 text-center text-sm text-ink-500">Belum ada notifikasi.</p>
        @endforelse
    </div>
    <div class="mt-6">{{ $notes->links('components.pagination') }}</div>
</div>
