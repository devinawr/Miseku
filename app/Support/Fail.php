<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;

/** Lempar pesan aturan bisnis yang ramah pengguna (ditangkap Livewire → toast). */
class Fail
{
    public static function because(string $message): never
    {
        throw ValidationException::withMessages(['action' => $message]);
    }

    public static function unless(bool $condition, string $message): void
    {
        if (! $condition) {
            self::because($message);
        }
    }
}
