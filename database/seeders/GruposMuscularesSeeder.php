<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GruposMuscularesSeeder extends Seeder
{
    public function run(): void
    {
        $nombres = [
            'Pecho y tríceps',
            'Espalda y bíceps',
            'Pierna',
            'Hombro',
            'Full body',
        ];

        foreach ($nombres as $nombre) {
            DB::table('grupos_musculares')->insert([
                'nombre'        => $nombre,
                'activo'        => true,
                'es_full_body'  => $nombre === 'Full body',
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        }
    }
}
