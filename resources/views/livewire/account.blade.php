@php
    $user  = $this->user;
    $roles = ['buyer' => 'Pembeli', 'artist' => 'Artis', 'admin' => 'Admin'];
    $tabs  = ['profil' => ['Profil', 'user'], 'keamanan' => ['Keamanan', 'lock'], 'fandom' => ['Fandom favorit', 'heart'], 'aktivitas' => ['Aktivitas & minat', 'chart'], 'alamat' => ['Alamat', 'map-pin'], 'tampilan' => ['Tampilan', 'palette']];
    if (! $user->isAdmin()) { $tabs['toko'] = [$user->artistProfile ? 'Toko saya' : 'Buka toko', 'store']; }
    // admin hanya bekerja di panel: tidak perlu fandom favorit, minat, atau alamat pengiriman
    if ($user->isAdmin()) { unset($tabs['fandom'], $tabs['aktivitas'], $tabs['alamat']); }
@endphp

<div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
    {{-- Kartu identitas --}}
    <div class="ms-card relative overflow-hidden">
        <div class="ms-gradient relative h-24 sm:h-28"><div class="ms-dots absolute inset-0"></div></div>
        <div class="flex flex-wrap items-end gap-4 px-5 pb-5 sm:px-6">
            <x-avatar :user="$user" class="relative z-10 -mt-10 h-20 w-20 shrink-0 text-2xl ring-4 ring-white" />
            <div class="min-w-0 flex-1 pt-2">
                <h1 class="truncate text-xl font-extrabold text-ink-900">{{ $user->name }}</h1>
                <p class="truncate text-sm text-ink-500">{{ $user->email }} · <span class="font-semibold text-brand-700">{{ $roles[$user->role] ?? $user->role }}</span>
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                @if ($user->isArtist() && $user->artistProfile)
                    <a href="{{ route('studio.dashboard') }}" wire:navigate class="ms-btn-soft"><x-icon name="store" class="h-4.5 w-4.5" /> Studio artis</a>
                @endif
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="ms-btn-ghost"><x-icon name="logout" class="h-4.5 w-4.5" /> Keluar</button>
                </form>
            </div>
        </div>
    </div>

    {{-- Tab --}}
    <div class="ms-scroll-x mt-5 flex gap-1.5">
        @foreach ($tabs as $key => [$label, $icon])
            <button type="button" wire:click="$set('tab', '{{ $key }}')" @class(['ms-tab', 'ms-tab-active' => $tab === $key])>
                <x-icon :name="$icon" class="h-4 w-4" /> {{ $label }}
            </button>
        @endforeach
    </div>

    <div class="mt-5">
        {{-- ================= PROFIL ================= --}}
        @if ($tab === 'profil')
            <form wire:submit="saveProfile" class="ms-card grid gap-6 p-5 sm:p-6 md:grid-cols-[180px_1fr]">
                <div class="flex flex-col items-center gap-3">
                    @if ($photo && ! $errors->has('photo'))
                        <img src="{{ $photo->temporaryUrl() }}" alt="" class="h-28 w-28 rounded-full object-cover">
                    @else
                        <x-avatar :user="$user" class="h-28 w-28 text-4xl" />
                    @endif
                    <label class="ms-btn-soft cursor-pointer px-3 py-1.5 text-xs">
                        <x-icon name="photo" class="h-4 w-4" /> Ganti foto
                        <input type="file" wire:model="photo" accept="image/jpeg,image/png,image/webp" class="sr-only">
                    </label>
                    @if ($user->profile_photo && ! $photo)
                        <button type="button" wire:click="removePhoto" class="text-xs font-bold text-ink-500 hover:text-red-600">Hapus foto</button>
                    @endif
                    @error('photo') <p class="ms-error text-center">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-5">
                    <div>
                        <label class="ms-label" for="name">Nama</label>
                        <input id="name" type="text" wire:model="name" class="ms-input">
                        @error('name') <p class="ms-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="ms-label" for="email">Email</label>
                        <input id="email" type="email" value="{{ $user->email }}" disabled class="ms-input cursor-not-allowed bg-ink-50 text-ink-500">
                        <p class="ms-hint">Email dipakai untuk masuk dan tidak bisa diubah di sini.</p>
                    </div>
                    <div>
                        <label class="ms-label" for="phone">Nomor HP <span class="font-normal text-ink-400">(opsional)</span></label>
                        <input id="phone" type="tel" wire:model="phone" class="ms-input" placeholder="08xxxxxxxxxx">
                        @error('phone') <p class="ms-error">{{ $message }}</p> @enderror
                    </div>
                    <button type="submit" class="ms-btn-primary" wire:loading.attr="disabled" wire:target="saveProfile,photo">Simpan profil</button>
                </div>
            </form>
        @endif

        {{-- ================= KEAMANAN ================= --}}
        @if ($tab === 'keamanan')
            <form wire:submit="savePassword" class="ms-card max-w-xl space-y-5 p-5 sm:p-6" x-data="{ show: false }">
                <h2 class="font-extrabold text-ink-900">Ganti kata sandi</h2>
                @foreach (['current_password' => 'Kata sandi lama', 'password' => 'Kata sandi baru', 'password_confirmation' => 'Ulangi kata sandi baru'] as $field => $label)
                    <div>
                        <label class="ms-label" for="{{ $field }}">{{ $label }}</label>
                        <input id="{{ $field }}" :type="show ? 'text' : 'password'" wire:model="{{ $field }}" class="ms-input" autocomplete="{{ $field === 'current_password' ? 'current-password' : 'new-password' }}">
                        @error($field) <p class="ms-error">{{ $message }}</p> @enderror
                    </div>
                @endforeach
                <p class="ms-hint -mt-2">Minimal 8 karakter, berisi huruf dan angka.</p>
                <label class="flex items-center gap-2 text-sm text-ink-600"><input type="checkbox" x-model="show" class="ms-check"> Tampilkan kata sandi</label>
                <button type="submit" class="ms-btn-primary">Simpan kata sandi</button>
            </form>
        @endif

        {{-- ================= FANDOM FAVORIT ================= --}}
        @if ($tab === 'fandom')
            <div class="grid items-start gap-6 lg:grid-cols-12">
                <section class="ms-card p-5 sm:p-6 lg:col-span-7">
                    <div class="flex flex-wrap items-end justify-between gap-3">
                        <div>
                            <h2 class="font-extrabold text-ink-900">Fandom favorit</h2>
                            <p class="text-sm text-ink-500">Boleh lebih dari satu. Dipakai untuk rekomendasi produk di beranda.</p>
                        </div>
                        <span class="ms-pill">{{ count($favorites) }} dipilih</span>
                    </div>
                    <div class="relative mt-4">
                        <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3.5 h-4.5 w-4.5 -translate-y-1/2 text-ink-400" />
                        <input type="search" wire:model.live.debounce.300ms="fandomFilter" placeholder="Cari fandom…" class="ms-input pl-10">
                    </div>
                    <div class="mt-4 flex max-h-80 flex-wrap gap-2 overflow-y-auto">
                        @foreach ($this->fandoms as $f)
                            @php $on = in_array($f->id, $favorites, true); @endphp
                            <button type="button" wire:click="toggleFavorite({{ $f->id }})" wire:key="fav-{{ $f->id }}"
                                    @class(['inline-flex items-center gap-1.5 rounded-full px-3.5 py-2 text-sm font-bold transition',
                                            'ms-gradient text-white shadow-[var(--shadow-glow)]' => $on,
                                            'bg-ink-100 text-ink-700 hover:bg-brand-50 hover:text-brand-700' => ! $on])>
                                @if ($on)<x-icon name="check" class="h-4 w-4" stroke="2.6" />@endif
                                {{ $f->fandom_name }}
                            </button>
                        @endforeach
                    </div>
                    <button type="button" wire:click="saveFavorites" class="ms-btn-primary mt-5">Simpan fandom favorit</button>
                </section>

                <section class="ms-card space-y-4 p-5 sm:p-6 lg:col-span-5">
                    <h2 class="font-extrabold text-ink-900">Kenapa ditanya fandom?</h2>
                    <div class="flex gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600"><x-icon name="sparkles" class="h-5 w-5" /></span>
                        <p class="text-sm text-ink-600">Fandom favorit menjadi isi awal <b>profil minat</b>-mu, jadi beranda langsung bisa menampilkan rekomendasi walau kamu belum pernah melihat atau membeli apa pun.</p>
                    </div>
                    <div class="flex gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-pop-50 text-pop-600"><x-icon name="chart" class="h-5 w-5" /></span>
                        <p class="text-sm text-ink-600">Setelah itu, produk yang kamu cari, lihat, masukkan keranjang, dan beli ikut membentuk rekomendasi. Lihat di tab <b>Aktivitas &amp; minat</b>.</p>
                    </div>
                    <div class="flex gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-accent-50 text-accent-600"><x-icon name="heart" class="h-5 w-5" /></span>
                        <p class="text-sm text-ink-600">Menyukai fandom <b>bukan</b> berarti bergabung ke grup diskusinya. Gabung grup itu terpisah dan sepenuhnya pilihanmu.</p>
                    </div>
                </section>
            </div>
        @endif

        {{-- ================= AKTIVITAS & MINAT ================= --}}
        @if ($tab === 'aktivitas')
            @php
                $interest = $this->interest;
                $blocks = ['character' => ['Karakter', 'bobot 0,5'], 'fandom' => ['Fandom', 'bobot 0,3'], 'type' => ['Jenis merch', 'bobot 0,2']];
                $srcLabel = ['favorite' => 'Fandom favorit', 'search' => 'Pencarian', 'view' => 'Produk dilihat', 'cart' => 'Keranjang', 'purchase' => 'Pembelian'];
            @endphp
            <section class="ms-card p-5 sm:p-6">
                <x-section-heading title="Profil minatmu" icon="sparkles" subtitle="Beginilah sistem rekomendasi (Content-Based Filtering) membaca seleramu." />
                <div class="grid gap-5 md:grid-cols-3">
                    @foreach ($blocks as $key => [$label, $weight])
                        <div class="rounded-2xl bg-ink-50 p-4">
                            <p class="flex items-baseline justify-between text-sm font-extrabold text-ink-900">{{ $label }} <span class="text-[11px] font-semibold text-ink-400">{{ $weight }}</span></p>
                            <div class="mt-3 space-y-2.5">
                                @forelse ($interest[$key] as $row)
                                    <div>
                                        <div class="flex justify-between text-xs"><span class="truncate font-semibold text-ink-700">{{ $row['name'] }}</span><span class="text-ink-400">{{ $row['value'] }}</span></div>
                                        <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-white"><div class="ms-gradient h-full rounded-full" style="width: {{ $row['percent'] }}%"></div></div>
                                    </div>
                                @empty
                                    <p class="text-xs text-ink-400">Belum ada data.</p>
                                @endforelse
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-5 flex flex-wrap gap-2 text-xs">
                    @foreach ($interest['sources'] as $src => $n)
                        <span class="ms-chip-muted">{{ $srcLabel[$src] ?? $src }}: <b class="ml-0.5">{{ $n }}</b></span>
                    @endforeach
                </div>
            </section>

            <section class="mt-8">
                <x-section-heading title="Terakhir kamu lihat" icon="clock" />
                @if ($this->recentlyViewed->isEmpty())
                    <x-empty-state icon="eye" title="Belum ada produk yang dilihat" description="Buka beberapa produk di katalog — riwayatnya muncul di sini.">
                        <a href="{{ route('catalog') }}" wire:navigate class="ms-btn-primary">Jelajahi katalog</a>
                    </x-empty-state>
                @else
                    <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                        @foreach ($this->recentlyViewed as $p)
                            <x-product-card :product="$p" wire:key="rv-{{ $p->id }}" />
                        @endforeach
                    </div>
                @endif
            </section>
        @endif
            {{-- ================= ALAMAT ================= --}}
        @if ($tab === 'alamat')
            <section class="ms-card p-5 sm:p-6">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-extrabold text-ink-900">Alamat pengiriman</h2>
                        <p class="text-sm text-ink-500">Dipakai untuk ongkir kurir. {{ $user->isArtist() ? 'Kota asal pengiriman toko diatur terpisah di tab Toko saya.' : '' }}</p>
                    </div>
                    <button type="button" wire:click="newAddress" class="ms-btn-primary"><x-icon name="plus" class="h-4.5 w-4.5" /> Tambah alamat</button>
                </div>

                @if ($addressForm)
                    <form wire:submit="saveAddress" class="mt-5 grid gap-4 rounded-2xl border border-brand-200 bg-brand-50/30 p-4 sm:grid-cols-2">
                        <label class="block"><span class="ms-label">Label</span><input type="text" wire:model="address_label" class="ms-input" placeholder="Rumah / Kos / Kantor">@error('address_label')<p class="ms-error">{{ $message }}</p>@enderror</label>
                        <label class="block"><span class="ms-label">Nama penerima</span><input type="text" wire:model="recipient_name" class="ms-input">@error('recipient_name')<p class="ms-error">{{ $message }}</p>@enderror</label>
                        <label class="block sm:col-span-2"><span class="ms-label">Alamat lengkap</span><textarea wire:model="full_address" rows="2" class="ms-input" placeholder="Jalan, nomor, RT/RW, kelurahan, kecamatan"></textarea>@error('full_address')<p class="ms-error">{{ $message }}</p>@enderror</label>
                        <div class="relative block">
                            <span class="ms-label">Kota / kabupaten</span>
                            @if ($city_label)<p class="mb-1 text-sm font-bold text-brand-700">{{ $city_label }}</p>@endif
                            <input type="search" wire:model.live.debounce.300ms="citySearch" class="ms-input" placeholder="Ketik min. 2 huruf, mis. Bandung">
                            @if ($this->cityResults)
                                <ul class="absolute z-10 mt-1 max-h-56 w-full overflow-y-auto rounded-xl border border-ink-200 bg-white shadow-lg">
                                    @foreach ($this->cityResults as $c)
                                        <li><button type="button" wire:click="pickCity(@js($c['id']), @js($c['label']), @js($c['province']))" class="block w-full px-3 py-2 text-left text-sm hover:bg-brand-50">{{ $c['label'] }} <span class="text-xs text-ink-400">{{ $c['province'] }}</span></button></li>
                                    @endforeach
                                </ul>
                            @endif
                            @error('city_id')<p class="ms-error">{{ $message }}</p>@enderror
                        </div>
                        <label class="block"><span class="ms-label">Kode pos</span><input type="text" wire:model="postal_code" class="ms-input"></label>
                        <label class="flex items-center gap-2 text-sm font-semibold sm:col-span-2"><input type="checkbox" class="ms-check" wire:model="is_primary"> Jadikan alamat utama</label>
                        <div class="flex gap-2 sm:col-span-2"><button type="submit" class="ms-btn-primary">Simpan alamat</button><button type="button" wire:click="$set('addressForm', false)" class="ms-btn-ghost">Batal</button></div>
                    </form>
                @endif

                <div class="mt-5 grid gap-3 sm:grid-cols-2">
                    @forelse ($this->addresses as $a)
                        <div class="rounded-2xl border-2 p-4 {{ $a->is_primary ? 'border-brand-400' : 'border-ink-200' }}" wire:key="ad-{{ $a->id }}">
                            <p class="font-bold text-ink-900">{{ $a->address_label }} @if ($a->is_primary)<span class="ms-chip ms-chip-ready ml-1">Utama</span>@endif</p>
                            <p class="text-sm text-ink-700">{{ $a->recipient_name }}</p>
                            <p class="text-sm text-ink-500">{{ $a->one_line }}</p>
                            <div class="mt-2 flex gap-3 text-xs font-bold">
                                <button type="button" wire:click="editAddress({{ $a->id }})" class="text-brand-700 hover:underline">Ubah</button>
                                @unless ($a->is_primary)<button type="button" wire:click="makePrimary({{ $a->id }})" class="text-brand-700 hover:underline">Jadikan utama</button>@endunless
                                <button type="button" wire:click="deleteAddress({{ $a->id }})" wire:confirm="Hapus alamat ini?" class="text-red-600 hover:underline">Hapus</button>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-ink-500 sm:col-span-2">Belum ada alamat.</p>
                    @endforelse
                </div>
                @unless (app(\App\Services\ShippingService::class)->isLive())
                    <p class="ms-hint mt-4">Mode ongkir simulasi: daftar kota lokal (isi RAJAONGKIR_API_KEY di .env untuk data kota/kecamatan RajaOngkir asli).</p>
                @endunless
            </section>
        @endif

        {{-- ================= TOKO ================= --}}
        @if ($tab === 'tampilan')
            <livewire:theme-picker variant="panel" wire:key="theme-panel" />
        @endif

        @if ($tab === 'toko' && ! $user->isAdmin())
            <form wire:submit="saveShop" class="ms-card max-w-2xl space-y-4 p-5 sm:p-6">
                <div>
                    <h2 class="text-lg font-extrabold text-ink-900">{{ $user->artistProfile ? 'Toko saya' : 'Buka toko di Miseku' }}</h2>
                    <p class="text-sm text-ink-500">{{ $user->artistProfile ? 'Nama toko, deskripsi, dan rekening untuk pencairan saldo.' : 'Jual merch karyamu sendiri. Akunmu jadi akun artis (tetap bisa belanja seperti biasa).' }}</p>
                </div>
                <label class="block"><span class="ms-label">Nama toko</span><input type="text" wire:model="shop_name" class="ms-input">@error('shop_name')<p class="ms-error">{{ $message }}</p>@enderror</label>
                <label class="block"><span class="ms-label">Deskripsi toko</span><textarea wire:model="shop_description" rows="3" class="ms-input"></textarea>@error('shop_description')<p class="ms-error">{{ $message }}</p>@enderror</label>
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="block"><span class="ms-label">Nama bank</span><input type="text" wire:model="bank_name" class="ms-input" placeholder="BCA / BRI / Mandiri…">@error('bank_name')<p class="ms-error">{{ $message }}</p>@enderror</label>
                    <label class="block"><span class="ms-label">Nomor rekening</span><input type="text" wire:model="bank_account_number" class="ms-input">@error('bank_account_number')<p class="ms-error">{{ $message }}</p>@enderror</label>
                </div>
                {{-- Alamat toko: diisi sendiri (bisa beda dengan alamat rumah). Dipakai sebagai kota asal ongkir. --}}
                <div class="space-y-4 rounded-2xl border border-ink-200 p-4">
                    <div>
                        <p class="font-extrabold text-ink-900">Alamat toko</p>
                        <p class="text-xs text-ink-500">Alamat asal pengiriman paket. Ongkir pembeli dihitung dari kota ini.</p>
                    </div>
                    <label class="block"><span class="ms-label">Alamat lengkap toko</span><textarea wire:model="shop_address" rows="2" class="ms-input" placeholder="Jalan, nomor, RT/RW, kelurahan, kecamatan"></textarea>@error('shop_address')<p class="ms-error">{{ $message }}</p>@enderror</label>
                    <div class="grid gap-4 sm:grid-cols-[2fr_1fr]">
                        <div class="relative">
                            <span class="ms-label">Kota / kabupaten</span>
                            @if ($shop_city_label)<p class="mb-1 text-sm font-bold text-brand-700">{{ $shop_city_label }}</p>@endif
                            <input type="search" wire:model.live.debounce.300ms="shopCitySearch" class="ms-input" placeholder="{{ $shop_city_label ? 'Ganti kota…' : 'Ketik min. 2 huruf, mis. Bandung' }}">
                            @if ($this->shopCityResults)
                                <ul class="absolute z-10 mt-1 max-h-56 w-full overflow-y-auto rounded-xl border border-ink-200 bg-white shadow-lg">
                                    @foreach ($this->shopCityResults as $c)
                                        <li><button type="button" wire:click="pickShopCity(@js($c['id']), @js($c['label']), @js($c['province']))" class="block w-full px-3 py-2 text-left text-sm hover:bg-brand-50">{{ $c['label'] }} <span class="text-xs text-ink-400">{{ $c['province'] }}</span></button></li>
                                    @endforeach
                                </ul>
                            @endif
                            @error('shop_city_id')<p class="ms-error">{{ $message }}</p>@enderror
                        </div>
                        <label class="block"><span class="ms-label">Kode pos</span><input type="text" wire:model="shop_postal_code" class="ms-input">@error('shop_postal_code')<p class="ms-error">{{ $message }}</p>@enderror</label>
                    </div>
                </div>
                <button type="submit" class="ms-btn-primary">{{ $user->artistProfile ? 'Simpan' : 'Buka toko sekarang' }}</button>
            </form>
        @endif
    </div>
</div>
