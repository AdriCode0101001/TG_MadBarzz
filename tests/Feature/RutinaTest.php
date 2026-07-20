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
 * Rutina estructurada (Etapa 3 del módulo de rutinas): el Entrenador (o
 * Administrador) elige un día del calendario, ve el músculo asignado, y
 * arma su rutina eligiendo SOLO ejercicios de ese grupo, con series y
 * repeticiones. Una rutina por persona por día.
 */
class RutinaTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(string $rol): User
    {
        return User::factory()->create(['rol_id' => Rol::firstOrCreate(['nombre' => $rol])->id]);
    }

    /** Próximo lunes real (fecha futura estable, cualquiera que sea "hoy"). */
    private function proximoLunes(): Carbon
    {
        return Carbon::now('America/La_Paz')->next(Carbon::MONDAY);
    }

    private function proximoDomingo(): Carbon
    {
        return Carbon::now('America/La_Paz')->next(Carbon::SUNDAY);
    }

    /** Asigna un grupo muscular a una fecha en el calendario (sede 1, como los usuarios de prueba). */
    private function asignarCalendario(Carbon $fecha, GrupoMuscular $grupo): CalendarioMusculo
    {
        return CalendarioMusculo::create([
            'fecha' => $fecha->toDateString(),
            'id_grupo_muscular' => $grupo->id,
            'id_sede' => 1,
        ]);
    }

    private function crearEjercicio(GrupoMuscular $grupo, string $nombre = 'Press banca'): Ejercicio
    {
        return Ejercicio::create([
            'nombre' => $nombre,
            'category' => 'chest',
            'target' => 'pectorals',
            'equipment' => 'barbell',
            'id_grupo_muscular' => $grupo->id,
            'origen' => 'dataset',
        ]);
    }

    // ── Armar rutina: happy path ──────────────────────────────────────────

    public function test_entrenador_arma_rutina_para_un_dia_con_musculo_asignado(): void
    {
        $grupo = GrupoMuscular::create(['nombre' => 'Pecho y tríceps', 'activo' => true]);
        $fecha = $this->proximoLunes();
        $this->asignarCalendario($fecha, $grupo);
        $ej1 = $this->crearEjercicio($grupo, 'Press banca');
        $ej2 = $this->crearEjercicio($grupo, 'Fondos en paralelas');

        $entrenador = $this->usuario('Entrenador');

        $this->actingAs($entrenador)
            ->put(route('rutinas.edit', $fecha->toDateString()), [
                'ejercicios' => [
                    ['id_ejercicio' => $ej1->id, 'series' => 4, 'repeticiones' => '8-12', 'nota' => 'Subir peso'],
                    ['id_ejercicio' => $ej2->id, 'series' => 3, 'repeticiones' => '10'],
                ],
            ])
            ->assertRedirect(route('rutinas.edit', $fecha->toDateString()));

        $this->assertDatabaseHas('rutinas', [
            'id_usuario' => $entrenador->id,
            'fecha' => $fecha->toDateString(),
            'id_grupo_muscular' => $grupo->id,
        ]);

        $rutina = Rutina::where('id_usuario', $entrenador->id)->first();
        $this->assertSame(2, $rutina->ejercicios()->count());
        $this->assertDatabaseHas('rutina_ejercicios', [
            'id_rutina' => $rutina->id, 'id_ejercicio' => $ej1->id,
            'series' => 4, 'repeticiones' => '8-12', 'nota' => 'Subir peso', 'orden' => 0,
        ]);
        $this->assertDatabaseHas('rutina_ejercicios', [
            'id_rutina' => $rutina->id, 'id_ejercicio' => $ej2->id,
            'series' => 3, 'repeticiones' => '10', 'orden' => 1,
        ]);
    }

    public function test_admin_tambien_puede_armar_su_propia_rutina(): void
    {
        $grupo = GrupoMuscular::create(['nombre' => 'Hombro', 'activo' => true]);
        $fecha = $this->proximoLunes();
        $this->asignarCalendario($fecha, $grupo);
        $ej = $this->crearEjercicio($grupo, 'Press militar');

        $admin = $this->usuario('Administrador');

        $this->actingAs($admin)
            ->put(route('rutinas.edit', $fecha->toDateString()), [
                'ejercicios' => [['id_ejercicio' => $ej->id, 'series' => 3, 'repeticiones' => '12']],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('rutinas', ['id_usuario' => $admin->id, 'fecha' => $fecha->toDateString()]);
    }

    // ── Rechazar ejercicio de otro grupo ──────────────────────────────────

    public function test_rechaza_un_ejercicio_que_no_es_del_grupo_del_dia(): void
    {
        $pecho  = GrupoMuscular::create(['nombre' => 'Pecho y tríceps', 'activo' => true]);
        $pierna = GrupoMuscular::create(['nombre' => 'Pierna', 'activo' => true]);
        $fecha = $this->proximoLunes();
        $this->asignarCalendario($fecha, $pecho); // el día es de PECHO

        $ejercicioDePierna = $this->crearEjercicio($pierna, 'Sentadilla'); // ejercicio de otro grupo

        $entrenador = $this->usuario('Entrenador');

        $this->actingAs($entrenador)
            ->put(route('rutinas.edit', $fecha->toDateString()), [
                'ejercicios' => [['id_ejercicio' => $ejercicioDePierna->id, 'series' => 3, 'repeticiones' => '10']],
            ])
            ->assertSessionHasErrors('ejercicios.0.id_ejercicio');

        $this->assertDatabaseCount('rutinas', 0);
    }

    // ── Rechazar domingo / día sin músculo ────────────────────────────────

    public function test_rechaza_armar_rutina_un_domingo(): void
    {
        $grupo  = GrupoMuscular::create(['nombre' => 'Pierna', 'activo' => true]);
        $domingo = $this->proximoDomingo();
        $ej = $this->crearEjercicio($grupo);

        $entrenador = $this->usuario('Entrenador');

        // La pantalla de armar tampoco debe abrir un domingo.
        $this->actingAs($entrenador)
            ->get(route('rutinas.edit', $domingo->toDateString()))
            ->assertRedirect(route('rutinas.index'));

        $this->actingAs($entrenador)
            ->put(route('rutinas.edit', $domingo->toDateString()), [
                'ejercicios' => [['id_ejercicio' => $ej->id, 'series' => 3, 'repeticiones' => '10']],
            ])
            ->assertSessionHasErrors('fecha');

        $this->assertDatabaseCount('rutinas', 0);
    }

    public function test_rechaza_armar_rutina_un_dia_sin_musculo_asignado(): void
    {
        $grupo = GrupoMuscular::create(['nombre' => 'Pecho y tríceps', 'activo' => true]);
        $fecha = $this->proximoLunes(); // sin asignarCalendario(): ese día no tiene músculo
        $ej = $this->crearEjercicio($grupo);

        $entrenador = $this->usuario('Entrenador');

        $this->actingAs($entrenador)
            ->get(route('rutinas.edit', $fecha->toDateString()))
            ->assertRedirect(route('rutinas.index'));

        $this->actingAs($entrenador)
            ->put(route('rutinas.edit', $fecha->toDateString()), [
                'ejercicios' => [['id_ejercicio' => $ej->id, 'series' => 3, 'repeticiones' => '10']],
            ])
            ->assertSessionHasErrors('fecha');

        $this->assertDatabaseCount('rutinas', 0);
    }

    // ── Una rutina por entrenador por día (reeditar no duplica) ───────────

    public function test_reeditar_la_rutina_del_mismo_dia_actualiza_en_vez_de_duplicar(): void
    {
        $grupo = GrupoMuscular::create(['nombre' => 'Espalda y bíceps', 'activo' => true]);
        $fecha = $this->proximoLunes();
        $this->asignarCalendario($fecha, $grupo);
        $ej1 = $this->crearEjercicio($grupo, 'Dominadas');
        $ej2 = $this->crearEjercicio($grupo, 'Remo con barra');

        $entrenador = $this->usuario('Entrenador');

        $this->actingAs($entrenador)->put(route('rutinas.edit', $fecha->toDateString()), [
            'ejercicios' => [['id_ejercicio' => $ej1->id, 'series' => 4, 'repeticiones' => '10']],
        ]);
        $this->actingAs($entrenador)->put(route('rutinas.edit', $fecha->toDateString()), [
            'ejercicios' => [
                ['id_ejercicio' => $ej2->id, 'series' => 3, 'repeticiones' => '12'],
            ],
        ]);

        $this->assertDatabaseCount('rutinas', 1);
        $rutina = Rutina::where('id_usuario', $entrenador->id)->first();
        $this->assertSame(1, $rutina->ejercicios()->count());
        $this->assertDatabaseHas('rutina_ejercicios', ['id_rutina' => $rutina->id, 'id_ejercicio' => $ej2->id]);
        $this->assertDatabaseMissing('rutina_ejercicios', ['id_rutina' => $rutina->id, 'id_ejercicio' => $ej1->id]);
    }

    public function test_dos_entrenadores_pueden_tener_cada_uno_su_propia_rutina_el_mismo_dia(): void
    {
        $grupo = GrupoMuscular::create(['nombre' => 'Pierna', 'activo' => true]);
        $fecha = $this->proximoLunes();
        $this->asignarCalendario($fecha, $grupo);
        $ej = $this->crearEjercicio($grupo, 'Sentadilla');

        $entrenadorA = $this->usuario('Entrenador');
        $entrenadorB = $this->usuario('Entrenador');

        $this->actingAs($entrenadorA)->put(route('rutinas.edit', $fecha->toDateString()), [
            'ejercicios' => [['id_ejercicio' => $ej->id, 'series' => 3, 'repeticiones' => '10']],
        ]);
        $this->actingAs($entrenadorB)->put(route('rutinas.edit', $fecha->toDateString()), [
            'ejercicios' => [['id_ejercicio' => $ej->id, 'series' => 5, 'repeticiones' => '5']],
        ]);

        $this->assertDatabaseCount('rutinas', 2);
        $this->assertDatabaseHas('rutinas', ['id_usuario' => $entrenadorA->id, 'fecha' => $fecha->toDateString()]);
        $this->assertDatabaseHas('rutinas', ['id_usuario' => $entrenadorB->id, 'fecha' => $fecha->toDateString()]);
    }

    // ── Permisos ───────────────────────────────────────────────────────────

    public function test_un_entrenador_no_puede_borrar_la_rutina_de_otro(): void
    {
        $grupo = GrupoMuscular::create(['nombre' => 'Pierna', 'activo' => true]);
        $fecha = $this->proximoLunes();
        $this->asignarCalendario($fecha, $grupo);
        $ej = $this->crearEjercicio($grupo);

        $dueño = $this->usuario('Entrenador');
        $this->actingAs($dueño)->put(route('rutinas.edit', $fecha->toDateString()), [
            'ejercicios' => [['id_ejercicio' => $ej->id, 'series' => 3, 'repeticiones' => '10']],
        ]);
        $rutina = Rutina::where('id_usuario', $dueño->id)->first();

        $otroEntrenador = $this->usuario('Entrenador');
        $this->actingAs($otroEntrenador)
            ->delete(route('rutinas.destroy', $rutina))
            ->assertStatus(403);

        $this->assertDatabaseHas('rutinas', ['id' => $rutina->id, 'deleted_at' => null]);
    }

    public function test_un_entrenador_no_puede_ver_la_rutina_de_otro(): void
    {
        $grupo = GrupoMuscular::create(['nombre' => 'Pierna', 'activo' => true]);
        $fecha = $this->proximoLunes();
        $this->asignarCalendario($fecha, $grupo);
        $ej = $this->crearEjercicio($grupo);

        $dueño = $this->usuario('Entrenador');
        $this->actingAs($dueño)->put(route('rutinas.edit', $fecha->toDateString()), [
            'ejercicios' => [['id_ejercicio' => $ej->id, 'series' => 3, 'repeticiones' => '10']],
        ]);
        $rutina = Rutina::where('id_usuario', $dueño->id)->first();

        $otroEntrenador = $this->usuario('Entrenador');
        $this->actingAs($otroEntrenador)
            ->get(route('rutinas.show', $rutina))
            ->assertStatus(403);
    }

    public function test_admin_puede_borrar_la_rutina_de_cualquier_entrenador(): void
    {
        $grupo = GrupoMuscular::create(['nombre' => 'Pierna', 'activo' => true]);
        $fecha = $this->proximoLunes();
        $this->asignarCalendario($fecha, $grupo);
        $ej = $this->crearEjercicio($grupo);

        $entrenador = $this->usuario('Entrenador');
        $this->actingAs($entrenador)->put(route('rutinas.edit', $fecha->toDateString()), [
            'ejercicios' => [['id_ejercicio' => $ej->id, 'series' => 3, 'repeticiones' => '10']],
        ]);
        $rutina = Rutina::where('id_usuario', $entrenador->id)->first();

        $admin = $this->usuario('Administrador');
        $this->actingAs($admin)
            ->delete(route('rutinas.destroy', $rutina))
            ->assertRedirect(route('rutinas.index'));

        $this->assertSoftDeleted('rutinas', ['id' => $rutina->id]);
    }

    public function test_recepcionista_no_accede_a_armar_rutinas(): void
    {
        $recepcionista = $this->usuario('Recepcionista');

        $this->actingAs($recepcionista)->get(route('rutinas.index'))->assertStatus(403);
        $this->actingAs($recepcionista)
            ->get(route('rutinas.edit', $this->proximoLunes()->toDateString()))
            ->assertStatus(403);
    }

    public function test_cliente_no_accede_a_armar_rutinas(): void
    {
        $cliente = $this->usuario('Cliente');

        $this->actingAs($cliente)->get(route('rutinas.index'))->assertStatus(403);
    }

    // ── Validaciones de series/repeticiones ───────────────────────────────

    public function test_rechaza_series_invalidas(): void
    {
        $grupo = GrupoMuscular::create(['nombre' => 'Pierna', 'activo' => true]);
        $fecha = $this->proximoLunes();
        $this->asignarCalendario($fecha, $grupo);
        $ej = $this->crearEjercicio($grupo);

        $entrenador = $this->usuario('Entrenador');

        $this->actingAs($entrenador)
            ->put(route('rutinas.edit', $fecha->toDateString()), [
                'ejercicios' => [['id_ejercicio' => $ej->id, 'series' => 0, 'repeticiones' => '10']],
            ])
            ->assertSessionHasErrors('ejercicios.0.series');

        $this->assertDatabaseCount('rutinas', 0);
    }
}
