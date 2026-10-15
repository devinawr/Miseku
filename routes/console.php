<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Proses otomatis Miseku (deadline pelunasan/pengiriman, PO tutup, event, voting).
// Jalankan scheduler: php artisan schedule:work
Schedule::command('miseku:tick')->everyFifteenMinutes()->withoutOverlapping();
