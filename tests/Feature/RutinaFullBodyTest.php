<?php

namespace Tests\Feature;

use App\Models\CalendarioMusculo;
use App\Models\Ejercicio;
use App\Models\GrupoMuscular;
use App\Models\Rol;
use App\Models\Rutina;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * "Full body": el dataset importado no clasificó ningún ejercicio en esa
 * categoría (no existía en el dataset de origen), así que un día Full body
 * en "Armar rutina" no mostraba NADA para elegir. La solución: ese día
 * puntual, el entrenador elige libremente de cualquier grupo (pestañas en la
 * vista); los demás días siguen restringidos al grupo del día, sin cambios.
 */
class RutinaFullBodyTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(string $rol): User
    {
        return User::factory()->create(['rol_id' => Rol::firstOrCreate(['nombre' => $rol])->id]);
    }

    private function proximoLunes(): Carbon
    {
        return Carbon::now('America/La_Paz')->next(Carbon::MONDAY);
    }

    private function asignarCalendario(Carbon $fecha, GrupoMuscular $grupo): CalendarioMusculo
    {
        return CalendarioMusculo::create([
            'fecha' => $fecha->toDateString(),
            'id_grupo_muscular' => $grupo->id,
            'id_sede' => 1,
        ]);
    }

    private function crearEjercicio(?GrupoMuscular $grupo, string $nombre): Ejercicio
    {
        return Ejercicio::create([
            'nombre' => $nombre,
            'category' => 'test',
            'target' => 'test',
            'equipment' => 'test',
            'id_grupo_muscular' => $grupo?->id,
            'origen' => 'dataset',
        ]);
    }

    // ── Día Full body: acepta ejercicios de varios grupos ──────────────────

    public function test_dia_full_body_permite_agregar_ejercicios_de_varios_grupos(): void
    {
        $pecho = GrupoMuscular::create(['nombre' => 'Pecho y tríceps', 'activo' => true, 'es_full_body' => false]);
        $pierna = GrupoMuscular::create(['nombre' => 'Pierna', 'activo' => true, 'es_full_body' => false]);
        $fullBody = GrupoMuscular::create(['nombre' => 'Full body', 'activo' => true, 'es_full_body' => true]);

        $fecha = $this->proximoLunes();
        $this->asignarCalendario($fecha, $fullBody);

        $ejPecho = $this->crearEjercicio($pecho, 'Press banca');
        $ejPierna = $this->crearEjercicio($pierna, 'Sentadilla');
        $ejSinGrupo = $this->crearEjercicio(null, 'Ejercicio suelto');

        $entrenador = $this->usuario('Entrenador');

        $this->actingAs($entrenador)
            ->put(route('rutinas.edit', $fecha->toDateString()), [
                'ejercicios' => [
                    ['id_ejercicio' => $ejPecho->id, 'series' => 3, 'repeticiones' => '10'],
                    ['id_ejercicio' => $ejPierna->id, 'series' => 4, 'repeticiones' => '8'],
                    ['id_ejercicio' => $ejSinGrupo->id, 'series' => 3, 'repeticiones' => '12'],
                ],
            ])
            ->assertRedirect(route('rutinas.edit', $fecha->toDateString()));

        $rutina = Rutina::where('id_usuario', $entrenador->id)->first();
        $this->assertNotNull($rutina);
        $this->assertSame(3, $rutina->ejercicios()->count());
        $this->assertDatabaseHas('rutina_ejercicios', ['id_rutina' => $rutina->id, 'id_ejercicio' => $ejPecho->id]);
        $this->assertDatabaseHas('rutina_ejercicios', ['id_rutina' => $rutina->id, 'id_ejercicio' => $ejPierna->id]);
        $this->assertDatabaseHas('rutina_ejercicios', ['id_rutina' => $rutina->id, 'id_ejercicio' => $ejSinGrupo->id]);
    }

    public function test_pantalla_armar_rutina_en_dia_full_body_ofrece_pestanas_de_todos_los_grupos(): void
    {
        $pecho = GrupoMuscular::create(['nombre' => 'Pecho y tríceps', 'activo' => true, 'es_full_body' => false]);
        $pierna = GrupoMuscular::create(['nombre' => 'Pierna', 'activo' => true, 'es_full_body' => false]);
        $fullBody = GrupoMuscular::create(['nombre' => 'Full body', 'activo' => true, 'es_full_body' => true]);

        $fecha = $this->proximoLunes();
        $this->asignarCalendario($fecha, $fullBody);

        $this->crearEjercicio($pecho, 'Press banca');
        $this->crearEjercicio($pierna, 'Sentadilla');

        $entrenador = $this->usuario('Entrenador');

        $respuesta = $this->actingAs($entrenador)
            ->get(route('rutinas.edit', $fecha->toDateString()))
            ->assertStatus(200);

        $respuesta->assertViewHas('esFullBody', true);
        $respuesta->assertViewHas('gruposParaTabs', function ($grupos) use ($pecho, $pierna, $fullBody) {
            $nombres = $grupos->pluck('nombre')->all();
            return in_array($pecho->nombre, $nombres, true)
                && in_array($pierna->nombre, $nombres, true)
                && !in_array($fullBody->nombre, $nombres, true);
        });

        // El catálogo completo (de ambos grupos) llega a la vista para las pestañas.
        $respuesta->assertSee('Press banca');
        $respuesta->assertSee('Sentadilla');
    }

    // ── Día NO Full body: sigue restringido al grupo del día ───────────────

    public function test_dia_no_full_body_sigue_rechazando_ejercicio_de_otro_grupo(): void
    {
        $pecho = GrupoMuscular::create(['nombre' => 'Pecho y tríceps', 'activo' => true, 'es_full_body' => false]);
        $pierna = GrupoMuscular::create(['nombre' => 'Pierna', 'activo' => true, 'es_full_body' => false]);

        $fecha = $this->proximoLunes();
        $this->asignarCalendario($fecha, $pecho); // el día es de PECHO, no Full body

        $ejPierna = $this->crearEjercicio($pierna, 'Sentadilla');
        $entrenador = $this->usuario('Entrenador');

        $this->actingAs($entrenador)
            ->put(route('rutinas.edit', $fecha->toDateString()), [
                'ejercicios' => [['id_ejercicio' => $ejPierna->id, 'series' => 3, 'repeticiones' => '10']],
            ])
            ->assertSessionHasErrors('ejercicios.0.id_ejercicio');

        $this->assertDatabaseCount('rutinas', 0);
    }

    public function test_pantalla_armar_rutina_en_dia_no_full_body_no_muestra_pestanas(): void
    {
        $pecho = GrupoMuscular::create(['nombre' => 'Pecho y tríceps', 'activo' => true, 'es_full_body' => false]);
        $fecha = $this->proximoLunes();
        $this->asignarCalendario($fecha, $pecho);
        $this->crearEjercicio($pecho, 'Press banca');

        $entrenador = $this->usuario('Entrenador');

        $this->actingAs($entrenador)
            ->get(route('rutinas.edit', $fecha->toDateString()))
            ->assertStatus(200)
            ->assertViewHas('esFullBody', false)
            ->assertViewHas('gruposParaTabs', fn ($grupos) => $grupos->isEmpty());
    }
}
