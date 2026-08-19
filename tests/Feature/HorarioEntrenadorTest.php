<?php

namespace Tests\Feature;

use App\Models\Entrenador;
use App\Models\HorarioEntrenador;
use App\Models\Persona;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Horario BASE semanal de entrenadores (Parte 1: gestión del Administrador).
 * Guardado GENERAL: la grilla se arma entera en pantalla (nada se persiste
 * solo) y un único botón "Guardar cambios" persiste las 84 casillas juntas,
 * en una transacción. El gimnasio abre lunes a sábado, franjas de 1 hora de
 * 07:00 a 21:00; el domingo nunca recibe asignación. El listado es de solo
 * lectura para Recepcionista/Entrenador; guardar sigue siendo exclusivo del
 * Administrador.
 */
class HorarioEntrenadorTest extends TestCase
{
    use RefreshDatabase;

    private static int $seq = 0;

    private function usuario(string $rol): User
    {
        return User::factory()->create(['rol_id' => Rol::firstOrCreate(['nombre' => $rol])->id]);
    }

    private function admin(): User
    {
        return $this->usuario('Administrador');
    }

    private function crearEntrenador(string $nombre = 'Juan', string $estado = 'activo'): Entrenador
    {
        self::$seq++;
        $s = self::$seq;

        $persona = Persona::create([
            'nombre_completo'  => $nombre,
            'apellido_paterno' => "Entrenador{$s}",
            'apellido_materno' => 'Test',
            'ci'               => "CIENTR{$s}",
            'telefono'         => "+59170000{$s}",
            'genero'           => 'masculino',
            'fecha_nacimiento' => '1990-01-01',
        ]);

        $user = User::factory()->create(['rol_id' => Rol::firstOrCreate(['nombre' => 'Entrenador'])->id]);

        return Entrenador::create([
            'id_persona'     => $persona->id_persona,
            'id_usuario'     => $user->id,
            'especialidad'   => ['Calistenia'],
            'experiencia'    => '5 años',
            'disponibilidad' => ['lunes', 'martes'],
            'estado'         => $estado,
        ]);
    }

    /** Franjas de 07:00 a 20:00 (las 14 que muestra la grilla). */
    private function franjas(): array
    {
        $franjas = [];
        for ($h = 7; $h < 21; $h++) {
            $franjas[] = sprintf('%02d:00', $h);
        }

        return $franjas;
    }

    /** Payload base con las 84 casillas vacías, para completar solo las que interesan al test. */
    private function payloadVacio(): array
    {
        $asignaciones = [];
        foreach (range(1, 6) as $dia) {
            foreach ($this->franjas() as $hora) {
                $asignaciones[$dia][$hora] = '';
            }
        }

        return ['asignaciones' => $asignaciones];
    }

    private function guardar(array $overrides): array
    {
        $payload = $this->payloadVacio();

        foreach ($overrides as [$dia, $hora, $idEntrenador]) {
            $payload['asignaciones'][$dia][$hora] = $idEntrenador;
        }

        return $payload;
    }

    // ── Guardar toda la grilla con el botón ──────────────────────────────────

    public function test_admin_guarda_toda_la_grilla_y_persisten_las_asignaciones(): void
    {
        $marco = $this->crearEntrenador('Marco');
        $vero  = $this->crearEntrenador('Vero');

        $payload = $this->guardar([
            [1, '07:00', $marco->id_entrenador],
            [1, '08:00', $marco->id_entrenador],
            [3, '15:00', $vero->id_entrenador],
        ]);

        $this->actingAs($this->admin())
            ->post(route('horarios_entrenadores.guardar'), $payload)
            ->assertRedirect();

        $this->assertDatabaseHas('horarios_entrenadores', [
            'dia_semana' => 1, 'hora_inicio' => '07:00', 'hora_fin' => '08:00', 'id_entrenador' => $marco->id_entrenador,
        ]);
        $this->assertDatabaseHas('horarios_entrenadores', [
            'dia_semana' => 1, 'hora_inicio' => '08:00', 'id_entrenador' => $marco->id_entrenador,
        ]);
        $this->assertDatabaseHas('horarios_entrenadores', [
            'dia_semana' => 3, 'hora_inicio' => '15:00', 'id_entrenador' => $vero->id_entrenador,
        ]);
    }

    // ── Nada se persiste hasta tocar "Guardar cambios" ───────────────────────

    public function test_nada_se_persiste_solo_con_ver_la_grilla(): void
    {
        $this->crearEntrenador();
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('horarios_entrenadores.index'))->assertOk();
        $this->actingAs($admin)->get(route('horarios_entrenadores.index'))->assertOk();

