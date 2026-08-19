<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Membresia;
use App\Models\MembresiaCliente;
use App\Models\Persona;
use App\Models\Rol;
use App\Models\Sede;
use App\Models\User;
use App\Services\AsistenciaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Pago en 2 Cuotas — Etapa 4: el comando membresias:desactivar-saldo-vencido
 * suspende (suspendida_saldo) las membresías activas con saldo_pendiente > 0
 * y fecha_limite_saldo ya vencida. Solo toca ESE caso puntual: pagadas
 * completas, saldo con plazo futuro, y membresías en otros estados quedan
 * exactamente igual. fecha_fin nunca se toca.
 */
class DesactivarSaldoVencidoTest extends TestCase
{
    use RefreshDatabase;

    private static int $seq = 0;

    private function crearCliente(?Sede $sede = null): Cliente
    {
        self::$seq++;
        $s = self::$seq;

        $sede ??= Sede::create(['nombre' => "Sede Desactiva {$s}", 'direccion' => 'Calle 1', 'activo' => true]);

        $user = User::factory()->create([
            'rol_id'  => Rol::firstOrCreate(['nombre' => 'Cliente'])->id,
            'id_sede' => $sede->id_sede,
        ]);

        $personaId = DB::table('personas')->insertGetId([
            'nombre_completo'  => "Desactiva {$s}",
            'apellido_paterno' => 'Test',
            'apellido_materno' => 'Saldo',
            'ci'               => "CIDESACT{$s}",
            'telefono'         => "+5917900000{$s}",
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

    private function crearMembresiaCliente(Cliente $cliente, array $overrides = []): MembresiaCliente
    {
        $membresia = Membresia::create(['tipo_membresia' => 'Mensual', 'precio' => 150]);

        return MembresiaCliente::create(array_merge([
            'id_cliente'   => $cliente->id_cliente,
            'id_membresia' => $membresia->id_membresia,
            'fecha_inicio' => now()->subDays(10)->toDateString(),
            'fecha_fin'    => now()->addDays(20)->toDateString(),
            'precio_final' => 150,
            'descuento'    => 0,
            'estado'       => MembresiaCliente::ESTADO_ACTIVA,
            'id_sede'      => $cliente->id_sede,
        ], $overrides));
    }

    // ── 1. Caso principal: saldo vencido y activa → se suspende ─────────────

    public function test_desactiva_una_membresia_activa_con_saldo_vencido(): void
    {
        $cliente = $this->crearCliente();
        $mc = $this->crearMembresiaCliente($cliente, [
            'saldo_pendiente'    => 80,
            'fecha_limite_saldo' => now()->subDays(2)->toDateString(),
        ]);
        $finOriginal = $mc->fecha_fin;

        $this->artisan('membresias:desactivar-saldo-vencido')->assertExitCode(0);

        $mc->refresh();
        $this->assertSame(MembresiaCliente::ESTADO_SUSPENDIDA_SALDO, $mc->estado);
        $this->assertSame((string) $finOriginal, (string) $mc->fecha_fin); // fecha_fin intacta
        $this->assertEquals(80.00, (float) $mc->saldo_pendiente); // el saldo no se toca, solo el estado
    }

    public function test_desactivar_bloquea_la_entrada_por_huella(): void
    {
        $sede    = Sede::create(['nombre' => 'Sede Bloqueo Huella', 'direccion' => 'Calle 1', 'activo' => true]);
        $cliente = $this->crearCliente($sede);
        $this->crearMembresiaCliente($cliente, [
            'saldo_pendiente'    => 80,
            'fecha_limite_saldo' => now()->subDays(2)->toDateString(),
            'id_sede'            => $sede->id_sede,
        ]);

        $this->artisan('membresias:desactivar-saldo-vencido');

        $servicio  = app(AsistenciaService::class);
        $resultado = $servicio->registrarEntrada($cliente, $sede->id_sede);

        $this->assertFalse($resultado['permitido']);
        $this->assertSame(AsistenciaService::MEMBRESIA_PENDIENTE_PAGO, $resultado['motivo']);
    }

    // ── 2. NO toca lo que no debe ─────────────────────────────────────────────

    public function test_no_toca_una_membresia_pagada_completa(): void
    {
        $cliente = $this->crearCliente();
        // saldo_pendiente null: pagada completa
        $mc = $this->crearMembresiaCliente($cliente);

        $this->artisan('membresias:desactivar-saldo-vencido');

        $mc->refresh();
        $this->assertSame(MembresiaCliente::ESTADO_ACTIVA, $mc->estado);
    }

    public function test_no_toca_una_membresia_pagada_completa_con_saldo_en_cero(): void
    {
        $cliente = $this->crearCliente();
        // Caso real tras pagar el saldo (Etapa 3): 0, no null, con
        // fecha_limite_saldo todavía puesta (histórica) — no debe activar
        // el filtro "saldo_pendiente > 0".
        $mc = $this->crearMembresiaCliente($cliente, [
            'saldo_pendiente'    => 0,
            'fecha_limite_saldo' => now()->subDays(5)->toDateString(),
        ]);

        $this->artisan('membresias:desactivar-saldo-vencido');

        $mc->refresh();
        $this->assertSame(MembresiaCliente::ESTADO_ACTIVA, $mc->estado);
    }

    public function test_no_toca_una_membresia_con_saldo_cuya_fecha_limite_todavia_no_llego(): void
    {
        $cliente = $this->crearCliente();
        $mc = $this->crearMembresiaCliente($cliente, [
            'saldo_pendiente'    => 80,
            'fecha_limite_saldo' => now()->addDays(3)->toDateString(),
        ]);

        $this->artisan('membresias:desactivar-saldo-vencido');

        $mc->refresh();
        $this->assertSame(MembresiaCliente::ESTADO_ACTIVA, $mc->estado);
    }

    public function test_no_toca_una_membresia_cuya_fecha_limite_es_justo_hoy(): void
    {
        // "hoy" todavía cuenta como a tiempo (mismo criterio que vigencia:
        // fecha_fin >= hoy sigue siendo vigente, no vencida).
        $cliente = $this->crearCliente();
        $mc = $this->crearMembresiaCliente($cliente, [
            'saldo_pendiente'    => 80,
            'fecha_limite_saldo' => now()->toDateString(),
        ]);

        $this->artisan('membresias:desactivar-saldo-vencido');

        $mc->refresh();
        $this->assertSame(MembresiaCliente::ESTADO_ACTIVA, $mc->estado);
    }

    public function test_no_toca_una_membresia_que_ya_esta_pendiente_de_pago(): void
    {
        $cliente = $this->crearCliente();
        $mc = $this->crearMembresiaCliente($cliente, [
            'estado'             => MembresiaCliente::ESTADO_PENDIENTE,
            'saldo_pendiente'    => 80,
            'fecha_limite_saldo' => now()->subDays(2)->toDateString(),
        ]);

        $this->artisan('membresias:desactivar-saldo-vencido');

        $mc->refresh();
        $this->assertSame(MembresiaCliente::ESTADO_PENDIENTE, $mc->estado);
    }

    public function test_no_toca_una_membresia_ya_suspendida_por_saldo(): void
    {
        $cliente = $this->crearCliente();
        $mc = $this->crearMembresiaCliente($cliente, [
            'estado'             => MembresiaCliente::ESTADO_SUSPENDIDA_SALDO,
            'saldo_pendiente'    => 80,
            'fecha_limite_saldo' => now()->subDays(10)->toDateString(),
        ]);

        $this->artisan('membresias:desactivar-saldo-vencido');

        $mc->refresh();
        $this->assertSame(MembresiaCliente::ESTADO_SUSPENDIDA_SALDO, $mc->estado); // sigue igual, no rompe nada
    }

    // ── 3. Varias membresías a la vez: solo las que corresponden ─────────────

    public function test_procesa_varias_membresias_y_solo_desactiva_las_que_corresponden(): void
    {
        $vencida1 = $this->crearMembresiaCliente($this->crearCliente(), [
            'saldo_pendiente' => 80, 'fecha_limite_saldo' => now()->subDays(1)->toDateString(),
        ]);
        $vencida2 = $this->crearMembresiaCliente($this->crearCliente(), [
            'saldo_pendiente' => 40, 'fecha_limite_saldo' => now()->subDays(30)->toDateString(),
        ]);
        $pagadaCompleta = $this->crearMembresiaCliente($this->crearCliente());
        $conPlazoFuturo = $this->crearMembresiaCliente($this->crearCliente(), [
            'saldo_pendiente' => 60, 'fecha_limite_saldo' => now()->addDays(5)->toDateString(),
        ]);

        $this->artisan('membresias:desactivar-saldo-vencido');

        $this->assertSame(MembresiaCliente::ESTADO_SUSPENDIDA_SALDO, $vencida1->fresh()->estado);
        $this->assertSame(MembresiaCliente::ESTADO_SUSPENDIDA_SALDO, $vencida2->fresh()->estado);
        $this->assertSame(MembresiaCliente::ESTADO_ACTIVA, $pagadaCompleta->fresh()->estado);
        $this->assertSame(MembresiaCliente::ESTADO_ACTIVA, $conPlazoFuturo->fresh()->estado);
    }

    // ── 4. --dry-run no cambia nada ───────────────────────────────────────────

    public function test_dry_run_no_desactiva_ninguna_membresia(): void
    {
        $cliente = $this->crearCliente();
        $mc = $this->crearMembresiaCliente($cliente, [
            'saldo_pendiente'    => 80,
            'fecha_limite_saldo' => now()->subDays(2)->toDateString(),
        ]);

        $this->artisan('membresias:desactivar-saldo-vencido', ['--dry-run' => true])
             ->assertExitCode(0);

        $mc->refresh();
        $this->assertSame(MembresiaCliente::ESTADO_ACTIVA, $mc->estado); // nada cambió
        $this->assertEquals(80.00, (float) $mc->saldo_pendiente);
    }

    public function test_dry_run_igual_loguea_lo_que_desactivaria(): void
    {
        Log::spy();

        $cliente = $this->crearCliente();
        $this->crearMembresiaCliente($cliente, [
            'saldo_pendiente'    => 80,
            'fecha_limite_saldo' => now()->subDays(2)->toDateString(),
        ]);

        $this->artisan('membresias:desactivar-saldo-vencido', ['--dry-run' => true]);

        Log::shouldHaveReceived('info')
            ->withArgs(fn ($mensaje) => str_contains($mensaje, 'DRY-RUN') && str_contains($mensaje, '1'))
            ->once();
    }

    public function test_sin_membresias_pendientes_no_rompe_y_no_loguea_como_error(): void
    {
        $this->artisan('membresias:desactivar-saldo-vencido')->assertExitCode(0);
        // No hay ninguna membresía en la BD: no debe fallar ni tirar excepción.
        $this->assertTrue(true);
    }
}
