<?php

namespace App\Providers;

use App\Models\Fandom;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // satu instance per request → hasil penentuan tema di-cache
        $this->app->scoped(\App\Services\ThemeService::class);
    }

    public function boot(): void
    {
        // Supaya translatedFormat() menghasilkan "29 September 2026", bukan bahasa Inggris.
        Carbon::setLocale(config('app.locale'));

        // Baris pintasan fandom di header (dipakai semua halaman publik).
        View::composer('components.layouts.app', function ($view) {
            static $fandoms = null;

            $fandoms ??= Fandom::active()->orderBy('fandom_name')->get(['id', 'fandom_name']);

            $view->with('navFandoms', $fandoms);
        });
    }
}
