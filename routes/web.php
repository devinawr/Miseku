<?php

use App\Livewire;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rute Miseku
|--------------------------------------------------------------------------
| Semua halaman adalah full-page Livewire component (tanpa controller),
| kecuali webhook Midtrans & logout.
*/

// ---------- Publik ----------
Route::get('/', Livewire\Home::class)->name('home');
Route::get('/rekomendasi', Livewire\Recommendations::class)->name('recommendations');
Route::get('/katalog', Livewire\ProductCatalog::class)->name('catalog');
Route::get('/produk/{product}', Livewire\ProductDetail::class)->name('product.detail');
Route::get('/toko/{artist}', Livewire\ArtistShop::class)->name('artist.shop');
Route::get('/voting', Livewire\Polls\Index::class)->name('polls.index');
Route::get('/voting/{poll}', Livewire\Polls\Show::class)->name('polls.show');
Route::get('/komunitas', Livewire\Community\Index::class)->name('community');
Route::get('/komunitas/fandom/{fandom}', Livewire\Community\FandomPage::class)->name('community.fandom');
Route::get('/komunitas/topik/{topic}', Livewire\Community\TopicPage::class)->name('community.topic');

// ---------- Tamu ----------
Route::get('/masuk', Livewire\Auth\Login::class)->name('login');
Route::get('/daftar', Livewire\Auth\Register::class)->name('register');

// ---------- Webhook Midtrans (tanpa CSRF, diverifikasi signature) ----------
Route::post('/midtrans/notification', function (Request $request, PaymentService $payments) {
    $payload = $request->all();
    abort_unless(app(\App\Services\MidtransGateway::class)->verifySignature($payload), 403, 'Signature tidak valid');
    $payments->handleMidtrans($payload);

    return response()->json(['ok' => true]);
})->name('midtrans.notification');

// ---------- Sudah login (semua peran) ----------
Route::middleware('auth')->group(function () {
    Route::get('/mulai', Livewire\Onboarding::class)->name('onboarding');
    Route::get('/akun', Livewire\Account::class)->name('account');
    Route::get('/keranjang', Livewire\Cart::class)->name('cart');
    Route::get('/checkout', Livewire\Checkout::class)->name('checkout');
    Route::get('/bayar/{payment}', Livewire\PaymentPage::class)->name('payments.show');
    Route::get('/pesanan', Livewire\Orders\Index::class)->name('orders.index');
    Route::get('/pesanan/{order}', Livewire\Orders\Show::class)->name('orders.show');
    Route::get('/notifikasi', Livewire\Notifications::class)->name('notifications');
    Route::get('/chat', Livewire\Chat::class)->name('chat');
    Route::get('/custom', Livewire\Custom\Index::class)->name('custom.index');
    Route::get('/custom/baru', Livewire\Custom\Create::class)->name('custom.create');
    Route::get('/custom/{customRequest}', Livewire\Custom\Show::class)->name('custom.show');

    Route::post('/keluar', function () {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('home');
    })->name('logout');
});

// ---------- Studio artis ----------
Route::middleware(['auth', 'role:artist'])->prefix('studio')->name('studio.')->group(function () {
    Route::get('/', Livewire\Studio\Dashboard::class)->name('dashboard');
    Route::get('/produk/baru', Livewire\Studio\ProductForm::class)->name('product.create');
    Route::get('/produk/{product}/ubah', Livewire\Studio\ProductForm::class)->name('product.edit');
    Route::get('/pesanan', Livewire\Studio\Orders::class)->name('orders');
    Route::get('/pesanan/{order}', Livewire\Studio\OrderShow::class)->name('orders.show');
    Route::get('/batch-po', Livewire\Studio\Batches::class)->name('batches');
    Route::get('/custom', Livewire\Studio\CustomRequests::class)->name('custom');
    Route::get('/voting', Livewire\Studio\Polls::class)->name('polls');
    Route::get('/event', Livewire\Studio\Events::class)->name('events');
    Route::get('/scan', Livewire\Studio\Scan::class)->name('scan');
    Route::get('/ots', Livewire\Studio\Ots::class)->name('ots');
    Route::get('/saldo', Livewire\Studio\Balance::class)->name('balance');
});

// ---------- Admin ----------
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', Livewire\Admin\Dashboard::class)->name('dashboard');
    Route::get('/persetujuan', Livewire\Admin\MasterApproval::class)->name('approvals');
    Route::get('/pengguna', Livewire\Admin\Users::class)->name('users');
    Route::get('/produk', Livewire\Admin\Products::class)->name('products');
    Route::get('/transaksi', Livewire\Admin\Transactions::class)->name('transactions');
    Route::get('/penarikan', Livewire\Admin\Withdrawals::class)->name('withdrawals');
    Route::get('/event', Livewire\Admin\Events::class)->name('events');
    Route::get('/tema', Livewire\Admin\Themes::class)->name('themes');
    Route::get('/pengaturan', Livewire\Admin\Settings::class)->name('settings');
});
