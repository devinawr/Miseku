<div x-data="{ open: false }" @keydown.escape="open = false" class="relative" wire:poll.30s>
    <button type="button" @click="open = !open" class="ms-icon-btn relative" title="Notifikasi">
        <x-icon name="bell" class="h-6 w-6" />
        @if ($unread)
            <span class="absolute -top-0.5 -right-0.5 flex h-5 min-w-5 items-center justify-center rounded-full bg-accent-500 px-1 text-[10px] font-extrabold text-white">{{ $unread > 99 ? '99+' : $unread }}</span>
        @endif
    </button>
    <div x-show="open" x-cloak @click.outside="open = false" x-transition.origin.top.right
         class="absolute right-0 z-50 mt-2 w-80 overflow-hidden rounded-2xl border border-ink-200 bg-white shadow-[var(--shadow-lift)]">
        <div class="flex items-center justify-between border-b border-ink-100 px-4 py-3">
            <p class="text-sm font-extrabold text-ink-900">Notifikasi</p>
            @if ($unread)<button type="button" wire:click="readAll" class="text-xs font-bold text-brand-700 hover:underline">Tandai semua dibaca</button>@endif
        </div>
        <ul class="max-h-96 divide-y divide-ink-100 overflow-y-auto">
            @forelse ($latest as $n)
                <li wire:key="nb-{{ $n->id }}">
                    <button type="button" wire:click="open({{ $n->id }})" class="block w-full px-4 py-3 text-left hover:bg-brand-50 {{ $n->is_read ? '' : 'bg-brand-50/50' }}">
                        <p class="flex items-center gap-2 text-sm font-bold text-ink-900">@unless ($n->is_read)<span class="h-2 w-2 shrink-0 rounded-full bg-accent-500"></span>@endunless {{ $n->title }}</p>
                        <p class="line-clamp-2 text-xs text-ink-500">{{ $n->body }}</p>
                        <p class="mt-0.5 text-[11px] text-ink-400">{{ $n->created_at->diffForHumans() }}</p>
                    </button>
                </li>
            @empty
                <li class="px-4 py-6 text-center text-sm text-ink-500">Belum ada notifikasi.</li>
            @endforelse
        </ul>
        <a href="{{ route('notifications') }}" wire:navigate class="block border-t border-ink-100 px-4 py-2.5 text-center text-sm font-bold text-brand-700 hover:bg-brand-50">Lihat semua</a>
    </div>
</div>
