<?php

namespace App\Livewire\Admin;

use App\Models\ArtistProfile;
use App\Models\PlatformSetting;
use App\Models\User;
use App\Services\AutomationService;
use App\Services\RatingService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Pengaturan platform (riwayat platform_setting: admin fee & penalti rating)
 * + tombol jalankan proses otomatis (sama dengan php artisan miseku:tick).
 */
#[Layout('components.layouts.app')]
#[Title('Pengaturan & otomasi')]
class Settings extends Component
{
    public $fee_percentage;

    public $fee_minimum;

    public $penalty_voluntary;

    public $penalty_settlement_timeout;

    public $penalty_shipping_timeout;

    public string $effective_from = '';

    public ?array $lastRun = null;

    public function mount(): void
    {
        $c = PlatformSetting::current();
        $this->fee_percentage = $c->fee_percentage;
        $this->fee_minimum = $c->fee_minimum;
        $this->penalty_voluntary = $c->penalty_voluntary;
        $this->penalty_settlement_timeout = $c->penalty_settlement_timeout;
        $this->penalty_shipping_timeout = $c->penalty_shipping_timeout;
        $this->effective_from = now()->format('Y-m-d\TH:i');
    }

    public function save(): void
    {
        $data = $this->validate([
            'fee_percentage'             => 'required|numeric|min:0|max:50',
            'fee_minimum'                => 'required|numeric|min:0',
            'penalty_voluntary'          => 'required|numeric|min:0|max:5',
            'penalty_settlement_timeout' => 'required|numeric|min:0|max:5',
            'penalty_shipping_timeout'   => 'required|numeric|min:0|max:5',
            'effective_from'             => 'required|date',
        ]);

        PlatformSetting::create($data + ['changed_by_id' => Auth::id()]);
        $this->recalc();
        $this->dispatch('toast', message: 'Pengaturan baru disimpan (riwayat lama tetap tercatat). Rating dihitung ulang.');
    }

    /** Hitung ulang rating semua toko & pembeli (penalti mengikuti pengaturan terbaru). */
    public function recalc(): void
    {
        $r = app(RatingService::class);
        ArtistProfile::pluck('id')->each(fn ($id) => $r->recalcArtist($id));
        User::where('role', '!=', 'admin')->pluck('id')->each(fn ($id) => $r->recalcBuyer($id));
    }

    public function runAutomation(): void
    {
        $this->lastRun = app(AutomationService::class)->run();
        $this->dispatch('toast', message: 'Proses otomatis selesai dijalankan.');
    }

    public function render()
    {
        return view('livewire.admin.settings', [
            'history' => PlatformSetting::with('changer')->orderByDesc('effective_from')->orderByDesc('id')->get(),
            'current' => PlatformSetting::current(),
        ]);
    }
}