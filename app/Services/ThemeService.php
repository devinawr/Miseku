<?php

namespace App\Services;

use App\Models\FandomTheming;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Penentu tema tampilan yang dipakai (keputusan survei: "Dua-duanya").
 *
 *  - auto   (default) → tema dari fandom favorit pengguna. Kalau fandom favoritnya punya
 *                       beberapa tema, dipilih bergiliran per hari supaya ada yang baru.
 *  - manual           → tema yang dipilih sendiri (user.active_theming_id).
 *  - off              → tema default Miseku.
 *
 * Tamu disimpan di session (theming_mode / theming_id); tamu mode auto = default Miseku
 * karena belum punya fandom favorit.
 */
class ThemeService
{
    private ?array $resolved = null;

    /** @return array{theme: ?FandomTheming, mode: string, source: ?string} */
    public function current(): array
    {
        return $this->resolved ??= $this->resolve(Auth::user());
    }

    /** @return array{theme: ?FandomTheming, mode: string, source: ?string} */
    public function resolve(?User $user): array
    {
        $mode = $user ? ($user->theming_mode ?: 'auto') : session('theming_mode', 'auto');
        $chosenId = $user ? $user->active_theming_id : session('theming_id');

        if ($mode === 'off') {
            return ['theme' => null, 'mode' => 'off', 'source' => null];
        }

        if ($mode === 'manual' && $chosenId) {
            $theme = FandomTheming::usable()->with('fandom')->find($chosenId);
            if ($theme) {
                return ['theme' => $theme, 'mode' => 'manual', 'source' => 'manual'];
            }
        }

        $theme = $user ? $this->autoFor($user) : null;

        return ['theme' => $theme, 'mode' => $mode === 'manual' ? 'manual' : 'auto', 'source' => $theme ? 'fandom' : null];
    }

    /** Tema otomatis dari fandom favorit (bergiliran per hari bila lebih dari satu). */
    public function autoFor(User $user): ?FandomTheming
    {
        $fandomIds = $user->favoriteFandoms()->pluck('fandom.id');
        if ($fandomIds->isEmpty()) {
            return null;
        }

        $themes = FandomTheming::usable()->with('fandom')->whereIn('fandom_id', $fandomIds)->orderBy('id')->get();
        if ($themes->isEmpty()) {
            return null;
        }

        return $themes[(int) now()->dayOfYear % $themes->count()];
    }

    /** Semua tema yang bisa dipilih, dikelompokkan per fandom (favorit pengguna di atas). */
    public function options(?User $user = null): Collection
    {
        $fav = $user ? $user->favoriteFandoms()->pluck('fandom.id')->all() : [];

        return FandomTheming::usable()->with('fandom')->get()
            ->sortBy(fn ($t) => [in_array($t->fandom_id, $fav, true) ? 0 : 1, $t->fandom->fandom_name, $t->theming_name])
            ->values();
    }

    public function set(?User $user, string $mode, ?int $themeId = null): void
    {
        $mode = in_array($mode, ['auto', 'manual', 'off'], true) ? $mode : 'auto';
        if ($mode === 'manual') {
            $themeId = FandomTheming::usable()->whereKey($themeId)->value('id');
            if (! $themeId) {
                $mode = 'auto';
            }
        } else {
            $themeId = null;
        }

        if ($user) {
            $user->forceFill(['theming_mode' => $mode, 'active_theming_id' => $themeId])->save();
        } else {
            session(['theming_mode' => $mode, 'theming_id' => $themeId]);
        }

        $this->resolved = null;
    }

    public function sourceLabel(array $current): string
    {
        return match (true) {
            $current['mode'] === 'off'       => 'Tema dimatikan — tampilan default Miseku',
            $current['source'] === 'manual'  => 'Dipilih sendiri',
            $current['source'] === 'fandom'  => 'Otomatis dari fandom favoritmu',
            default                          => 'Otomatis — belum ada tema untuk fandom favoritmu, pakai default Miseku',
        };
    }
}