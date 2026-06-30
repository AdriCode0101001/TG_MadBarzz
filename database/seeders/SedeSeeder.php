<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SedeSeeder extends Seeder
{
    public function run(): void
    {
        // insertOrIgnore: si la migración ya insertó la fila, no duplica.
        DB::table('sedes')->insertOrIgnore([
            'id_sede'    => 1,
            'nombre'     => 'Sede Principal',
            'direccion'  => null,
            'telefono'   => null,
            'activo'     => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
