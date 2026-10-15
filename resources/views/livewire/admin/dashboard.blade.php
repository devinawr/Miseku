<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <x-admin-nav />
    <x-page-title title="Ringkasan admin" subtitle="Pantau transaksi, dana escrow, dan antrean tugas admin." />

    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        @foreach ($stats as [$label, $value, $icon])
            <div class="ms-card flex items-center gap-3 p-4">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-50 text-brand-700"><x-icon :name="$icon" class="h-5 w-5" /></span>
                <div class="min-w-0"><p class="truncate text-lg font-extrabold text-ink-900">{{ is_numeric($value) ? number_format($value, 0, ',', '.') : $value }}</p><p class="text-xs text-ink-500">{{ $label }}</p></div>
            </div>
        @endforeach
    </div>

    <div class="mt-6 grid items-start gap-6 lg:grid-cols-12">
        <section class="ms-card p-5 lg:col-span-5">
            <h3 class="font-extrabold text-ink-900">Antrean tugas</h3>
            <ul class="mt-3 space-y-2">
                @foreach ($tasks as [$label, $n, $href])
                    <li><a href="{{ $href }}" wire:navigate class="flex items-center justify-between rounded-xl border border-ink-200 px-4 py-3 text-sm font-bold hover:border-brand-300">{{ $label }} <span class="ms-chip {{ $n ? 'bg-accent-100 text-accent-700' : 'ms-chip-muted' }}">{{ $n }}</span></a></li>
                @endforeach
            </ul>
        </section>
        <section class="ms-card p-5 lg:col-span-7">
            <h3 class="font-extrabold text-ink-900">Aktivitas notifikasi terbaru</h3>
            <ul class="mt-3 divide-y divide-ink-100 text-sm">
                @foreach ($activity as $a)
                    <li class="py-2"><b>{{ $a->title }}</b> <span class="text-xs text-ink-400">→ {{ $a->user?->name }} · {{ $a->created_at->diffForHumans() }}</span><p class="line-clamp-1 text-xs text-ink-500">{{ $a->body }}</p></li>
                @endforeach
            </ul>
        </section>
    </div>
</div>
