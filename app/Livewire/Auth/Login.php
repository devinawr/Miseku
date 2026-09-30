<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * Modul 4a — Masuk.
 *
 * Memakai guard 'web' bawaan Laravel yang sudah menunjuk ke App\Models\User,
 * jadi tidak perlu konfigurasi tambahan meski nama tabelnya singular (`user`).
 * Ada pembatasan percobaan (5x per email+IP) dan pemeriksaan kolom is_active
 * sesuai aturan suspend akun di ERD.
 */
#[Layout('components.layouts.guest')]
#[Title('Masuk')]
class Login extends Component
{
    #[Validate('required|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

    public function mount()
    {
        // Sudah login? tidak perlu lihat halaman ini lagi.
        if (Auth::check()) {
            return $this->redirect(route('account'), navigate: true);
        }

        return null;
    }

    protected function messages(): array
    {
        return [
            'email.required'    => 'Email wajib diisi.',
            'email.email'       => 'Format email tidak valid.',
            'password.required' => 'Password wajib diisi.',
        ];
    }

    public function login()
    {
        $this->validate();

        $key = 'masuk:' . Str::lower($this->email) . '|' . request()->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Terlalu banyak percobaan masuk. Coba lagi dalam '
                    . RateLimiter::availableIn($key) . ' detik.',
            ]);
        }

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages([
                'email' => 'Email atau password tidak cocok.',
            ]);
        }

        // Akun yang di-suspend admin tidak boleh masuk, tapi datanya tetap utuh.
        if (! Auth::user()->is_active) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'Akun ini sedang tidak aktif. Hubungi admin Miseku.',
            ]);
        }

        RateLimiter::clear($key);
        request()->session()->regenerate();

        return $this->redirectIntended(route('home'), navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
