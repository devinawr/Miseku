<div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
    <a href="{{ route('community.fandom', $topic->fandom) }}" wire:navigate class="ms-link text-sm"><x-icon name="chevron-left" class="h-4 w-4" /> Komunitas {{ $topic->fandom->fandom_name }}</a>
    <div class="mt-2 mb-6 flex flex-wrap items-start justify-between gap-3">
        <h1 class="text-2xl font-extrabold text-ink-900">{{ $topic->title }}</h1>
        @if ($role === 'moderator' || $topic->creator_id === auth()->id())
            <button type="button" wire:click="deleteTopic" wire:confirm="Hapus topik beserta semua pesannya?" class="ms-btn-ghost text-red-600"><x-icon name="trash" class="h-4.5 w-4.5" /> Hapus topik</button>
        @endif
    </div>

    <div class="space-y-4">
        @foreach ($messages as $m)
            <div class="ms-card p-5" wire:key="tm-{{ $m->id }}">
                @include('partials.topic-message', ['m' => $m, 'role' => $role])
                @if ($m->replies->isNotEmpty())
                    <div class="mt-4 space-y-3 border-l-2 border-brand-100 pl-4">
                        @foreach ($m->replies as $r)
                            <div wire:key="tr-{{ $r->id }}">@include('partials.topic-message', ['m' => $r, 'role' => $role])</div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    @if (auth()->user()?->isAdmin())
        <p class="ms-card mt-6 p-4 text-sm text-brand-800 bg-brand-50"><b>Mode pantau admin.</b> Admin bisa menghapus topik atau pesan yang melanggar, tapi tidak ikut membalas diskusi.</p>
    @elseif ($canPost)
        <form wire:submit="post" class="ms-card mt-6 space-y-3 p-5">
            @if ($replyTo)
                <p class="flex items-center gap-2 text-sm font-bold text-brand-700">Membalas pesan <button type="button" wire:click="$set('replyTo', null)"><x-icon name="x" class="h-4 w-4" /></button></p>
            @endif
            <textarea wire:model="body" rows="3" class="ms-input" placeholder="Tulis balasan…"></textarea>
            @error('body')<p class="ms-error">{{ $message }}</p>@enderror
            <x-photo-previews :photos="$photos" />
            <div class="grid gap-3 sm:grid-cols-2">
                <select wire:model="attachProduct" class="ms-select">
                    <option value="">Lampirkan produk (opsional)</option>
                    @foreach ($products as $p)<option value="{{ $p->id }}">{{ $p->product_name }}</option>@endforeach
                </select>
                <select wire:model="attachArtist" class="ms-select">
                    <option value="">Rekomendasikan artis (opsional)</option>
                    @foreach ($artists as $a)<option value="{{ $a->id }}">{{ $a->shop_name }}</option>@endforeach
                </select>
            </div>
            <div class="flex items-center gap-2">
                <x-photo-picker class="ring-1 ring-ink-200" />
                <span class="text-xs text-ink-500">Lampirkan foto (maks. 4)</span>
                <button type="submit" class="ms-btn-primary ml-auto" wire:loading.attr="disabled" wire:target="photos,post">Kirim</button>
            </div>
        </form>
    @else
        <p class="ms-card mt-6 p-4 text-sm text-ink-600">
            @auth Gabung komunitas <a href="{{ route('community.fandom', $topic->fandom) }}" wire:navigate class="ms-link">{{ $topic->fandom->fandom_name }}</a> untuk ikut berdiskusi.
            @else <a href="{{ route('login') }}" wire:navigate class="ms-link">Masuk</a> untuk ikut berdiskusi. @endauth
        </p>
    @endif
</div>
