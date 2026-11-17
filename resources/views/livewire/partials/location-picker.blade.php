@php
    $shippingLive = app(\App\Services\ShippingService::class)->isLive();
    $provinceProperty = $shop ? 'shopProvinceId' : 'cityProvinceId';
    $placeProperty = $shop ? 'shopPlaceId' : 'cityPlaceId';
    $searchProperty = $shop ? 'shopCitySearch' : 'citySearch';
    $selectedLabel = $shop ? $shop_city_label : $city_label;
    $selectedProvince = $shop ? $shopProvinceId : $cityProvinceId;
    $selectedPlace = $shop ? $shopPlaceId : $cityPlaceId;
    $searchText = $shop ? $shopCitySearch : $citySearch;
    $page = $shop ? $shopCityPage : $cityPage;
    $places = $shop ? $this->shopPlaceOptions : $this->cityPlaceOptions;
    $results = $shop ? $this->shopCityResults : $this->cityResults;
    $pickMethod = $shop ? 'pickShopCity' : 'pickCity';
    $errorField = $shop ? 'shop_city_id' : 'city_id';
@endphp
<div class="space-y-3">
    @if ($selectedLabel)
        <p class="rounded-lg bg-brand-50 px-3 py-2 text-sm font-semibold text-brand-700">Wilayah terpilih: {{ $selectedLabel }}</p>
    @endif
    <label class="block">
        <span class="ms-label">Provinsi</span>
        <select wire:model.live="{{ $provinceProperty }}" class="ms-input">
            <option value="">Pilih provinsi</option>
            @foreach ($this->provinceOptions['items'] as $province)
                <option value="{{ $province['id'] }}" wire:key="{{ $provinceProperty }}-{{ $province['id'] }}">{{ $province['name'] }}</option>
            @endforeach
        </select>
    </label>
    @if ($this->provinceOptions['error'])
        <p class="text-sm text-red-600">Daftar provinsi dari RajaOngkir tidak tersedia. Periksa API key dan koneksi, lalu muat ulang halaman.</p>
    @endif
    <label class="block">
        <span class="ms-label">Kota / kabupaten</span>
        <select wire:model.live="{{ $placeProperty }}" class="ms-input" @disabled(! $selectedProvince)>
            <option value="">Pilih kota / kabupaten</option>
            @foreach ($places['items'] as $place)
                <option value="{{ $place['id'] }}" wire:key="{{ $placeProperty }}-{{ $place['id'] }}">{{ $place['name'] }}</option>
            @endforeach
        </select>
    </label>
    @if ($places['error'])
        <p class="text-sm text-red-600">Daftar kota dari RajaOngkir tidak tersedia. Coba pilih ulang provinsi atau gunakan pencarian wilayah di bawah.</p>
    @endif
    @if ($shippingLive)
        <div>
            <label class="block"><span class="ms-label">Kecamatan / kelurahan untuk ongkir</span>
                <input type="search" wire:model.live.debounce.500ms="{{ $searchProperty }}" class="ms-input" placeholder="Cari minimal 3 huruf, mis. nama kecamatan">
            </label>
            <p class="mt-1 text-xs text-ink-500">Pilih hasil yang sesuai alamat. Bisa mencari langsung jika daftar provinsi atau kota tidak tersedia.</p>
            <p class="mt-1 text-xs text-ink-500" wire:loading wire:target="{{ $searchProperty }},{{ $placeProperty }},changeCityPage">Mencari wilayah…</p>
            @if (mb_strlen(trim($searchText)) >= 3)
                @if ($results['error'])
                    <p class="mt-2 text-sm text-red-600">Pencarian RajaOngkir gagal. Periksa API key dan koneksi, lalu coba lagi.</p>
                @elseif (count($results['items']))
                    <div class="mt-2 overflow-hidden rounded-xl border border-ink-200 bg-white shadow-sm">
                        <ul class="max-h-56 overflow-y-auto divide-y divide-ink-100">
                            @foreach ($results['items'] as $c)
                                <li><button type="button" wire:click="{{ $pickMethod }}(@js($c['id']), @js($c['label']), @js($c['province']))" class="block w-full px-3 py-2 text-left text-sm hover:bg-brand-50">{{ $c['label'] }}</button></li>
                            @endforeach
                        </ul>
                        @if ($page > 0 || count($results['items']) === 25)
                            <div class="flex justify-between gap-2 border-t border-ink-100 p-2 text-xs font-bold">
                                @if ($page > 0)<button type="button" wire:click="changeCityPage(-1, {{ $shop ? 'true' : 'false' }})" class="text-brand-700">← Sebelumnya</button>@endif
                                @if (count($results['items']) === 25)<button type="button" wire:click="changeCityPage(1, {{ $shop ? 'true' : 'false' }})" class="ml-auto text-brand-700">Berikutnya →</button>@endif
                            </div>
                        @endif
                    </div>
                @else
                    <p class="mt-2 text-sm text-ink-500">Wilayah tidak ditemukan. Coba nama kecamatan atau kelurahan lain.</p>
                @endif
            @endif
        </div>
    @else
        <p class="text-xs text-ink-500">Mode simulasi: hanya kota contoh lokal. Isi API key RajaOngkir untuk pilihan wilayah dari Komerce.</p>
    @endif
    @error($errorField)<p class="ms-error">{{ $message }}</p>@enderror
</div>
