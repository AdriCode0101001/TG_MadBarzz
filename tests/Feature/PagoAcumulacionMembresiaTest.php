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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * ACUMULACIÓN de tiempo: si un cliente que YA tiene una membresía activa vigente
 * paga otra, la nueva NO reemplaza ni pisa a la vigente. Arranca el día siguiente
 * al fin de aquella y NO lleva día de regalo (el regalo solo aplica al arrancar
 * desde cero). El cliente nunca pierde los días que le quedaban. Cada membresía
 * conserva su propio registro con su propio pago (histórico intacto).
 */
class PagoAcumulacionMembresiaTest extends TestCase
{
    use RefreshDatabase;

    private static int $seq = 0;

    private function operador(string $rol = 'Recepcionista', ?int $idSede = null): User
    {
        $attrs = ['rol_id' => Rol::firstOrCreate(['nombre' => $rol])->id];
        if ($idSede !== null) {
            $attrs['id_sede'] = $idSede;
        }

        return User::factory()->create($attrs);
    }

    private function crearCliente(?Sede $sede = null): Cliente
    {
        self::$seq++;
        $s = self::$seq;

        $sede ??= Sede::create(['nombre' => "Sede Acum {$s}", 'direccion' => 'Calle 1', 'activo' => true]);

        $user = User::factory()->create([
            'rol_id'  => Rol::firstOrCreate(['nombre' => 'Cliente'])->id,
            'id_sede' => $sede->id_sede,
        ]);

        $personaId = DB::table('personas')->insertGetId([
            'nombre_completo'  => "Acumulador {$s}",
            'apellido_paterno' => 'Test',
            'apellido_materno' => 'Acum',
            'ci'               => "CIACU{$s}",
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

    private function seedMembresia(Cliente $cliente, Membresia $membresia, string $inicio, string $fin, string $estado = MembresiaCliente::ESTADO_ACTIVA): MembresiaCliente
    {
        return MembresiaCliente::create([
            'id_cliente'   => $cliente->id_cliente,
            'id_membresia' => $membresia->id_membresia,
            'fecha_inicio' => $inicio,
            'fecha_fin'    => $fin,
            'precio_final' => $membresia->precio,
            'descuento'    => 0,
            'estado'       => $estado,
            'id_sede'      => $cliente->id_sede,
        ]);
    }

    private function registrarPago(Cliente $cliente, Membresia $membresia, array $extra = [])
    {
        // El operador registra el pago en la sede del cliente (SedeScope)
        return $this->actingAs($this->operador('Recepcionista', $cliente->id_sede))->post('/pagos', array_merge([
            'id_cliente'   => $cliente->id_cliente,
            'id_membresia' => $membresia->id_membresia,
            'id_descuento' => $this->sinDescuento()->id,
            'metodo_pago'  => 'efectivo',
        ], $extra));
    }

    // ── 1. Sin membresía vigente → flujo NORMAL con día de regalo ────────────

    public function test_sin_membresia_vigente_usa_fechas_normales_con_dia_de_regalo(): void
    {
        $this->travelTo(Carbon::parse('2030-07-15 10:00:00', config('app.timezone')));

        $cliente   = $this->crearCliente();
        $membresia = Membresia::create(['tipo_membresia' => 'Mensual', 'precio' => 150]);

        $this->registrarPago($cliente, $membresia)->assertRedirect(route('pagos.index'));

        $this->assertDatabaseHas('membresia_cliente', [
            'id_cliente'   => $cliente->id_cliente,
            'fecha_inicio' => '2030-07-15',        // hoy
            'fecha_fin'    => '2030-08-16',        // +1 mes +1 día de regalo
            'estado'       => 'activa',
        ]);
    }

    // ── 2. Con membresía vigente → ACUMULA sin regalo (caso del enunciado) ───

    public function test_con_membresia_vigente_la_nueva_arranca_al_dia_siguiente_sin_regalo(): void
    {
        // Cliente con MENSUAL vigente hasta 30/07; hoy 15/07 paga una TRIMESTRAL.
        $this->travelTo(Carbon::parse('2030-07-15 10:00:00', config('app.timezone')));

        $cliente = $this->crearCliente();
        $mensual = Membresia::create(['tipo_membresia' => 'Mensual', 'precio' => 150]);
        $this->seedMembresia($cliente, $mensual, '2030-06-30', '2030-07-30');

        $this->assertTrue($cliente->fresh()->tieneMembresiaVigente());

        $trimestral = Membresia::create(['tipo_membresia' => 'Trimestral', 'precio' => 400]);
        $this->registrarPago($cliente, $trimestral)->assertRedirect(route('pagos.index'));

        // La nueva arranca el 31/07 y vence el 31/10 (3 meses), SIN día de regalo.
        $this->assertDatabaseHas('membresia_cliente', [
            'id_cliente'   => $cliente->id_cliente,
            'id_membresia' => $trimestral->id_membresia,
            'fecha_inicio' => '2030-07-31',
            'fecha_fin'    => '2030-10-31',
            'estado'       => 'activa',
        ]);
    }

    // ── 3. El histórico conserva AMBAS membresías y AMBOS pagos ──────────────

    public function test_el_historico_conserva_ambas_membresias_y_ambos_pagos(): void
    {
        $this->travelTo(Carbon::parse('2030-07-15 10:00:00', config('app.timezone')));

        $cliente = $this->crearCliente();
        $mensual = Membresia::create(['tipo_membresia' => 'Mensual', 'precio' => 150]);

        // Primera membresía comprada por el flujo normal (queda vigente hasta 08/16)
        $this->registrarPago($cliente, $mensual)->assertRedirect(route('pagos.index'));

        // Segunda compra: se ACUMULA sobre la vigente
        $trimestral = Membresia::create(['tipo_membresia' => 'Trimestral', 'precio' => 400]);
        $this->registrarPago($cliente, $trimestral)->assertRedirect(route('pagos.index'));

        // Dos registros de membresía y dos pagos, cada uno con su vínculo
        $this->assertSame(2, MembresiaCliente::where('id_cliente', $cliente->id_cliente)->count());
        $this->assertSame(2, Pago::where('id_cliente', $cliente->id_cliente)->count());

        // La primera vence 2030-08-16 (con regalo); la segunda acumula desde ahí:
        // inicio 2030-08-17, fin 2030-11-17 (3 meses, sin regalo).
        $this->assertDatabaseHas('membresia_cliente', [
            'id_cliente' => $cliente->id_cliente,
            'fecha_fin'  => '2030-08-16',
        ]);
        $this->assertDatabaseHas('membresia_cliente', [
            'id_cliente'   => $cliente->id_cliente,
            'fecha_inicio' => '2030-08-17',
            'fecha_fin'    => '2030-11-17',
        ]);

        // Cada pago quedó vinculado a su propia membresía (vínculos distintos)
        $vinculos = Pago::where('id_cliente', $cliente->id_cliente)->pluck('id_membresia_cliente');
        $this->assertCount(2, $vinculos->unique());
        $this->assertFalse($vinculos->contains(null));
    }

    // ── 4. Membresía VENCIDA no acumula: arranca hoy con regalo ──────────────

    public function test_membresia_vencida_no_acumula_arranca_hoy_con_regalo(): void
    {
        $this->travelTo(Carbon::parse('2030-07-15 10:00:00', config('app.timezone')));

        $cliente = $this->crearCliente();
        $mensual = Membresia::create(['tipo_membresia' => 'Mensual', 'precio' => 150]);
        // Vencida: terminó hace dos semanas (aunque su estado siga 'activa')
        $this->seedMembresia($cliente, $mensual, '2030-06-01', '2030-07-01');

        $this->assertFalse($cliente->fresh()->tieneMembresiaVigente());

        $this->registrarPago($cliente, $mensual)->assertRedirect(route('pagos.index'));

        // La nueva arranca HOY con día de regalo (no acumula sobre la vencida)
        $this->assertDatabaseHas('membresia_cliente', [
            'id_cliente'   => $cliente->id_cliente,
            'fecha_inicio' => '2030-07-15',
            'fecha_fin'    => '2030-08-16',
        ]);
        // Y el histórico conserva la vencida
        $this->assertSame(2, MembresiaCliente::where('id_cliente', $cliente->id_cliente)->count());
    }

    // ── 5. Membresía pendiente de pago (no activa) no cuenta como vigente ────

    public function test_membresia_pendiente_de_pago_no_cuenta_como_vigente_para_acumular(): void
    {
        $this->travelTo(Carbon::parse('2030-07-15 10:00:00', config('app.timezone')));

        $cliente = $this->crearCliente();
        $mensual = Membresia::create(['tipo_membresia' => 'Mensual', 'precio' => 150]);
        // Fin futuro pero SIN pago: no da acceso, no es vigente
        $this->seedMembresia($cliente, $mensual, '2030-07-10', '2030-08-30', MembresiaCliente::ESTADO_PENDIENTE);

        $this->assertFalse($cliente->fresh()->tieneMembresiaVigente());

        $this->registrarPago($cliente, $mensual)->assertRedirect(route('pagos.index'));

        // Arranca hoy con regalo, no acumula sobre la pendiente
        $this->assertDatabaseHas('membresia_cliente', [
            'id_cliente'   => $cliente->id_cliente,
            'fecha_inicio' => '2030-07-15',
            'fecha_fin'    => '2030-08-16',
            'estado'       => 'activa',
        ]);
    }

    // ── 6. Acumulación en cadena: pagar dos veces suma dos veces ─────────────

    public function test_acumulacion_en_cadena_suma_sobre_la_ultima_cada_vez(): void
    {
        $this->travelTo(Carbon::parse('2030-07-15 10:00:00', config('app.timezone')));

        $cliente = $this->crearCliente();
        $mensual = Membresia::create(['tipo_membresia' => 'Mensual', 'precio' => 150]);

        // 1ª: normal → hoy 07-15 a 08-16 (con regalo)
        $this->registrarPago($cliente, $mensual);
        // 2ª: acumula → 08-17 a 09-17 (sin regalo)
        $this->registrarPago($cliente, $mensual);
        // 3ª: acumula sobre la 2ª → 09-18 a 10-18 (sin regalo)
        $this->registrarPago($cliente, $mensual);

        $this->assertSame(3, MembresiaCliente::where('id_cliente', $cliente->id_cliente)->count());

        $this->assertDatabaseHas('membresia_cliente', ['id_cliente' => $cliente->id_cliente, 'fecha_fin' => '2030-08-16']);
        $this->assertDatabaseHas('membresia_cliente', ['id_cliente' => $cliente->id_cliente, 'fecha_inicio' => '2030-08-17', 'fecha_fin' => '2030-09-17']);
        $this->assertDatabaseHas('membresia_cliente', ['id_cliente' => $cliente->id_cliente, 'fecha_inicio' => '2030-09-18', 'fecha_fin' => '2030-10-18']);
    }

    // ── 7. El aviso de acumulación llega al formulario (para el operador) ────

    public function test_el_formulario_avisa_al_operador_cuando_el_cliente_tiene_membresia_vigente(): void
    {
        $this->travelTo(Carbon::parse('2030-07-15 10:00:00', config('app.timezone')));

        $cliente = $this->crearCliente();
        $mensual = Membresia::create(['tipo_membresia' => 'Mensual', 'precio' => 150]);
        $this->seedMembresia($cliente, $mensual, '2030-06-30', '2030-07-30');

        // El operador ve los clientes de SU sede (SedeScope), así que comparte la del cliente
        $this->actingAs($this->operador('Recepcionista', $cliente->id_sede))
             ->get('/pagos/create')
             ->assertStatus(200)
             // El texto del aviso está presente en la página
             ->assertSee('se sumará a partir de esa fecha')
             // Y el mapa JSON incluye a este cliente con su fecha de vigencia
             ->assertSee('"' . $cliente->id_cliente . '":', false)
             ->assertSee('30\/07\/2030', false); // hasta 30/07/2030 (JSON escapa la barra)
    }
}
