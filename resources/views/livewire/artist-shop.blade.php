<div>
    {{-- ======================= BANNER + IDENTITAS TOKO ======================= --}}
    <section class="relative">
        <div class="h-40 w-full overflow-hidden bg-miseku-200 sm:h-56 lg:h-64">
            <img src="{{ $artist->banner_url }}" alt="Banner {{ $artist->shop_name }}"
                 class="h-full w-full object-cover">
        </div>

        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="ms-card relative -mt-14 p-5 sm:-mt-16 sm:p-6">
                <div class="flex flex-col gap-5 sm:flex-row sm:items-center">
                    <img src="{{ $artist->user->avatar_url }}" alt="{{ $artist->shop_name }}"
                         class="h-24 w-24 shrink-0 rounded-2xl object-cover ring-4 ring-white shadow-[var(--shadow-soft)] sm:h-28 sm:w-28">

                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h1 class="text-2xl font-extrabold tracking-tight text-miseku-900">
                                {{ $artist->shop_name }}
                            </h1>
                            @if ($artist->rating >= 4.5 && $artist->rating_count >= 3)
                                <span class="ms-chip bg-miseku-100 text-miseku-600">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M10 1.6l2.6 5.3 5.8.8-4.2 4.1 1 5.8-5.2-2.8-5.2 2.8 1-5.8L1.6 7.7l5.8-.8L10 1.6z"/>
                                    </svg>
                                    Toko favorit
                                </span>
                            @endif
                        </div>

                        <div class="mt-2 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm">
                            <x-star-rating :rating="$artist->rating" :count="$artist->rating_count" size="md" />
                            <span class="text-miseku-500">
                                <span class="font-bold text-miseku-800">{{ $this->stats['karya'] }}</span> karya
                            </span>
                            <span class="text-miseku-500">
                                <span class="font-bold text-miseku-800">{{ $this->stats['po'] }}</span> pre-order aktif
                            </span>
                            <span class="text-miseku-500">
                                Bergabung {{ $artist->created_at->translatedFormat('F Y') }}
                            </span>
                        </div>
                    </div>

                    <div class="flex shrink-0 gap-2">
                        <button type="button" class="ms-btn-primary">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M8.625 9.75h.008v.008h-.008V9.75Zm3.375 0h.008v.008H12V9.75Zm3.375 0h.008v.008h-.008V9.75ZM21 12c0 4.14-4.03 7.5-9 7.5a9.7 9.7 0 0 1-2.6-.35l-3.9 1.35 1.28-3.1C3.66 16.1 3 14.14 3 12c0-4.14 4.03-7.5 9-7.5s9 3.36 9 7.5Z" />
                            </svg>
                            Chat toko
                        </button>
                        <button type="button" class="ms-btn-ghost !px-3" title="Bagikan toko">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M7.22 10.9a3 3 0 1 0 0 2.2m0-2.2a3 3 0 1 1 0 2.2m0-2.2 9.56-4.4m-9.56 6.6 9.56 4.4m0-11a3 3 0 1 0 0-2.2 3 3 0 0 0 0 2.2Zm0 13.2a3 3 0 1 0 0 2.2 3 3 0 0 0 0-2.2Z" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

        {{-- ======================= TAB ======================= --}}
        <div class="mt-6 border-b border-miseku-200">
            <nav class="-mb-px flex gap-1" aria-label="Tab toko">
                @foreach ([
                    'karya'   => ['Karya', $this->stats['karya']],
                    'review'  => ['Review', $this->stats['review']],
                    'tentang' => ['Tentang', null],
                ] as $key => [$label, $count])
                    <button
                        type="button"
                        wire:click="selectTab('{{ $key }}')"
                        @class(['ms-tab', 'ms-tab-active' => $tab === $key])
                    >
                        {{ $label }}
                        @if (! is_null($count))
                            <span @class([
                                'ml-1 rounded-full px-1.5 py-0.5 text-[11px] font-bold',
                                'bg-miseku-500 text-white' => $tab === $key,
                                'bg-miseku-100 text-miseku-500' => $tab !== $key,
                            ])>{{ $count }}</span>
                        @endif

                        @if ($tab === $key)
                            <span class="absolute inset-x-0 -bottom-px h-0.5 rounded-full bg-miseku-500"></span>
                        @endif
                    </button>
                @endforeach
            </nav>
        </div>

        {{-- ======================= TAB: KARYA ======================= --}}
        @if ($tab === 'karya')
            <div class="mt-6">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <p class="text-sm text-miseku-600">
                        <span class="font-bold text-miseku-800">{{ $products->total() }}</span> karya aktif di toko ini
                    </p>
                    <div class="relative w-52">
                        <select wire:model.live="sort" class="ms-select" aria-label="Urutkan karya">
                            <option value="terbaru">Terbaru</option>
                            <option value="termurah">Harga termurah</option>
                            <option value="termahal">Harga tertinggi</option>
                            <option value="rating">Rating tertinggi</option>
                        </select>
                        <x-chevron />
                    </div>
                </div>

                @if ($products->isEmpty())
                    <x-empty-state class="mt-5" title="Toko ini belum punya karya aktif"
                                   description="Karya akan tampil di sini setelah artis mengunggah produknya." />
                @else
                    <div class="mt-5 grid grid-cols-2 gap-4 sm:gap-5 md:grid-cols-3 lg:grid-cols-4">
                        @foreach ($products as $product)
                            <x-product-card :product="$product" :show-artist="false" :key="'ap-'.$product->id" />
                        @endforeach
                    </div>

                    @if ($products->hasPages())
                        <div class="mt-10">{{ $products->onEachSide(1)->links() }}</div>
                    @endif
                @endif
            </div>
        @endif

        {{-- ======================= TAB: REVIEW ======================= --}}
        @if ($tab === 'review')
            <div class="mt-6 grid gap-6 lg:grid-cols-12">
                <div class="lg:col-span-4">
                    <div class="ms-card p-6 text-center">
                        <p class="text-5xl font-extrabold leading-none text-miseku-700">
                            {{ number_format((float) $artist->rating, 1, ',', '.') }}
                        </p>
                        <x-star-rating :rating="$artist->rating" size="lg" class="mt-2 justify-center" :show-value="false" />
                        <p class="mt-2 text-sm text-miseku-500">
                            dari {{ $artist->rating_count }} ulasan pembeli
                        </p>
                        <p class="mt-4 border-t border-miseku-100 pt-4 text-xs leading-relaxed text-miseku-400">
                            Rating toko dihitung dari ulasan pembeli setelah pesanan selesai,
                            dan bisa terpengaruh penalti bila artis melewati tenggat pengiriman.
                        </p>
                    </div>
                </div>

                <div class="lg:col-span-8">
                    @if ($this->reviews->isEmpty())
                        <x-empty-state title="Belum ada ulasan"
                                       description="Ulasan muncul setelah ada pesanan yang selesai di toko ini." />
                    @else
                        <div class="space-y-4">
                            @foreach ($this->reviews as $review)
                                <article class="ms-card flex gap-4 p-5">
                                    <img src="{{ $review->reviewer->avatar_url }}" alt=""
                                         class="h-11 w-11 shrink-0 rounded-full object-cover ring-1 ring-miseku-200">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-x-3">
                                            <p class="text-sm font-bold text-miseku-800">{{ $review->reviewer->name }}</p>
                                            <span class="text-xs text-miseku-300">
                                                {{ $review->created_at->translatedFormat('d M Y') }}
                                            </span>
                                        </div>
                                        <x-star-rating :rating="$review->rating" size="sm" class="mt-1" :show-value="false" />
                                        @if ($review->comment)
                                            <p class="mt-2 text-sm leading-relaxed text-miseku-600">{{ $review->comment }}</p>
                                        @endif
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        @endif

        {{-- ======================= TAB: TENTANG ======================= --}}
        @if ($tab === 'tentang')
            <div class="mt-6 grid gap-6 lg:grid-cols-12">
                <section class="ms-card p-6 lg:col-span-7">
                    <h2 class="text-lg font-bold text-miseku-900">Tentang toko</h2>
                    <p class="mt-3 whitespace-pre-line text-sm leading-relaxed text-miseku-600">
                        {{ $artist->shop_description ?: 'Artis belum menulis deskripsi toko.' }}
                    </p>

                    <dl class="mt-6 grid gap-4 border-t border-miseku-100 pt-5 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="text-xs font-medium text-miseku-400">Nama artis</dt>
                            <dd class="font-semibold text-miseku-800">{{ $artist->user->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-miseku-400">Bergabung sejak</dt>
                            <dd class="font-semibold text-miseku-800">
                                {{ $artist->created_at->translatedFormat('d F Y') }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-miseku-400">Total karya</dt>
                            <dd class="font-semibold text-miseku-800">{{ $this->stats['karya'] }} karya</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-miseku-400">Pre-order aktif</dt>
                            <dd class="font-semibold text-miseku-800">{{ $this->stats['po'] }} batch</dd>
                        </div>
                    </dl>
                </section>

                <section class="ms-card p-6 lg:col-span-5">
                    <h2 class="text-lg font-bold text-miseku-900">Fandom yang digarap</h2>
                    <p class="mt-1 text-xs text-miseku-400">Dihitung dari karya aktif toko ini.</p>

                    <ul class="mt-4 space-y-3">
                        @forelse ($this->topFandoms as $fandomName => $total)
                            <li>
                                <div class="flex items-center justify-between text-sm">
                                    <span class="font-medium text-miseku-700">{{ $fandomName }}</span>
                                    <span class="text-xs font-semibold text-miseku-500">{{ $total }} karya</span>
                                </div>
                                <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-miseku-100">
                                    <div class="h-full rounded-full bg-miseku-400"
                                         style="width: {{ $this->topFandoms->max() > 0 ? round($total / $this->topFandoms->max() * 100) : 0 }}%"></div>
                                </div>
                            </li>
                        @empty
                            <li class="text-sm text-miseku-400">Belum ada data fandom.</li>
                        @endforelse
                    </ul>
                </section>
            </div>
        @endif
    </div>
</div>
