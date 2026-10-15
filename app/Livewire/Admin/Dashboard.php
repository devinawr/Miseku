<?php

namespace App\Livewire\Admin;

use App\Models\ArtistProfile;
use App\Models\CustomerOrder;
use App\Models\Fandom;
use App\Models\FandomCharacter;
use App\Models\MerchandiseType;
use App\Models\Payment;
use App\Models\Product;
use App\Models\TrackTransaksi;
use App\Models\User;
use App\Models\Withdrawal;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/** Ringkasan admin: angka utama + antrean tugas + aktivitas terbaru. */
#[Layout('components.layouts.app')]
#[Title('Admin')]
class Dashboard extends Component
{
    public function render()
    {
        $completed = CustomerOrder::where('order_status', 'completed')->where('order_type', '!=', 'ots_event');

        return view('livewire.admin.dashboard', [
            'stats' => [
                ['Pengguna aktif', User::where('is_active', true)->count(), 'users'],
                ['Toko artis', ArtistProfile::count(), 'store'],
                ['Produk tayang', Product::where('product_status', 'active')->count(), 'box'],
                ['Pesanan berjalan', CustomerOrder::whereIn('order_status', CustomerOrder::ACTIVE)->count(), 'receipt'],
                ['GMV pesanan selesai', \App\Support\Money::rp((clone $completed)->sum('total_amount')), 'chart'],
                ['Pendapatan admin fee', \App\Support\Money::rp((clone $completed)->sum('admin_fee')), 'wallet'],
                ['Dana tertahan (escrow)', \App\Support\Money::rp(ArtistProfile::sum('held_balance')), 'lock'],
                ['Saldo tersedia artis', \App\Support\Money::rp(ArtistProfile::sum('available_balance')), 'wallet'],
            ],
            'tasks' => [
                ['Usulan master data', Fandom::where('status', 'pending')->count() + FandomCharacter::where('status', 'pending')->count() + MerchandiseType::where('status', 'pending')->count(), route('admin.approvals')],
                ['Penarikan dana menunggu', Withdrawal::whereIn('withdrawal_status', ['submitted', 'processing'])->count(), route('admin.withdrawals')],
                ['Refund manual menunggu', Payment::where('payment_type', 'refund')->where('payment_status', 'pending')->count(), route('admin.transactions', ['tab' => 'refund'])],
                ['Tagihan pending', Payment::where('payment_type', '!=', 'refund')->where('payment_status', 'pending')->count(), route('admin.transactions', ['tab' => 'pembayaran'])],
            ],
            'activity' => TrackTransaksi::with('user')->latest('id')->take(12)->get(),
        ]);
    }
}
