<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Descuento;
use App\Models\Membresia;
use App\Models\MembresiaCliente;
use App\Models\Pago;
use App\Models\Rol;
use App\Models\Sede;
use App\Models\User;
use App\Services\AsistenciaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Pago en 2 Cuotas — Etapa 2: registrar el pago PARCIAL (primera cuota) en
 * PagoController::store. El campo nuevo es "monto_pagado" (NO "monto" —
 * a propósito, para no chocar con el test existente que manda monto=1
 * esperando que se ignore). Si no se manda, o llega igual al precio final,
 * es el pago completo de siempre — probado explícitamente acá también,
 * además de toda la suite de PagoRegistroTest/PagoMembresiaActivacionTest/
 * PagoAcumulacionMembresiaTest, que quedaron sin tocar y siguen en verde.
 */
class PagoCuotasEtapa2Test extends TestCase
{
    use RefreshDatabase;

    private static int $seq = 0;

    private function operador(string $rol = 'Recepcionista'): User
    {
        return User::factory()->create(['rol_id' => Rol::firstOrCreate(['nombre' => $rol])->id]);
    }

    private function crearCliente(?Sede $sede = null): Cliente
    {
        self::$seq++;
        $s = self::$seq;

        $sede ??= Sede::create(['nombre' => "Sede Cuotas {$s}", 'direccion' => 'Calle 1', 'activo' => true]);

        $user = User::factory()->create([
            'rol_id'  => Rol::firstOrCreate(['nombre' => 'Cliente'])->id,
            'id_sede' => $sede->id_sede,
        ]);

        $personaId = DB::table('personas')->insertGetId([
            'nombre_completo'  => "Cuotas Etapa2 {$s}",
            'apellido_paterno' => 'Test',
            'apellido_materno' => 'Parcial',
            'ci'               => "CIET2{$s}",
            'telefono'         => "+5917700000{$s}",
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

    private function sinDescuento(): Descuento
    {
        return Descuento::where('nombre', 'Sin descuento')->firstOrFail();
    }

    private function registrarPago(Cliente $cliente, Membresia $membresia, array $extra = [])
    {
        return $this->actingAs($this->operador())->post('/pagos', array_merge([
            'id_cliente'   => $cliente->id_cliente,
            'id_membresia' => $membresia->id_membresia,
            'id_descuento' => $this->sinDescuento()->id,
            'metodo_pago'  => 'efectivo',
        ], $extra));
    }

    // ── 1. Pago completo (sin monto_pagado): EXACTAMENTE como hoy ────────────

    public function test_sin_monto_pagado_el_saldo_y_la_fecha_limite_quedan_null(): void
    {
        $cliente   = $this->crearCliente();
        $membresia = Membresia::create(['tipo_membresia' => 'Mensual', 'precio' => 150]);

        $this->registrarPago($cliente, $membresia)->assertRedirect(route('pagos.index'));

        $mc   = MembresiaCliente::firstOrFail();
        $pago = Pago::firstOrFail();

        $this->assertSame('activa', $mc->estado);
        $this->assertNull($mc->saldo_pendiente);
        $this->assertNull($mc->fecha_limite_saldo);
        $this->assertEquals(150.00, (float) $pago->monto);
    }

    // ── 2. monto_pagado explícito PERO igual al precio final: también completo ──

    public function test_monto_pagado_igual_al_precio_final_se_trata_como_pago_completo(): void
    {
        $cliente   = $this->crearCliente();
        $membresia = Membresia::create(['tipo_membresia' => 'Mensual', 'precio' => 150]);

        $this->registrarPago($cliente, $membresia, ['monto_pagado' => 150])
             ->assertRedirect(route('pagos.index'));

        $mc = MembresiaCliente::firstOrFail();

        $this->assertSame('activa', $mc->estado);
        $this->assertNull($mc->saldo_pendiente);
        $this->assertNull($mc->fecha_limite_saldo);
    }

    // ── 3. Pago parcial: activa igual, guarda saldo y fecha límite ───────────

    public function test_pago_parcial_activa_la_membresia_y_guarda_el_saldo_y_la_fecha(): void
    {
        $cliente   = $this->crearCliente();
        $membresia = Membresia::create(['tipo_membresia' => 'Mensual', 'precio' => 150]);
        $fechaLimite = now()->addDays(15)->toDateString();

        $this->registrarPago($cliente, $membresia, [
            'monto_pagado'       => 70,
            'fecha_limite_saldo' => $fechaLimite,
        ])->assertRedirect(route('pagos.index'));

        $mc   = MembresiaCliente::firstOrFail();
        $pago = Pago::firstOrFail();

        $this->assertSame('activa', $mc->estado); // activa igual, con saldo pendiente
        $this->assertEquals(80.00, (float) $mc->saldo_pendiente); // 150 - 70, calculado en servidor
        $this->assertSame($fechaLimite, $mc->fecha_limite_saldo->toDateString());
        $this->assertEquals(70.00, (float) $pago->monto);
        $this->assertTrue($mc->tieneSaldoPendiente());
    }

    public function test_pago_parcial_permite_entrar_con_huella_igual_que_un_pago_completo(): void
    {
        $sede      = Sede::create(['nombre' => 'Sede Cuota Parcial', 'direccion' => 'Calle 1', 'activo' => true]);
        $cliente   = $this->crearCliente($sede);
        $membresia = Membresia::create(['tipo_membresia' => 'Mensual', 'precio' => 150]);

        $this->registrarPago($cliente, $membresia, [
            'monto_pagado'       => 70,
            'fecha_limite_saldo' => now()->addDays(15)->toDateString(),
        ]);

        $servicio  = app(AsistenciaService::class);
        $resultado = $servicio->registrarEntrada($cliente, $sede->id_sede);

        $this->assertTrue($resultado['permitido']);
        $this->assertDatabaseCount('asistencias', 1);
    }

    public function test_el_saldo_se_calcula_en_el_servidor_no_se_confia_en_el_cliente(): void
    {
        $cliente   = $this->crearCliente();
        $membresia = Membresia::create(['tipo_membresia' => 'Mensual', 'precio' => 150]);
        $descuento = Descuento::create(['nombre' => 'Cuotas20', 'porcentaje' => 20, 'activo' => true]);
        // precioFinal real = 150 * 0.8 = 120

        $this->registrarPago($cliente, $membresia, [
            'id_descuento'       => $descuento->id,
            'monto_pagado'       => 50,
            'fecha_limite_saldo' => now()->addDays(10)->toDateString(),
            // Intento de manipular el saldo directamente: no existe tal campo
            // en la validación/creación, así que se ignora sin más.
            'saldo_pendiente'    => 1,
        ]);

        $mc = MembresiaCliente::firstOrFail();

        $this->assertEquals(120.00, (float) $mc->precio_final);
        $this->assertEquals(70.00, (float) $mc->saldo_pendiente); // 120 - 50, NO el "1" manipulado
    }

    // ── 4. Validaciones de rechazo ────────────────────────────────────────────

    public function test_rechaza_monto_pagado_cero(): void
    {
        $cliente   = $this->crearCliente();
        $membresia = Membresia::create(['tipo_membresia' => 'Mensual', 'precio' => 150]);

        $this->registrarPago($cliente, $membresia, [
            'monto_pagado'       => 0,
            'fecha_limite_saldo' => now()->addDays(10)->toDateString(),
        ])->assertSessionHasErrors('monto_pagado');

        $this->assertDatabaseCount('membresia_cliente', 0);
        $this->assertDatabaseCount('pagos', 0);
    }

    public function test_rechaza_monto_pagado_negativo(): void
    {
        $cliente   = $this->crearCliente();
        $membresia = Membresia::create(['tipo_membresia' => 'Mensual', 'precio' => 150]);

        $this->registrarPago($cliente, $membresia, [
            'monto_pagado'       => -10,
            'fecha_limite_saldo' => now()->addDays(10)->toDateString(),
        ])->assertSessionHasErrors('monto_pagado');

        $this->assertDatabaseCount('membresia_cliente', 0);
    }

    public function test_rechaza_monto_pagado_mayor_al_precio_final(): void
    {
        $cliente   = $this->crearCliente();
        $membresia = Membresia::create(['tipo_membresia' => 'Mensual', 'precio' => 150]);

        $this->registrarPago($cliente, $membresia, ['monto_pagado' => 200])
             ->assertSessionHasErrors('monto_pagado');

        $this->assertDatabaseCount('membresia_cliente', 0);
        $this->assertDatabaseCount('pagos', 0);
    }

    public function test_rechaza_pago_parcial_sin_fecha_limite(): void
    {
        $cliente   = $this->crearCliente();
        $membresia = Membresia::create(['tipo_membresia' => 'Mensual', 'precio' => 150]);

        $this->registrarPago($cliente, $membresia, ['monto_pagado' => 70])
             ->assertSessionHasErrors('fecha_limite_saldo');

        $this->assertDatabaseCount('membresia_cliente', 0);
        $this->assertDatabaseCount('pagos', 0);
    }

    public function test_rechaza_fecha_limite_en_el_pasado(): void
    {
        $cliente   = $this->crearCliente();
        $membresia = Membresia::create(['tipo_membresia' => 'Mensual', 'precio' => 150]);

        $this->registrarPago($cliente, $membresia, [
            'monto_pagado'       => 70,
            'fecha_limite_saldo' => now()->subDay()->toDateString(),
        ])->assertSessionHasErrors('fecha_limite_saldo');

        $this->assertDatabaseCount('membresia_cliente', 0);
    }

    public function test_rechaza_fecha_limite_hoy_mismo(): void
    {
        $cliente   = $this->crearCliente();
        $membresia = Membresia::create(['tipo_membresia' => 'Mensual', 'precio' => 150]);

        $this->registrarPago($cliente, $membresia, [
            'monto_pagado'       => 70,
            'fecha_limite_saldo' => now()->toDateString(),
        ])->assertSessionHasErrors('fecha_limite_saldo');
    }

    public function test_rechaza_fecha_limite_demasiado_lejana(): void
    {
        $cliente   = $this->crearCliente();
        $membresia = Membresia::create(['tipo_membresia' => 'Mensual', 'precio' => 150]);

        $this->registrarPago($cliente, $membresia, [
            'monto_pagado'       => 70,
            'fecha_limite_saldo' => now()->addMonths(4)->toDateString(),
        ])->assertSessionHasErrors('fecha_limite_saldo');

        $this->assertDatabaseCount('membresia_cliente', 0);
    }

    public function test_fecha_limite_enviada_en_un_pago_completo_se_ignora(): void
    {
        $cliente   = $this->crearCliente();
        $membresia = Membresia::create(['tipo_membresia' => 'Mensual', 'precio' => 150]);

        // Pago completo (monto_pagado === precioFinal) con una fecha límite
        // colada en el envío: debe ignorarse sin error y sin persistirse.
        $this->registrarPago($cliente, $membresia, [
            'monto_pagado'       => 150,
            'fecha_limite_saldo' => now()->addDays(10)->toDateString(),
        ])->assertSessionHasNoErrors();

        $mc = MembresiaCliente::firstOrFail();
        $this->assertNull($mc->fecha_limite_saldo);
        $this->assertNull($mc->saldo_pendiente);
    }

    // ── Nada de esto afecta la transacción atómica: si falla, no queda nada ──

    public function test_si_la_validacion_de_cuotas_falla_no_queda_nada_creado(): void
    {
        $cliente   = $this->crearCliente();
        $membresia = Membresia::create(['tipo_membresia' => 'Mensual', 'precio' => 150]);

        $this->registrarPago($cliente, $membresia, ['monto_pagado' => -5]);

        $this->assertDatabaseCount('membresia_cliente', 0);
        $this->assertDatabaseCount('pagos', 0);
    }
}
