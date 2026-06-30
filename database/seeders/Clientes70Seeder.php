<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Persona;
use App\Models\User;
use App\Models\Cliente;

class Clientes70Seeder extends Seeder
{
    public function run()
    {
        $faker = \Faker\Factory::create('es_ES'); // nombres en español
        // Si quieres especificar otro locale: 'es_PE' o 'es_MX'

        // Opcional: si quieres limpiar tablas antes de insertar (descomenta si lo deseas)
        // DB::table('clientes')->truncate();
        // DB::table('users')->truncate();
        // DB::table('personas')->truncate();

        for ($i = 1; $i <= 70; $i++) {
            DB::transaction(function () use ($faker, $i) {

                // Generar nombre y apellidos únicos (por seguridad añadimos el índice)
                $firstName = $faker->firstName;
                $lastName1  = $faker->lastName;
                $lastName2  = $faker->lastName;
                $fullName   = trim("{$firstName} {$lastName1}");

                // CI único (9 dígitos)
                $ci = $faker->unique()->numerify('#########');

                // Teléfono único (formato similar a tus ejemplos: 72xxxxxx -> 8 dígitos)
                $telefono = $faker->unique()->numerify('72######');

                // Email único en dominio gmail
                $username = Str::slug("{$firstName}.{$lastName1}") . $i;
                $email = strtolower($username) . '@gmail.com';

                // Usuario
                $user = User::create([
                    'name' => $username,
                    'email' => $email,
                    'password' => Hash::make('password123'), // password por defecto
                    'rol_id' => 1, // asumo rol 1 = cliente; cambia si corresponde
                ]);

                // Persona
                $persona = Persona::create([
                    'nombre_completo'  => $firstName,
                    'apellido_paterno' => $lastName1,
                    'apellido_materno' => $lastName2,
                    'ci'               => $ci,
                    'telefono'         => $telefono,
                    'genero'           => $faker->randomElement(['masculino','femenino','otro']),
                    'fecha_nacimiento' => $faker->date('Y-m-d', '2002-01-01'), // mayor de edad por defecto
                ]);

                // Cliente
                Cliente::create([
                    'id_persona'     => $persona->id_persona,
                    'id_usuario'     => $user->id,
                    'dias_asistidos' => $faker->numberBetween(0, 60),
                    'huella_digital' => null,
                    'estado'         => $faker->randomElement(['activo','inactivo']),
                ]);
            });
        }
    }
}
