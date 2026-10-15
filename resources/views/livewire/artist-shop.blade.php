@php $isOwner = auth()->id() === $artist->user_id; @endphp

<div>
    {{-- Banner --}}
    <section class="relative">
        <div class="ms-banner relative h-40 w-full overflow-hidden sm:h-56 lg:h-64">
            @if ($artist->banner?->url)
                <img src="{{ $artist->banner->url }}" alt="Banner {{ $artist->shop_name }}" class="h-full w-full object-cover">
            @else
                <div class="ms-dots absolute inset-0 opacity-25"></div>
            @endif
        </div>

        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="ms-card relative -mt-14 p-5 sm:-mt-16 sm:p-6">
                <div class="flex flex-col gap-5 sm:flex-row sm:items-center">
                    <x-avatar :user="$artist->user" :name="$artist->shop_name" class="h-24 w-24 text-3xl ring-4 ring-white sm:h-28 sm:w-28" />

                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h1 class="text-2xl font-extrabold tracking-tight text-ink-900">{{ $artist->shop_name }}</h1>
                            @if ($artist->rating >= 4.5 && $artist->rating_count >= 3)
                                <span class="ms-chip-ready"><x-icon name="shield" class="h-3.5 w-3.5" /> Toko terpercaya</span>
                            @endif
                        </div>
                        <p class="mt-0.5 text-sm text-ink-500">oleh {{ $artist->user->name }} · bergabung {{ $artist->created_at->translatedFormat('M Y') }}</p>

                        <div class="mt-2 flex flex-wrap items-center gap-x-5 gap-y-1 text-sm text-ink-500">
                            <span><b class="text-ink-900">{{ $this->stats['produk'] }}</b> produk</span>
                            <span><b class="text-ink-900">{{ $this->stats['po'] }}</b> pre-order aktif</span>
                            @if ($artist->rating_count > 0)
                                <x-star-rating :rating="$artist->rating" :count="$artist->rating_count" size="sm" />
                            @endif
                        </div>
                    </div>

                    <div class="flex shrink-0 gap-2" x-data="{ copied: false }">
                        @if ($isOwner)
                            <a href="{{ route('studio.dashboard') }}" wire:navigate class="ms-btn-primary"><x-icon name="store" class="h-4.5 w-4.5" /> Kelola di studio</a>
                        @endif
                        <button type="button" class="ms-btn-ghost"
                                @click="navigator.clipboard?.writeText(window.location.href); copied = true; setTimeout(() => copied = false, 2000)">
                            <x-icon name="share" class="h-4.5 w-4.5" />
                            <span x-text="copied ? 'Tautan disalin!' : 'Bagikan'"></span>
                        </button>
                    </div>
                </div>

                @if ($this->topFandoms->isNotEmpty())
                    <div class="mt-4 flex flex-wrap gap-1.5 border-t border-ink-100 pt-4">
                        <span class="mr-1 text-xs font-bold text-ink-500">Sering menggambar:</span>
                        @foreach ($this->topFandoms as $name => $count)
                            <span class="ms-pill">{{ $name }} · {{ $count }}</span>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </section>

    <div class="mx-auto max-w-7xl px-4 pb-6 sm:px-6 lg:px-8">
        {{-- Tab --}}
        <div class="ms-scroll-x mt-6 flex gap-1.5">
            @foreach (['produk' => ['Produk', $this->stats['produk']], 'review' => ['Ulasan', $this->stats['review']], 'tentang' => ['Tentang', null]] as $key => [$label, $count])
                <button type="button" wire:click="selectTab('{{ $key }}')" @class(['ms-tab', 'ms-tab-active' => $tab === $key])>
                    {{ $label }}
                    @if (! is_null($count))<span class="rounded-full bg-black/5 px-1.5 text-[11px]">{{ $count }}</span>@endif
                </button>
            @endforeach
        </div>

        @if ($tab === 'produk')
            <div class="mt-5 flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm text-ink-500"><b class="text-ink-900">{{ $products->total() }}</b> produk aktif</p>
                <select wire:model.live="sort" class="ms-select w-48" aria-label="Urutkan produk">
                    <option value="terbaru">Terbaru</option>
                    <option value="rating">Rating tertinggi</option>
                    <option value="termurah">Harga terendah</option>
                    <option value="termahal">Harga tertinggi</option>
                </select>
            </div>

            @if ($products->isEmpty())
                <x-empty-state class="mt-5" icon="photo" title="Belum ada produk aktif" description="Artis ini belum menayangkan produk. Cek lagi nanti, ya!" />
            @else
                <div class="mt-5 grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4">
                    @foreach ($products as $product)
                        <x-product-card :product="$product" :show-artist="false" wire:key="ap-{{ $product->id }}" />
                    @endforeach
                </div>
                @if ($products->hasPages())
                    <div class="mt-10">{{ $products->onEachSide(1)->links('components.pagination') }}</div>
                @endif
            @endif
        @endif

        @if ($tab === 'review')
            <div class="mt-5 grid gap-6 lg:grid-cols-12">
                <div class="ms-card p-6 text-center lg:col-span-4">
                    <p class="text-5xl leading-none font-extrabold text-ink-900">{{ number_format((float) $artist->rating, 1, ',', '.') }}</p>
                    <x-star-rating :rating="$artist->rating" size="lg" class="mt-2 justify-center" :show-value="false" />
                    <p class="mt-2 text-sm text-ink-500">dari {{ $artist->rating_count }} ulasan pembeli</p>
                </div>
                <div class="space-y-4 lg:col-span-8">
                    @forelse ($this->reviews as $review)
                        <article class="ms-card flex gap-4 p-5" wire:key="rv-{{ $review->id }}">
                            <x-avatar :user="$review->reviewer" class="h-11 w-11 text-base" />
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-bold text-ink-900">{{ $review->reviewer->name }}
                                    <span class="ml-1 text-xs font-normal text-ink-400">{{ $review->created_at->translatedFormat('d M Y') }}</span></p>
                                <x-star-rating :rating="$review->rating" size="sm" class="mt-1" :show-value="false" />
                                @if ($review->comment)
                                    <p class="mt-2 text-sm leading-relaxed text-ink-600">{{ $review->comment }}</p>
                                @endif
                            </div>
                        </article>
                    @empty
                        <x-empty-state icon="heart" title="Belum ada ulasan" description="Ulasan muncul setelah ada pesanan selesai di toko ini." />
                    @endforelse
                </div>
            </div>
        @endif

        @if ($tab === 'tentang')
            <section class="ms-card mt-5 max-w-3xl p-6">
                <h2 class="ms-section-title">Tentang toko</h2>
                <p class="mt-3 text-sm leading-relaxed whitespace-pre-line text-ink-600">{{ $artist->shop_description ?: 'Artis belum menulis deskripsi toko.' }}</p>
                <dl class="mt-6 grid gap-4 border-t border-ink-100 pt-5 text-sm sm:grid-cols-2">
                    <div><dt class="text-xs text-ink-500">Nama artis</dt><dd class="font-semibold text-ink-900">{{ $artist->user->name }}</dd></div>
                    <div><dt class="text-xs text-ink-500">Bergabung sejak</dt><dd class="font-semibold text-ink-900">{{ $artist->created_at->translatedFormat('d F Y') }}</dd></div>
                    <div><dt class="text-xs text-ink-500">Total produk</dt><dd class="font-semibold text-ink-900">{{ $this->stats['produk'] }} produk</dd></div>
                    <div><dt class="text-xs text-ink-500">Pre-order aktif</dt><dd class="font-semibold text-ink-900">{{ $this->stats['po'] }} produk</dd></div>
                </dl>
            </section>
        @endif
    </div>
</div>
