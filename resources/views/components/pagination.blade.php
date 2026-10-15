{{-- Pagination Livewire custom: [Sebelumnya] ...nomor halaman... [Berikutnya] --}}
@if ($paginator->hasPages())
    @php
        $pageName = $paginator->getPageName();
        $btn = 'inline-flex h-10 items-center justify-center gap-1 rounded-xl border px-4 text-sm font-bold transition';
        $on  = 'border-ink-300 bg-white text-ink-800 hover:border-brand-400 hover:text-brand-700';
        $off = 'border-ink-200 bg-ink-50 text-ink-300 cursor-not-allowed';
        $num = 'inline-flex h-10 min-w-10 items-center justify-center rounded-xl px-3 text-sm font-bold transition';
        $top = "window.scrollTo({ top: 0, behavior: 'smooth' })";
    @endphp
    <nav role="navigation" aria-label="Navigasi halaman" class="flex items-center justify-between gap-3">
        {{-- Sebelumnya --}}
        @if ($paginator->onFirstPage())
            <span class="{{ $btn }} {{ $off }}">« Sebelumnya</span>
        @else
            <button type="button" wire:click="previousPage('{{ $pageName }}')" wire:loading.attr="disabled"
                    x-on:click="{{ $top }}" class="{{ $btn }} {{ $on }}">« Sebelumnya</button>
        @endif

        {{-- HP: cukup info halaman --}}
        <span class="text-sm text-ink-500 sm:hidden">{{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>

        {{-- Desktop: nomor halaman --}}
        <div class="hidden items-center gap-1.5 sm:flex">
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="{{ $num }} text-ink-400">{{ $element }}</span>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page" class="{{ $num }} ms-gradient text-white">{{ $page }}</span>
                        @else
                            <button type="button" wire:click="gotoPage({{ $page }}, '{{ $pageName }}')"
                                    x-on:click="{{ $top }}"
                                    class="{{ $num }} text-ink-700 hover:bg-brand-50 hover:text-brand-700">{{ $page }}</button>
                        @endif
                    @endforeach
                @endif
            @endforeach
        </div>

        {{-- Berikutnya --}}
        @if ($paginator->hasMorePages())
            <button type="button" wire:click="nextPage('{{ $pageName }}')" wire:loading.attr="disabled"
                    x-on:click="{{ $top }}" class="{{ $btn }} {{ $on }}">Berikutnya »</button>
        @else
            <span class="{{ $btn }} {{ $off }}">Berikutnya »</span>
        @endif
    </nav>
@endif
