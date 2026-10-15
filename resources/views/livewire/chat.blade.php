<div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
    <x-page-title title="Chat" subtitle="Tanya stok, diskusi custom, atau koordinasi pesanan langsung dengan artis/pembeli." />

    <div class="ms-card grid min-h-[32rem] overflow-hidden md:grid-cols-[18rem_1fr]">
        {{-- Daftar percakapan --}}
        <aside class="border-b border-ink-100 md:border-r md:border-b-0 {{ $partner ? 'hidden md:block' : '' }}">
            <ul class="max-h-[36rem] divide-y divide-ink-100 overflow-y-auto">
                @if ($partner && ! $conversations->firstWhere('user.id', $partner->id))
                    <li class="bg-brand-50 px-4 py-3">
                        <p class="font-bold text-ink-900">{{ $partner->artistProfile?->shop_name ?? $partner->name }}</p>
                        <p class="text-xs text-ink-500">Percakapan baru</p>
                    </li>
                @endif
                @forelse ($conversations as $c)
                    <li wire:key="cv-{{ $c['user']->id }}">
                        <button type="button" wire:click="select({{ $c['user']->id }})" class="flex w-full gap-3 px-4 py-3 text-left hover:bg-brand-50 {{ $partner?->id === $c['user']->id ? 'bg-brand-50' : '' }}">
                            <x-avatar :user="$c['user']" class="h-10 w-10 shrink-0 text-sm" />
                            <span class="min-w-0">
                                <span class="block truncate font-bold text-ink-900">{{ $c['user']->artistProfile?->shop_name ?? $c['user']->name }}</span>
                                <span class="block truncate text-xs text-ink-500">{{ $c['last']->sender_id === $me->id ? 'Kamu: ' : '' }}{{ $c['last']->message_body !== '' ? $c['last']->message_body : '📷 Foto' }}</span>
                            </span>
                        </button>
                    </li>
                @empty
                    @unless ($partner)<li class="px-4 py-10 text-center text-sm text-ink-500">Belum ada percakapan. Mulai dari tombol "Chat artis" di halaman produk/toko/pesanan.</li>@endunless
                @endforelse
            </ul>
        </aside>

        {{-- Thread --}}
        <section class="flex flex-col {{ $partner ? '' : 'hidden md:flex' }}">
            @if ($partner)
                <header class="flex items-center gap-3 border-b border-ink-100 px-5 py-3">
                    <button type="button" wire:click="$set('partnerId', null)" class="ms-icon-btn md:hidden"><x-icon name="chevron-left" class="h-5 w-5" /></button>
                    <x-avatar :user="$partner" class="h-9 w-9 text-sm" />
                    <div>
                        <p class="font-bold text-ink-900">{{ $partner->artistProfile?->shop_name ?? $partner->name }}</p>
                        <p class="text-xs text-ink-500">{{ ['admin' => 'Admin', 'artist' => 'Artis', 'buyer' => 'Pembeli'][$partner->role] }}</p>
                    </div>
                </header>
                <div class="flex-1 space-y-2 overflow-y-auto bg-ink-50/50 px-5 py-4" style="max-height: 28rem" wire:poll.8s
                     x-data x-init="$el.scrollTop = $el.scrollHeight" x-effect="$el.scrollTop = $el.scrollHeight">
                    @forelse ($thread as $m)
                        @php $own = $m->sender_id === $me->id; @endphp
                        <div class="flex {{ $own ? 'justify-end' : 'justify-start' }}" wire:key="m-{{ $m->id }}">
                            <div class="max-w-[80%] rounded-2xl px-4 py-2 text-sm {{ $own ? 'bg-brand-600 text-white' : 'bg-white text-ink-800 ring-1 ring-ink-200' }}">
                                @if ($m->product || $m->order || $m->customRequest)
                                    <p class="mb-1 text-[11px] font-bold {{ $own ? 'text-white/80' : 'text-brand-700' }}">
                                        @if ($m->product) 🛍 {{ $m->product->product_name }} @endif
                                        @if ($m->order) 📦 {{ $m->order->order_code }} @endif
                                        @if ($m->customRequest) ✏️ Custom: {{ $m->customRequest->design_character }} @endif
                                    </p>
                                @endif
                                <x-message-photos :images="$m->images" />
                                @if ($m->message_body !== '')<p class="whitespace-pre-line {{ $m->images->isNotEmpty() ? 'mt-1.5' : '' }}">{{ $m->message_body }}</p>@endif
                                <p class="mt-0.5 text-right text-[10px] {{ $own ? 'text-white/70' : 'text-ink-400' }}">{{ $m->sent_at?->translatedFormat('d M H:i') }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="py-10 text-center text-sm text-ink-500">Belum ada pesan. Sapa duluan 👋</p>
                    @endforelse
                </div>
                <form wire:submit="send" class="border-t border-ink-100 p-3">
                    @if ($ctxProduct || $ctxOrder || $ctxCustom)
                        <div class="mb-2 flex items-center gap-2 rounded-xl bg-brand-50 px-3 py-2 text-xs font-bold text-brand-700">
                            Tentang: {{ $ctxProduct?->product_name ?? $ctxOrder?->order_code ?? ('Custom ' . $ctxCustom?->design_character) }}
                            <button type="button" wire:click="clearContext" class="ml-auto"><x-icon name="x" class="h-4 w-4" /></button>
                        </div>
                    @endif
                    <x-photo-previews :photos="$photos" />
                    <div class="flex items-center gap-2">
                        <x-photo-picker />
                        <input type="text" wire:model="body" class="ms-input flex-1" placeholder="Tulis pesan…" autocomplete="off">
                        <button type="submit" class="ms-btn-primary" wire:loading.attr="disabled" wire:target="photos,send"><x-icon name="send" class="h-5 w-5" /></button>
                    </div>
                    @error('body')<p class="ms-error">{{ $message }}</p>@enderror
                </form>
            @else
                <div class="flex flex-1 items-center justify-center p-10 text-center text-sm text-ink-500">Pilih percakapan di kiri.</div>
            @endif
        </section>
    </div>
</div>
