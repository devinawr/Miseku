<div>
    <h1 class="text-2xl font-extrabold tracking-tight text-miseku-900">Buat akun Miseku</h1>
    <p class="mt-1.5 text-sm text-miseku-500">
        Sudah punya akun?
        <a href="{{ route('login') }}" wire:navigate class="font-semibold text-miseku-600 hover:text-miseku-700">
            Masuk di sini
        </a>
    </p>

    <form wire:submit="register" class="mt-7 space-y-5">

        {{-- ============ Pilih peran ============ --}}
        <div>
            <span class="mb-2 block text-sm font-semibold text-miseku-800">Daftar sebagai</span>
            <div class="grid grid-cols-2 gap-3">
                @foreach ([
                    'buyer'  => ['Pembeli', 'Belanja merchandise fandom favoritmu.'],
                    'artist' => ['Artis', 'Buka toko dan jual karyamu sendiri.'],
                ] as $value => [$label, $hint])
                    <button type="button" wire:click="$set('role', '{{ $value }}')"
                            @class([
                                'rounded-xl border-2 p-3.5 text-left transition',
                                'border-miseku-500 bg-miseku-50 shadow-[var(--shadow-soft)]' => $role === $value,
                                'border-miseku-200 bg-white hover:border-miseku-300' => $role !== $value,
                            ])>
                        <div class="flex items-center justify-between">
                            <span @class([
                                'text-sm font-bold',
                                'text-miseku-700' => $role === $value,
                                'text-miseku-600' => $role !== $value,
                            ])>{{ $label }}</span>

                            <span @class([
                                'flex h-4 w-4 items-center justify-center rounded-full border-2',
                                'border-miseku-500 bg-miseku-500' => $role === $value,
                                'border-miseku-300' => $role !== $value,
                            ])>
                                @if ($role === $value)
                                    <svg class="h-2.5 w-2.5 text-white" fill="none" stroke="currentColor" stroke-width="3.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                    </svg>
                                @endif
                            </span>
                        </div>
                        <p class="mt-1 text-xs leading-snug text-miseku-400">{{ $hint }}</p>
                    </button>
                @endforeach
            </div>
        </div>

        {{-- ============ Data akun ============ --}}
        <div>
            <label for="name" class="mb-1.5 block text-sm font-semibold text-miseku-800">Nama lengkap</label>
            <input id="name" type="text" wire:model.blur="name" autocomplete="name" placeholder="Devina Wongkar"
                   @class(['ms-input', 'border-red-300 focus:border-red-400 focus:ring-red-100' => $errors->has('name')])>
            @error('name') <p class="mt-1.5 text-xs font-medium text-red-500">{{ $message }}</p> @enderror
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="email" class="mb-1.5 block text-sm font-semibold text-miseku-800">Email</label>
                <input id="email" type="email" wire:model.blur="email" autocomplete="email" placeholder="nama@email.com"
                       @class(['ms-input', 'border-red-300 focus:border-red-400 focus:ring-red-100' => $errors->has('email')])>
                @error('email') <p class="mt-1.5 text-xs font-medium text-red-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="phone" class="mb-1.5 block text-sm font-semibold text-miseku-800">
                    No. HP <span class="font-normal text-miseku-400">(opsional)</span>
                </label>
                <input id="phone" type="tel" wire:model.blur="phone" autocomplete="tel" placeholder="08xxxxxxxxxx"
                       @class(['ms-input', 'border-red-300 focus:border-red-400 focus:ring-red-100' => $errors->has('phone')])>
                @error('phone') <p class="mt-1.5 text-xs font-medium text-red-500">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- ============ Data toko: hanya untuk peran artis ============ --}}
        @if ($role === 'artist')
            <div class="space-y-5 rounded-xl border border-miseku-200 bg-miseku-50/70 p-4">
                <p class="text-xs font-bold uppercase tracking-wide text-miseku-500">Data toko</p>

                <div>
                    <label for="shop_name" class="mb-1.5 block text-sm font-semibold text-miseku-800">Nama toko</label>
                    <input id="shop_name" type="text" wire:model.blur="shop_name" placeholder="Kirara Studio"
                           @class(['ms-input', 'border-red-300 focus:border-red-400 focus:ring-red-100' => $errors->has('shop_name')])>
                    @error('shop_name') <p class="mt-1.5 text-xs font-medium text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="shop_description" class="mb-1.5 block text-sm font-semibold text-miseku-800">
                        Deskripsi toko <span class="font-normal text-miseku-400">(opsional)</span>
                    </label>
                    <textarea id="shop_description" rows="3" wire:model.blur="shop_description"
                              placeholder="Ceritakan gaya karyamu, bahan yang dipakai, dan estimasi produksi."
                              class="ms-input resize-none"></textarea>
                    @error('shop_description') <p class="mt-1.5 text-xs font-medium text-red-500">{{ $message }}</p> @enderror
                </div>

                <p class="text-xs leading-relaxed text-miseku-500">
                    Satu akun artis = satu toko. Akun ini tetap bisa dipakai berbelanja sebagai pembeli.
                </p>
            </div>
        @endif

        {{-- ============ Password ============ --}}
        <div class="grid gap-5 sm:grid-cols-2" x-data="{ show: false }">
            <div>
                <label for="password" class="mb-1.5 block text-sm font-semibold text-miseku-800">Password</label>
                <div class="relative">
                    <input id="password" :type="show ? 'text' : 'password'" wire:model.blur="password"
                           autocomplete="new-password" placeholder="Minimal 8 karakter"
                           @class(['ms-input pr-11', 'border-red-300 focus:border-red-400 focus:ring-red-100' => $errors->has('password')])>
                    <button type="button" @click="show = !show" tabindex="-1"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-miseku-400 hover:text-miseku-600">
                        <svg x-show="!show" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.04 12.32a1.01 1.01 0 0 1 0-.64C3.42 7.51 7.36 4.5 12 4.5c4.64 0 8.57 3.01 9.96 7.18.07.21.07.43 0 .64C20.58 16.49 16.64 19.5 12 19.5c-4.64 0-8.57-3.01-9.96-7.18Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        </svg>
                        <svg x-show="show" x-cloak class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.22A10.48 10.48 0 0 0 2.04 11.68a1.01 1.01 0 0 0 0 .64C3.42 16.49 7.36 19.5 12 19.5c.99 0 1.95-.14 2.86-.4M6.23 6.23A10.45 10.45 0 0 1 12 4.5c4.64 0 8.57 3.01 9.96 7.18.07.21.07.43 0 .64a10.53 10.53 0 0 1-4.13 5.41M6.23 6.23 3 3m3.23 3.23 3.65 3.65m7.89 7.89L21 21m-3.23-3.23-3.65-3.65m0 0a3 3 0 1 0-4.24-4.24m4.24 4.24L9.88 9.88" />
                        </svg>
                    </button>
                </div>
                @error('password') <p class="mt-1.5 text-xs font-medium text-red-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password_confirmation" class="mb-1.5 block text-sm font-semibold text-miseku-800">
                    Ulangi password
                </label>
                <input id="password_confirmation" :type="show ? 'text' : 'password'"
                       wire:model.blur="password_confirmation" autocomplete="new-password" placeholder="••••••••"
                       class="ms-input">
            </div>
        </div>

        {{-- ============ Persetujuan ============ --}}
        <div>
            <label class="flex cursor-pointer items-start gap-2.5 text-sm text-miseku-600">
                <input type="checkbox" wire:model="agree"
                       class="mt-0.5 h-4 w-4 shrink-0 rounded border-miseku-300 text-miseku-500 focus:ring-miseku-200">
                <span>
                    Saya setuju dengan ketentuan layanan Miseku, termasuk potongan biaya platform
                    yang berlaku untuk penjual.
                </span>
            </label>
            @error('agree') <p class="mt-1.5 text-xs font-medium text-red-500">{{ $message }}</p> @enderror
        </div>

        <button type="submit" class="ms-btn-primary w-full !py-3" wire:loading.attr="disabled">
            <svg wire:loading wire:target="register" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.4 0 0 5.4 0 12h4Z"/>
            </svg>
            <span wire:loading.remove wire:target="register">
                {{ $role === 'artist' ? 'Daftar & buka toko' : 'Daftar sekarang' }}
            </span>
            <span wire:loading wire:target="register">Menyimpan…</span>
        </button>
    </form>
</div>
