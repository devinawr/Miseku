<?php

namespace App\Livewire;

use App\Services\ThemeService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Pemilih tema tampilan (skin fandom).
 *  - variant "menu"  → ikon palet di header (dropdown ringkas)
 *  - variant "panel" → tab "Tampilan" di halaman Akun (kartu lengkap + pratinjau)
 *
 * Pilihan: Otomatis (dari fandom favorit) · pilih tema sendiri · Matikan (default Miseku).
 * Setelah disimpan halaman dimuat ulang supaya seluruh warna ikut berganti.
 */
class ThemePicker extends Component
{
    public string $variant = 'menu';

    public function choose(string $mode, ?int $themeId = null): void
    {
        app(ThemeService::class)->set(Auth::user(), $mode, $themeId);

        $this->js('window.location.reload()');
    }

    public function render(ThemeService $themes)
    {
        $user = Auth::user();
        $current = $themes->resolve($user);

        return view('livewire.theme-picker', [
            'current' => $current,
            'sourceLabel' => $themes->sourceLabel($current),
            'autoTheme' => $user ? $themes->autoFor($user) : null,
            'options' => $themes->options($user),
            'favoriteIds' => $user ? $user->favoriteFandoms()->pluck('fandom.id')->all() : [],
            'isGuest' => ! $user,
        ]);
    }
}
