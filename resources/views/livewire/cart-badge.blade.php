<a href="{{ route('cart') }}" wire:navigate class="ms-icon-btn" title="Keranjang">
    <x-icon name="cart" class="h-6 w-6" />
    @if ($count > 0)
        <span class="absolute -top-0.5 -right-0.5 flex h-5 min-w-5 items-center justify-center rounded-full bg-accent-500 px-1 text-[10px] font-extrabold text-white ring-2 ring-white">
            {{ $count > 99 ? '99+' : $count }}
        </span>
    @endif
</a>