        $this->assertDatabaseCount('horarios_entrenadores', 0);
    }

    public function test_los_cambios_recien_quedan_al_enviar_el_formulario_completo(): void
    {
        $marco = $this->crearEntrenador('Marco');

        $this->assertDatabaseCount('horarios_entrenadores', 0);

        $this->actingAs($this->admin())->post(route('horarios_entrenadores.guardar'), $this->guardar([
            [1, '07:00', $marco->id_entrenador],
        ]));

        $this->assertDatabaseCount('horarios_entrenadores', 84);
        $this->assertDatabaseHas('horarios_entrenadores', [
            'dia_semana' => 1, 'hora_inicio' => '07:00', 'id_entrenador' => $marco->id_entrenador,
        ]);
    }

    // ── Mismo entrenador en varias franjas, en un solo guardado ──────────────

    public function test_admin_puede_asignar_el_mismo_entrenador_a_varias_franjas_en_un_guardado(): void
    {
        $juan = $this->crearEntrenador('Juan');

        $payload = $this->guardar([
            [1, '07:00', $juan->id_entrenador],
            [1, '08:00', $juan->id_entrenador],
            [1, '09:00', $juan->id_entrenador],
            [1, '10:00', $juan->id_entrenador],
        ]);

        $this->actingAs($this->admin())->post(route('horarios_entrenadores.guardar'), $payload)->assertRedirect();

        $this->assertSame(
            4,
            HorarioEntrenador::where('id_entrenador', $juan->id_entrenador)->count()
        );
    }

    // ── Dejar una franja sin asignar ─────────────────────────────────────────

    public function test_admin_puede_dejar_una_franja_sin_asignar(): void
    {
        $payload = $this->guardar([]); // todo vacío

        $this->actingAs($this->admin())->post(route('horarios_entrenadores.guardar'), $payload)->assertRedirect();

        $this->assertDatabaseHas('horarios_entrenadores', [
            'dia_semana' => 2, 'hora_inicio' => '09:00', 'id_entrenador' => null,
        ]);
    }

    // ── Editar/cambiar una asignación ya guardada ────────────────────────────

    public function test_admin_puede_editar_una_asignacion_en_un_guardado_posterior(): void
    {
        $juan  = $this->crearEntrenador('Juan');
        $marco = $this->crearEntrenador('Marco');
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('horarios_entrenadores.guardar'), $this->guardar([
            [3, '11:00', $juan->id_entrenador],
        ]));
        $this->actingAs($admin)->post(route('horarios_entrenadores.guardar'), $this->guardar([
            [3, '11:00', $marco->id_entrenador],
        ]));

        $this->assertDatabaseCount('horarios_entrenadores', 84); // no duplica filas
        $this->assertDatabaseHas('horarios_entrenadores', [
            'dia_semana' => 3, 'hora_inicio' => '11:00', 'id_entrenador' => $marco->id_entrenador,
        ]);
    }

    // ── Domingo cerrado ───────────────────────────────────────────────────────

    public function test_rechaza_domingo_en_el_guardado_general(): void
    {
        $entrenador = $this->crearEntrenador();
        $payload = $this->guardar([]);
        $payload['asignaciones'][7] = ['09:00' => $entrenador->id_entrenador]; // domingo colado a mano

        $this->actingAs($this->admin())
            ->post(route('horarios_entrenadores.guardar'), $payload)
            ->assertSessionHasErrors('asignaciones');

        $this->assertDatabaseCount('horarios_entrenadores', 0);
    }

    // ── Franja fuera de rango ─────────────────────────────────────────────────

    public function test_rechaza_una_franja_fuera_del_rango_horario(): void
    {
        $entrenador = $this->crearEntrenador();
        $payload = $this->guardar([]);
        $payload['asignaciones'][1]['22:00'] = $entrenador->id_entrenador;

        $this->actingAs($this->admin())
            ->post(route('horarios_entrenadores.guardar'), $payload)
            ->assertSessionHasErrors('asignaciones');

        $this->assertDatabaseCount('horarios_entrenadores', 0);
    }

    // ── Entrenador inexistente o inactivo ─────────────────────────────────────

    public function test_no_se_puede_asignar_un_entrenador_inactivo(): void
    {
        $entrenador = $this->crearEntrenador('Inactivo', 'inactivo');

        $this->actingAs($this->admin())
            ->post(route('horarios_entrenadores.guardar'), $this->guardar([
                [1, '07:00', $entrenador->id_entrenador],
            ]))
            ->assertSessionHasErrors();

        $this->assertDatabaseCount('horarios_entrenadores', 0);
    }

    public function test_no_se_puede_asignar_un_entrenador_que_no_existe(): void
    {
        $this->actingAs($this->admin())
            ->post(route('horarios_entrenadores.guardar'), $this->guardar([
                [1, '07:00', 999999],
            ]))
            ->assertSessionHasErrors();

        $this->assertDatabaseCount('horarios_entrenadores', 0);
    }

    // ── Permisos ──────────────────────────────────────────────────────────────

    public function test_recepcionista_y_entrenador_ven_la_grilla_en_solo_lectura(): void
    {
        foreach (['Recepcionista', 'Entrenador'] as $rol) {
            $this->actingAs($this->usuario($rol))
                ->get(route('horarios_entrenadores.index'))
                ->assertStatus(200)
                ->assertDontSee('name="asignaciones', false);
        }
    }

    public function test_cliente_no_accede_a_la_grilla(): void
    {
        $this->actingAs($this->usuario('Cliente'))
            ->get(route('horarios_entrenadores.index'))
            ->assertStatus(403);
    }

    public function test_solo_administrador_puede_guardar_la_grilla(): void
    {
        $entrenador = $this->crearEntrenador();
        $payload = $this->guardar([[1, '07:00', $entrenador->id_entrenador]]);

        foreach (['Recepcionista', 'Entrenador', 'Cliente'] as $rol) {
            $this->actingAs($this->usuario($rol))
                ->post(route('horarios_entrenadores.guardar'), $payload)
                ->assertStatus(403);
        }

        $this->assertDatabaseCount('horarios_entrenadores', 0);
    }

    public function test_admin_ve_la_grilla_con_los_selects_de_edicion(): void
    {
        $this->actingAs($this->admin())
            ->get(route('horarios_entrenadores.index'))
            ->assertStatus(200)
            ->assertSee('name="asignaciones[1][07:00]"', false)
            ->assertSee('Guardar cambios');
    }
}
