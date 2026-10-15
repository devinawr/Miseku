<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Livewire\Livewire;

/**
 * Nama rute halaman yang sedang dibuka — tetap benar saat komponen Livewire
 * di-render ulang (request /livewire/update), supaya tab navigasi aktif tidak hilang.
 */
class CurrentRoute
{
    public static function name(): ?string
    {
        if (Livewire::isLivewireRequest()) {
            try {
                return app('router')->getRoutes()->match(Request::create(Livewire::originalUrl()))->getName();
            } catch (\Throwable) {
                return null;
            }
        }

        return request()->route()?->getName();
    }

    public static function is(string ...$patterns): bool
    {
        $name = self::name();

        return $name !== null && Str::is($patterns, $name);
    }
}
