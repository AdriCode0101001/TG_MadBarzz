<?php

namespace Database\Seeders;

use App\Models\Administrador;
use App\Models\Persona;
use App\Models\Rol;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Seeder MÍNIMO y seguro para preparar una base de PRODUCCIÓN nueva desde
 * cero: los 4 roles + una sede + (opcional) el primer Administrador real,
 * con una contraseña generada al azar que se muestra UNA sola vez en la
 * consola (mismo criterio que `php artisan agente:token`) — nunca una
 * contraseña fija en el código.
 *
 * A diferencia de DatabaseSeeder (que corta solo en producción, ver ese
 * archivo), este seeder NO corre automático en ningún lado — se ejecuta a
 * mano, una sola vez, al preparar un servidor nuevo:
 *
 *   php artisan db:seed --class=ProduccionInicialSeeder
 *
 * Sin datos de demo: ningún cliente, entrenador ni recepcionista falso.
 */
class ProduccionInicialSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Administrador', 'Recepcionista', 'Entrenador', 'Cliente'] as $nombre) {
            Rol::firstOrCreate(['nombre' => $nombre]);
        }

        $sede = Sede::firstOrCreate(
            ['nombre' => 'Sede Principal'],
            ['activo' => true]
        );

        $this->command?->info('Roles y sede listos ("Sede Principal").');

        $yaHayAdmin = User::whereHas('rol', fn ($q) => $q->where('nombre', 'Administrador'))->exists();

        if ($yaHayAdmin) {
            $this->command?->info('Ya existe al menos un Administrador — no se crea ninguno nuevo.');

            return;
        }

        if (!$this->command || !$this->command->confirm('¿Crear el primer Administrador ahora?', true)) {
            $this->command?->info('Podés crear el Administrador más tarde con este mismo comando.');

            return;
        }

        $nombreCompleto = $this->command->ask('Nombre completo del Administrador');
        $email          = $this->command->ask('Email de acceso');
        $telefono       = $this->command->ask('Teléfono de contacto (ej. +59171234567)');

        $persona = Persona::create([
            'nombre_completo'  => $nombreCompleto,
            'apellido_paterno' => '-',
            'apellido_materno' => '-',
            'ci'               => 'ADMIN-' . Str::upper(Str::random(6)),
            'telefono'         => $telefono,
            'genero'           => 'otro',
            'fecha_nacimiento' => '1990-01-01',
        ]);

        $passwordGenerada = Str::password(16);

        $usuario = User::create([
            'name'              => Str::slug($nombreCompleto, '.'),
            'email'             => $email,
            'password'          => Hash::make($passwordGenerada),
            'rol_id'            => Rol::where('nombre', 'Administrador')->value('id'),
            'id_sede'           => $sede->id_sede,
            'email_verified_at' => now(),
        ]);

        Administrador::create([
            'id_persona'       => $persona->id_persona,
            'id_usuario'       => $usuario->id,
            'nivel_acceso'     => 'superadmin',
            'area_responsable' => 'Administración General',
            'estado'           => 'activo',
        ]);

        $this->command->newLine();
        $this->command->line('<fg=yellow;options=bold>CONTRASEÑA GENERADA (copiala ahora, no se muestra de nuevo):</>');
        $this->command->line('  ' . $passwordGenerada);
        $this->command->newLine();
        $this->command->info("Administrador \"{$email}\" creado correctamente.");
    }
}
