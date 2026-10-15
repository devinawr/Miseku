<div>
    <span class="ms-chip bg-brand-50 text-brand-700"><x-icon name="sparkles" class="h-3.5 w-3.5" /> Gratis, 1 menit</span>
    <h1 class="mt-3 text-3xl font-extrabold tracking-tight text-ink-900">Buat akun Miseku</h1>
    <p class="mt-2 text-sm text-ink-500">
        Sudah punya akun?
        <a href="{{ route('login') }}" wire:navigate class="font-bold text-brand-600 hover:text-accent-600">Masuk</a>
    </p>

    <form wire:submit="register" class="mt-7 space-y-5">
        {{-- Pendaftaran selalu sebagai pembeli. Toko dibuka belakangan dari Akun → Buka toko (seperti Shopee). --}}
        <div class="flex items-start gap-3 rounded-2xl border border-brand-100 bg-brand-50 p-3.5">
            <span class="ms-gradient flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-white"><x-icon name="heart" class="h-5 w-5" /></span>
            <p class="text-xs leading-relaxed text-ink-600">
                <span class="block text-sm font-extrabold text-ink-900">Daftar sebagai pembeli</span>
                Mau jualan merch karyamu? Setelah daftar, buka toko kapan saja dari <b>Akun → Buka toko</b>.
            </p>
        </div>

        <div>
            <label for="name" class="ms-label">Nama lengkap</label>
            <input id="name" type="text" wire:model.blur="name" autocomplete="name" placeholder="Devina Wongkar"
                   @class(['ms-input', '!border-red-300' => $errors->has('name')])>
            @error('name') <p class="ms-error">{{ $message }}</p> @enderror
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="email" class="ms-label">Email</label>
                <input id="email" type="email" wire:model.blur="email" autocomplete="email" placeholder="nama@email.com"
                       @class(['ms-input', '!border-red-300' => $errors->has('email')])>
                @error('email') <p class="ms-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="phone" class="ms-label">No. HP <span class="font-normal text-ink-400">(opsional)</span></label>
                <input id="phone" type="tel" wire:model.blur="phone" autocomplete="tel" placeholder="08xxxxxxxxxx" class="ms-input">
                @error('phone') <p class="ms-error">{{ $message }}</p> @enderror
            </div>
        </div>


        <div class="grid gap-5 sm:grid-cols-2" x-data="{ show: false }">
            <div>
                <label for="password" class="ms-label">Password</label>
                <div class="relative">
                    <input id="password" :type="show ? 'text' : 'password'" wire:model.blur="password" autocomplete="new-password"
                           placeholder="Min. 8 karakter" @class(['ms-input pr-11', '!border-red-300' => $errors->has('password')])>
                    <button type="button" @click="show = !show" tabindex="-1" class="absolute top-1/2 right-3 -translate-y-1/2 text-ink-400 hover:text-brand-600">
                        <x-icon name="eye" class="h-5 w-5" x-show="!show" />
                        <x-icon name="eye-off" class="h-5 w-5" x-show="show" x-cloak />
                    </button>
                </div>
                @error('password') <p class="ms-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="password_confirmation" class="ms-label">Ulangi password</label>
                <input id="password_confirmation" :type="show ? 'text' : 'password'" wire:model.blur="password_confirmation"
                       autocomplete="new-password" placeholder="••••••••" class="ms-input">
            </div>
        </div>

        <div>
            <label class="flex cursor-pointer items-start gap-2.5 text-sm text-ink-600">
                <input type="checkbox" wire:model="agree" class="ms-check mt-0.5">
                <span>Saya menyetujui ketentuan layanan & kebijakan privasi Miseku.</span>
            </label>
            @error('agree') <p class="ms-error">{{ $message }}</p> @enderror
        </div>

        <button type="submit" class="ms-btn-primary w-full !py-3 text-base" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="register">Daftar sekarang</span>
            <span wire:loading wire:target="register">Menyimpan…</span>
        </button>

        <p class="text-center text-xs text-ink-400">Setelah daftar, kamu akan diminta memilih fandom favorit supaya rekomendasi langsung pas.</p>
    </form>
</div>
