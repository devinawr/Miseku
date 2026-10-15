<div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
    <x-page-title :title="'Komunitas ' . $fandom->fandom_name" :subtitle="$members . ' anggota · ' . $topics->count() . ' topik'">
        <a href="{{ route('catalog', ['fandom' => $fandom->id]) }}" wire:navigate class="ms-btn-ghost">Merch {{ $fandom->fandom_name }}</a>
        @unless ($isMember)<button type="button" wire:click="join" class="ms-btn-primary">Gabung komunitas</button>@endunless
    </x-page-title>

    @if ($isMember)
        <form wire:submit="createTopic" class="ms-card mb-6 space-y-3 p-5" x-data="{ open: false }">
            <button type="button" class="font-extrabold text-ink-900" @click="open = !open">+ Buat topik baru</button>
            <div x-show="open" x-cloak class="space-y-3">
                <input type="text" wire:model="title" class="ms-input" placeholder="Judul topik">
                @error('title')<p class="ms-error">{{ $message }}</p>@enderror
                <textarea wire:model="body" rows="4" class="ms-input" placeholder="Tulis pembuka diskusi…"></textarea>
                @error('body')<p class="ms-error">{{ $message }}</p>@enderror
                <x-photo-previews :photos="$photos" />
                <div class="flex items-center gap-2">
                    <x-photo-picker class="ring-1 ring-ink-200" />
                    <span class="text-xs text-ink-500">Lampirkan foto (maks. 4)</span>
                    <button type="submit" class="ms-btn-primary ml-auto" wire:loading.attr="disabled" wire:target="photos,createTopic">Posting topik</button>
                </div>
            </div>
        </form>
    @else
        <p class="ms-card mb-6 p-4 text-sm text-ink-600">Gabung komunitas untuk membuat topik dan membalas diskusi.</p>
    @endif

    <div class="ms-card divide-y divide-ink-100 overflow-hidden">
        @forelse ($topics as $t)
            <a href="{{ route('community.topic', $t) }}" wire:navigate wire:key="t-{{ $t->id }}" class="flex items-center gap-3 px-5 py-4 hover:bg-brand-50">
                <x-avatar :user="$t->creator" class="h-10 w-10 shrink-0 text-sm" />
                <div class="min-w-0 flex-1">
                    <p class="truncate font-bold text-ink-900">{{ $t->title }}</p>
                    <p class="text-xs text-ink-500">oleh {{ $t->creator?->name }} · {{ $t->created_at->translatedFormat('d M Y') }}</p>
                </div>
                <span class="ms-chip ms-chip-muted"><x-icon name="chat" class="h-3.5 w-3.5" /> {{ $t->messages_count }}</span>
            </a>
        @empty
            <p class="px-5 py-10 text-center text-sm text-ink-500">Belum ada topik. Jadi yang pertama!</p>
        @endforelse
    </div>
</div>
