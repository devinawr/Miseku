<?php

namespace App\Livewire;

use App\Models\Address;
use App\Models\ArtistProfile;
use App\Models\Fandom;
use App\Models\Product;
use App\Models\ProductView;
use App\Services\ContentBasedRecommender;
use App\Services\ShippingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Akun saya — 7 tab:
 *  profil     : nama, no. HP, foto profil
 *  keamanan   : ganti kata sandi
 *  fandom     : kelola fandom favorit (favorite_fandom)
 *  aktivitas  : produk terakhir dilihat + profil minat CBF (transparansi rekomendasi)
 *  alamat     : buku alamat pengiriman (kota RajaOngkir / daftar lokal)
 *  toko       : buka toko (pembeli → artis) / ubah nama toko & rekening pencairan
 *  tampilan   : tema fandom (otomatis / pilih sendiri / matikan) → komponen ThemePicker
 */
#[Layout('components.layouts.app')]
#[Title('Akun saya')]
class Account extends Component
{
    use WithFileUploads;

    #[Url(as: 'tab', except: 'profil')]
    public string $tab = 'profil';

    // profil
    public string $name = '';
    public string $phone = '';
    public $photo = null;

    // keamanan
    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    // fandom
    public array $favorites = [];
    public string $fandomFilter = '';

    // alamat
    public ?int $addressId = null;
    public bool $addressForm = false;
    public string $address_label = 'Rumah';
    public string $recipient_name = '';
    public string $full_address = '';
    public string $city_id = '';
    public string $city_label = '';
    public string $citySearch = '';
    public string $postal_code = '';
    public bool $is_primary = false;

    // toko
    public string $shop_name = '';
    public string $shop_description = '';
    public string $bank_name = '';
    public string $bank_account_number = '';
    // alamat toko (asal pengiriman) — diisi sendiri, terpisah dari buku alamat pembeli
    public string $shop_address = '';
    public string $shop_city_id = '';
    public string $shop_city_label = '';
    public string $shopCitySearch = '';
    public string $shop_postal_code = '';

    public function mount(): void
    {
        if (! in_array($this->tab, ['profil', 'keamanan', 'fandom', 'aktivitas', 'alamat', 'toko', 'tampilan'], true)) {
            $this->tab = 'profil';
        }
        if (Auth::user()->isAdmin() && in_array($this->tab, ['fandom', 'aktivitas', 'alamat', 'toko'], true)) {
            $this->tab = 'profil';
        }

        $user = Auth::user();
        $this->name = $user->name;
        $this->phone = (string) $user->phone;
        $this->favorites = $user->favoriteFandoms()->pluck('fandom.id')->map(fn ($id) => (int) $id)->all();

        if ($shop = $user->artistProfile) {
            $this->shop_name = $shop->shop_name;
            $this->shop_description = (string) $shop->shop_description;
            $this->bank_name = (string) $shop->bank_name;
            $this->bank_account_number = (string) $shop->bank_account_number;
        }
        if ($sa = $user->shopAddress) {
            $this->shop_address = $sa->full_address;
            $this->shop_city_id = (string) $sa->rajaongkir_city_id;
            $this->shop_city_label = $sa->city_name . ', ' . $sa->province;
            $this->shop_postal_code = (string) $sa->postal_code;
        }
    }

    #[Computed]
    public function user()
    {
        return Auth::user()->load(['artistProfile']);
    }

    // ---------- Profil ----------

