<div>
    <h1 class="text-2xl font-extrabold tracking-tight text-miseku-900">Masuk ke Miseku</h1>
    <p class="mt-1.5 text-sm text-miseku-500">
        Belum punya akun?
        <a href="{{ route('register') }}" wire:navigate class="font-semibold text-miseku-600 hover:text-miseku-700">
            Daftar dulu
        </a>
    </p>

    @if (session('status'))
        <div class="mt-5 rounded-xl bg-miseku-100 px-4 py-3 text-sm font-medium text-miseku-700">
            {{ session('status') }}
        </div>
    @endif

    <form wire:submit="login" class="mt-7 space-y-5">
        {{-- Email --}}
        <div>
            <label for="email" class="mb-1.5 block text-sm font-semibold text-miseku-800">Email</label>
            <input id="email" type="email" wire:model.blur="email" autocomplete="email" autofocus
                   placeholder="nama@email.com"
                   @class(['ms-input', 'border-red-300 focus:border-red-400 focus:ring-red-100' => $errors->has('email')])>
            @error('email')
                <p class="mt-1.5 text-xs font-medium text-red-500">{{ $message }}</p>
            @enderror
        </div>

        {{-- Password dengan tombol lihat/sembunyi (Alpine, tanpa request server) --}}
        <div x-data="{ show: false }">
            <label for="password" class="mb-1.5 block text-sm font-semibold text-miseku-800">Password</label>
            <div class="relative">
                <input id="password" :type="show ? 'text' : 'password'" wire:model="password"
                       autocomplete="current-password" placeholder="••••••••"
                       @class(['ms-input pr-11', 'border-red-300 focus:border-red-400 focus:ring-red-100' => $errors->has('password')])>
                <button type="button" @click="show = !show" tabindex="-1"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-miseku-400 hover:text-miseku-600"
                        :aria-label="show ? 'Sembunyikan password' : 'Tampilkan password'">
                    <svg x-show="!show" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.04 12.32a1.01 1.01 0 0 1 0-.64C3.42 7.51 7.36 4.5 12 4.5c4.64 0 8.57 3.01 9.96 7.18.07.21.07.43 0 .64C20.58 16.49 16.64 19.5 12 19.5c-4.64 0-8.57-3.01-9.96-7.18Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                    <svg x-show="show" x-cloak class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.22A10.48 10.48 0 0 0 2.04 11.68a1.01 1.01 0 0 0 0 .64C3.42 16.49 7.36 19.5 12 19.5c.99 0 1.95-.14 2.86-.4M6.23 6.23A10.45 10.45 0 0 1 12 4.5c4.64 0 8.57 3.01 9.96 7.18.07.21.07.43 0 .64a10.53 10.53 0 0 1-4.13 5.41M6.23 6.23 3 3m3.23 3.23 3.65 3.65m7.89 7.89L21 21m-3.23-3.23-3.65-3.65m0 0a3 3 0 1 0-4.24-4.24m4.24 4.24L9.88 9.88" />
                    </svg>
                </button>
            </div>
            @error('password')
                <p class="mt-1.5 text-xs font-medium text-red-500">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center justify-between">
            <label class="flex cursor-pointer items-center gap-2 text-sm text-miseku-600">
                <input type="checkbox" wire:model="remember"
                       class="h-4 w-4 rounded border-miseku-300 text-miseku-500 focus:ring-miseku-200">
                Ingat saya
            </label>
            <span class="cursor-not-allowed text-sm text-miseku-300" title="Modul belum dikerjakan">
                Lupa password?
            </span>
        </div>

        <button type="submit" class="ms-btn-primary w-full !py-3" wire:loading.attr="disabled">
            <svg wire:loading wire:target="login" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.4 0 0 5.4 0 12h4Z"/>
            </svg>
            <span wire:loading.remove wire:target="login">Masuk</span>
            <span wire:loading wire:target="login">Memeriksa…</span>
        </button>
    </form>

    {{-- Akun contoh, khusus tahap bimbingan --}}
    <div class="ms-card mt-8 p-4">
        <p class="text-xs font-bold uppercase tracking-wide text-miseku-400">Akun contoh (tahap bimbingan)</p>
        <div class="mt-3 space-y-2 text-xs">
            @foreach ([
                ['buyer1@miseku.test', 'Pembeli'],
                ['kirara@miseku.test', 'Artis'],
                ['admin@miseku.test', 'Admin'],
            ] as [$mail, $label])
                <button type="button"
                        wire:click="$set('email', '{{ $mail }}')"
                        class="flex w-full items-center justify-between rounded-lg px-2.5 py-2 text-left transition hover:bg-miseku-100">
                    <span class="font-mono text-miseku-700">{{ $mail }}</span>
                    <span class="ms-chip bg-miseku-100 text-miseku-500">{{ $label }}</span>
                </button>
            @endforeach
        </div>
        <p class="mt-3 border-t border-miseku-100 pt-2.5 text-xs text-miseku-400">
            Password semuanya <span class="font-mono font-semibold text-miseku-600">password</span>.
            Klik salah satu untuk mengisi kolom email.
        </p>
    </div>
</div>
