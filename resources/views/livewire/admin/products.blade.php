<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <x-admin-nav />
    <x-page-title title="Moderasi produk">
        <select wire:model.live="status" class="ms-select w-40"><option value="">Semua status</option><option value="active">Tayang</option><option value="inactive">Disembunyikan</option><option value="deleted">Dihapus</option></select>
        <input type="search" wire:model.live.debounce.300ms="q" class="ms-input w-64" placeholder="Cari produk…">
    </x-page-title>
    <div class="ms-card divide-y divide-ink-100">
        @foreach ($products as $p)
            <div class="flex flex-wrap items-center gap-3 px-5 py-3" wire:key="ap-{{ $p->id }}">
                <img src="{{ $p->cover_url }}" class="h-12 w-12 rounded-lg object-cover" alt="">
                <div class="min-w-0 flex-1">
                    <a href="{{ route('product.detail', $p) }}" wire:navigate class="font-bold text-ink-900 hover:text-brand-700">{{ $p->product_name }}</a>
                    <p class="text-xs text-ink-500">{{ $p->artistProfile->shop_name }} · {{ $p->fandom_name }} · {{ $p->merchandiseType?->type_name }} · <x-rupiah :value="$p->display_price" /></p>
                </div>
                <span class="ms-chip ms-chip-muted">{{ ['active' => 'Tayang', 'inactive' => 'Disembunyikan', 'deleted' => 'Dihapus'][$p->product_status] }}</span>
                @if ($p->product_status !== 'deleted')
                    <button type="button" wire:click="toggle({{ $p->id }})" class="ms-btn-ghost !py-1.5 text-xs">{{ $p->product_status === 'active' ? 'Sembunyikan' : 'Tayangkan' }}</button>
                @endif
            </div>
        @endforeach
    </div>
    <div class="mt-5">{{ $products->links('components.pagination') }}</div>
</div>