    public function updatedPhoto(): void
    {
        $this->validate(['photo' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048']], [], ['photo' => 'foto profil']);
    }

    public function saveProfile(): void
    {
        $data = $this->validate([
            'name'  => ['required', 'string', 'min:3', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s]{8,20}$/'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], ['phone.regex' => 'Nomor HP hanya boleh angka (8–20 digit).'], ['name' => 'nama', 'phone' => 'nomor HP', 'photo' => 'foto profil']);

        $user = Auth::user();
        $values = ['name' => trim($data['name']), 'phone' => $data['phone'] ?: null];

        if ($this->photo) {
            $this->deleteOwnPhoto($user->profile_photo);
            $values['profile_photo'] = '/uploads/' . $this->photo->store('avatars', 'uploads');
        }

        $user->update($values);
        $this->photo = null;
        unset($this->user);
        $this->dispatch('toast', message: 'Profil disimpan.');
    }

    public function removePhoto(): void
    {
        $user = Auth::user();
        $this->deleteOwnPhoto($user->profile_photo);
        $user->update(['profile_photo' => null]);
        unset($this->user);
    }

    private function deleteOwnPhoto(?string $url): void
    {
        if ($url && str_starts_with($url, '/uploads/')) {
            Storage::disk('uploads')->delete(substr($url, strlen('/uploads/')));
        }
    }

    // ---------- Keamanan ----------

    public function savePassword(): void
    {
        $this->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'confirmed', Password::min(8)->letters()->numbers(), 'different:current_password'],
        ], [
            'current_password.current_password' => 'Kata sandi lama salah.',
            'password.different' => 'Kata sandi baru harus berbeda dari yang lama.',
        ], ['current_password' => 'kata sandi lama', 'password' => 'kata sandi baru']);

        Auth::user()->update(['password' => Hash::make($this->password)]);
        $this->reset(['current_password', 'password', 'password_confirmation']);
        $this->dispatch('toast', message: 'Kata sandi diganti.');
    }

    // ---------- Fandom favorit ----------

    #[Computed]
    public function fandoms()
    {
        return Fandom::active()
            ->when($this->fandomFilter !== '', fn ($q) => $q->where('fandom_name', 'like', '%' . $this->fandomFilter . '%'))
            ->orderBy('fandom_name')->get(['id', 'fandom_name']);
    }

    public function toggleFavorite(int $fandomId): void
    {
        $this->favorites = in_array($fandomId, $this->favorites, true)
            ? array_values(array_diff($this->favorites, [$fandomId]))
            : [...$this->favorites, $fandomId];
    }

    public function saveFavorites(): void
    {
        $this->validate(['favorites.*' => ['integer', 'exists:fandom,id']]);

        $user = Auth::user();
        $existing = $user->favoriteFandoms()->pluck('fandom.id')->all();
        $user->favoriteFandoms()->detach(array_diff($existing, $this->favorites));
        $user->favoriteFandoms()->attach(array_diff($this->favorites, $existing), ['created_at' => now()]);

        $this->dispatch('toast', message: 'Fandom favorit disimpan — rekomendasi ikut diperbarui.');
    }

    // ---------- Aktivitas ----------

    #[Computed]
    public function recentlyViewed()
    {
        $ids = ProductView::query()
            ->where('user_id', Auth::id())
            ->orderByDesc('viewed_at')
            ->limit(40)
            ->pluck('product_id')
            ->unique()
            ->take(8)
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return Product::visible()
            ->with(['primaryImage', 'openBatch', 'artistProfile', 'merchandiseType', 'character.fandom'])
            ->whereIn('id', $ids)
            ->get()
            ->sortBy(fn (Product $p) => $ids->search($p->id))
            ->values();
    }

    #[Computed]
    public function interest(): array
    {
        return app(ContentBasedRecommender::class)->summary(Auth::user());
    }

    // ---------- Alamat ----------

    #[Computed]
    public function addresses()
    {
        return Auth::user()->addresses()->orderByDesc('is_primary')->orderBy('id')->get();
    }

    #[Computed]
    public function cityResults(): array
    {
        return mb_strlen(trim($this->citySearch)) >= 2 ? app(ShippingService::class)->searchCities($this->citySearch, 8) : [];
    }

    #[Computed]
    public function shopCityResults(): array
    {
        return mb_strlen(trim($this->shopCitySearch)) >= 2 ? app(ShippingService::class)->searchCities($this->shopCitySearch, 8) : [];
    }

    public function pickShopCity(string $id, string $label, string $province): void
    {
        $this->shop_city_id = $id;
        $this->shop_city_label = $label . ($province ? ', ' . $province : '');
        $this->shopCitySearch = '';
    }

    public function newAddress(): void
    {
        $this->resetErrorBag();
        $this->addressId = null;
        $this->address_label = 'Rumah';
        $this->recipient_name = Auth::user()->name;
        $this->full_address = $this->postal_code = $this->city_id = $this->city_label = $this->citySearch = '';
        $this->is_primary = $this->addresses->isEmpty();
        $this->addressForm = true;
    }

    public function editAddress(int $id): void
    {
        $a = Auth::user()->addresses()->findOrFail($id);
        $this->resetErrorBag();
        $this->addressId = $a->id;
        $this->address_label = $a->address_label;
        $this->recipient_name = $a->recipient_name;
        $this->full_address = $a->full_address;
        $this->city_id = (string) $a->rajaongkir_city_id;
        $this->city_label = $a->city_name . ', ' . $a->province;
        $this->citySearch = '';
        $this->postal_code = (string) $a->postal_code;
        $this->is_primary = $a->is_primary;
        $this->addressForm = true;
    }

    public function pickCity(string $id, string $label, string $province): void
    {
        $this->city_id = $id;
        $this->city_label = $label . ($province ? ', ' . $province : '');
        $this->citySearch = '';
    }

    public function saveAddress(): void
    {
        $data = $this->validate([
            'address_label'  => ['required', 'string', 'max:50'],
            'recipient_name' => ['required', 'string', 'max:100'],
            'full_address'   => ['required', 'string', 'min:10', 'max:500'],
            'city_id'        => ['required', 'string', 'max:20'],
            'postal_code'    => ['nullable', 'string', 'max:10'],
        ], ['city_id.required' => 'Pilih kota/kecamatan dari hasil pencarian.'], ['address_label' => 'label', 'recipient_name' => 'nama penerima', 'full_address' => 'alamat lengkap']);

        $user = Auth::user();
        $shipping = app(ShippingService::class);
        $province = $shipping->provinceOf($data['city_id']) ?? trim((string) strrchr($this->city_label, ','), ', ');

        DB::transaction(function () use ($user, $data, $province) {
            $primary = $this->is_primary || ! $user->addresses()->whereKeyNot($this->addressId ?? 0)->exists();
            if ($primary) {
                $user->addresses()->update(['is_primary' => false]);
            }
            $values = [
                'address_label' => $data['address_label'], 'recipient_name' => $data['recipient_name'], 'full_address' => $data['full_address'],
                'rajaongkir_city_id' => $data['city_id'], 'province' => mb_substr($province ?: '-', 0, 100), 'postal_code' => $data['postal_code'] ?: null,
                'is_primary' => $primary,
            ];
            $this->addressId ? $user->addresses()->findOrFail($this->addressId)->update($values) : $user->addresses()->create($values);
        });

        $this->addressForm = false;
        unset($this->addresses);
        $this->dispatch('toast', message: 'Alamat disimpan.');
    }

    public function makePrimary(int $id): void
    {
        $user = Auth::user();
        $user->addresses()->findOrFail($id);
        $user->addresses()->update(['is_primary' => false]);
        $user->addresses()->whereKey($id)->update(['is_primary' => true]);
        unset($this->addresses);
    }

    public function deleteAddress(int $id): void
    {
        $a = Auth::user()->addresses()->findOrFail($id);
        if (\App\Models\CustomerOrder::where('address_id', $a->id)->whereIn('order_status', \App\Models\CustomerOrder::ACTIVE)->exists()) {
            $this->dispatch('toast', message: 'Alamat dipakai pesanan yang masih berjalan.', tone: 'error');

            return;
        }
        if (\App\Models\CustomerOrder::where('address_id', $a->id)->exists()) {
            $this->dispatch('toast', message: 'Alamat ini tercatat di riwayat pesanan, jadi tidak dihapus. Ubah saja isinya bila perlu.', tone: 'error');

            return;
        }
        $wasPrimary = $a->is_primary;
        $a->delete();
        if ($wasPrimary) {
            Auth::user()->addresses()->orderBy('id')->limit(1)->update(['is_primary' => true]);
        }
        unset($this->addresses);
        $this->dispatch('toast', message: 'Alamat dihapus.');
    }

    // ---------- Toko ----------

    public function saveShop(): void
    {
        $user = Auth::user();
        abort_if($user->isAdmin(), 403);

        $data = $this->validate([
            'shop_name'           => ['required', 'string', 'min:3', 'max:100', Rule::unique('artist_profile', 'shop_name')->ignore($user->artistProfile?->id)],
            'shop_description'    => ['nullable', 'string', 'max:1000'],
            'bank_name'           => ['nullable', 'string', 'max:50'],
            'bank_account_number' => ['nullable', 'string', 'max:30', 'regex:/^[0-9\-\s]{5,30}$/'],
            'shop_address'        => ['required', 'string', 'min:10', 'max:500'],
            'shop_city_id'        => ['required', 'string', 'max:20'],
            'shop_postal_code'    => ['nullable', 'string', 'max:10'],
        ], [
            'bank_account_number.regex' => 'Nomor rekening hanya angka.',
            'shop_city_id.required'     => 'Pilih kota/kecamatan toko dari hasil pencarian.',
        ], ['shop_name' => 'nama toko', 'bank_name' => 'nama bank', 'bank_account_number' => 'nomor rekening', 'shop_address' => 'alamat toko', 'shop_postal_code' => 'kode pos']);

        $province = app(ShippingService::class)->provinceOf($data['shop_city_id']) ?? trim((string) strrchr($this->shop_city_label, ','), ', ');
        $addressValues = [
            'recipient_name' => trim($data['shop_name']), 'full_address' => $data['shop_address'],
            'rajaongkir_city_id' => $data['shop_city_id'], 'province' => mb_substr($province ?: '-', 0, 80),
            'postal_code' => $data['shop_postal_code'] ?: null, 'is_primary' => false,
        ];
        $saveAddress = fn () => Address::updateOrCreate(['user_id' => $user->id, 'address_label' => Address::SHOP_LABEL], $addressValues);

        $values = [
            'shop_name' => trim($data['shop_name']), 'shop_description' => $data['shop_description'] ?: null,
            'bank_name' => $data['bank_name'] ?: null, 'bank_account_number' => $data['bank_account_number'] ?: null,
        ];

        if ($user->artistProfile) {
            DB::transaction(function () use ($user, $values, $saveAddress) {
                $user->artistProfile->update($values);
                $saveAddress();
            });
            $this->dispatch('toast', message: 'Info toko disimpan.');

            return;
        }

        DB::transaction(function () use ($user, $values, $saveAddress) {
            $saveAddress();
            ArtistProfile::create($values + ['user_id' => $user->id, 'available_balance' => 0, 'held_balance' => 0, 'rating' => 0, 'rating_count' => 0]);
            $user->update(['role' => 'artist']);
        });

        session()->flash('status', 'Toko dibuka! Selamat datang di Studio artis.');
        $this->redirectRoute('studio.dashboard', navigate: false);
    }

    public function render()
    {
        return view('livewire.account');
    }
}
