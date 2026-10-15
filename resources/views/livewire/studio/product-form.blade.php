@php
    $isPo   = $availability_type === 'pre_order';
    $total  = count($keptImageIds) + count($photos);
@endphp

<div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
    <x-studio-nav />
    <a href="{{ route('studio.dashboard') }}" wire:navigate class="ms-link text-sm"><x-icon name="chevron-left" class="h-4 w-4" /> Studio artis</a>
    <h1 class="mt-2 text-2xl font-extrabold tracking-tight text-ink-900 sm:text-3xl">{{ $product ? 'Ubah produk' : 'Upload produk baru' }}</h1>
    <p class="mt-1 text-sm text-ink-500">Isi karakter, fandom, dan jenis merch dengan tepat — data ini dipakai sistem rekomendasi untuk mempertemukan produkmu dengan fans yang cocok.</p>

    @if ($opt = $this->designOption)
        <div class="mt-5 flex flex-wrap items-center gap-4 rounded-2xl border border-brand-200 bg-brand-50 p-4">
            <img src="{{ $opt->design_image }}" alt="" class="h-16 w-16 shrink-0 rounded-xl object-cover ring-2 ring-white">
            <div class="min-w-0 flex-1 text-sm">
                <p class="font-extrabold text-brand-800">PO dari desain pemenang voting "{{ $opt->poll->poll_title }}"</p>
                <p class="text-brand-700">Form sudah diatur sebagai pre-order. Setelah disimpan, semua yang ikut voting mendapat notifikasi PO dibuka. Upload foto mockup/desain di bawah.</p>
            </div>
        </div>
    @endif

    <form wire:submit="save" class="mt-6 grid items-start gap-6 lg:grid-cols-12">
        <div class="space-y-6 lg:col-span-8">

            {{-- ===== Foto ===== --}}
            <section class="ms-card p-5 sm:p-6">
                <div class="flex items-baseline justify-between">
                    <h2 class="font-extrabold text-ink-900">Foto produk</h2>
                    <span class="text-xs font-semibold text-ink-500">{{ $total }}/{{ \App\Livewire\Studio\ProductForm::MAX_PHOTOS }}</span>
                </div>
                <p class="ms-hint">JPG/PNG/WEBP, maks. 2 MB per foto. Foto pertama jadi sampul.</p>

                <div class="mt-4 grid grid-cols-3 gap-3 sm:grid-cols-5">
                    @foreach ($this->keptImages as $i => $img)
                        <div class="group relative aspect-square overflow-hidden rounded-xl bg-ink-100 ring-1 ring-ink-200" wire:key="k-{{ $img->id }}">
                            <img src="{{ $img->url }}" alt="" class="h-full w-full object-cover">
                            @if ($i === 0)
                                <span class="ms-gradient absolute bottom-1.5 left-1.5 rounded-md px-1.5 py-0.5 text-[10px] font-bold text-white">Sampul</span>
                            @else
                                <button type="button" wire:click="makeCover({{ $img->id }})" class="absolute bottom-1.5 left-1.5 rounded-md bg-white/90 px-1.5 py-0.5 text-[10px] font-bold text-ink-700 opacity-0 group-hover:opacity-100">Jadikan sampul</button>
                            @endif
                            <button type="button" wire:click="removeKeptImage({{ $img->id }})" class="absolute top-1.5 right-1.5 flex h-6 w-6 items-center justify-center rounded-full bg-ink-900/70 text-white hover:bg-red-600" aria-label="Hapus foto">
                                <x-icon name="x" class="h-3.5 w-3.5" stroke="2.4" />
                            </button>
                        </div>
                    @endforeach

                    @foreach ($photos as $i => $photo)
                        <div class="relative aspect-square overflow-hidden rounded-xl bg-ink-100 ring-1 ring-brand-200" wire:key="n-{{ $i }}-{{ $photo->getFilename() }}">
                            <img src="{{ $photo->temporaryUrl() }}" alt="" class="h-full w-full object-cover">
                            @if (count($keptImageIds) === 0 && $i === 0)
                                <span class="ms-gradient absolute bottom-1.5 left-1.5 rounded-md px-1.5 py-0.5 text-[10px] font-bold text-white">Sampul</span>
                            @else
                                <span class="absolute bottom-1.5 left-1.5 rounded-md bg-brand-600 px-1.5 py-0.5 text-[10px] font-bold text-white">Baru</span>
                            @endif
                            <button type="button" wire:click="removeNewPhoto({{ $i }})" class="absolute top-1.5 right-1.5 flex h-6 w-6 items-center justify-center rounded-full bg-ink-900/70 text-white hover:bg-red-600" aria-label="Hapus foto">
                                <x-icon name="x" class="h-3.5 w-3.5" stroke="2.4" />
                            </button>
                        </div>
                    @endforeach

                    @if ($total < \App\Livewire\Studio\ProductForm::MAX_PHOTOS)
                        <label class="flex aspect-square cursor-pointer flex-col items-center justify-center gap-1 rounded-xl border-2 border-dashed border-brand-300 bg-brand-50/60 text-brand-700 transition hover:border-brand-500 hover:bg-brand-50">
                            <span wire:loading.remove wire:target="newPhotos" class="flex flex-col items-center gap-1">
                                <x-icon name="photo" class="h-6 w-6" />
                                <span class="text-xs font-bold">Tambah foto</span>
                            </span>
                            <span wire:loading wire:target="newPhotos" class="text-xs font-bold">Mengunggah…</span>
                            <input type="file" wire:model="newPhotos" accept="image/jpeg,image/png,image/webp" multiple class="sr-only">
                        </label>
                    @endif
                </div>
                @error('photos') <p class="ms-error">{{ $message }}</p> @enderror
                @error('newPhotos.*') <p class="ms-error">{{ $message }}</p> @enderror
                @error('photos.*') <p class="ms-error">{{ $message }}</p> @enderror
            </section>

            {{-- ===== Info produk ===== --}}
            <section class="ms-card space-y-5 p-5 sm:p-6">
                <h2 class="font-extrabold text-ink-900">Informasi produk</h2>

                <div>
                    <label for="product_name" class="ms-label">Nama produk</label>
                    <input id="product_name" type="text" wire:model.blur="product_name" maxlength="150" class="ms-input" placeholder="cth. Acrylic Stand Furina — Hydro Archon">
                    @error('product_name') <p class="ms-error">{{ $message }}</p> @enderror
                </div>

                <div class="grid gap-5 sm:grid-cols-3">
                    <div>
                        <label for="fandom_id" class="ms-label">Fandom</label>
                        <select id="fandom_id" wire:model.live="fandom_id" class="ms-select">
                            <option value="">Pilih fandom</option>
                            @foreach ($this->fandoms as $f)
                                <option value="{{ $f->id }}">{{ $f->fandom_name }} @if ($f->status === 'pending') (usulan) @endif</option>
                            @endforeach
                        </select>
                        @error('fandom_id') <p class="ms-error">{{ $message }}</p> @enderror
                        <button type="button" wire:click="startProposal('fandom')" class="ms-link mt-1.5 text-xs">+ Fandom belum ada? Usulkan</button>
                    </div>
                    <div>
                        <label for="fandom_character_id" class="ms-label">Karakter</label>
                        <select id="fandom_character_id" wire:model.live="fandom_character_id" class="ms-select" @disabled($fandom_id === '')>
                            <option value="">{{ $fandom_id === '' ? 'Pilih fandom dulu' : 'Pilih karakter' }}</option>
                            @foreach ($this->characters as $c)
                                <option value="{{ $c->id }}">{{ $c->character_name }} @if ($c->status === 'pending') (usulan) @endif</option>
                            @endforeach
                        </select>
                        @error('fandom_character_id') <p class="ms-error">{{ $message }}</p> @enderror
                        <button type="button" wire:click="startProposal('character')" class="ms-link mt-1.5 text-xs">+ Karakter belum ada? Usulkan</button>
                    </div>
                    <div>
                        <label for="merchandise_type_id" class="ms-label">Jenis merchandise</label>
                        <select id="merchandise_type_id" wire:model.live="merchandise_type_id" class="ms-select">
                            <option value="">Pilih jenis</option>
                            @foreach ($this->types as $t)
                                <option value="{{ $t->id }}">{{ $t->type_name }} @if ($t->status === 'pending') (usulan) @endif</option>
                            @endforeach
                        </select>
                        @error('merchandise_type_id') <p class="ms-error">{{ $message }}</p> @enderror
                        <button type="button" wire:click="startProposal('type')" class="ms-link mt-1.5 text-xs">+ Jenis belum ada? Usulkan</button>
                    </div>
                </div>

                @if ($proposing)
                    @php
                        $fandomName = $this->fandoms->firstWhere('id', (int) $fandom_id)?->fandom_name;
                        $titles = [
                            'fandom'    => ['Usulkan fandom baru', 'cth. Wuthering Waves'],
                            'character' => ['Usulkan karakter baru untuk ' . $fandomName, 'cth. Nama karakter'],
                            'type'      => ['Usulkan jenis merchandise baru', 'cth. Shikishi'],
                        ][$proposing];
                    @endphp
                    <div class="rounded-2xl border border-brand-200 bg-brand-50/60 p-4" wire:key="proposal-{{ $proposing }}">
                        <p class="text-sm font-extrabold text-ink-900">{{ $titles[0] }}</p>
                        <p class="ms-hint">Usulan dicek admin dulu. Kamu bisa langsung memakainya, tapi produk baru tampil di katalog setelah usulan disetujui.</p>
                        <div class="mt-3 flex flex-col gap-2 sm:flex-row">
                            <input type="text" wire:model="proposalName" wire:keydown.enter.prevent="submitProposal" maxlength="120" class="ms-input flex-1" placeholder="{{ $titles[1] }}" autofocus>
                            <div class="flex gap-2">
                                <button type="button" wire:click="submitProposal" class="ms-btn-primary">Kirim usulan</button>
                                <button type="button" wire:click="cancelProposal" class="ms-btn-ghost">Batal</button>
                            </div>
                        </div>
                        @error('proposalName') <p class="ms-error">{{ $message }}</p> @enderror
                    </div>
                @endif

                <div>
                    <label for="description" class="ms-label">Deskripsi <span class="font-normal text-ink-400">(opsional)</span></label>
                    <textarea id="description" wire:model.blur="description" rows="5" maxlength="5000" class="ms-input" placeholder="Bahan, finishing, isi paket, catatan pengiriman…"></textarea>
                    @error('description') <p class="ms-error">{{ $message }}</p> @enderror
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="price" class="ms-label">Harga</label>
                        <div class="relative">
                            <span class="pointer-events-none absolute top-1/2 left-3.5 -translate-y-1/2 text-sm font-bold text-ink-500">Rp</span>
                            <input id="price" type="number" inputmode="numeric" min="1000" step="500" wire:model.blur="price" class="ms-input pl-11" placeholder="45000">
                        </div>
                        @error('price') <p class="ms-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="size" class="ms-label">Ukuran <span class="font-normal text-ink-400">(opsional)</span></label>
                        <input id="size" type="text" wire:model.blur="size" maxlength="60" class="ms-input" placeholder="cth. 15 cm, A5, 58 mm">
                        @error('size') <p class="ms-error">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>

            {{-- ===== Ketersediaan ===== --}}
            <section class="ms-card space-y-5 p-5 sm:p-6">
                <h2 class="font-extrabold text-ink-900">Ketersediaan</h2>

                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach (['ready_stock' => ['Ready stock', 'Barang sudah jadi, langsung dikirim.', 'truck'], 'pre_order' => ['Pre-order', 'Produksi setelah periode PO ditutup.', 'clock']] as $val => [$title, $desc, $icon])
                        <label @class([
                            'flex cursor-pointer gap-3 rounded-2xl border-2 p-4 transition',
                            'border-brand-500 bg-brand-50' => $availability_type === $val,
                            'border-ink-200 hover:border-brand-300' => $availability_type !== $val,
                        ])>
                            <input type="radio" wire:model.live="availability_type" value="{{ $val }}" class="sr-only">
                            <span @class(['flex h-10 w-10 shrink-0 items-center justify-center rounded-xl', 'ms-gradient text-white' => $availability_type === $val, 'bg-ink-100 text-ink-500' => $availability_type !== $val])>
                                <x-icon :name="$icon" class="h-5 w-5" />
                            </span>
                            <span>
                                <span class="block font-bold text-ink-900">{{ $title }}</span>
                                <span class="block text-xs text-ink-500">{{ $desc }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>

                @if (! $isPo)
                    <div class="sm:w-1/2">
                        <label for="stock" class="ms-label">Stok</label>
                        <input id="stock" type="number" min="0" wire:model.blur="stock" class="ms-input">
                        @error('stock') <p class="ms-error">{{ $message }}</p> @enderror
                    </div>
                @else
                    <div class="grid gap-5 rounded-2xl bg-ink-50 p-4 sm:grid-cols-2 sm:p-5">
                        <div class="sm:col-span-2">
                            <p class="ms-label">Skema pembayaran</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach (['full' => 'Bayar penuh', 'dp' => 'DP (uang muka)'] as $val => $label)
                                    <label @class(['ms-chip cursor-pointer px-3.5 py-2 text-sm', 'ms-gradient text-white' => $payment_scheme === $val, 'bg-white text-ink-700 ring-1 ring-ink-200' => $payment_scheme !== $val])>
                                        <input type="radio" wire:model.live="payment_scheme" value="{{ $val }}" class="sr-only"> {{ $label }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        @if ($payment_scheme === 'dp')
                            <div>
                                <label for="dp_amount" class="ms-label">Nominal DP</label>
                                <div class="relative">
                                    <span class="pointer-events-none absolute top-1/2 left-3.5 -translate-y-1/2 text-sm font-bold text-ink-500">Rp</span>
                                    <input id="dp_amount" type="number" min="1000" step="500" wire:model.blur="dp_amount" class="ms-input pl-11">
                                </div>
                                @error('dp_amount') <p class="ms-error">{{ $message }}</p> @enderror
                            </div>
                        @endif
                        <div>
                            <label for="closes_at" class="ms-label">PO ditutup</label>
                            <input id="closes_at" type="date" min="{{ now()->format('Y-m-d') }}" wire:model.blur="closes_at" class="ms-input">
                            @error('closes_at') <p class="ms-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="estimated_month" class="ms-label">Estimasi barang jadi</label>
                            <input id="estimated_month" type="month" min="{{ now()->format('Y-m') }}" wire:model.blur="estimated_month" class="ms-input">
                            @error('estimated_month') <p class="ms-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="min_order" class="ms-label">Minimum produksi <span class="font-normal text-ink-400">(opsional)</span></label>
                            <input id="min_order" type="number" min="1" wire:model.blur="min_order" class="ms-input" placeholder="cth. 20">
                            @error('min_order') <p class="ms-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="max_slot" class="ms-label">Kuota slot <span class="font-normal text-ink-400">(opsional)</span></label>
                            <input id="max_slot" type="number" min="1" wire:model.blur="max_slot" class="ms-input" placeholder="cth. 100">
                            @error('max_slot') <p class="ms-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                @endif

                <label class="flex items-center gap-3 text-sm text-ink-700">
                    <input type="checkbox" wire:model="allow_event_pickup" class="ms-check">
                    Pembeli boleh ambil langsung di booth event
                </label>
            </section>
        </div>

        {{-- ===== Sidebar simpan + pratinjau ===== --}}
        <aside class="space-y-4 lg:sticky lg:top-24 lg:col-span-4">
            <div class="ms-card p-5">
                <p class="text-xs font-bold tracking-wide text-ink-500 uppercase">Pratinjau kartu</p>
                @php
                    $cover = $this->keptImages->first()?->url ?? (isset($photos[0]) ? $photos[0]->temporaryUrl() : null);
                    $fandomName = $this->fandoms->firstWhere('id', (int) $fandom_id)?->fandom_name;
                    $charName = $this->characters->firstWhere('id', (int) $fandom_character_id)?->character_name;
                    $typeName = $this->types->firstWhere('id', (int) $merchandise_type_id)?->type_name;
                @endphp
                <div class="mt-3 overflow-hidden rounded-2xl border border-ink-200/70">
                    <div class="aspect-square bg-ink-100">
                        @if ($cover)
                            <img src="{{ $cover }}" alt="" class="h-full w-full object-cover">
                        @else
                            <div class="flex h-full items-center justify-center text-ink-300"><x-icon name="photo" class="h-10 w-10" /></div>
                        @endif
                    </div>
                    <div class="p-3.5">
                        <p class="truncate text-[11px] font-bold tracking-wide text-accent-600 uppercase">{{ $fandomName ?? 'Fandom' }}</p>
                        <p class="line-clamp-2 text-sm font-bold text-ink-900">{{ $product_name ?: 'Nama produk' }}</p>
                        <p class="truncate text-xs text-ink-500">{{ $charName ?? 'Karakter' }} · {{ $typeName ?? 'Jenis' }}</p>
                        <p class="mt-2 font-extrabold text-ink-900">Rp{{ number_format((int) $price, 0, ',', '.') }}</p>
                    </div>
                </div>
            </div>

            <div class="ms-card space-y-4 p-5">
                <label class="flex items-start gap-3">
                    <input type="checkbox" wire:model="publish" class="ms-check mt-0.5">
                    <span class="text-sm"><b class="text-ink-900">Tayangkan di katalog</b><br><span class="text-ink-500">Matikan untuk simpan sebagai draf.</span></span>
                </label>
                <button type="submit" class="ms-btn-primary w-full py-3" wire:loading.attr="disabled" wire:target="save,newPhotos">
                    <span wire:loading.remove wire:target="save">{{ $product ? 'Simpan perubahan' : 'Upload produk' }}</span>
                    <span wire:loading wire:target="save">Menyimpan…</span>
                </button>
                @if ($errors->any())
                    <p class="ms-error text-center">Ada {{ $errors->count() }} isian yang perlu diperbaiki.</p>
                @endif
                <a href="{{ route('studio.dashboard') }}" wire:navigate class="ms-btn-ghost w-full">Batal</a>
            </div>
        </aside>
    </form>
</div>
