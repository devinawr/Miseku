<div>
    <span class="ms-chip bg-brand-50 text-brand-700"><x-icon name="sparkles" class="h-3.5 w-3.5" /> Selamat datang kembali</span>
    <h1 class="mt-3 text-3xl font-extrabold tracking-tight text-ink-900">Masuk ke Miseku</h1>
    <p class="mt-2 text-sm text-ink-500">
        Belum punya akun?
        <a href="{{ route('register') }}" wire:navigate class="font-bold text-brand-600 hover:text-accent-600">Daftar gratis</a>
    </p>

    @if (session('status'))
        <div class="mt-5 flex items-start gap-2 rounded-xl bg-brand-50 px-4 py-3 text-sm font-semibold text-brand-800">
            <x-icon name="info" class="mt-0.5 h-4.5 w-4.5 shrink-0" /> {{ session('status') }}
        </div>
    @endif

    <form wire:submit="login" class="mt-8 space-y-5">
        <div>
            <label for="email" class="ms-label">Email</label>
            <input id="email" type="email" wire:model.blur="email" autocomplete="email" autofocus placeholder="nama@email.com"
                   @class(['ms-input', '!border-red-300' => $errors->has('email')])>
            @error('email') <p class="ms-error">{{ $message }}</p> @enderror
        </div>

        <div x-data="{ show: false }">
            <label for="password" class="ms-label">Password</label>
            <div class="relative">
                <input id="password" :type="show ? 'text' : 'password'" wire:model="password" autocomplete="current-password"
                       placeholder="••••••••" @class(['ms-input pr-11', '!border-red-300' => $errors->has('password')])>
                <button type="button" @click="show = !show" tabindex="-1" class="absolute top-1/2 right-3 -translate-y-1/2 text-ink-400 hover:text-brand-600">
                    <x-icon name="eye" class="h-5 w-5" x-show="!show" />
                    <x-icon name="eye-off" class="h-5 w-5" x-show="show" x-cloak />
                </button>
            </div>
            @error('password') <p class="ms-error">{{ $message }}</p> @enderror
        </div>

        <button type="submit" class="ms-btn-primary w-full !py-3 text-base" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="login">Masuk</span>
            <span wire:loading wire:target="login">Memeriksa…</span>
        </button>
    </form>

    {{-- Akun contoh, khusus tahap bimbingan --}}
    <div class="mt-8 rounded-2xl border border-dashed border-ink-300 p-4">
        <p class="text-xs font-extrabold tracking-wide text-ink-400 uppercase">Akun contoh (tahap bimbingan)</p>
        <div class="mt-2 space-y-1 text-xs">
            @foreach ([['buyer1@miseku.test', 'Pembeli'], ['kirara@miseku.test', 'Artis'], ['admin@miseku.test', 'Admin']] as [$mail, $label])
                <button type="button" wire:click="$set('email', '{{ $mail }}')"
                        class="flex w-full items-center justify-between rounded-lg px-2.5 py-2 text-left transition hover:bg-brand-50">
                    <span class="font-mono text-ink-700">{{ $mail }}</span>
                    <span class="ms-chip bg-ink-100 text-ink-600">{{ $label }}</span>
                </button>
            @endforeach
        </div>
        <p class="mt-2 text-xs text-ink-400">Password semuanya <b class="font-mono text-ink-600">password</b>. Klik untuk mengisi email.</p>
    </div>
</div>
