<?php

namespace Tests\Feature;

use App\Console\Commands\NotificacionesSaldoPendienteCommand;
use App\Jobs\EnviarNotificacionJob;
use App\Models\MembresiaCliente;
use App\Models\Notificacion;
use App\Models\Rol;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Pago en 2 Cuotas — Etapa 5: notificaciones:saldo-pendiente, hermano de
 * notificaciones:vencimientos (sin tocarlo — ver NotificacionesVencimientosTest,
 * corrido junto a esta suite en la verificación final). Mismo patrón: Job en
 * cola + dedupe por tipo+cliente+día, pero con su propio tipo
 * ('recordatorio_saldo_cuota') y su propio criterio (saldo_pendiente > 0 +
 * fecha_limite_saldo, no fecha_fin de la membresía).
 */
class NotificacionesSaldoPendienteTest extends TestCase
{
    use RefreshDatabase;

    private function crearClienteConSaldo(int $diasParaVencerSaldo, ?float $saldoPendiente = 80): MembresiaCliente
    {
        $sede = Sede::create(['nombre' => 'Sede Saldo Test', 'direccion' => 'Calle 1']);
        $rol  = Rol::firstOrCreate(['nombre' => 'Cliente']);
        $user = User::factory()->create(['rol_id' => $rol->id, 'id_sede' => $sede->id_sede]);

        $personaId = DB::table('personas')->insertGetId([
            'nombre_completo'  => 'Carla',
            'apellido_paterno' => 'Cuotas',
            'apellido_materno' => 'Saldo',
            'ci'               => '87654321',
            'telefono'         => '76543210',
            'genero'           => 'F',
            'fecha_nacimiento' => '1990-01-01',
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        $clienteId = DB::table('clientes')->insertGetId([
            'id_persona'     => $personaId,
            'id_usuario'     => $user->id,
            'estado'         => 'activo',
            'dias_asistidos' => 0,
            'id_sede'        => $sede->id_sede,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        $membresiaId = DB::table('membresias')->insertGetId([
            'tipo_membresia' => 'Mensual',
            'precio'         => 150,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        return MembresiaCliente::create([
            'id_cliente'          => $clienteId,
            'id_membresia'        => $membresiaId,
            'fecha_inicio'        => now()->subDays(10),
            'fecha_fin'           => now()->addDays(20)->toDateString(),
            'precio_final'        => 150,
            'id_sede'             => $sede->id_sede,
            'estado'              => MembresiaCliente::ESTADO_ACTIVA,
            'saldo_pendiente'     => $saldoPendiente,
            'fecha_limite_saldo'  => now()->addDays($diasParaVencerSaldo)->toDateString(),
        ]);
    }

    // ── 1. Caso principal: saldo que vence en 3 días ──────────────────────────

    public function test_encola_notificacion_para_saldo_que_vence_en_3_dias(): void
    {
        Bus::fake();

        $this->crearClienteConSaldo(diasParaVencerSaldo: 3);

        $this->artisan('notificaciones:saldo-pendiente')->assertSuccessful();

        $this->assertDatabaseCount('notificaciones', 1);
        $this->assertDatabaseHas('notificaciones', [
            'tipo'   => NotificacionesSaldoPendienteCommand::TIPO,
            'estado' => 'pendiente',
        ]);
        Bus::assertDispatched(EnviarNotificacionJob::class);
    }

    public function test_el_mensaje_incluye_el_monto_y_la_fecha_reales(): void
    {
        Bus::fake();

        $mc = $this->crearClienteConSaldo(diasParaVencerSaldo: 3, saldoPendiente: 80);

        $this->artisan('notificaciones:saldo-pendiente');

        $notificacion = Notificacion::firstOrFail();
        $this->assertStringContainsString('80.00', $notificacion->mensaje);
        $this->assertStringContainsString($mc->fecha_limite_saldo->format('d/m/Y'), $notificacion->mensaje);
        $this->assertStringContainsString('saldo pendiente', $notificacion->mensaje);
    }

    // ── 2. NO notifica lo que no corresponde ──────────────────────────────────

    public function test_no_notifica_una_membresia_pagada_completa(): void
    {
        Bus::fake();

        // saldo_pendiente null: pagada completa, aunque tenga fecha_fin cerca
        $this->crearClienteConSaldo(diasParaVencerSaldo: 3, saldoPendiente: null);

        $this->artisan('notificaciones:saldo-pendiente')->assertSuccessful();

        $this->assertDatabaseCount('notificaciones', 0);
        Bus::assertNotDispatched(EnviarNotificacionJob::class);
    }

    public function test_no_notifica_una_membresia_pagada_completa_con_saldo_en_cero(): void
    {
        Bus::fake();

        $this->crearClienteConSaldo(diasParaVencerSaldo: 3, saldoPendiente: 0);

        $this->artisan('notificaciones:saldo-pendiente');

        $this->assertDatabaseCount('notificaciones', 0);
        Bus::assertNotDispatched(EnviarNotificacionJob::class);
    }

    public function test_ignora_saldos_fuera_del_rango_de_dias(): void
    {
        Bus::fake();

        $this->crearClienteConSaldo(diasParaVencerSaldo: 10);

        $this->artisan('notificaciones:saldo-pendiente')->assertSuccessful();

        $this->assertDatabaseCount('notificaciones', 0);
        Bus::assertNotDispatched(EnviarNotificacionJob::class);
    }

    public function test_omite_clientes_inactivos(): void
    {
        Bus::fake();

        $mc = $this->crearClienteConSaldo(diasParaVencerSaldo: 3);
        DB::table('clientes')->where('id_cliente', $mc->id_cliente)->update(['estado' => 'inactivo']);

        $this->artisan('notificaciones:saldo-pendiente')->assertSuccessful();

        $this->assertDatabaseCount('notificaciones', 0);
        Bus::assertNotDispatched(EnviarNotificacionJob::class);
    }

    // ── 3. Dedupe: no se envía dos veces el mismo día ─────────────────────────

    public function test_no_duplica_si_el_cliente_ya_fue_notificado_hoy(): void
    {
        Bus::fake();

        $mc = $this->crearClienteConSaldo(diasParaVencerSaldo: 3);

        Notificacion::create([
            'id_cliente' => $mc->id_cliente,
            'tipo'       => NotificacionesSaldoPendienteCommand::TIPO,
            'mensaje'    => 'aviso previo',
            'estado'     => 'enviado',
        ]);

        $this->artisan('notificaciones:saldo-pendiente')->assertSuccessful();

        $this->assertDatabaseCount('notificaciones', 1); // sigue siendo solo el previo
        Bus::assertNotDispatched(EnviarNotificacionJob::class);
    }

    // ── 4. Tipo propio: no se mezcla con el de vencimiento de membresía ──────

    public function test_usa_un_tipo_distinto_al_recordatorio_de_vencimiento_de_membresia(): void
    {
        Bus::fake();

        $this->crearClienteConSaldo(diasParaVencerSaldo: 3);

        $this->artisan('notificaciones:saldo-pendiente');

        $notificacion = Notificacion::firstOrFail();
        $this->assertSame('recordatorio_saldo_cuota', $notificacion->tipo);
        $this->assertNotSame('recordatorio_vencimiento', $notificacion->tipo);
    }

    public function test_el_dedupe_de_vencimiento_no_bloquea_el_de_saldo_el_mismo_dia(): void
    {
        Bus::fake();

        $mc = $this->crearClienteConSaldo(diasParaVencerSaldo: 3);

        // Ya se le mandó HOY el aviso de vencimiento de MEMBRESÍA (tipo
        // distinto) — el de saldo debe seguir enviándose igual.
        Notificacion::create([
            'id_cliente' => $mc->id_cliente,
            'tipo'       => 'recordatorio_vencimiento',
            'mensaje'    => 'aviso de vencimiento de membresía, ya enviado hoy',
            'estado'     => 'enviado',
        ]);

        $this->artisan('notificaciones:saldo-pendiente')->assertSuccessful();

        $this->assertDatabaseCount('notificaciones', 2); // el previo + el nuevo de saldo
        Bus::assertDispatched(EnviarNotificacionJob::class);
    }

    // ── 5. Reusa el Job/canales existentes: se ve reflejado en el envío real ──

    public function test_el_job_encolado_usa_el_mismo_notificacionservice_multicanal(): void
    {
        Bus::fake();

        $this->crearClienteConSaldo(diasParaVencerSaldo: 3);

        $this->artisan('notificaciones:saldo-pendiente');

        Bus::assertDispatched(EnviarNotificacionJob::class, function ($job) {
            $notificacion = $job->notificacion;
            return $notificacion->tipo === NotificacionesSaldoPendienteCommand::TIPO
                && $notificacion->estado === 'pendiente';
        });
    }
}
