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
 * Pago en 2 Cuotas — Etapa 3: pagar el SALDO (cuota 2) y el rediseño de
 * destroy() para dejar la membresía en un estado coherente sin importar
 * cuál de sus (hasta 2) pagos se borre. El pago normal completo y el pago
 * parcial (Etapa 2) no se tocaron acá — su blindaje sigue viviendo en
 * PagoRegistroTest/PagoMembresiaActivacionTest/PagoAcumulacionMembresiaTest
 * y PagoCuotasEtapa2Test.php, corridos junto a esta suite en la
 * verificación final.
 */
class PagoCuotasEtapa3Test extends TestCase
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

        $sede ??= Sede::create(['nombre' => "Sede Cuotas3 {$s}", 'direccion' => 'Calle 1', 'activo' => true]);

        $user = User::factory()->create([
            'rol_id'  => Rol::firstOrCreate(['nombre' => 'Cliente'])->id,
            'id_sede' => $sede->id_sede,
        ]);

        $personaId = DB::table('personas')->insertGetId([
            'nombre_completo'  => "Cuotas Etapa3 {$s}",
            'apellido_paterno' => 'Test',
            'apellido_materno' => 'Saldo',
            'ci'               => "CIET3{$s}",
            'telefono'         => "+5917800000{$s}",
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

    private function registrarPagoParcial(Cliente $cliente, Membresia $membresia, float $montoPagado, string $fechaLimite, ?string $sedeAdminOperador = null)
    {
        return $this->actingAs($this->operador())->post('/pagos', [
            'id_cliente'          => $cliente->id_cliente,
            'id_membresia'        => $membresia->id_membresia,
            'id_descuento'        => $this->sinDescuento()->id,
            'metodo_pago'         => 'efectivo',
            'monto_pagado'        => $montoPagado,
            'fecha_limite_saldo'  => $fechaLimite,
        ]);
    }

    private function pagarSaldo(MembresiaCliente $mc, array $extra = [])
    {
        return $this->actingAs($this->operador())->post("/pagos/saldo/{$mc->id}", array_merge([
            'metodo_pago' => 'efectivo',
        ], $extra));
    }

    // ── 1. Pagar el saldo salda la deuda y mantiene activa ───────────────────

    public function test_pagar_el_saldo_lo_salda_y_la_membresia_sigue_activa(): void
    {
        $cliente   = $this->crearCliente();
        $membresia = Membresia::create(['tipo_membresia' => 'Mensual', 'precio' => 150]);
        $this->registrarPagoParcial($cliente, $membresia, 70, now()->addDays(15)->toDateString());

        $mc = MembresiaCliente::firstOrFail();
        $fechaFinOriginal = $mc->fecha_fin;

        $this->pagarSaldo($mc)->assertRedirect(route('pagos.index'));

        $mc->refresh();
        $this->assertEquals(0, (float) $mc->saldo_pendiente);
        $this->assertFalse($mc->tieneSaldoPendiente());
        $this->assertSame('activa', $mc->estado);
        // fecha_fin NUNCA se toca
        $this->assertSame((string) $fechaFinOriginal, (string) $mc->fecha_fin);

        $this->assertDatabaseCount('pagos', 2);
        $pagoSaldo = Pago::orderBy('id_pago', 'desc')->first();
        $this->assertEquals(80.00, (float) $pagoSaldo->monto); // 150 - 70
        $this->assertSame($mc->id, $pagoSaldo->id_membresia_cliente);
    }

    public function test_pagar_el_saldo_reactiva_una_membresia_suspendida_por_saldo(): void
    {
        $sede      = Sede::create(['nombre' => 'Sede Reactiva', 'direccion' => 'Calle 1', 'activo' => true]);
        $cliente   = $this->crearCliente($sede);
        $membresia = Membresia::create(['tipo_membresia' => 'Mensual', 'precio' => 150]);

        $this->registrarPagoParcial($cliente, $membresia, 70, now()->addDays(5)->toDateString());
        $mc = MembresiaCliente::firstOrFail();

        // Simula lo que hará la Etapa 4 (desactivación automática): se venció
        // el plazo sin pagar, la membresía queda suspendida.
        $mc->update(['estado' => MembresiaCliente::ESTADO_SUSPENDIDA_SALDO]);

        $servicio = app(AsistenciaService::class);
        $this->assertFalse($servicio->registrarEntrada($cliente, $sede->id_sede)['permitido']);

        $this->pagarSaldo($mc);

        $mc->refresh();
        $this->assertSame('activa', $mc->estado);
        $this->assertTrue($cliente->fresh()->tieneMembresiaVigente());
        $this->assertTrue($servicio->registrarEntrada($cliente, $sede->id_sede)['permitido']);
    }

    // ── 2. No se puede pagar el saldo si no debe nada ─────────────────────────

    public function test_no_se_puede_pagar_el_saldo_de_una_membresia_sin_deuda(): void
    {
        $cliente   = $this->crearCliente();
        $membresia = Membresia::create(['tipo_membresia' => 'Mensual', 'precio' => 150]);

        // Pago completo normal: sin saldo
        $this->actingAs($this->operador())->post('/pagos', [
            'id_cliente'   => $cliente->id_cliente,
            'id_membresia' => $membresia->id_membresia,
            'id_descuento' => $this->sinDescuento()->id,
            'metodo_pago'  => 'efectivo',
        ]);
        $mc = MembresiaCliente::firstOrFail();

        $this->pagarSaldo($mc)->assertRedirect(route('pagos.index'));

        $this->assertDatabaseCount('pagos', 1); // no se creó un segundo pago
        $this->assertSame('activa', $mc->fresh()->estado);
    }

    public function test_no_se_puede_pagar_el_saldo_dos_veces(): void
    {
        $cliente   = $this->crearCliente();
        $membresia = Membresia::create(['tipo_membresia' => 'Mensual', 'precio' => 150]);
        $this->registrarPagoParcial($cliente, $membresia, 70, now()->addDays(15)->toDateString());
        $mc = MembresiaCliente::firstOrFail();

        $this->pagarSaldo($mc)->assertRedirect(route('pagos.index'));
        $this->assertDatabaseCount('pagos', 2);

        // Segundo intento: ya no debe nada
        $this->pagarSaldo($mc);
        $this->assertDatabaseCount('pagos', 2); // sigue en 2, no se creó un tercero
    }

    // ── 3. destroy(): borrar el pago INICIAL (único o cuota 1) ──────────────

    public function test_borrar_el_pago_unico_de_una_membresia_sin_cuotas_vuelve_a_pendiente(): void
    {
        // Comportamiento de siempre, para el caso SIN cuotas — sigue igual.
        $sede      = Sede::create(['nombre' => 'Sede Borra Unico', 'direccion' => 'Calle 1', 'activo' => true]);
        $cliente   = $this->crearCliente($sede);
        $membresia = Membresia::create(['tipo_membresia' => 'Mensual', 'precio' => 150]);
        $admin     = $this->operador('Administrador');

        $this->actingAs($this->operador())->post('/pagos', [
            'id_cliente'   => $cliente->id_cliente,
            'id_membresia' => $membresia->id_membresia,
            'id_descuento' => $this->sinDescuento()->id,
            'metodo_pago'  => 'efectivo',
        ]);
        $mc   = MembresiaCliente::firstOrFail();
        $pago = Pago::firstOrFail();

        $this->actingAs($admin)->delete("/pagos/{$pago->id_pago}")->assertRedirect(route('pagos.index'));

        $mc->refresh();
        $this->assertSame('pendiente_pago', $mc->estado);
        $this->assertNull($mc->saldo_pendiente);
        $this->assertNull($mc->fecha_limite_saldo);
    }

    public function test_borrar_el_pago_inicial_de_una_membresia_con_saldo_vuelve_a_pendiente_total(): void
    {
        $cliente   = $this->crearCliente();
        $membresia = Membresia::create(['tipo_membresia' => 'Mensual', 'precio' => 150]);
        $admin     = $this->operador('Administrador');

        $this->registrarPagoParcial($cliente, $membresia, 70, now()->addDays(15)->toDateString());
        $mc          = MembresiaCliente::firstOrFail();
        $pagoInicial = Pago::firstOrFail();

        $this->actingAs($admin)->delete("/pagos/{$pagoInicial->id_pago}")->assertRedirect(route('pagos.index'));

        $mc->refresh();
        $this->assertSame('pendiente_pago', $mc->estado);
        $this->assertNull($mc->saldo_pendiente);
        $this->assertNull($mc->fecha_limite_saldo);
    }

    // ── 4. destroy(): borrar el pago del SALDO (cuota 2) ─────────────────────

    public function test_borrar_el_pago_del_saldo_restaura_la_deuda_sin_tocar_el_pago_inicial(): void
    {
        $cliente   = $this->crearCliente();
        $membresia = Membresia::create(['tipo_membresia' => 'Mensual', 'precio' => 150]);
        $admin     = $this->operador('Administrador');
        $fechaLimite = now()->addDays(15)->toDateString();

        $this->registrarPagoParcial($cliente, $membresia, 70, $fechaLimite);
        $mc = MembresiaCliente::firstOrFail();
        $this->pagarSaldo($mc);
        $mc->refresh();
        $this->assertEquals(0, (float) $mc->saldo_pendiente);

        $pagoSaldo = Pago::orderBy('id_pago', 'desc')->first();
        $this->actingAs($admin)->delete("/pagos/{$pagoSaldo->id_pago}")->assertRedirect(route('pagos.index'));

        $mc->refresh();
        $this->assertEquals(80.00, (float) $mc->saldo_pendiente); // vuelve a lo que ese pago cubría
        $this->assertSame('activa', $mc->estado); // el plazo todavía no había vencido
        $this->assertSame(1, Pago::count()); // el pago inicial sigue intacto (sin contar el soft-deleted)
        $this->assertSoftDeleted('pagos', ['id_pago' => $pagoSaldo->id_pago]);
    }

    public function test_borrar_el_pago_del_saldo_de_una_membresia_ya_suspendida_la_deja_suspendida(): void
    {
        $cliente   = $this->crearCliente();
        $membresia = Membresia::create(['tipo_membresia' => 'Mensual', 'precio' => 150]);
        $admin     = $this->operador('Administrador');

        // Fecha límite en el pasado simulada directamente en BD (la
        // validación normal no permite crearla vencida desde el formulario).
        $this->registrarPagoParcial($cliente, $membresia, 70, now()->addDays(5)->toDateString());
        $mc = MembresiaCliente::firstOrFail();
        $mc->update(['fecha_limite_saldo' => now()->subDays(2)->toDateString()]);

        $this->pagarSaldo($mc); // paga tarde, pero el sistema lo acepta igual
        $mc->refresh();
        $this->assertSame('activa', $mc->estado);

        $pagoSaldo = Pago::orderBy('id_pago', 'desc')->first();
        $this->actingAs($admin)->delete("/pagos/{$pagoSaldo->id_pago}");

        $mc->refresh();
        // El plazo YA estaba vencido cuando se pagó tarde: al deshacer ese
        // pago, corresponde volver a "suspendida", no a "activa".
        $this->assertSame('suspendida_saldo', $mc->estado);
        $this->assertEquals(80.00, (float) $mc->saldo_pendiente);
    }

    // ── 5. No queda ningún dato huérfano en ningún caso ──────────────────────

    public function test_ningun_escenario_de_borrado_deja_saldo_pendiente_sin_estado_coherente(): void
    {
        $cliente   = $this->crearCliente();
        $membresia = Membresia::create(['tipo_membresia' => 'Mensual', 'precio' => 150]);
        $admin     = $this->operador('Administrador');

        $this->registrarPagoParcial($cliente, $membresia, 70, now()->addDays(15)->toDateString());
        $mc = MembresiaCliente::firstOrFail();
        $this->pagarSaldo($mc);
        $mc->refresh();

        $pagoSaldo = Pago::orderBy('id_pago', 'desc')->first();
        $this->actingAs($admin)->delete("/pagos/{$pagoSaldo->id_pago}");
        $mc->refresh();

        // "con saldo pendiente" y "activa" es una combinación válida y
        // coherente (el diseño lo permite explícitamente).
        $this->assertTrue($mc->tieneSaldoPendiente());
        $this->assertNotNull($mc->fecha_limite_saldo);
        $this->assertContains($mc->estado, ['activa', 'suspendida_saldo']);
    }
}
