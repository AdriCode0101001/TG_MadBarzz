<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Membresia;
use App\Models\MembresiaCliente;
use App\Models\Pago;
use App\Models\Rol;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * El tooltip de la torta "Ingresos por tipo de membresía" (panel /estadisticas)
 * debe informar, además del ingreso, cuántos clientes adquirieron cada tipo
 * (histórico completo de membresia_cliente, no solo los últimos 12 meses).
 */
class IngresosPorTipoMembresiaTooltipTest extends TestCase
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

    private function crearClienteConMembresia(Membresia $membresia, Sede $sede, string $fechaPago): Cliente
    {
        self::$seq++;
        $s = self::$seq;

        $user = User::factory()->create([
            'rol_id'  => Rol::firstOrCreate(['nombre' => 'Cliente'])->id,
            'id_sede' => $sede->id_sede,
        ]);

        $personaId = DB::table('personas')->insertGetId([
            'nombre_completo' => "Torta {$s}", 'apellido_paterno' => 'Test', 'apellido_materno' => 'Chart',
            'ci' => "CICHART{$s}", 'telefono' => "+59171000{$s}00", 'genero' => 'M', 'fecha_nacimiento' => '1990-01-01',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $cliente = Cliente::create([
            'id_persona' => $personaId, 'id_usuario' => $user->id, 'estado' => 'activo',
            'dias_asistidos' => 0, 'id_sede' => $sede->id_sede,
        ]);

        $mc = MembresiaCliente::create([
            'id_cliente' => $cliente->id_cliente, 'id_membresia' => $membresia->id_membresia,
            'fecha_inicio' => $fechaPago, 'fecha_fin' => now()->addDays(30)->toDateString(),
            'precio_final' => $membresia->precio, 'descuento' => 0, 'estado' => 'activa',
            'id_sede' => $sede->id_sede,
        ]);

        Pago::create([
            'id_cliente' => $cliente->id_cliente, 'id_membresia' => $membresia->id_membresia,
            'id_membresia_cliente' => $mc->id, 'monto' => $membresia->precio,
            'fecha_pago' => $fechaPago, 'metodo_pago' => 'efectivo', 'id_sede' => $sede->id_sede,
        ]);

        return $cliente;
    }

    public function test_el_panel_sigue_cargando(): void
    {
        // Todos los widgets Filament son lazy por defecto (CanBeLazy::$isLazy),
        // así que el HTML inicial de /estadisticas solo trae un placeholder;
        // aquí solo se confirma que la página entera sigue cargando sin error.
        $sede = Sede::create(['nombre' => 'Sede Chart', 'direccion' => 'Calle 1', 'activo' => true]);

        $this->actingAs($this->admin($sede))
            ->get('/estadisticas')
            ->assertStatus(200)
            ->assertSee('IngresosPorTipoMembresiaChart', false);
    }

    public function test_el_widget_renderiza_el_tooltip_con_el_conteo_de_clientes(): void
    {
        // Renderiza el widget Livewire directamente (sin el lazy-load de
        // Filament) para inspeccionar el x-data="chart({...})" real que
        // recibe Alpine, con el callback de tooltip embebido.
        $sede      = Sede::create(['nombre' => 'Sede Chart', 'direccion' => 'Calle 1', 'activo' => true]);
        $membresia = Membresia::create(['tipo_membresia' => 'Mensual', 'precio' => 150]);

        $this->crearClienteConMembresia($membresia, $sede, now()->toDateString());
        $this->crearClienteConMembresia($membresia, $sede, now()->toDateString());

        $this->actingAs($this->admin($sede));

        $html = Livewire::test(\App\Filament\Widgets\IngresosPorTipoMembresiaChart::class)
            ->assertOk()
            ->html();

        // El callback de tooltip quedó embebido en el x-data del widget
        $this->assertStringContainsString('JSON.parse', $html);
        $this->assertStringContainsString('etiquetaClientes', $html);
        $this->assertStringContainsString('tooltip', $html);

        // El atributo x-data quedó bien formado (sin comillas rotas): el HTML
        // sigue teniendo el resto de refs del componente del gráfico
        // DESPUÉS del bloque que inyectamos (si la comilla se hubiera roto,
        // el atributo quedaría truncado y esto ya no aparecería).
        $this->assertStringContainsString('x-ref="canvas"', $html);
        $this->assertStringContainsString('backgroundColorElement', $html);

        // El mapa de conteos (2 adquisiciones de "Mensual") llegó al JS
        $this->assertMatchesRegularExpression('/Mensual.{0,30}2/u', $html);
    }

    public function test_el_conteo_historico_cuenta_todas_las_adquisiciones_no_solo_12_meses(): void
    {
        $sede      = Sede::create(['nombre' => 'Sede Historico', 'direccion' => 'Calle 1', 'activo' => true]);
        $membresia = Membresia::create(['tipo_membresia' => 'Trimestral', 'precio' => 400]);

        // Una adquisición reciente (dentro de los 12 meses) y otra vieja
        // (hace 2 años, fuera de la ventana de ingresos del gráfico)
        $this->crearClienteConMembresia($membresia, $sede, now()->toDateString());
        $this->crearClienteConMembresia($membresia, $sede, now()->subYears(2)->toDateString());

        $widget = new \App\Filament\Widgets\IngresosPorTipoMembresiaChart();
        $this->actingAs($this->admin($sede));

        $ref = new \ReflectionMethod($widget, 'getData');
        $ref->setAccessible(true);
        $ref->invoke($widget); // llena conteosPorTipo como side effect

        $refConteos = new \ReflectionProperty($widget, 'conteosPorTipo');
        $refConteos->setAccessible(true);
        $conteos = $refConteos->getValue($widget);

        // Las 2 adquisiciones cuentan, aunque una sea de hace 2 años
        $this->assertSame(2, $conteos['Trimestral']);
    }

    public function test_el_conteo_respeta_la_sede_del_administrador(): void
    {
        $sedeA = Sede::create(['nombre' => 'Sede A Chart', 'direccion' => 'Calle 1', 'activo' => true]);
        $sedeB = Sede::create(['nombre' => 'Sede B Chart', 'direccion' => 'Calle 2', 'activo' => true]);
        $membresia = Membresia::create(['tipo_membresia' => 'Anual', 'precio' => 1300]);

        $this->crearClienteConMembresia($membresia, $sedeA, now()->toDateString());
        $this->crearClienteConMembresia($membresia, $sedeA, now()->toDateString());
        $this->crearClienteConMembresia($membresia, $sedeB, now()->toDateString());

        $widget = new \App\Filament\Widgets\IngresosPorTipoMembresiaChart();
        $this->actingAs($this->admin($sedeA)); // admin scopeado a sede A

        $ref = new \ReflectionMethod($widget, 'getData');
        $ref->setAccessible(true);
        $ref->invoke($widget);

        $refConteos = new \ReflectionProperty($widget, 'conteosPorTipo');
        $refConteos->setAccessible(true);
        $conteos = $refConteos->getValue($widget);

        // Solo ve las 2 de su sede, no la de la sede B
        $this->assertSame(2, $conteos['Anual']);
    }
}
