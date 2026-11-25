@php
    $user  = $this->user;
    $roles = ['buyer' => 'Pembeli', 'artist' => 'Artis', 'admin' => 'Admin'];
    $tabs  = ['profil' => ['Profil', 'user'], 'keamanan' => ['Keamanan', 'lock'], 'fandom' => ['Fandom favorit', 'heart'], 'aktivitas' => ['Aktivitas', 'chart'], 'alamat' => ['Alamat', 'map-pin'], 'tampilan' => ['Tampilan', 'palette']];
    if (! $user->isAdmin()) { $tabs['toko'] = [$user->artistProfile ? 'Toko saya' : 'Buka toko', 'store']; }
    // admin hanya bekerja di panel: tidak perlu fandom favorit, minat, atau alamat pengiriman
    if ($user->isAdmin()) { unset($tabs['fandom'], $tabs['aktivitas'], $tabs['alamat']); }
    // mode Studio (dibuka dari Studio): hanya tab akun & toko, tanpa tab belanja
    $studio = \App\Support\StudioMode::active();
    if ($studio) { unset($tabs['fandom'], $tabs['aktivitas'], $tabs['alamat']); }
@endphp

<div @class(['mx-auto px-4 py-8 sm:px-6 lg:px-8', 'max-w-7xl' => $studio, 'max-w-6xl' => ! $studio])>
    @if ($studio)<x-studio-nav />@endif
    {{-- Kartu identitas --}}
    <div class="ms-card relative overflow-hidden">
        @if ($shopBanner)
            <div class="relative h-24 sm:h-28"><img src="{{ $shopBanner->url }}" alt="" class="h-full w-full object-cover"></div>
        @else
            <div class="ms-gradient relative h-24 sm:h-28"><div class="ms-dots absolute inset-0"></div></div>
        @endif
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
                    <div class="relative">
                        <x-avatar :user="$user" class="h-28 w-28 text-4xl" />
                        <div wire:loading.flex wire:target="photo,removePhoto" class="absolute inset-0 items-center justify-center rounded-full bg-white/70 text-xs font-bold text-brand-700">Memproses…</div>
                    </div>
                    @include('livewire.partials.image-positioner', ['kind' => 'photo', 'label' => $user->profile_photo ? 'Ganti foto' : 'Unggah foto'])
                    @if ($user->profile_photo)
                        <button type="button" wire:click="removePhoto" wire:confirm="Hapus foto profil?" class="inline-flex items-center gap-1 text-xs font-bold text-ink-500 hover:text-red-600"><x-icon name="trash" class="h-3.5 w-3.5" /> Hapus foto</button>
                    @endif
                    <p class="text-center text-[11px] text-ink-400">JPG, PNG, atau WEBP · maks. 2 MB.<br>Atur posisi lalu simpan.</p>
                    @if ($user->artistProfile)<p class="text-center text-[11px] font-semibold text-brand-700">Foto ini juga jadi foto tokomu.</p>@endif
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
                    <button type="submit" class="ms-btn-primary" wire:loading.attr="disabled" wire:target="saveProfile">Simpan profil</button>
                </div>
            </form>

            {{-- Khusus artis: banner toko ikut diatur di sini (pembeli tidak punya banner, sama seperti Shopee) --}}
            @if ($user->artistProfile)
                {{-- Banner toko: tampil di halaman toko publik --}}
                <div class="ms-card mt-5 space-y-3 p-5 sm:p-6">
                    <div>
                        <h2 class="text-lg font-extrabold text-ink-900">Banner toko</h2>
                        <p class="text-sm text-ink-500">Tampil di bagian atas halaman tokomu. Disarankan 1500×500 piksel (rasio 3:1), JPG/PNG/WEBP maks. 3 MB.</p>
                    </div>
                    <div class="relative aspect-[3/1] w-full overflow-hidden rounded-2xl bg-ink-100">
                        @if ($shopBanner)
                            <img src="{{ $shopBanner->url }}" alt="Banner {{ $user->artistProfile->shop_name }}" class="h-full w-full object-cover">
                        @else
                            <div class="ms-gradient flex h-full w-full items-center justify-center"><span class="rounded-full bg-white/80 px-3 py-1 text-xs font-bold text-ink-600">Belum ada banner — toko memakai banner bawaan</span></div>
                        @endif
                        <div wire:loading.flex wire:target="banner,removeBanner" class="absolute inset-0 items-center justify-center bg-white/70 text-sm font-bold text-brand-700">Memproses…</div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        @include('livewire.partials.image-positioner', ['kind' => 'banner', 'label' => $shopBanner ? 'Ganti banner' : 'Unggah banner'])
                        @if ($shopBanner)
                            <button type="button" wire:click="removeBanner" wire:confirm="Hapus banner toko? Toko akan memakai banner bawaan." class="ms-btn-ghost !py-1.5 text-xs text-red-600"><x-icon name="trash" class="h-4 w-4" /> Hapus banner</button>
                        @endif
                        <a href="{{ route('artist.shop', $user->artistProfile) }}" wire:navigate class="ms-btn-ghost !py-1.5 text-xs">Lihat halaman toko</a>
                    </div>
                    @error('banner') <p class="ms-error">{{ $message }}</p> @enderror
                </div>
            @endif
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
                        <p class="text-sm text-ink-600">Fandom favorit membantumu menemukan rekomendasi produk di beranda, bahkan sebelum kamu mulai berbelanja.</p>
                    </div>
                    <div class="flex gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-pop-50 text-pop-600"><x-icon name="chart" class="h-5 w-5" /></span>
                        <p class="text-sm text-ink-600">Produk yang kamu cari, lihat, masukkan keranjang, dan beli juga membantu menyesuaikan rekomendasi.</p>
                    </div>
                    <div class="flex gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-accent-50 text-accent-600"><x-icon name="heart" class="h-5 w-5" /></span>
                        <p class="text-sm text-ink-600">Menyukai fandom <b>bukan</b> berarti bergabung ke grup diskusinya. Gabung grup itu terpisah dan sepenuhnya pilihanmu.</p>
                    </div>
                </section>
            </div>
        @endif

        {{-- ================= AKTIVITAS ================= --}}
        @if ($tab === 'aktivitas')
            <section>
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
                        <div class="sm:col-span-2">@include('livewire.partials.location-picker', ['shop' => false])</div>
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
            @if ($user->artistProfile)
                {{-- Foto & banner toko diatur di tab Profil (seperti Shopee: foto akun = foto/logo toko) --}}
                <div class="mb-5 flex max-w-2xl items-start gap-3 rounded-2xl bg-brand-50 px-4 py-3 text-sm text-brand-800 ring-1 ring-brand-100">
                    <x-icon name="info" class="mt-0.5 h-5 w-5 shrink-0" />
                    <p>Foto toko memakai <b>foto profil</b>-mu, dan <b>banner toko</b> juga diatur di tab
                        <button type="button" wire:click="$set('tab', 'profil')" class="font-bold underline underline-offset-2">Profil</button>.</p>
                </div>
            @endif
            <form wire:submit="saveShop" class="ms-card max-w-2xl space-y-4 p-5 sm:p-6">
                <div>
                    <h2 class="text-lg font-extrabold text-ink-900">{{ $user->artistProfile ? 'Toko saya' : 'Buka toko di Miseku' }}</h2>
                    <p class="text-sm text-ink-500">{{ $user->artistProfile ? 'Nama toko, deskripsi, dan rekening untuk pencairan saldo.' : 'Ajukan toko dengan akun sosial. Toko dibuka setelah admin memeriksa dan menyetujui.' }}</p>
                </div>
                @if (! $user->artistProfile && $shopApplication)
                    <p class="text-sm">Pengajuan: {{ ['pending'=>'Menunggu admin', 'rejected'=>'Ditolak', 'approved'=>'Disetujui'][$shopApplication->status] ?? $shopApplication->status }} @if($shopApplication->review_note) · {{ $shopApplication->review_note }} @endif</p>
                @endif
                @if (! $user->artistProfile)
                    <label class="block"><span class="ms-label">Tautan Instagram/X atau portofolio (wajib)</span><input type="url" wire:model="social_url" class="ms-input" placeholder="https://...">@error('social_url')<p class="ms-error">{{ $message }}</p>@enderror</label>
                @endif
                <label class="block"><span class="ms-label">Nomor HP <span class="text-red-600">(wajib)</span></span><input type="tel" wire:model="phone" class="ms-input" autocomplete="tel" placeholder="Contoh: 081234567890" required>@error('phone')<p class="ms-error">{{ $message }}</p>@enderror<span class="mt-1 block text-xs text-ink-500">Nomor ini sama dengan nomor HP di profil akunmu.</span></label>
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
                        <div>@include('livewire.partials.location-picker', ['shop' => true])</div>
                        <label class="block"><span class="ms-label">Kode pos</span><input type="text" wire:model="shop_postal_code" class="ms-input">@error('shop_postal_code')<p class="ms-error">{{ $message }}</p>@enderror</label>
                    </div>
                </div>
                <button type="submit" class="ms-btn-primary">{{ $user->artistProfile ? 'Simpan' : 'Kirim pengajuan toko' }}</button>
            </form>
        @endif
    </div>
</div>
