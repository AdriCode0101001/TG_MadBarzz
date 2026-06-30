<?php

namespace Database\Factories;

use App\Models\Rol;
use App\Models\Sede;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name'              => fake()->name(),
            'email'             => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password'          => static::$password ??= Hash::make('password'),
            'remember_token'    => Str::random(10),
            // rol_id y id_sede son NOT NULL; se crean los registros si no existen.
            'rol_id'  => Rol::firstOrCreate(['nombre' => 'Administrador'])->id,
            'id_sede' => Sede::firstOrCreate(
                ['nombre' => 'Sede Principal'],
                ['direccion' => null]
            )->id_sede,
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
