<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <x-studio-nav />
    <x-page-title title="Voting desain" subtitle="Ajak fans memilih desain. Setelah selesai, buka PO dari desain pemenang — pemilih otomatis dikabari.">
        <button type="button" wire:click="$toggle('creating')" class="ms-btn-primary"><x-icon name="plus" class="h-4.5 w-4.5" /> Buat voting</button>
    </x-page-title>

    @if ($creating)
        <form wire:submit="save" class="ms-card mb-6 space-y-4 p-6">
            <div class="grid gap-4 md:grid-cols-2">
                <label class="block md:col-span-2"><span class="ms-label">Judul voting</span><input type="text" wire:model="poll_title" class="ms-input">@error('poll_title')<p class="ms-error">{{ $message }}</p>@enderror</label>
                <label class="block md:col-span-2"><span class="ms-label">Deskripsi</span><textarea wire:model="description" rows="2" class="ms-input"></textarea></label>
                <label class="block"><span class="ms-label">Mulai</span><input type="datetime-local" wire:model="start_date" class="ms-input"></label>
                <label class="block"><span class="ms-label">Berakhir</span><input type="datetime-local" wire:model="end_date" class="ms-input">@error('end_date')<p class="ms-error">{{ $message }}</p>@enderror</label>
            </div>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($options as $i => $op)
                    <div class="rounded-xl border border-ink-200 p-3" wire:key="opt-{{ $i }}">
                        <div class="flex items-center justify-between"><span class="ms-label">Opsi {{ $i + 1 }}</span>
                            @if (count($options) > 2)<button type="button" wire:click="removeOption({{ $i }})" class="text-red-600"><x-icon name="x" class="h-4 w-4" /></button>@endif
                        </div>
                        @if (isset($images[$i]))<img src="{{ $images[$i]->temporaryUrl() }}" class="mb-2 aspect-square w-full rounded-lg object-cover" alt="">@endif
                        <input type="file" wire:model="images.{{ $i }}" accept="image/*" class="block w-full text-xs">
                        @error('images.' . $i)<p class="ms-error">{{ $message }}</p>@enderror
                        <input type="text" wire:model="options.{{ $i }}.description" class="ms-input mt-2" placeholder="Keterangan desain">
                        @error('options.' . $i . '.description')<p class="ms-error">{{ $message }}</p>@enderror
                    </div>
                @endforeach
            </div>
            @error('images')<p class="ms-error">{{ $message }}</p>@enderror
            <div class="flex flex-wrap gap-2">
                @if (count($options) < 6)<button type="button" wire:click="addOption" class="ms-btn-ghost">+ Tambah opsi</button>@endif
                <button type="submit" class="ms-btn-primary">Simpan & mulai voting</button>
            </div>
        </form>
    @endif

    <div class="space-y-4">
        @forelse ($polls as $poll)
            @php $winner = $poll->poll_status === 'completed' ? $poll->options->sortBy([['votes_count', 'desc'], ['id', 'asc']])->first() : null; @endphp
            <div class="ms-card p-5" wire:key="sp-{{ $poll->id }}">
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('polls.show', $poll) }}" wire:navigate class="font-extrabold text-ink-900 hover:text-brand-700">{{ $poll->poll_title }}</a>
                    <span class="ms-chip ms-chip-po">{{ $poll->status_label }}</span>
                    <span class="text-sm text-ink-500">{{ $poll->votes_count }} suara · s.d. {{ $poll->end_date->translatedFormat('d M Y H:i') }}</span>
                    <div class="ml-auto flex gap-2">
                        @if ($poll->poll_status === 'running')
                            <button type="button" wire:click="finish({{ $poll->id }})" wire:confirm="Akhiri voting sekarang?" class="ms-btn-soft">Akhiri sekarang</button>
                            <button type="button" wire:click="cancel({{ $poll->id }})" wire:confirm="Batalkan voting?" class="ms-btn-ghost">Batalkan</button>
                        @endif
                    </div>
                </div>
                <div class="mt-3 grid grid-cols-3 gap-3 sm:grid-cols-6">
                    @foreach ($poll->options as $op)
                        <div class="text-center {{ $winner && $winner->id === $op->id ? 'rounded-xl ring-2 ring-pop-400' : '' }}">
                            <img src="{{ $op->design_image }}" class="aspect-square w-full rounded-xl object-cover" alt="">
                            <p class="mt-1 truncate text-xs font-bold">{{ $op->description }}</p>
                            <p class="text-xs text-ink-500">{{ $op->votes_count }} suara</p>
                        </div>
                    @endforeach
                </div>
                @if ($winner)
                    <div class="mt-3">
                        @if ($winner->product)
                            <a href="{{ route('studio.product.edit', $winner->product) }}" wire:navigate class="ms-btn-soft">PO desain pemenang: {{ $winner->product->product_name }}</a>
                        @else
                            <a href="{{ route('studio.product.create', ['design_option' => $winner->id]) }}" wire:navigate class="ms-btn-primary">Buka PO desain pemenang</a>
                        @endif
                    </div>
                @endif
            </div>
        @empty
            <x-empty-state icon="chart" title="Belum ada voting" description="Buat voting untuk mengukur minat fans sebelum produksi." />
        @endforelse
    </div>
</div>
