<?php

namespace Tests\Feature;

use App\Models\CalendarioMusculo;
use App\Models\Cliente;
use App\Models\Ejercicio;
use App\Models\GrupoMuscular;
use App\Models\Rol;
use App\Models\Rutina;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Etapa 4: portal del cliente, "Mi Rutina". El cliente (con membresía
 * vigente — mismo control que ya usa Mi Membresía) ve qué músculo toca el
 * día y elige de qué entrenador seguir la rutina.
 */
class PortalRutinaTest extends TestCase
{
    use RefreshDatabase;

    private static int $seq = 0;

    private function crearClienteConUsuario(): array
    {
        self::$seq++;
        $s = self::$seq;

        $sede = Sede::create(['nombre' => "Sede R{$s}", 'direccion' => 'Calle 1', 'activo' => true]);
        $rol  = Rol::firstOrCreate(['nombre' => 'Cliente']);
        $user = User::factory()->create(['rol_id' => $rol->id, 'id_sede' => $sede->id_sede]);

        $personaId = DB::table('personas')->insertGetId([
            'nombre_completo'  => "Cliente R{$s}",
            'apellido_paterno' => 'Portal',
            'apellido_materno' => 'Rutina',
            'ci'               => "CIRUT{$s}",
            'telefono'         => "+5917400000{$s}",
            'genero'           => 'M',
            'fecha_nacimiento' => '1990-01-01',
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        $cliente = Cliente::create([
            'id_persona'     => $personaId,
            'id_usuario'     => $user->id,
            'estado'         => 'activo',
            'dias_asistidos' => 0,
            'id_sede'        => $sede->id_sede,
        ]);

        return [$user, $cliente, $sede];
    }

    private function darMembresiaVigente(Cliente $cliente): void
    {
        $idMembresia = DB::table('membresias')->insertGetId([
            'tipo_membresia' => 'Mensual', 'precio' => 100,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::table('membresia_cliente')->insert([
            'id_cliente'   => $cliente->id_cliente,
            'id_membresia' => $idMembresia,
            'fecha_inicio' => now()->subDays(10)->toDateString(),
            'fecha_fin'    => now()->addDays(20)->toDateString(),
            'estado'       => 'activa',
            'created_at'   => now(), 'updated_at' => now(),
        ]);
    }

    private function usuario(string $rol): User
    {
        return User::factory()->create(['rol_id' => Rol::firstOrCreate(['nombre' => $rol])->id]);
    }

    private function crearEjercicio(GrupoMuscular $grupo, string $nombre): Ejercicio
    {
        return Ejercicio::create([
            'nombre' => $nombre, 'category' => 'chest', 'target' => 'pectorals',
            'equipment' => 'barbell', 'id_grupo_muscular' => $grupo->id, 'origen' => 'dataset',
        ]);
    }

    /** Entrenador con su rutina ya armada para $fecha, en la sede dada. */
    private function crearRutinaDeEntrenador(Carbon $fecha, GrupoMuscular $grupo, int $idSede, array $ejercicios): Rutina
    {
        $entrenador = $this->usuario('Entrenador');

        $rutina = Rutina::create([
            'fecha' => $fecha->toDateString(),
            'id_grupo_muscular' => $grupo->id,
            'id_usuario' => $entrenador->id,
            'id_sede' => $idSede,
        ]);

        foreach ($ejercicios as $i => $ej) {
            $rutina->ejercicios()->create([
                'id_ejercicio' => $ej->id, 'series' => 4, 'repeticiones' => '10', 'orden' => $i,
            ]);
        }

        return $rutina;
    }

    // ── Membresía vigente ve el músculo del día y las rutinas disponibles ──

    public function test_cliente_con_membresia_vigente_ve_el_musculo_de_hoy_y_las_rutinas(): void
    {
        $this->travelTo(Carbon::create(2026, 7, 20, 10, 0, 0, 'America/La_Paz')); // lunes

        [$user, $cliente, $sede] = $this->crearClienteConUsuario();
        $this->darMembresiaVigente($cliente);

        $grupo = GrupoMuscular::create(['nombre' => 'Pecho y tríceps', 'activo' => true]);
        CalendarioMusculo::create(['fecha' => '2026-07-20', 'id_grupo_muscular' => $grupo->id, 'id_sede' => $sede->id_sede]);
        $ej = $this->crearEjercicio($grupo, 'Press banca');
        $this->crearRutinaDeEntrenador(Carbon::parse('2026-07-20'), $grupo, $sede->id_sede, [$ej]);

        $this->actingAs($user)
            ->get('/mi-rutina')
            ->assertStatus(200)
            ->assertSee('Pecho y tríceps')
            ->assertSee('1 ejercicio(s)');
    }

    public function test_cliente_elige_un_entrenador_y_ve_sus_ejercicios_con_series_y_repeticiones(): void
    {
        $this->travelTo(Carbon::create(2026, 7, 20, 10, 0, 0, 'America/La_Paz'));

        [$user, $cliente, $sede] = $this->crearClienteConUsuario();
        $this->darMembresiaVigente($cliente);

        $grupo = GrupoMuscular::create(['nombre' => 'Pierna', 'activo' => true]);
        CalendarioMusculo::create(['fecha' => '2026-07-20', 'id_grupo_muscular' => $grupo->id, 'id_sede' => $sede->id_sede]);
        $ej = $this->crearEjercicio($grupo, 'Sentadilla');
        $rutina = $this->crearRutinaDeEntrenador(Carbon::parse('2026-07-20'), $grupo, $sede->id_sede, [$ej]);

        $this->actingAs($user)
            ->get('/mi-rutina?entrenador=' . $rutina->id_usuario)
            ->assertStatus(200)
            ->assertSee('Sentadilla')
            ->assertSee('4') // series
            ->assertSee('10'); // repeticiones
    }

    public function test_puede_elegir_entre_varios_entrenadores_del_mismo_dia(): void
    {
        $this->travelTo(Carbon::create(2026, 7, 20, 10, 0, 0, 'America/La_Paz'));

        [$user, $cliente, $sede] = $this->crearClienteConUsuario();
        $this->darMembresiaVigente($cliente);

        $grupo = GrupoMuscular::create(['nombre' => 'Hombro', 'activo' => true]);
        CalendarioMusculo::create(['fecha' => '2026-07-20', 'id_grupo_muscular' => $grupo->id, 'id_sede' => $sede->id_sede]);
        $ejA = $this->crearEjercicio($grupo, 'Press militar');
        $ejB = $this->crearEjercicio($grupo, 'Elevaciones laterales');
        $rutinaA = $this->crearRutinaDeEntrenador(Carbon::parse('2026-07-20'), $grupo, $sede->id_sede, [$ejA]);
        $rutinaB = $this->crearRutinaDeEntrenador(Carbon::parse('2026-07-20'), $grupo, $sede->id_sede, [$ejB]);

        $this->actingAs($user)->get('/mi-rutina?entrenador=' . $rutinaA->id_usuario)
            ->assertSee('Press militar')->assertDontSee('Elevaciones laterales');

        $this->actingAs($user)->get('/mi-rutina?entrenador=' . $rutinaB->id_usuario)
            ->assertSee('Elevaciones laterales')->assertDontSee('Press militar');
    }

    // ── Día sin rutinas publicadas ──────────────────────────────────────────

    public function test_dia_con_musculo_pero_sin_rutinas_publicadas_muestra_el_mensaje(): void
    {
        $this->travelTo(Carbon::create(2026, 7, 20, 10, 0, 0, 'America/La_Paz'));

        [$user, $cliente, $sede] = $this->crearClienteConUsuario();
        $this->darMembresiaVigente($cliente);

        $grupo = GrupoMuscular::create(['nombre' => 'Espalda y bíceps', 'activo' => true]);
        CalendarioMusculo::create(['fecha' => '2026-07-20', 'id_grupo_muscular' => $grupo->id, 'id_sede' => $sede->id_sede]);
        // Sin ninguna rutina creada ese día.

        $this->actingAs($user)
            ->get('/mi-rutina')
            ->assertStatus(200)
            ->assertSee('Espalda y bíceps')
            ->assertSee('Aún no hay rutinas publicadas');
    }

    public function test_domingo_muestra_mensaje_de_descanso(): void
    {
        $this->travelTo(Carbon::create(2026, 7, 19, 10, 0, 0, 'America/La_Paz')); // domingo

        [$user, $cliente] = $this->crearClienteConUsuario();
        $this->darMembresiaVigente($cliente);

        $this->actingAs($user)
            ->get('/mi-rutina')
            ->assertStatus(200)
            ->assertSee('Hoy descansa');
    }

    public function test_dia_sin_musculo_asignado_muestra_mensaje_claro(): void
    {
        $this->travelTo(Carbon::create(2026, 7, 20, 10, 0, 0, 'America/La_Paz')); // lunes, sin calendario

        [$user, $cliente] = $this->crearClienteConUsuario();
        $this->darMembresiaVigente($cliente);

        $this->actingAs($user)
            ->get('/mi-rutina')
            ->assertStatus(200)
            ->assertSee('Hoy el gimnasio no tiene rutina asignada');
    }

    // ── Sin membresía vigente ────────────────────────────────────────────────

    public function test_cliente_sin_membresia_vigente_ve_mensaje_de_renovar_sin_datos(): void
    {
        $this->travelTo(Carbon::create(2026, 7, 20, 10, 0, 0, 'America/La_Paz'));

        [$user, $cliente, $sede] = $this->crearClienteConUsuario(); // sin darMembresiaVigente()

        $grupo = GrupoMuscular::create(['nombre' => 'Pecho y tríceps', 'activo' => true]);
        CalendarioMusculo::create(['fecha' => '2026-07-20', 'id_grupo_muscular' => $grupo->id, 'id_sede' => $sede->id_sede]);
        $ej = $this->crearEjercicio($grupo, 'Press banca');
        $this->crearRutinaDeEntrenador(Carbon::parse('2026-07-20'), $grupo, $sede->id_sede, [$ej]);

        $this->actingAs($user)
            ->get('/mi-rutina')
            ->assertStatus(200)
            ->assertSee('No tienes una membresía activa')
            ->assertSee('Renueva tu membresía')
            // el bloqueo es del servidor: ni el músculo ni la rutina se filtran a la vista
            ->assertDontSee('Pecho y tríceps')
            ->assertDontSee('Press banca');
    }

    public function test_cliente_con_membresia_vencida_no_ve_la_rutina(): void
    {
        $this->travelTo(Carbon::create(2026, 7, 20, 10, 0, 0, 'America/La_Paz'));

        [$user, $cliente, $sede] = $this->crearClienteConUsuario();

        $idMembresia = DB::table('membresias')->insertGetId([
            'tipo_membresia' => 'Mensual', 'precio' => 100, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('membresia_cliente')->insert([
            'id_cliente' => $cliente->id_cliente, 'id_membresia' => $idMembresia,
            'fecha_inicio' => now()->subDays(40)->toDateString(),
            'fecha_fin' => now()->subDays(5)->toDateString(), // vencida
            'estado' => 'activa', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->get('/mi-rutina')
            ->assertStatus(200)
            ->assertSee('No tienes una membresía activa');
    }

    // ── Historial: día anterior ──────────────────────────────────────────────

    public function test_cliente_puede_ver_la_rutina_de_un_dia_anterior(): void
    {
        $this->travelTo(Carbon::create(2026, 7, 21, 10, 0, 0, 'America/La_Paz')); // martes

        [$user, $cliente, $sede] = $this->crearClienteConUsuario();
        $this->darMembresiaVigente($cliente);

        $grupo = GrupoMuscular::create(['nombre' => 'Pierna', 'activo' => true]);
        // Ayer (lunes 2026-07-20) tocaba Pierna, hoy no hay nada asignado.
        CalendarioMusculo::create(['fecha' => '2026-07-20', 'id_grupo_muscular' => $grupo->id, 'id_sede' => $sede->id_sede]);
        $ej = $this->crearEjercicio($grupo, 'Sentadilla');
        $this->crearRutinaDeEntrenador(Carbon::parse('2026-07-20'), $grupo, $sede->id_sede, [$ej]);

        // Hoy: sin músculo asignado.
        $this->actingAs($user)->get('/mi-rutina')
            ->assertSee('Hoy el gimnasio no tiene rutina asignada');

        // Ayer: sí había.
        $this->actingAs($user)->get('/mi-rutina?fecha=2026-07-20')
            ->assertStatus(200)
            ->assertSee('Pierna');
    }

    public function test_no_se_puede_ver_una_fecha_futura(): void
    {
        $this->travelTo(Carbon::create(2026, 7, 20, 10, 0, 0, 'America/La_Paz'));

        [$user, $cliente, $sede] = $this->crearClienteConUsuario();
        $this->darMembresiaVigente($cliente);

        $grupo = GrupoMuscular::create(['nombre' => 'Full body', 'activo' => true]);
        // Mañana (futuro) sí tiene músculo asignado, pero no debe verse hoy.
        CalendarioMusculo::create(['fecha' => '2026-07-21', 'id_grupo_muscular' => $grupo->id, 'id_sede' => $sede->id_sede]);

        $this->actingAs($user)
            ->get('/mi-rutina?fecha=2026-07-21')
            ->assertStatus(200)
            // se recorta a "hoy" (20 de julio), que no tiene músculo asignado
            ->assertSee('Hoy el gimnasio no tiene rutina asignada')
            ->assertDontSee('Full body');
    }

    // ── Permisos: solo Cliente ────────────────────────────────────────────────

    public function test_solo_el_rol_cliente_accede_a_mi_rutina(): void
    {
        foreach (['Administrador', 'Recepcionista', 'Entrenador'] as $rol) {
            $this->actingAs($this->usuario($rol))
                ->get('/mi-rutina')
                ->assertStatus(403);
        }
    }
}
