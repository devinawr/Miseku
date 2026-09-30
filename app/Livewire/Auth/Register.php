<?php

namespace App\Livewire\Auth;

use App\Models\ArtistProfile;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Modul 4b — Daftar.
 *
 * Satu form untuk dua peran. Memilih "artist" otomatis membuat baris
 * artist_profile (relasi 1:1 dengan user) dalam satu transaksi, sesuai ERD:
 * satu akun = satu toko, tapi akun itu tetap bisa belanja sebagai pembeli.
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

    /** buyer | artist */
    public string $role = 'buyer';

    public string $shop_name = '';
    public string $shop_description = '';

    public bool $agree = false;

    public function mount()
    {
        // Sudah login? tidak perlu lihat halaman ini lagi.
        if (Auth::check()) {
            return $this->redirect(route('account'), navigate: true);
        }

        return null;
    }

    protected function rules(): array
    {
        return [
            'name'             => ['required', 'string', 'min:3', 'max:120'],
            'email'            => ['required', 'email', 'max:150', Rule::unique('user', 'email')],
            'phone'            => ['nullable', 'string', 'max:20'],
            'password'         => ['required', 'confirmed', Password::min(8)],
            'role'             => ['required', Rule::in(['buyer', 'artist'])],
            'shop_name'        => ['nullable', 'required_if:role,artist', 'string', 'max:120', Rule::unique('artist_profile', 'shop_name')],
            'shop_description' => ['nullable', 'string', 'max:1000'],
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
            'shop_name.required_if' => 'Nama toko wajib diisi untuk akun artis.',
            'shop_name.unique'      => 'Nama toko ini sudah dipakai artis lain.',
            'agree.accepted'        => 'Kamu harus menyetujui ketentuan Miseku.',
        ];
    }

    /** Validasi per field saat user pindah input, biar errornya terasa langsung. */
    public function updated(string $property): void
    {
        if (in_array($property, ['agree', 'role'], true)) {
            return;
        }

        $this->validateOnly($property);
    }

    public function register()
    {
        $data = $this->validate();

        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                'name'          => $data['name'],
                'email'         => $data['email'],
                'phone'         => $data['phone'] ?: null,
                'password'      => $data['password'],
                'role'          => $data['role'],
                'profile_photo' => null,
            ]);

            if ($data['role'] === 'artist') {
                ArtistProfile::create([
                    'user_id'          => $user->id,
                    'shop_name'        => $data['shop_name'],
                    'shop_description' => $data['shop_description'] ?: null,
                ]);
            }

            return $user;
        });

        Auth::login($user, remember: true);
        request()->session()->regenerate();

        session()->flash('status', $data['role'] === 'artist'
            ? 'Akun artis berhasil dibuat. Tokomu sudah aktif — modul unggah karya menyusul.'
            : 'Akun berhasil dibuat. Selamat menjelajah katalog Miseku!');

        return $this->redirect(route('account'), navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.register');
    }
}
