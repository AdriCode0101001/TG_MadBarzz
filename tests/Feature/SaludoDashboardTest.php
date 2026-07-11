<?php

namespace Tests\Feature;

use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * El saludo del dashboard del personal muestra el NOMBRE REAL
 * (personas.nombre_completo), no el nombre de usuario.
 */
class SaludoDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_saludo_del_dashboard_muestra_nombre_real_del_personal(): void
    {
        $rol  = Rol::firstOrCreate(['nombre' => 'Recepcionista']);
        $user = User::factory()->create(['rol_id' => $rol->id, 'name' => 'maria.lopez']);

        $personaId = DB::table('personas')->insertGetId([
            'nombre_completo'  => 'María Fernanda',
            'apellido_paterno' => 'López',
            'apellido_materno' => 'Suárez',
            'ci'               => 'CISAL1',
            'telefono'         => '+59171111111',
            'genero'           => 'F',
            'fecha_nacimiento' => '1992-05-10',
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        DB::table('recepcionistas')->insert([
            'id_persona'     => $personaId,
            'id_usuario'     => $user->id,
            'turno'          => 'mañana',
            'punto_atencion' => 'Caja 1',
            'estado'         => 'activo',
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        $this->actingAs($user)
             ->get('/dashboard/index')
             ->assertStatus(200)
             ->assertSee('María Fernanda');
    }

    public function test_sin_persona_vinculada_cae_al_nombre_de_usuario(): void
    {
        $rol  = Rol::firstOrCreate(['nombre' => 'Administrador']);
        $user = User::factory()->create(['rol_id' => $rol->id, 'name' => 'admin.sistema']);

        $this->actingAs($user)
             ->get('/dashboard/index')
             ->assertStatus(200)
             ->assertSee('admin.sistema');
    }
}
