<?php

namespace Tests\Feature;

use App\Models\AgentToken;
use App\Models\Cliente;
use App\Models\Rol;
use App\Models\Sede;
use App\Models\User;
use App\Services\AsistenciaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Regla de negocio: un cliente solo puede registrar UNA asistencia por día
 * calendario (hora local de la app). Aplica a la huella (agente biométrico)
 * y al escáner QR, porque vive en AsistenciaService.
 */
class AsistenciaUnaPorDiaTest extends TestCase
{
    use RefreshDatabase;

    private static int $seq = 0;

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function enFecha(string $fechaHora): void
    {
        $this->travelTo(Carbon::parse($fechaHora, config('app.timezone')));
    }

    private function crearSede(string $nombre = 'Sede Test'): Sede
    {
        return Sede::create(['nombre' => $nombre, 'direccion' => 'Calle 1', 'activo' => true]);
    }

    private function crearAgentToken(Sede $sede, string $plain = 'token-plano-test'): AgentToken
    {
        return AgentToken::create([
            'id_sede' => $sede->id_sede,
            'nombre'  => 'Agente Test',
            'token'   => hash('sha256', $plain),
            'activo'  => true,
        ]);
    }

    private function crearCliente(Sede $sede): Cliente
    {
        self::$seq++;
        $s = self::$seq;

        $rol  = Rol::firstOrCreate(['nombre' => 'Cliente']);
        $user = User::factory()->create(['rol_id' => $rol->id, 'id_sede' => $sede->id_sede]);

        $personaId = DB::table('personas')->insertGetId([
            'nombre_completo'  => "Cliente {$s}",
            'apellido_paterno' => 'Test',
            'apellido_materno' => 'Test',
            'ci'               => "CIDIA{$s}",
            'telefono'         => "+5917100000{$s}",
            'genero'           => 'M',
            'fecha_nacimiento' => '1990-01-01',
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        return Cliente::create([
            'id_persona'     => $personaId,
            'id_usuario'     => $user->id,
            'estado'         => 'activo',
            'dias_asistidos' => 0,
            'id_sede'        => $sede->id_sede,
        ]);
    }

    private function crearMembresia(Cliente $cliente): void
    {
        $idMembresia = DB::table('membresias')->insertGetId([
            'tipo_membresia' => 'Mensual',
            'precio'         => 100,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        DB::table('membresia_cliente')->insert([
            'id_cliente'   => $cliente->id_cliente,
            'id_membresia' => $idMembresia,
            'fecha_inicio' => now()->subDays(10)->toDateString(),
            'fecha_fin'    => now()->addDays(60)->toDateString(),
            'estado'       => 'activa', // pagada: da acceso
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);
    }

    private function crearOperador(Sede $sede, string $rolNombre = 'Recepcionista'): User
    {
        $rol = Rol::firstOrCreate(['nombre' => $rolNombre]);

        return User::factory()->create(['rol_id' => $rol->id, 'id_sede' => $sede->id_sede]);
    }

    private function payloadAgente(Cliente $cliente, Sede $sede): array
    {
        return [
            'id_cliente' => $cliente->id_cliente,
            'id_sede'    => $sede->id_sede,
            'fecha_hora' => now()->toDateTimeString(),
        ];
    }

    private function headersAgente(string $plain = 'token-plano-test'): array
    {
        return ['Authorization' => "Bearer {$plain}"];
    }

    // ── Vía agente biométrico (huella) ────────────────────────────────────────

    public function test_agente_una_asistencia_por_dia_y_al_dia_siguiente_vuelve_a_registrar(): void
    {
        $this->enFecha('2030-06-10 08:00:00');

        $sede    = $this->crearSede();
        $this->crearAgentToken($sede);
        $cliente = $this->crearCliente($sede);
        $this->crearMembresia($cliente);

        // Primera marcación del día → registra
        $this->postJson('/api/agente/asistencia', $this->payloadAgente($cliente, $sede), $this->headersAgente())
             ->assertStatus(201);

        // Segunda marcación el mismo día (horas después, fuera de la ventana de minutos) → rechazada
        $this->enFecha('2030-06-10 17:30:00');
        $this->postJson('/api/agente/asistencia', $this->payloadAgente($cliente, $sede), $this->headersAgente())
             ->assertStatus(409)
             ->assertJsonFragment([
                 'duplicado' => true,
                 'motivo'    => AsistenciaService::YA_MARCO_HOY,
                 'message'   => AsistenciaService::MENSAJE_YA_MARCO_HOY,
             ]);

        $this->assertDatabaseCount('asistencias', 1);

        // Al día siguiente → registra de nuevo
        $this->enFecha('2030-06-11 08:00:00');
        $this->postJson('/api/agente/asistencia', $this->payloadAgente($cliente, $sede), $this->headersAgente())
             ->assertStatus(201);

        $this->assertDatabaseCount('asistencias', 2);
    }

    public function test_agente_el_limite_diario_es_por_cliente_no_global(): void
    {
        $this->enFecha('2030-06-10 08:00:00');

        $sede = $this->crearSede();
        $this->crearAgentToken($sede);

        $clienteA = $this->crearCliente($sede);
        $clienteB = $this->crearCliente($sede);
        $this->crearMembresia($clienteA);
        $this->crearMembresia($clienteB);

        $this->postJson('/api/agente/asistencia', $this->payloadAgente($clienteA, $sede), $this->headersAgente())
             ->assertStatus(201);

        // Otro cliente el mismo día no se ve afectado
        $this->postJson('/api/agente/asistencia', $this->payloadAgente($clienteB, $sede), $this->headersAgente())
             ->assertStatus(201);

        $this->assertDatabaseCount('asistencias', 2);
    }

    // ── Vía escáner QR (pantalla web) ─────────────────────────────────────────

    public function test_qr_una_asistencia_por_dia_y_al_dia_siguiente_vuelve_a_registrar(): void
    {
        $this->enFecha('2030-06-10 08:00:00');

        $sede     = $this->crearSede();
        $cliente  = $this->crearCliente($sede);
        $this->crearMembresia($cliente);
        $operador = $this->crearOperador($sede);

        // Primera marcación del día → registra
        $this->actingAs($operador)
             ->postJson('/asistencias-qr', ['qr_token' => $cliente->qr_token])
             ->assertStatus(200)
             ->assertJsonFragment(['estado' => 'ok']);

        // Segunda marcación el mismo día → ámbar informativo "ya marcaste hoy"
        $this->enFecha('2030-06-10 17:30:00');
        $this->actingAs($operador)
             ->postJson('/asistencias-qr', ['qr_token' => $cliente->qr_token])
             ->assertStatus(200)
             ->assertJsonFragment([
                 'estado'  => 'duplicado',
                 'mensaje' => AsistenciaService::MENSAJE_YA_MARCO_HOY,
             ]);

        $this->assertDatabaseCount('asistencias', 1);

        // Al día siguiente → registra de nuevo
        $this->enFecha('2030-06-11 08:00:00');
        $this->actingAs($operador)
             ->postJson('/asistencias-qr', ['qr_token' => $cliente->qr_token])
             ->assertStatus(200)
             ->assertJsonFragment(['estado' => 'ok']);

        $this->assertDatabaseCount('asistencias', 2);
    }

    public function test_qr_duplicado_inmediato_sigue_bloqueado(): void
    {
        $this->enFecha('2030-06-10 08:00:00');

        $sede     = $this->crearSede();
        $cliente  = $this->crearCliente($sede);
        $this->crearMembresia($cliente);
        $operador = $this->crearOperador($sede);

        $this->actingAs($operador)
             ->postJson('/asistencias-qr', ['qr_token' => $cliente->qr_token])
             ->assertJsonFragment(['estado' => 'ok']);

        // Re-escaneo al minuto: también rechazado (una por día ya lo cubre)
        $this->enFecha('2030-06-10 08:01:00');
        $this->actingAs($operador)
             ->postJson('/asistencias-qr', ['qr_token' => $cliente->qr_token])
             ->assertJsonFragment(['estado' => 'duplicado']);

        $this->assertDatabaseCount('asistencias', 1);
    }
}
