<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * Factory user disesuaikan dengan ERD TA: tabel `user`, kolom role/phone/is_active,
 * tanpa email_verified_at dan remember_token.
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name'      => fake()->name(),
            'email'     => fake()->unique()->safeEmail(),
            'password'  => static::$password ??= Hash::make('password'),
            'phone'     => '08' . fake()->numerify('##########'),
            'role'      => 'buyer',
            'is_active' => true,
        ];
    }

    public function artist(): static
    {
        return $this->state(fn () => ['role' => 'artist']);
    }

    public function admin(): static
    {
        return $this->state(fn () => ['role' => 'admin']);
    }
}
