<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
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
