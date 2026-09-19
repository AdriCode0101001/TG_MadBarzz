<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Membresia;
use App\Models\MembresiaCliente;
use App\Models\Pago;
use App\Models\Persona;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Responsive de tablas: 9 vistas de listado no envolvían su <table> en
 * <div class="table-responsive">, lo que hacía que en celular la tabla
 * desbordara y arrastrara TODA la página (header y sidebar incluidos) —
 * ver diagnóstico previo. El fix es puramente de wrapper HTML, mismo patrón
 * que ya usan clientes/index, pagos/index, etc. Estas pruebas confirman que
 * el wrapper quedó puesto y que no se rompió el contenido de cada tabla.
 */
class ResponsiveTablasTest extends TestCase
{
    use RefreshDatabase;

    private static int $seq = 0;

    private function admin(): User
    {
        return User::factory()->create(['rol_id' => Rol::firstOrCreate(['nombre' => 'Administrador'])->id]);
    }

    private function crearCliente(): Cliente
    {
        self::$seq++;
        $s = self::$seq;

        $user = User::factory()->create(['rol_id' => Rol::firstOrCreate(['nombre' => 'Cliente'])->id]);

        $personaId = DB::table('personas')->insertGetId([
            'nombre_completo'  => "Responsive {$s}",
            'apellido_paterno' => 'Test',
            'apellido_materno' => 'Tabla',
            'ci'               => "CIRESP{$s}",
            'telefono'         => "+5917100{$s}",
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
        ]);
    }

    private function crearPago(): Pago
    {
        $cliente   = $this->crearCliente();
        $membresia = Membresia::create(['tipo_membresia' => 'Mensual', 'precio' => 150]);
        $mc = MembresiaCliente::create([
            'id_cliente' => $cliente->id_cliente, 'id_membresia' => $membresia->id_membresia,
            'fecha_inicio' => now()->toDateString(), 'fecha_fin' => now()->addDays(30)->toDateString(),
            'precio_final' => 150, 'descuento' => 0, 'estado' => 'activa',
        ]);

        return Pago::create([
            'id_cliente' => $cliente->id_cliente, 'id_membresia' => $membresia->id_membresia,
            'id_membresia_cliente' => $mc->id, 'monto' => 150,
            'fecha_pago' => now()->toDateString(), 'metodo_pago' => 'efectivo',
        ]);
    }

    private function assertVistaEnvuelveSuTablaEnResponsive(string $url): void
    {
        $this->crearCliente(); // que haya al menos una fila, no solo el estado vacío

        $html = $this->actingAs($this->admin())
            ->get($url)
            ->assertStatus(200)
            ->getContent();

        $this->assertStringContainsString('table-responsive', $html, "Falta table-responsive en {$url}");
        // El wrapper debe envolver a la tabla, no aparecer suelto en cualquier lado
        $this->assertMatchesRegularExpression('/<div class="table-responsive"[^>]*>\s*<table/', $html, "El wrapper no envuelve la tabla en {$url}");
    }

    public function test_users_index_envuelve_su_tabla_en_table_responsive(): void
    {
        $this->assertVistaEnvuelveSuTablaEnResponsive('/users');
    }

    public function test_roles_index_envuelve_su_tabla_en_table_responsive(): void
    {
        $this->assertVistaEnvuelveSuTablaEnResponsive('/roles');
    }

    public function test_personas_index_envuelve_su_tabla_en_table_responsive(): void
    {
        $this->assertVistaEnvuelveSuTablaEnResponsive('/personas');
    }

    public function test_reporte_clientes_estado_envuelve_su_tabla_en_table_responsive(): void
    {
        $this->assertVistaEnvuelveSuTablaEnResponsive('/reportes/clientes-estado');
    }

    public function test_reporte_clientes_membresia_vencida_envuelve_su_tabla_en_table_responsive(): void
    {
        $this->assertVistaEnvuelveSuTablaEnResponsive('/reportes/clientes-membresia-vencida');
    }

    public function test_reporte_clientes_nuevos_envuelve_su_tabla_en_table_responsive(): void
    {
        $this->assertVistaEnvuelveSuTablaEnResponsive('/reportes/clientes-nuevos');
    }

    public function test_reporte_pagos_por_mes_envuelve_su_tabla_en_table_responsive(): void
    {
        $this->assertVistaEnvuelveSuTablaEnResponsive('/reportes/pagos-por-mes');
    }

    public function test_pagos_show_envuelve_su_tabla_en_table_responsive(): void
    {
        $pago = $this->crearPago();

        $html = $this->actingAs($this->admin())
            ->get("/pagos/{$pago->id_pago}")
            ->assertStatus(200)
            ->getContent();

        $this->assertStringContainsString('table-responsive', $html);
        $this->assertMatchesRegularExpression('/<div class="table-responsive"[^>]*>\s*<table/', $html);
    }

    // ── El contenido de las tablas no cambió ──────────────────────────────────

    public function test_users_index_sigue_mostrando_los_datos_del_usuario(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get('/users')
             ->assertStatus(200)
             ->assertSee($admin->name)
             ->assertSee($admin->email);
    }

    public function test_personas_index_sigue_mostrando_los_datos_de_la_persona(): void
    {
        $cliente = $this->crearCliente();

        $this->actingAs($this->admin())->get('/personas')
             ->assertStatus(200)
             ->assertSee($cliente->persona->ci);
    }

    public function test_reporte_pagos_por_mes_sigue_mostrando_el_monto(): void
    {
        $this->crearPago();

        $this->actingAs($this->admin())->get('/reportes/pagos-por-mes')
             ->assertStatus(200)
             ->assertSee('150.00');
    }
}
