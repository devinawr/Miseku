<?php

use App\Livewire\Account;
use App\Livewire\ArtistShop;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Home;
use App\Livewire\ProductCatalog;
use App\Livewire\ProductDetail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rute Miseku — modul tahap awal
|--------------------------------------------------------------------------
| Semua halaman adalah full-page Livewire component, jadi tidak perlu
| controller maupun view perantara. Parameter rute di-resolve lewat
| route model binding pada method mount() masing-masing komponen.
*/

// ---------- Publik ----------
Route::get('/', Home::class)->name('home');
Route::get('/katalog', ProductCatalog::class)->name('catalog');
Route::get('/produk/{product}', ProductDetail::class)->name('product.detail');
Route::get('/toko/{artist}', ArtistShop::class)->name('artist.shop');

// ---------- Hanya untuk yang belum login ----------
// Pengalihan bagi user yang sudah login ditangani di mount() masing-masing
// komponen, supaya tidak bergantung pada tujuan default middleware 'guest'.
Route::get('/masuk', Login::class)->name('login');
Route::get('/daftar', Register::class)->name('register');

// ---------- Hanya untuk yang sudah login ----------
Route::middleware('auth')->group(function () {
    Route::get('/akun', Account::class)->name('account');

    Route::post('/keluar', function () {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('home');
    })->name('logout');
});
