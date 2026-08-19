<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Membresia;
use App\Models\MembresiaCliente;
use App\Models\Persona;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pago en 2 Cuotas — Etapa 1: solo terreno de datos (columnas + estado +
 * helpers de lectura). Nada de esto debe cambiar el comportamiento actual —
 * los tests acá confirman que los campos nuevos nacen en null y que los
 * helpers reflejan correctamente ese estado "sin nada raro". La prueba de
 * que NO se rompió nada existente es la suite completa (pago, activación,
 * huella, vigencia), corrida aparte al final.
 */
class PagoCuotasEtapa1Test extends TestCase
{
    use RefreshDatabase;

    private static int $seq = 0;

    private function crearCliente(): Cliente
    {
        self::$seq++;
        $s = self::$seq;

        $user = User::factory()->create(['rol_id' => Rol::firstOrCreate(['nombre' => 'Cliente'])->id]);

        $persona = Persona::create([
            'nombre_completo'  => "Cuotas {$s}",
            'apellido_paterno' => 'Test',
            'apellido_materno' => 'Etapa1',
            'ci'               => "CICUOTAS{$s}",
            'telefono'         => "+59177200{$s}",
            'genero'           => 'masculino',
            'fecha_nacimiento' => '1990-01-01',
        ]);

        return Cliente::create([
            'id_persona'     => $persona->id_persona,
            'id_usuario'     => $user->id,
            'estado'         => 'activo',
            'dias_asistidos' => 0,
        ]);
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

    // ── 1. La migración corrió bien: columnas existen y nacen en null ────────

    public function test_las_columnas_nuevas_existen_y_nacen_en_null(): void
    {
        $cliente = $this->crearCliente();
        $mc = $this->crearMembresiaCliente($cliente);

        $this->assertDatabaseHas('membresia_cliente', [
            'id'                  => $mc->id,
            'saldo_pendiente'     => null,
            'fecha_limite_saldo'  => null,
        ]);
    }

    public function test_una_membresia_existente_creada_sin_mencionar_los_campos_nuevos_queda_en_null(): void
    {
        $cliente = $this->crearCliente();
        // Ni siquiera se los pasa: simula el flujo actual de PagoController,
        // que no conoce estos campos todavía.
        $mc = $this->crearMembresiaCliente($cliente);

        $this->assertNull($mc->saldo_pendiente);
        $this->assertNull($mc->fecha_limite_saldo);
    }

    // ── 2. Constante del nuevo estado ─────────────────────────────────────────

    public function test_la_constante_del_nuevo_estado_existe_con_el_valor_correcto(): void
    {
        $this->assertSame('suspendida_saldo', MembresiaCliente::ESTADO_SUSPENDIDA_SALDO);
        // Las dos ya existentes siguen intactas
        $this->assertSame('pendiente_pago', MembresiaCliente::ESTADO_PENDIENTE);
        $this->assertSame('activa', MembresiaCliente::ESTADO_ACTIVA);
    }

    // ── 3. Helpers de lectura ──────────────────────────────────────────────────

    public function test_tiene_saldo_pendiente_es_false_cuando_el_saldo_es_null(): void
    {
        $cliente = $this->crearCliente();
        $mc = $this->crearMembresiaCliente($cliente);

        $this->assertFalse($mc->tieneSaldoPendiente());
    }

    public function test_tiene_saldo_pendiente_es_false_cuando_el_saldo_es_cero(): void
    {
        $cliente = $this->crearCliente();
        $mc = $this->crearMembresiaCliente($cliente, ['saldo_pendiente' => 0]);

        $this->assertFalse($mc->tieneSaldoPendiente());
    }

    public function test_tiene_saldo_pendiente_es_true_cuando_el_saldo_es_mayor_a_cero(): void
    {
        $cliente = $this->crearCliente();
        $mc = $this->crearMembresiaCliente($cliente, [
            'saldo_pendiente'    => 80,
            'fecha_limite_saldo' => now()->addDays(10)->toDateString(),
        ]);

        $this->assertTrue($mc->tieneSaldoPendiente());
        $this->assertSame('80.00', (string) $mc->saldo_pendiente);
        $this->assertNotNull($mc->fecha_limite_saldo);
    }

    public function test_esta_suspendida_por_saldo_es_false_para_los_estados_actuales(): void
    {
        $cliente = $this->crearCliente();

        $activa = $this->crearMembresiaCliente($cliente, ['estado' => MembresiaCliente::ESTADO_ACTIVA]);
        $this->assertFalse($activa->estaSuspendidaPorSaldo());

        $pendiente = $this->crearMembresiaCliente($cliente, ['estado' => MembresiaCliente::ESTADO_PENDIENTE]);
        $this->assertFalse($pendiente->estaSuspendidaPorSaldo());
    }

    public function test_esta_suspendida_por_saldo_es_true_con_el_nuevo_estado(): void
    {
        $cliente = $this->crearCliente();
        $mc = $this->crearMembresiaCliente($cliente, ['estado' => MembresiaCliente::ESTADO_SUSPENDIDA_SALDO]);

        $this->assertTrue($mc->estaSuspendidaPorSaldo());
    }

    // ── 4. Los campos nuevos son fillable (mass-assignment funciona) ─────────

    public function test_los_campos_nuevos_son_fillable(): void
    {
        $fillable = (new MembresiaCliente())->getFillable();

        $this->assertContains('saldo_pendiente', $fillable);
        $this->assertContains('fecha_limite_saldo', $fillable);
    }
}
