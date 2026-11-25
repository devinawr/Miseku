<?php

namespace App\Livewire;

use App\Models\Address;
use App\Models\ArtistProfile;
use App\Models\ArtistApplication;
use App\Services\NotificationService;
use App\Models\Fandom;
use App\Models\FileUpload;
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
 *  profil     : nama, no. HP, foto profil (unggah / ganti / hapus — semua peran)
 *  keamanan   : ganti kata sandi
 *  fandom     : kelola fandom favorit (favorite_fandom)
 *  aktivitas  : produk terakhir dilihat + profil minat CBF (transparansi rekomendasi)
 *  alamat     : buku alamat pengiriman (kota RajaOngkir / daftar lokal)
 *  toko       : buka toko (pembeli → artis) / ubah nama toko & rekening pencairan, banner toko (unggah / ganti / hapus)
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

    // banner toko (khusus artis)
    public $banner = null;

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
    public string $cityProvinceId = '';
    public string $cityPlaceId = '';
    public string $cityProvinceName = '';
    public int $cityPage = 0;
    public string $postal_code = '';
    public bool $is_primary = false;

    // toko
    public string $shop_name = '';
    public string $shop_description = '';
    public string $social_url = '';
    public string $bank_name = '';
    public string $bank_account_number = '';
    // alamat toko (asal pengiriman) — diisi sendiri, terpisah dari buku alamat pembeli
    public string $shop_address = '';
    public string $shop_city_id = '';
    public string $shop_city_label = '';
    public string $shopCitySearch = '';
    public string $shopProvinceId = '';
    public string $shopPlaceId = '';
    public string $shopProvinceName = '';
    public int $shopCityPage = 0;
    public string $shop_postal_code = '';

    public function mount(): void
    {
        if (! in_array($this->tab, ['profil', 'keamanan', 'fandom', 'aktivitas', 'alamat', 'toko', 'tampilan'], true)) {
            $this->tab = 'profil';
        }
        if (Auth::user()->isAdmin() && in_array($this->tab, ['fandom', 'aktivitas', 'alamat', 'toko'], true)) {
            $this->tab = 'profil';
        }
        // Mode Studio: tab belanja (fandom, minat, alamat pengiriman) tidak ditampilkan
        if (\App\Support\StudioMode::active() && in_array($this->tab, ['fandom', 'aktivitas', 'alamat'], true)) {
            $this->tab = 'toko';
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
            $this->shopProvinceName = (string) $sa->province;
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

    /** Foto langsung disimpan begitu dipilih (unggah baru atau ganti), tanpa perlu klik Simpan profil. */
    public function updatedPhoto(): void
    {
        $this->validate(['photo' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048']], [], ['photo' => 'foto profil']);

        $user = Auth::user();
        $had = (bool) $user->profile_photo;
        $old = $user->profile_photo;
        $user->update(['profile_photo' => '/uploads/' . $this->photo->store('avatars', 'uploads')]);
        $this->deleteOwnPhoto($old);

        $this->photo = null;
        unset($this->user);
        $this->dispatch('toast', message: $had ? 'Foto profil diganti.' : 'Foto profil diunggah.');
    }

    public function saveProfile(): void
    {
        $data = $this->validate([
            'name'  => ['required', 'string', 'min:3', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s]{8,20}$/'],
        ], ['phone.regex' => 'Nomor HP hanya boleh angka (8–20 digit).'], ['name' => 'nama', 'phone' => 'nomor HP']);

        Auth::user()->update(['name' => trim($data['name']), 'phone' => $data['phone'] ?: null]);
        unset($this->user);
        $this->dispatch('toast', message: 'Profil disimpan.');
    }

    public function removePhoto(): void
    {
        $user = Auth::user();
        if (! $user->profile_photo) {
            return;
        }
        $this->deleteOwnPhoto($user->profile_photo);
        $user->update(['profile_photo' => null]);
        unset($this->user);
        $this->dispatch('toast', message: 'Foto profil dihapus.');
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
    public function provinceOptions(): array
    {
        return app(ShippingService::class)->regionOptions('province');
    }

    #[Computed]
    public function cityPlaceOptions(): array
    {
        return $this->cityProvinceId !== '' ? app(ShippingService::class)->regionOptions('city', $this->cityProvinceId) : ['items' => [], 'error' => false];
    }

    #[Computed]
    public function shopPlaceOptions(): array
    {
        return $this->shopProvinceId !== '' ? app(ShippingService::class)->regionOptions('city', $this->shopProvinceId) : ['items' => [], 'error' => false];
    }

    #[Computed]
    public function cityResults(): array
    {
        return app(ShippingService::class)->lookupCities($this->citySearch, 25, $this->cityPage * 25);
    }

    #[Computed]
    public function shopCityResults(): array
    {
        return app(ShippingService::class)->lookupCities($this->shopCitySearch, 25, $this->shopCityPage * 25);
    }

    public function updatedCityProvinceId(): void
    {
        $this->cityPlaceId = $this->city_id = $this->city_label = $this->citySearch = $this->cityProvinceName = '';
        $this->cityPage = 0;
        $this->postal_code = '';
        foreach ($this->provinceOptions['items'] as $row) if ($row['id'] === $this->cityProvinceId) $this->cityProvinceName = $row['name'];
    }

    public function updatedShopProvinceId(): void
    {
        $this->shopPlaceId = $this->shop_city_id = $this->shop_city_label = $this->shopCitySearch = $this->shopProvinceName = '';
        $this->shopCityPage = 0;
        $this->shop_postal_code = '';
        foreach ($this->provinceOptions['items'] as $row) if ($row['id'] === $this->shopProvinceId) $this->shopProvinceName = $row['name'];
    }

    public function updatedCityPlaceId(): void
    {
        $this->city_id = $this->city_label = $this->citySearch = '';
        $this->cityPage = 0;
        $this->postal_code = '';
        foreach ($this->cityPlaceOptions['items'] as $row) {
            if ($row['id'] !== $this->cityPlaceId) continue;
            if (app(ShippingService::class)->isLive()) $this->citySearch = $row['name'];
            else $this->pickCity($row['id'], $row['name'], $this->cityProvinceName);
            break;
        }
    }

    public function updatedShopPlaceId(): void
    {
        $this->shop_city_id = $this->shop_city_label = $this->shopCitySearch = '';
        $this->shopCityPage = 0;
        $this->shop_postal_code = '';
        foreach ($this->shopPlaceOptions['items'] as $row) {
            if ($row['id'] !== $this->shopPlaceId) continue;
            if (app(ShippingService::class)->isLive()) $this->shopCitySearch = $row['name'];
            else $this->pickShopCity($row['id'], $row['name'], $this->shopProvinceName);
            break;
        }
    }

    public function updatedCitySearch(): void
    {
        $this->city_id = $this->city_label = '';
        $this->cityPage = 0;
    }

    public function updatedShopCitySearch(): void
    {
        $this->shop_city_id = $this->shop_city_label = '';
        $this->shopCityPage = 0;
    }

    /** Ganti tujuan: jangan biarkan ID atau kode pos pilihan lama tersimpan. */
    public function changeSelectedLocation(bool $shop = false): void
    {
        if ($shop) {
            $this->shop_city_id = $this->shop_city_label = $this->shopCitySearch = $this->shop_postal_code = '';
            $this->shopCityPage = 0;
        } else {
            $this->city_id = $this->city_label = $this->citySearch = $this->postal_code = '';
            $this->cityPage = 0;
        }
    }

    public function changeCityPage(int $delta, bool $shop = false): void
    {
        if ($shop) $this->shopCityPage = max(0, $this->shopCityPage + $delta);
        else $this->cityPage = max(0, $this->cityPage + $delta);
    }

    public function pickShopCity(string $id, string $label, string $province, string $zip = '', string $city = ''): void
    {
        $this->shop_city_id = $id;
        $this->shop_city_label = $label . ($province && ! str_contains(mb_strtolower($label), mb_strtolower($province)) ? ', ' . $province : '');
        $this->shopProvinceName = $province;
        $this->shop_postal_code = $this->postalFromLocation($zip, $label);
        $this->syncLocationSelectors($province, $city, true);
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
        $this->cityProvinceId = $this->cityPlaceId = $this->cityProvinceName = '';
        $this->cityPage = 0;
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
        $this->cityProvinceName = (string) $a->province;
        $this->cityProvinceId = $this->cityPlaceId = '';
        $this->cityPage = 0;
        $this->city_label = $a->city_name . ', ' . $a->province;
        $this->citySearch = '';
        $this->postal_code = (string) $a->postal_code;
        $this->is_primary = $a->is_primary;
        $this->addressForm = true;
    }

    public function pickCity(string $id, string $label, string $province, string $zip = '', string $city = ''): void
    {
        $this->city_id = $id;
        $this->city_label = $label . ($province && ! str_contains(mb_strtolower($label), mb_strtolower($province)) ? ', ' . $province : '');
        $this->cityProvinceName = $province;
        $this->postal_code = $this->postalFromLocation($zip, $label);
        $this->syncLocationSelectors($province, $city, false);
        $this->citySearch = '';
    }

    /** Only fill a postal code supplied by the chosen destination, never guess one. */
    private function postalFromLocation(string $zip, string $label): string
    {
        $zip = trim($zip);
        if (preg_match('/^\d{5}$/', $zip)) return $zip;
        return preg_match('/(?<!\d)(\d{5})\s*$/u', $label, $match) ? $match[1] : '';
    }

    /** Synchronise dropdowns only when the API's names can be matched unambiguously. */
    private function syncLocationSelectors(string $province, string $city, bool $shop): void
    {
        if (! app(ShippingService::class)->isLive() || trim($province) === '') return;
        $norm = fn (string $s) => mb_strtoupper(trim(preg_replace('/\s+/u', ' ', $s)));
        $provinces = collect($this->provinceOptions['items'])->filter(fn ($p) => $norm($p['name']) === $norm($province))->values();
        $provinceId = $provinces->count() === 1 ? (string) $provinces[0]['id'] : '';
        $placeId = '';
        if ($provinceId !== '' && trim($city) !== '') {
            $places = collect(app(ShippingService::class)->regionOptions('city', $provinceId)['items']);
            $matches = $places->filter(fn ($p) => $norm($p['name']) === $norm($city))->values();
            if ($matches->isEmpty()) {
                $short = fn (string $s) => preg_replace('/^(KOTA|KABUPATEN|KAB\.)\s+/u', '', $norm($s));
                $matches = $places->filter(fn ($p) => $short($p['name']) === $short($city))->values();
            }
            if ($matches->count() === 1) $placeId = (string) $matches[0]['id'];
        }
        if ($shop) {
            $this->shopProvinceId = $provinceId;
            $this->shopPlaceId = $placeId;
            unset($this->shopPlaceOptions);
        } else {
            $this->cityProvinceId = $provinceId;
            $this->cityPlaceId = $placeId;
            unset($this->cityPlaceOptions);
        }
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
        $province = $this->cityProvinceName ?: ($shipping->provinceOf($data['city_id']) ?? trim((string) strrchr($this->city_label, ','), ', '));

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

    /**
     * Banner toko disimpan di file_upload (source_table = 'artist_profile', source_id = id toko).
     * Unggah / ganti langsung tersimpan begitu file dipilih; file lama di public/uploads ikut dihapus.
     */
    public function updatedBanner(): void
    {
        $shop = Auth::user()->artistProfile;
        abort_unless($shop, 403);

        $this->validate(['banner' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:3072', 'dimensions:min_width=800,min_height=200']], [
            'banner.dimensions' => 'Ukuran banner minimal 800×200 piksel (disarankan 1500×500).',
        ], ['banner' => 'banner toko']);

        $url = '/uploads/' . $this->banner->store('banners', 'uploads');
        $current = $this->shopBanner($shop);

        if ($current) {
            $old = $current->url;
            $current->update(['url' => $url, 'uploaded_by' => Auth::id()]);
            $this->deleteOwnPhoto($old);
        } else {
            FileUpload::create([
                'url' => $url, 'source_table' => 'artist_profile', 'source_id' => $shop->id,
                'uploaded_by' => Auth::id(), 'sort_order' => 1,
            ]);
        }

        $this->banner = null;
        unset($this->user);
        $this->dispatch('toast', message: $current ? 'Banner toko diganti.' : 'Banner toko diunggah.');
    }

    public function removeBanner(): void
    {
        $shop = Auth::user()->artistProfile;
        abort_unless($shop, 403);

        FileUpload::where('source_table', 'artist_profile')->where('source_id', $shop->id)->get()
            ->each(function (FileUpload $f) {
                $this->deleteOwnPhoto($f->url);
                $f->delete();
            });

        unset($this->user);
        $this->dispatch('toast', message: 'Banner toko dihapus. Toko memakai banner bawaan.');
    }

    private function shopBanner(?ArtistProfile $shop): ?FileUpload
    {
        return $shop ? FileUpload::where('source_table', 'artist_profile')->where('source_id', $shop->id)->orderBy('sort_order')->first() : null;
    }


    public function saveShop(): void
    {
        $user = Auth::user();
        abort_if($user->isAdmin(), 403);

        $data = $this->validate([
            'shop_name'           => ['required', 'string', 'min:3', 'max:100', Rule::unique('artist_profile', 'shop_name')->ignore($user->artistProfile?->id)],
            'shop_description'    => ['nullable', 'string', 'max:1000'],
            'social_url'          => [$user->artistProfile ? 'nullable' : 'required', 'url:http,https', 'max:500'],
            'bank_name'           => ['nullable', 'string', 'max:50'],
            'bank_account_number' => ['nullable', 'string', 'max:30', 'regex:/^[0-9\-\s]{5,30}$/'],
            'shop_address'        => ['required', 'string', 'min:10', 'max:500'],
            'shop_city_id'        => ['required', 'string', 'max:20'],
            'shop_postal_code'    => ['nullable', 'string', 'max:10'],
        ], [
            'bank_account_number.regex' => 'Nomor rekening hanya angka.',
            'shop_city_id.required'     => 'Pilih kota/kecamatan toko dari hasil pencarian.',
        ], ['shop_name' => 'nama toko', 'bank_name' => 'nama bank', 'bank_account_number' => 'nomor rekening', 'shop_address' => 'alamat toko', 'shop_postal_code' => 'kode pos']);

        $province = $this->shopProvinceName ?: (app(ShippingService::class)->provinceOf($data['shop_city_id']) ?? trim((string) strrchr($this->shop_city_label, ','), ', '));
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

        DB::transaction(function () use ($user, $values, $data, $province) {
            $user = \App\Models\User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            abort_if($user->artistProfile()->exists(), 422);
            $application = ArtistApplication::where('user_id', $user->id)->lockForUpdate()->first();
            if ($application?->status === 'pending') {
                \App\Support\Fail::because('Pengajuan toko masih menunggu admin.');
            }
            $payload = $values + ['user_id'=>$user->id, 'social_url'=>$data['social_url'],
                'shop_address'=>$data['shop_address'], 'shop_city_id'=>$data['shop_city_id'],
                'shop_province'=>mb_substr($province ?: '-', 0, 80), 'shop_postal_code'=>$data['shop_postal_code'] ?: null,
                'status'=>'pending', 'reviewed_by'=>null, 'reviewed_at'=>null, 'review_note'=>null];
            if ($application) $application->update($payload); else ArtistApplication::create($payload);
            app(NotificationService::class)->toAdmins('order', 'Pengajuan toko artis', "{$user->name} mengajukan toko {$values['shop_name']}. Periksa identitas dan akun media sosial di Admin → Pengguna.");
        });
        $this->dispatch('toast', message: 'Pengajuan terkirim. Toko aktif setelah disetujui admin.');

    }

    public function render()
    {
        return view('livewire.account', [
            'shopBanner' => $this->shopBanner(Auth::user()->artistProfile),
            'shopApplication' => ArtistApplication::where('user_id', Auth::id())->first(),
        ]);
    }
}
