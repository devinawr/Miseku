<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureRole::class,
        ]);

        // Admin hanya di panel admin (tidak ada beranda/belanja)
        $middleware->appendToGroup('web', \App\Http\Middleware\KeepAdminInPanel::class);

        // Webhook Midtrans dipanggil server Midtrans (tanpa token CSRF)
        $middleware->validateCsrfTokens(except: ['midtrans/*']);

        // Tamu yang membuka halaman khusus login diarahkan ke /masuk
        $middleware->redirectGuestsTo(fn () => route('login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
