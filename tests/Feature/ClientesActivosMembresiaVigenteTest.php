<?php

namespace Tests\Feature;

use App\Filament\Widgets\EstadisticasGenerales;
use App\Models\Cliente;
use App\Models\Membresia;
use App\Models\MembresiaCliente;
use App\Models\Rol;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * "Clientes activos" pasó de contar por Cliente.estado a contar por
 * membresía VIGENTE (Cliente::tieneMembresiaVigente(): estado activa Y
 * fecha_fin >= hoy) — el mismo criterio central que ya usan asistencia,
 * rutinas y biometría. Se prueba en el dashboard principal y en el widget
 * Filament EstadisticasGenerales (/estadisticas).
 */
class ClientesActivosMembresiaVigenteTest extends TestCase
{
    use RefreshDatabase;

    private static int $seq = 0;

    private function admin(?Sede $sede = null): User
    {
        return User::factory()->create([
            'rol_id'  => Rol::firstOrCreate(['nombre' => 'Administrador'])->id,
            'id_sede' => $sede?->id_sede,
        ]);
    }

    private function crearCliente(?Sede $sede = null, string $estadoCliente = 'activo'): Cliente
    {
        self::$seq++;
        $s = self::$seq;

        $sede ??= Sede::create(['nombre' => "Sede KPI {$s}", 'direccion' => 'Calle 1', 'activo' => true]);

        $user = User::factory()->create([
            'rol_id'  => Rol::firstOrCreate(['nombre' => 'Cliente'])->id,
            'id_sede' => $sede->id_sede,
        ]);

        $personaId = DB::table('personas')->insertGetId([
            'nombre_completo'  => "KPI {$s}", 'apellido_paterno' => 'Test', 'apellido_materno' => 'Activo',
            'ci' => "CIKPI{$s}", 'telefono' => "+59171999{$s}00", 'genero' => 'M', 'fecha_nacimiento' => '1990-01-01',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return Cliente::create([
            'id_persona' => $personaId, 'id_usuario' => $user->id,
            'estado' => $estadoCliente, 'dias_asistidos' => 0, 'id_sede' => $sede->id_sede,
        ]);
    }

    private function asignarMembresia(Cliente $cliente, string $fechaInicio, string $fechaFin, string $estado = 'activa'): MembresiaCliente
    {
        $membresia = Membresia::create(['tipo_membresia' => 'Mensual', 'precio' => 150]);

        return MembresiaCliente::create([
            'id_cliente'   => $cliente->id_cliente,
            'id_membresia' => $membresia->id_membresia,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin'    => $fechaFin,
            'precio_final' => 150,
            'descuento'    => 0,
            'estado'       => $estado,
            'id_sede'      => $cliente->id_sede,
        ]);
    }

    // ── Casos base del criterio (comunes a dashboard y Filament) ─────────────

    public function test_cliente_con_membresia_vigente_cuenta_como_activo_en_ambos_lugares(): void
    {
        $sede    = Sede::create(['nombre' => 'Sede Vigente', 'direccion' => 'Calle 1', 'activo' => true]);
        $cliente = $this->crearCliente($sede);
        $this->asignarMembresia($cliente, now()->subDays(5)->toDateString(), now()->addDays(25)->toDateString());

        $this->assertTrue($cliente->fresh()->tieneMembresiaVigente());

        // Dashboard
        $html = $this->actingAs($this->admin($sede))->get('/dashboard/index')->assertStatus(200)->getContent();
        $this->assertKpi($html, activos: 1, inactivos: 0);

        // Filament
        [$activos, $total] = $this->statsClientes($sede);
        $this->assertSame(1, $activos);
        $this->assertSame(1, $total);
    }

    public function test_cliente_con_membresia_vencida_no_cuenta_como_activo(): void
    {
        $sede    = Sede::create(['nombre' => 'Sede Vencida', 'direccion' => 'Calle 1', 'activo' => true]);
        $cliente = $this->crearCliente($sede);
        // Vencida hace 3 días, aunque su estado siga 'activa'
        $this->asignarMembresia($cliente, now()->subDays(33)->toDateString(), now()->subDays(3)->toDateString());

        $this->assertFalse($cliente->fresh()->tieneMembresiaVigente());

        $html = $this->actingAs($this->admin($sede))->get('/dashboard/index')->assertStatus(200)->getContent();
        $this->assertKpi($html, activos: 0, inactivos: 1);

        [$activos, $total] = $this->statsClientes($sede);
        $this->assertSame(0, $activos);
        $this->assertSame(1, $total);
    }

    public function test_cliente_sin_ninguna_membresia_no_cuenta_como_activo(): void
    {
        $sede = Sede::create(['nombre' => 'Sede SinMembresia', 'direccion' => 'Calle 1', 'activo' => true]);
        $this->crearCliente($sede); // sin membresía alguna

        $html = $this->actingAs($this->admin($sede))->get('/dashboard/index')->assertStatus(200)->getContent();
        $this->assertKpi($html, activos: 0, inactivos: 1);

        [$activos, $total] = $this->statsClientes($sede);
        $this->assertSame(0, $activos);
        $this->assertSame(1, $total);
    }

    public function test_cliente_con_estado_inactivo_pero_membresia_vigente_cuenta_como_activo(): void
    {
        // Confirma que YA NO se usa el campo estado del cliente: uno marcado
        // 'inactivo' pero con membresía vigente ahora SÍ cuenta como activo.
        $sede    = Sede::create(['nombre' => 'Sede EstadoVsMembresia', 'direccion' => 'Calle 1', 'activo' => true]);
        $cliente = $this->crearCliente($sede, estadoCliente: 'inactivo');
        $this->asignarMembresia($cliente, now()->toDateString(), now()->addDays(30)->toDateString());

        $html = $this->actingAs($this->admin($sede))->get('/dashboard/index')->assertStatus(200)->getContent();
        $this->assertKpi($html, activos: 1, inactivos: 0);
    }

    public function test_cliente_con_dos_membresias_vigentes_cuenta_una_sola_vez(): void
    {
        $sede    = Sede::create(['nombre' => 'Sede DobleMembresia', 'direccion' => 'Calle 1', 'activo' => true]);
        $cliente = $this->crearCliente($sede);

        // Dos membresías vigentes simultáneas (acumulada + otra), el mismo cliente
        $this->asignarMembresia($cliente, now()->toDateString(), now()->addDays(10)->toDateString());
        $this->asignarMembresia($cliente, now()->addDays(11)->toDateString(), now()->addDays(40)->toDateString());

        $otroCliente = $this->crearCliente($sede);
        $this->asignarMembresia($otroCliente, now()->toDateString(), now()->addDays(10)->toDateString());

        $html = $this->actingAs($this->admin($sede))->get('/dashboard/index')->assertStatus(200)->getContent();
        // 2 clientes en total, ambos vigentes → activos = 2 (no 3, aunque hay 3 filas de membresia_cliente)
        $this->assertKpi($html, activos: 2, inactivos: 0);

        [$activos, $total] = $this->statsClientes($sede);
        $this->assertSame(2, $activos);
        $this->assertSame(2, $total);
    }

    public function test_membresia_activa_pero_sin_pago_pendiente_de_pago_no_cuenta_como_activo(): void
    {
        // "Sin pago no hay membresía activa": pendiente_pago no da acceso,
        // aunque la fecha aún no haya vencido (mismo criterio de asistencia).
        $sede    = Sede::create(['nombre' => 'Sede Pendiente', 'direccion' => 'Calle 1', 'activo' => true]);
        $cliente = $this->crearCliente($sede);
        $this->asignarMembresia($cliente, now()->toDateString(), now()->addDays(30)->toDateString(), estado: 'pendiente_pago');

        $this->assertFalse($cliente->fresh()->tieneMembresiaVigente());

        $html = $this->actingAs($this->admin($sede))->get('/dashboard/index')->assertStatus(200)->getContent();
        $this->assertKpi($html, activos: 0, inactivos: 1);
    }

    // ── Sede: el widget Filament respeta el scope del admin ──────────────────

    public function test_el_widget_filament_respeta_la_sede_del_administrador(): void
    {
        $sedeA = Sede::create(['nombre' => 'Sede A KPI', 'direccion' => 'Calle 1', 'activo' => true]);
        $sedeB = Sede::create(['nombre' => 'Sede B KPI', 'direccion' => 'Calle 2', 'activo' => true]);

        $clienteA = $this->crearCliente($sedeA);
        $this->asignarMembresia($clienteA, now()->toDateString(), now()->addDays(30)->toDateString());

        $clienteB = $this->crearCliente($sedeB);
        $this->asignarMembresia($clienteB, now()->toDateString(), now()->addDays(30)->toDateString());

        [$activos, $total] = $this->statsClientes($sedeA);
        $this->assertSame(1, $activos);
        $this->assertSame(1, $total); // no ve al cliente de la sede B
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function assertKpi(string $html, int $activos, int $inactivos): void
    {
        $this->assertStringContainsString(
            "<div class=\"mb-kpi2__num\">{$activos}</div>\n            <div class=\"mb-kpi2__lbl\">Clientes activos</div>",
            $html
        );
        $this->assertStringContainsString(
            "<div class=\"mb-kpi2__num\">{$inactivos}</div>\n            <div class=\"mb-kpi2__lbl\">Clientes inactivos</div>",
            $html
        );
    }

    /** @return array{0:int,1:int} [activos, total] leídos del Stat "Clientes activos" */
    private function statsClientes(Sede $sede): array
    {
        $this->actingAs($this->admin($sede));

        $widget = Livewire::test(EstadisticasGenerales::class)->instance();

        // getStats() es protected: se invoca por reflexión, igual que se hizo
        // para getData() en IngresosPorTipoMembresiaTooltipTest.
        $metodo = new \ReflectionMethod($widget, 'getStats');
        $metodo->setAccessible(true);
        $stats = $metodo->invoke($widget);

        // Stat::make('Clientes activos', "{$activos} de {$total}")
        $valor = $stats[0]->getValue();
        preg_match('/(\d+) de (\d+)/', $valor, $m);

        return [(int) $m[1], (int) $m[2]];
    }
}
