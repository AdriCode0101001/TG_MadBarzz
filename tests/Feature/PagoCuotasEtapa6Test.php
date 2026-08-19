<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Descuento;
use App\Models\Membresia;
use App\Models\MembresiaCliente;
use App\Models\Persona;
use App\Models\Rol;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Pago en 2 Cuotas — Etapa 6: la UI. El backend (Etapas 1-5) no se tocó acá
 * — estos tests verifican que las vistas muestran/ocultan lo correcto y que
 * el formulario, en modo normal, sigue registrando un pago completo
 * exactamente como antes (los tests de Etapas 2/3, sin modificar, siguen
 * siendo el blindaje principal de eso).
 */
class PagoCuotasEtapa6Test extends TestCase
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

        $sede ??= Sede::create(['nombre' => "Sede Etapa6 {$s}", 'direccion' => 'Calle 1', 'activo' => true]);

        $user = User::factory()->create([
            'rol_id'  => Rol::firstOrCreate(['nombre' => 'Cliente'])->id,
            'id_sede' => $sede->id_sede,
        ]);

        $personaId = DB::table('personas')->insertGetId([
            'nombre_completo'  => "Etapa6 {$s}",
            'apellido_paterno' => 'Test',
            'apellido_materno' => 'UI',
            'ci'               => "CIET6{$s}",
            'telefono'         => "+5917910000{$s}",
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

    private function crearMembresiaCliente(Cliente $cliente, array $overrides = []): MembresiaCliente
    {
        $membresia = Membresia::create(['tipo_membresia' => 'Mensual', 'precio' => 150]);

        return MembresiaCliente::create(array_merge([
            'id_cliente'   => $cliente->id_cliente,
            'id_membresia' => $membresia->id_membresia,
            'fecha_inicio' => now()->toDateString(),
            'fecha_fin'    => now()->addDays(30)->toDateString(),
            'precio_final' => 150,
            'descuento'    => 0,
            'estado'       => MembresiaCliente::ESTADO_ACTIVA,
        ], $overrides));
    }

    // ── 1. Formulario de pago: el checkbox y los campos nacen apagados ───────

    public function test_el_formulario_de_pago_incluye_el_checkbox_de_cuotas_apagado_por_defecto(): void
    {
        $html = $this->actingAs($this->operador())
            ->get('/pagos/create')
            ->assertStatus(200)
            ->getContent();

        $this->assertStringContainsString('id="pagoEnCuotas"', $html);
        $this->assertMatchesRegularExpression('/<input[^>]*id="monto_pagado"[^>]*disabled/', $html);
        $this->assertMatchesRegularExpression('/<input[^>]*id="fecha_limite_saldo"[^>]*disabled/', $html);
    }

    // ── 2. Modo normal: registra el pago completo exactamente como antes ────

    public function test_el_formulario_en_modo_normal_sigue_registrando_un_pago_completo(): void
    {
        $cliente   = $this->crearCliente();
        $membresia = Membresia::create(['tipo_membresia' => 'Mensual', 'precio' => 150]);

        // Simula el envío real del navegador con el checkbox destildado: los
        // campos disabled no viajan, así que no se manda monto_pagado ni
        // fecha_limite_saldo — igual que antes de esta etapa.
        $this->actingAs($this->operador())->post('/pagos', [
            'id_cliente'   => $cliente->id_cliente,
            'id_membresia' => $membresia->id_membresia,
            'id_descuento' => $this->sinDescuento()->id,
            'metodo_pago'  => 'efectivo',
        ])->assertRedirect(route('pagos.index'));

        $mc = MembresiaCliente::firstOrFail();
        $this->assertSame('activa', $mc->estado);
        $this->assertNull($mc->saldo_pendiente);
        $this->assertNull($mc->fecha_limite_saldo);
    }

    // ── 3. Modo cuotas: los mismos campos que la UI usa registran el parcial ─

    public function test_el_formulario_en_modo_cuotas_registra_el_pago_parcial(): void
    {
        $cliente   = $this->crearCliente();
        $membresia = Membresia::create(['tipo_membresia' => 'Mensual', 'precio' => 150]);
        $fechaLimite = now()->addDays(15)->toDateString();

        $this->actingAs($this->operador())->post('/pagos', [
            'id_cliente'          => $cliente->id_cliente,
            'id_membresia'        => $membresia->id_membresia,
            'id_descuento'        => $this->sinDescuento()->id,
            'metodo_pago'         => 'efectivo',
            'monto_pagado'        => 70,
            'fecha_limite_saldo'  => $fechaLimite,
        ])->assertRedirect(route('pagos.index'));

        $mc = MembresiaCliente::firstOrFail();
        $this->assertSame('activa', $mc->estado);
        $this->assertEquals(80.00, (float) $mc->saldo_pendiente);
        $this->assertSame($fechaLimite, $mc->fecha_limite_saldo->toDateString());
    }

    // ── 4. "Pagar saldo": aparece solo cuando corresponde ────────────────────

    public function test_el_boton_pagar_saldo_aparece_en_membresia_cliente_index_cuando_hay_deuda(): void
    {
        $cliente = $this->crearCliente();
        $mc = $this->crearMembresiaCliente($cliente, [
            'saldo_pendiente'    => 80,
            'fecha_limite_saldo' => now()->addDays(10)->toDateString(),
        ]);

        $html = $this->actingAs($this->operador())
            ->get('/membresia_cliente')
            ->assertStatus(200)
            ->getContent();

        $this->assertStringContainsString('mb-btn-pagar-saldo', $html);
        $this->assertStringContainsString(route('pagos.saldo', $mc->id), $html);
        $this->assertStringContainsString('Debe Bs 80.00', $html);
    }

    public function test_el_boton_pagar_saldo_no_aparece_para_una_membresia_pagada_completa(): void
    {
        $cliente = $this->crearCliente();
        $this->crearMembresiaCliente($cliente); // sin saldo

        $html = $this->actingAs($this->operador())
            ->get('/membresia_cliente')
            ->assertStatus(200)
            ->getContent();

        // El texto "mb-btn-pagar-saldo" existe igual en el <script> del modal
        // compartido (el selector querySelectorAll) aunque ninguna fila lo
        // use — lo que importa es que no exista el BOTÓN en sí.
        $this->assertDoesNotMatchRegularExpression('/<button[^>]*mb-btn-pagar-saldo/', $html);
    }

    public function test_membresia_cliente_index_distingue_suspendida_por_saldo_de_pendiente_de_pago(): void
    {
        $cliente = $this->crearCliente();
        $this->crearMembresiaCliente($cliente, [
            'estado'             => MembresiaCliente::ESTADO_SUSPENDIDA_SALDO,
            'saldo_pendiente'    => 80,
            'fecha_limite_saldo' => now()->subDays(2)->toDateString(),
        ]);

        $this->actingAs($this->operador())
            ->get('/membresia_cliente')
            ->assertStatus(200)
            ->assertSee('Suspendida (saldo)');
    }

    // ── 5. pagos/index y pagos/show muestran el saldo si aplica ──────────────

    public function test_pagos_index_muestra_el_saldo_pendiente_cuando_aplica(): void
    {
        $cliente   = $this->crearCliente();
        $membresia = Membresia::create(['tipo_membresia' => 'Mensual', 'precio' => 150]);

        $this->actingAs($this->operador())->post('/pagos', [
            'id_cliente'          => $cliente->id_cliente,
            'id_membresia'        => $membresia->id_membresia,
            'id_descuento'        => $this->sinDescuento()->id,
            'metodo_pago'         => 'efectivo',
            'monto_pagado'        => 70,
            'fecha_limite_saldo'  => now()->addDays(15)->toDateString(),
        ]);

        $this->actingAs($this->operador())
            ->get('/pagos')
            ->assertStatus(200)
            ->assertSee('Debe Bs 80.00');
    }

    public function test_pagos_index_no_muestra_saldo_para_un_pago_completo(): void
    {
        $cliente   = $this->crearCliente();
        $membresia = Membresia::create(['tipo_membresia' => 'Mensual', 'precio' => 150]);

        $this->actingAs($this->operador())->post('/pagos', [
            'id_cliente'   => $cliente->id_cliente,
            'id_membresia' => $membresia->id_membresia,
            'id_descuento' => $this->sinDescuento()->id,
            'metodo_pago'  => 'efectivo',
        ]);

        $this->actingAs($this->operador())
            ->get('/pagos')
            ->assertStatus(200)
            ->assertDontSee('Debe Bs');
    }

    public function test_pagos_show_muestra_la_fila_de_saldo_pendiente_cuando_aplica(): void
    {
        $cliente   = $this->crearCliente();
        $membresia = Membresia::create(['tipo_membresia' => 'Mensual', 'precio' => 150]);
        $fechaLimite = now()->addDays(15)->toDateString();

        $this->actingAs($this->operador())->post('/pagos', [
            'id_cliente'          => $cliente->id_cliente,
            'id_membresia'        => $membresia->id_membresia,
            'id_descuento'        => $this->sinDescuento()->id,
            'metodo_pago'         => 'efectivo',
            'monto_pagado'        => 70,
            'fecha_limite_saldo'  => $fechaLimite,
        ]);
        $pago = \App\Models\Pago::firstOrFail();

        $this->actingAs($this->operador())
            ->get("/pagos/{$pago->id_pago}")
            ->assertStatus(200)
            ->assertSee('Saldo pendiente')
            ->assertSee('80.00');
    }

    // ── 6. Portal del cliente ─────────────────────────────────────────────────

    public function test_portal_muestra_el_saldo_pendiente_de_una_membresia_vigente(): void
    {
        $cliente = $this->crearCliente();
        $this->crearMembresiaCliente($cliente, [
            'saldo_pendiente'    => 80,
            'fecha_limite_saldo' => now()->addDays(10)->toDateString(),
        ]);

        $this->actingAs($cliente->user)
            ->get('/mi-membresia')
            ->assertStatus(200)
            ->assertSee('saldo pendiente')
            ->assertSee('80.00');
    }

    public function test_portal_muestra_mensaje_distinto_para_suspendida_por_saldo(): void
    {
        $cliente = $this->crearCliente();
        $this->crearMembresiaCliente($cliente, [
            'estado'             => MembresiaCliente::ESTADO_SUSPENDIDA_SALDO,
            'saldo_pendiente'    => 80,
            'fecha_limite_saldo' => now()->subDays(2)->toDateString(),
        ]);

        $this->actingAs($cliente->user)
            ->get('/mi-membresia')
            ->assertStatus(200)
            ->assertSee('Tu membresía está suspendida')
            ->assertDontSee('No tienes una membresía activa');
    }

    public function test_portal_muestra_el_mensaje_generico_para_quien_nunca_pago(): void
    {
        $cliente = $this->crearCliente();
        // Sin ninguna MembresiaCliente en absoluto.

        $this->actingAs($cliente->user)
            ->get('/mi-membresia')
            ->assertStatus(200)
            ->assertSee('No tienes una membresía activa')
            ->assertDontSee('Tu membresía está suspendida');
    }
}
