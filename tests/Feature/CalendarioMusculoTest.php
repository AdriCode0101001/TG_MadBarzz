<?php

namespace Tests\Feature;

use App\Models\CalendarioMusculo;
use App\Models\GrupoMuscular;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Calendario mensual de grupos musculares (Etapa 2 del módulo de rutinas):
 * el Administrador asigna un músculo a cada día lunes-sábado; los domingos
 * el gimnasio cierra y no aceptan asignación. Solo el Administrador
 * gestiona esta pantalla por ahora.
 */
class CalendarioMusculoTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(string $rol): User
    {
        return User::factory()->create(['rol_id' => Rol::firstOrCreate(['nombre' => $rol])->id]);
    }

    private function admin(): User
    {
        return $this->usuario('Administrador');
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

    // ── Asignar un día lunes-sábado ───────────────────────────────────────

    public function test_admin_asigna_musculo_a_un_dia_lunes_a_sabado(): void
    {
        $grupo = GrupoMuscular::create(['nombre' => 'Pecho y tríceps', 'activo' => true]);
        $fecha = $this->proximoLunes();

        $this->actingAs($this->admin())
            ->put(route('calendario_musculos.dia', $fecha->toDateString()), [
                'id_grupo_muscular' => $grupo->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('calendario_musculos', [
            'fecha'             => $fecha->toDateString(),
            'id_grupo_muscular' => $grupo->id,
        ]);
    }

    // ── Domingo cerrado ────────────────────────────────────────────────────

    public function test_no_se_puede_asignar_musculo_a_un_domingo(): void
    {
        $grupo  = GrupoMuscular::create(['nombre' => 'Pierna', 'activo' => true]);
        $domingo = $this->proximoDomingo();

        $this->actingAs($this->admin())
            ->put(route('calendario_musculos.dia', $domingo->toDateString()), [
                'id_grupo_muscular' => $grupo->id,
            ])
            ->assertSessionHasErrors('id_grupo_muscular');

        $this->assertDatabaseMissing('calendario_musculos', ['fecha' => $domingo->toDateString()]);
    }

    // ── Reasignar actualiza (no duplica) ──────────────────────────────────

    public function test_reasignar_un_dia_lo_actualiza_sin_duplicar(): void
    {
        $grupoA = GrupoMuscular::create(['nombre' => 'Espalda y bíceps', 'activo' => true]);
        $grupoB = GrupoMuscular::create(['nombre' => 'Hombro', 'activo' => true]);
        $fecha  = $this->proximoLunes();

        $admin = $this->admin();

        $this->actingAs($admin)->put(route('calendario_musculos.dia', $fecha->toDateString()), [
            'id_grupo_muscular' => $grupoA->id,
        ]);
        $this->actingAs($admin)->put(route('calendario_musculos.dia', $fecha->toDateString()), [
            'id_grupo_muscular' => $grupoB->id,
        ]);

        $this->assertDatabaseCount('calendario_musculos', 1);
        $this->assertDatabaseHas('calendario_musculos', [
            'fecha'             => $fecha->toDateString(),
            'id_grupo_muscular' => $grupoB->id,
        ]);
    }

    // ── Quitar (dejar el día sin músculo) ─────────────────────────────────

    public function test_quitar_el_musculo_de_un_dia_lo_libera(): void
    {
        $grupo = GrupoMuscular::create(['nombre' => 'Full body', 'activo' => true]);
        $fecha = $this->proximoLunes();
        $admin = $this->admin();

        $this->actingAs($admin)->put(route('calendario_musculos.dia', $fecha->toDateString()), [
            'id_grupo_muscular' => $grupo->id,
        ]);
        $this->actingAs($admin)->put(route('calendario_musculos.dia', $fecha->toDateString()), [
            'id_grupo_muscular' => '',
        ]);

        $this->assertSoftDeleted('calendario_musculos', ['fecha' => $fecha->toDateString()]);
    }

    public function test_se_puede_reasignar_un_dia_que_habia_sido_liberado(): void
    {
        $grupo = GrupoMuscular::create(['nombre' => 'Pierna', 'activo' => true]);
        $fecha = $this->proximoLunes();
        $admin = $this->admin();

        $this->actingAs($admin)->put(route('calendario_musculos.dia', $fecha->toDateString()), ['id_grupo_muscular' => $grupo->id]);
        $this->actingAs($admin)->put(route('calendario_musculos.dia', $fecha->toDateString()), ['id_grupo_muscular' => '']);
        $this->actingAs($admin)->put(route('calendario_musculos.dia', $fecha->toDateString()), ['id_grupo_muscular' => $grupo->id]);

        $this->assertDatabaseCount('calendario_musculos', 1);
        $this->assertDatabaseHas('calendario_musculos', [
            'fecha'             => $fecha->toDateString(),
            'id_grupo_muscular' => $grupo->id,
            'deleted_at'        => null,
        ]);
    }

    // ── Grupo muscular inactivo o borrado ─────────────────────────────────

    public function test_no_se_puede_asignar_un_grupo_muscular_inactivo(): void
    {
        $grupo = GrupoMuscular::create(['nombre' => 'Inactivo', 'activo' => false]);

        $this->actingAs($this->admin())
            ->put(route('calendario_musculos.dia', $this->proximoLunes()->toDateString()), [
                'id_grupo_muscular' => $grupo->id,
            ])
            ->assertSessionHasErrors('id_grupo_muscular');
    }

    public function test_no_se_puede_asignar_un_grupo_muscular_borrado(): void
    {
        $grupo = GrupoMuscular::create(['nombre' => 'Borrado', 'activo' => true]);
        $grupo->delete();

        $this->actingAs($this->admin())
            ->put(route('calendario_musculos.dia', $this->proximoLunes()->toDateString()), [
                'id_grupo_muscular' => $grupo->id,
            ])
            ->assertSessionHasErrors('id_grupo_muscular');
    }

    // ── Patrón semanal ─────────────────────────────────────────────────────

    public function test_aplicar_patron_semanal_llena_el_mes_sin_tocar_domingos(): void
    {
        $pecho   = GrupoMuscular::create(['nombre' => 'Pecho', 'activo' => true]);
        $espalda = GrupoMuscular::create(['nombre' => 'Espalda', 'activo' => true]);
        $pierna  = GrupoMuscular::create(['nombre' => 'Pierna', 'activo' => true]);
        $hombro  = GrupoMuscular::create(['nombre' => 'Hombro', 'activo' => true]);
        $brazo   = GrupoMuscular::create(['nombre' => 'Brazo', 'activo' => true]);
        $full    = GrupoMuscular::create(['nombre' => 'Full body', 'activo' => true]);

        $referencia = Carbon::now('America/La_Paz')->addMonthNoOverflow();
        $anio = (int) $referencia->year;
        $mes  = (int) $referencia->month;

        $this->actingAs($this->admin())->post(route('calendario_musculos.patron'), [
            'anio'   => $anio,
            'mes'    => $mes,
            'patron' => [
                1 => $pecho->id,
                2 => $espalda->id,
                3 => $pierna->id,
                4 => $hombro->id,
                5 => $brazo->id,
                6 => $full->id,
            ],
        ])->assertRedirect(route('calendario_musculos.index', ['anio' => $anio, 'mes' => $mes]));

        $inicio = Carbon::createFromDate($anio, $mes, 1)->startOfMonth();
        $fin    = $inicio->copy()->endOfMonth();

        $diasHabiles = 0;
        for ($f = $inicio->copy(); $f->lte($fin); $f->addDay()) {
            if ($f->dayOfWeekIso !== 7) {
                $diasHabiles++;
            }
        }

        $this->assertDatabaseCount('calendario_musculos', $diasHabiles);
        $this->assertSame(0, CalendarioMusculo::whereBetween('fecha', [$inicio->toDateString(), $fin->toDateString()])
            ->get()
            ->filter(fn ($c) => $c->fecha->dayOfWeekIso === 7)
            ->count());
    }

    public function test_patron_semanal_no_toca_los_dias_dejados_sin_definir(): void
    {
        $pecho = GrupoMuscular::create(['nombre' => 'Pecho', 'activo' => true]);

        $referencia = Carbon::now('America/La_Paz')->addMonthNoOverflow();
        $anio = (int) $referencia->year;
        $mes  = (int) $referencia->month;

        $this->actingAs($this->admin())->post(route('calendario_musculos.patron'), [
            'anio'   => $anio,
            'mes'    => $mes,
            'patron' => [1 => $pecho->id], // solo lunes
        ]);

        $lunes = 0;
        $inicio = Carbon::createFromDate($anio, $mes, 1)->startOfMonth();
        $fin    = $inicio->copy()->endOfMonth();
        for ($f = $inicio->copy(); $f->lte($fin); $f->addDay()) {
            if ($f->dayOfWeekIso === 1) {
                $lunes++;
            }
        }

        $this->assertDatabaseCount('calendario_musculos', $lunes);
    }

    // ── Permisos: solo Administrador ──────────────────────────────────────

    public function test_solo_administrador_ve_el_calendario(): void
    {
        foreach (['Recepcionista', 'Entrenador', 'Cliente'] as $rol) {
            $this->actingAs($this->usuario($rol))
                ->get(route('calendario_musculos.index'))
                ->assertStatus(403);
        }

        $this->actingAs($this->admin())
            ->get(route('calendario_musculos.index'))
            ->assertStatus(200);
    }

    public function test_solo_administrador_puede_asignar_un_dia(): void
    {
        $grupo = GrupoMuscular::create(['nombre' => 'Pierna', 'activo' => true]);
        $fecha = $this->proximoLunes()->toDateString();

        foreach (['Recepcionista', 'Entrenador', 'Cliente'] as $rol) {
            $this->actingAs($this->usuario($rol))
                ->put(route('calendario_musculos.dia', $fecha), ['id_grupo_muscular' => $grupo->id])
                ->assertStatus(403);
        }

        $this->assertDatabaseCount('calendario_musculos', 0);
    }

    public function test_solo_administrador_puede_aplicar_el_patron(): void
    {
        $grupo = GrupoMuscular::create(['nombre' => 'Pierna', 'activo' => true]);
        $referencia = Carbon::now('America/La_Paz')->addMonthNoOverflow();

        foreach (['Recepcionista', 'Entrenador', 'Cliente'] as $rol) {
            $this->actingAs($this->usuario($rol))
                ->post(route('calendario_musculos.patron'), [
                    'anio'   => $referencia->year,
                    'mes'    => $referencia->month,
                    'patron' => [1 => $grupo->id],
                ])
                ->assertStatus(403);
        }

        $this->assertDatabaseCount('calendario_musculos', 0);
    }
}
