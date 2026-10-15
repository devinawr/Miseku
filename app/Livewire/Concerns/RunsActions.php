<?php

namespace App\Livewire\Concerns;

use Illuminate\Validation\ValidationException;

/**
 * Jalankan aksi bisnis; pesan aturan (Fail::because) tampil sebagai toast merah,
 * error validasi field biasa tetap tampil di bawah input.
 */
trait RunsActions
{
    protected function attempt(callable $action, ?string $success = null): mixed
    {
        try {
            $result = $action();
        } catch (ValidationException $e) {
            $errors = $e->errors();

            if (isset($errors['action'])) {
                $this->dispatch('toast', message: $errors['action'][0], tone: 'error');

                return false;
            }

            throw $e;
        }

        if ($success) {
            $this->dispatch('toast', message: $success);
        }

        return $result ?? true;
    }
}
