<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * TODOS los seeders llamados acá son de DEMO (clientes falsos,
     * contraseñas fijas 123456/pass1234, 70 clientes de relleno) — pensados
     * SOLO para desarrollo/pruebas. Revisión de producción (bloqueante
     * #4): si alguien corre `php artisan migrate --seed` o `db:seed` en un
     * servidor de producción por costumbre, esto ensuciaría la base real
     * con datos falsos y cuentas inseguras. Por eso se corta ACÁ, antes de
     * llamar a cualquiera de ellos.
     *
     * Para preparar una base de producción nueva (roles + sede + el primer
     * Administrador real, sin nada de demo), usar aparte:
     *   php artisan db:seed --class=ProduccionInicialSeeder
     */
    public function run(): void
    {
        if (app()->environment('production')) {
            $mensaje = 'Los seeders de demo no deben correr en producción. '
                . 'Usá un seeder mínimo dedicado para el admin inicial '
                . '(php artisan db:seed --class=ProduccionInicialSeeder).';

            $this->command?->error($mensaje);

            throw new RuntimeException($mensaje);
        }

        $this->call([
            SedeSeeder::class,        // debe ir primero: otros seeders dependen de id_sede=1
            RolesSeeder::class,
            UsersSeeder::class,
            MembresiasSeeder::class,
            GruposMuscularesSeeder::class,
            ClienteSeeder::class,
            EntrenadorSeeder::class,
            RecepcionistaSeeder::class,
            AdministradorSeeder::class,
            Clientes70Seeder::class
        ]);
    }
}
