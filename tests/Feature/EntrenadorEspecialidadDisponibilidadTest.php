<?php

namespace Tests\Feature;

use App\Models\Entrenador;
use App\Models\Persona;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Revisión general de pantallas: EntrenadorController::edit() reventaba
 * (TypeError de json_decode) para cualquier entrenador cuyas columnas
 * especialidad/disponibilidad tuvieran el array limpio que el propio
 * modelo ya castea — store()/update() las codificaban DE MÁS (comas/JSON)
 * encima del cast 'array' del modelo, y edit() las decodificaba dos veces
 * para compensar. Corregidos los tres a la vez + comando de normalización
 * para los registros viejos.
 */
class EntrenadorEspecialidadDisponibilidadTest extends TestCase
{
    use RefreshDatabase;

    private static int $seq = 0;

    private function admin(): User
    {
        return User::factory()->create(['rol_id' => Rol::firstOrCreate(['nombre' => 'Administrador'])->id]);
    }

    private function payload(array $overrides = []): array
    {
        self::$seq++;
        $s = self::$seq;

        return array_merge([
            'nombre_completo'       => "Entrenador Esp {$s}",
            'apellido_paterno'      => 'Apellido',
            'apellido_materno'      => 'Materno',
            'ci'                    => "CIESP{$s}",
            'telefono'              => "+59171300{$s}",
            'genero'                => 'masculino',
            'fecha_nacimiento'      => '1990-01-01',
            'name'                  => "entesp{$s}",
            'email'                 => "entesp{$s}@madbarzz.test",
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'especialidad'          => ['Calistenia', 'Yoga'],
            'experiencia'           => '5 años',
            'disponibilidad'        => ['Lunes', 'Martes'],
            'estado'                => 'activo',
        ], $overrides);
    }

    // ── store() guarda arrays limpios (no doble-codificados) ─────────────────

    public function test_store_guarda_especialidad_y_disponibilidad_como_array_limpio(): void
    {
        Rol::firstOrCreate(['nombre' => 'Entrenador']);

        $this->actingAs($this->admin())->post('/entrenadores', $this->payload())
             ->assertRedirect(route('entrenadores.index'));

        $entrenador = Entrenador::firstOrFail();

        // Vía el modelo (cast 'array'): tal cual se mandó.
        $this->assertSame(['Calistenia', 'Yoga'], $entrenador->especialidad);
        $this->assertSame(['Lunes', 'Martes'], $entrenador->disponibilidad);

        // Cruda en la BD: un solo nivel de JSON, no JSON-dentro-de-JSON.
        $crudo = DB::table('entrenadores')->where('id_entrenador', $entrenador->id_entrenador)->first();
        $this->assertSame(['Calistenia', 'Yoga'], json_decode($crudo->especialidad, true));
        $this->assertSame(['Lunes', 'Martes'], json_decode($crudo->disponibilidad, true));
    }

    // ── edit() ya no revienta con datos limpios ───────────────────────────────

    public function test_edit_renderiza_bien_y_muestra_las_opciones_seleccionadas(): void
    {
        Rol::firstOrCreate(['nombre' => 'Entrenador']);
        $admin = $this->admin();

        $this->actingAs($admin)->post('/entrenadores', $this->payload())->assertRedirect();
        $entrenador = Entrenador::firstOrFail();

        $html = $this->actingAs($admin)
            ->get(route('entrenadores.edit', $entrenador->id_entrenador))
            ->assertStatus(200)
            ->getContent();

        $this->assertStringContainsString('selected', $html);
    }

    // ── update() también guarda limpio, sin doble-codificar ──────────────────

    public function test_update_guarda_especialidad_y_disponibilidad_como_array_limpio(): void
    {
        Rol::firstOrCreate(['nombre' => 'Entrenador']);
        $admin = $this->admin();

        $this->actingAs($admin)->post('/entrenadores', $this->payload())->assertRedirect();
        $entrenador = Entrenador::firstOrFail();

        $nuevoPayload = $this->payload([
            'especialidad'   => ['Crossfit'],
            'disponibilidad' => ['Miercoles'],
            'ci'             => $entrenador->persona->ci,
            'telefono'       => $entrenador->persona->telefono,
            'name'           => $entrenador->user->name,
            'email'          => $entrenador->user->email,
        ]);
        unset($nuevoPayload['password'], $nuevoPayload['password_confirmation']);

        $this->actingAs($admin)
             ->put(route('entrenadores.update', $entrenador->id_entrenador), $nuevoPayload)
             ->assertRedirect(route('entrenadores.index'));

        $entrenador->refresh();
        $this->assertSame(['Crossfit'], $entrenador->especialidad);
        $this->assertSame(['Miercoles'], $entrenador->disponibilidad);
    }

    // ── Comando de normalización de datos viejos ──────────────────────────────

    public function test_comando_normaliza_un_registro_con_el_formato_viejo_doble_codificado(): void
    {
        $rol  = Rol::firstOrCreate(['nombre' => 'Entrenador']);
        $user = User::factory()->create(['rol_id' => $rol->id]);
        $persona = Persona::create([
            'nombre_completo' => 'Viejo', 'apellido_paterno' => 'Formato', 'apellido_materno' => 'Test',
            'ci' => 'CIVIEJO1', 'telefono' => '+59171399901', 'genero' => 'masculino', 'fecha_nacimiento' => '1990-01-01',
        ]);

        // Simula exactamente el formato que dejaba el bug: especialidad como
        // comas-string re-envuelta en JSON, disponibilidad como JSON doble.
        $idEntrenador = DB::table('entrenadores')->insertGetId([
            'id_persona' => $persona->id_persona, 'id_usuario' => $user->id,
            'especialidad' => json_encode('Yoga, Pilates'),
            'disponibilidad' => json_encode(json_encode(['Lunes', 'Martes'])),
            'experiencia' => '3 años', 'estado' => 'activo',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->artisan('entrenadores:limpiar-especialidad')
             ->expectsOutputToContain('Normalizados: 1. Ya estaban limpios: 0.')
             ->assertExitCode(0);

        $entrenador = Entrenador::find($idEntrenador);
        $this->assertSame(['Yoga', 'Pilates'], $entrenador->especialidad);
        $this->assertSame(['Lunes', 'Martes'], $entrenador->disponibilidad);

        // Y ahora edit() ya no revienta para este registro migrado.
        $this->actingAs($this->admin())
             ->get(route('entrenadores.edit', $idEntrenador))
             ->assertStatus(200);
    }

    public function test_comando_no_toca_un_registro_que_ya_esta_limpio(): void
    {
        $rol  = Rol::firstOrCreate(['nombre' => 'Entrenador']);
        $user = User::factory()->create(['rol_id' => $rol->id]);
        $persona = Persona::create([
            'nombre_completo' => 'Ya', 'apellido_paterno' => 'Limpio', 'apellido_materno' => 'Test',
            'ci' => 'CILIMPIO1', 'telefono' => '+59171399902', 'genero' => 'masculino', 'fecha_nacimiento' => '1990-01-01',
        ]);
        Entrenador::create([
            'id_persona' => $persona->id_persona, 'id_usuario' => $user->id,
            'especialidad' => ['Natacion'], 'disponibilidad' => ['Jueves'],
            'experiencia' => '2 años', 'estado' => 'activo',
        ]);

        $this->artisan('entrenadores:limpiar-especialidad')
             ->expectsOutputToContain('Normalizados: 0. Ya estaban limpios: 1.')
             ->assertExitCode(0);
    }
}
