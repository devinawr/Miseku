<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Modul 4b — Daftar.
 *
 * Pendaftaran SELALU sebagai pembeli (role = buyer). Toko dibuka belakangan
 * dari Akun → Buka toko (membuat artist_profile + alamat toko), seperti Shopee.
 */
#[Layout('components.layouts.guest')]
#[Title('Daftar')]
class Register extends Component
{
    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $password = '';
    public string $password_confirmation = '';

    public bool $agree = false;

    public function mount()
    {
        // Sudah login? tidak perlu lihat halaman ini lagi.
        if (Auth::check()) {
            return $this->redirect(route('home'), navigate: true);
        }

        return null;
    }

    protected function rules(): array
    {
        return [
            'name'             => ['required', 'string', 'min:3', 'max:120'],
            'email'            => ['required', 'email', 'max:150', Rule::unique('user', 'email')],
            'phone'            => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\\-\\s]{8,20}$/'],
            'password'         => ['required', 'confirmed', Password::min(8)],
            'agree'            => ['accepted'],
        ];
    }

    protected function messages(): array
    {
        return [
            'name.required'         => 'Nama wajib diisi.',
            'name.min'              => 'Nama minimal 3 karakter.',
            'email.required'        => 'Email wajib diisi.',
            'email.email'           => 'Format email tidak valid.',
            'email.unique'          => 'Email ini sudah terdaftar.',
            'password.required'     => 'Password wajib diisi.',
            'password.confirmed'    => 'Konfirmasi password tidak sama.',
            'password.min'          => 'Password minimal 8 karakter.',
            'phone.regex'           => 'Nomor HP hanya boleh angka (8–20 digit).',
            'agree.accepted'        => 'Kamu harus menyetujui ketentuan Miseku.',
        ];
    }

    /** Validasi per field saat user pindah input, biar errornya terasa langsung. */
    public function updated(string $property): void
    {
        if ($property === 'agree') {
            return;
        }

        $this->validateOnly($property);
    }

    public function register()
    {
        $data = $this->validate();

        $user = User::create([
            'name'          => $data['name'],
            'email'         => $data['email'],
            'phone'         => $data['phone'] ?: null,
            'password'      => $data['password'],
            'role'          => 'buyer',
            'profile_photo' => null,
        ]);

        Auth::login($user);
        request()->session()->regenerate();

        session()->flash('status', 'Akun berhasil dibuat! Satu langkah lagi: pilih fandom favoritmu.');

        // Langkah onboarding: pilih fandom favorit → mengatasi cold start rekomendasi.
        return $this->redirect(route('onboarding'), navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.register');
    }
}
