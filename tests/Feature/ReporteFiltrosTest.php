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
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Reportes: los filtros de mes/año vienen precargados con el período ACTUAL
 * al abrir la pantalla (sin que el usuario tenga que elegirlo a mano), y el
 * reporte de Clientes Nuevos muestra el celular del cliente.
 */
class ReporteFiltrosTest extends TestCase
{
    use RefreshDatabase;

    private static int $seq = 0;

    private function admin(): User
    {
        return User::factory()->create(['rol_id' => Rol::firstOrCreate(['nombre' => 'Administrador'])->id]);
    }

    private function crearCliente(string $telefono = '+59171234567'): Cliente
    {
        self::$seq++;
        $s = self::$seq;

        $user = User::factory()->create(['rol_id' => Rol::firstOrCreate(['nombre' => 'Cliente'])->id]);

        $personaId = DB::table('personas')->insertGetId([
            'nombre_completo'  => "Reporte {$s}",
            'apellido_paterno' => 'Test',
            'apellido_materno' => 'Filtro',
            'ci'               => "CIREP{$s}",
            'telefono'         => $telefono,
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

    // ── 1. Mes/año actual por defecto en los 3 reportes con filtro de fecha ──

    public function test_clientes_nuevos_abre_con_mes_y_anio_actual_seleccionados(): void
    {
        $this->travelTo(Carbon::parse('2030-07-15 10:00:00', config('app.timezone')));
        $this->crearCliente();

        $html = $this->actingAs($this->admin())
            ->get('/reportes/clientes-nuevos')
            ->assertStatus(200)
            ->getContent();

        // El <option> de Julio y de 2030 deben venir con "selected"
        $this->assertMatchesRegularExpression('/<option value="7"[^>]*selected[^>]*>\s*Julio/i', $html);
        $this->assertStringContainsString('<option value="2030" selected>', $html);
    }

    public function test_clientes_membresia_vencida_abre_con_mes_y_anio_actual_seleccionados(): void
    {
        $this->travelTo(Carbon::parse('2030-09-03 10:00:00', config('app.timezone')));

        $html = $this->actingAs($this->admin())
            ->get('/reportes/clientes-membresia-vencida')
            ->assertStatus(200)
            ->getContent();

        $this->assertMatchesRegularExpression('/<option value="9"[^>]*selected[^>]*>\s*[Ss]eptiembre/i', $html);
        $this->assertStringContainsString('<option value="2030" selected>', $html);
    }

    public function test_pagos_por_mes_abre_con_mes_y_anio_actual_seleccionados(): void
    {
        $this->travelTo(Carbon::parse('2030-02-10 10:00:00', config('app.timezone')));

        $html = $this->actingAs($this->admin())
            ->get('/reportes/pagos-por-mes')
            ->assertStatus(200)
            ->getContent();

        $this->assertMatchesRegularExpression('/<option value="2"[^>]*selected[^>]*>\s*[Ff]ebrero/i', $html);
        $this->assertStringContainsString('<option value="2030" selected>', $html);
    }

    public function test_pagos_por_mes_filtra_por_el_mes_actual_sin_elegir_nada(): void
    {
        $this->travelTo(Carbon::parse('2030-05-10 10:00:00', config('app.timezone')));

        $cliente   = $this->crearCliente();
        $membresia = Membresia::create(['tipo_membresia' => 'Mensual', 'precio' => 150]);
        $mc = MembresiaCliente::create([
            'id_cliente' => $cliente->id_cliente, 'id_membresia' => $membresia->id_membresia,
            'fecha_inicio' => now()->toDateString(), 'fecha_fin' => now()->addDays(30)->toDateString(),
            'precio_final' => 150, 'descuento' => 0, 'estado' => 'activa',
        ]);
        Pago::create([
            'id_cliente' => $cliente->id_cliente, 'id_membresia' => $membresia->id_membresia,
            'id_membresia_cliente' => $mc->id, 'monto' => 150,
            'fecha_pago' => now()->toDateString(), 'metodo_pago' => 'efectivo',
        ]);

        // Sin pasar mes/anio en la URL: el pago de HOY (mayo 2030) ya aparece
        $this->actingAs($this->admin())
             ->get('/reportes/pagos-por-mes')
             ->assertStatus(200)
             ->assertSee('150');
    }

    // ── 2. Columna de celular en Clientes Nuevos ──────────────────────────────

    public function test_reporte_clientes_nuevos_muestra_el_celular_en_pantalla(): void
    {
        $this->crearCliente('+59171234567');

        $this->actingAs($this->admin())
             ->get('/reportes/clientes-nuevos')
             ->assertStatus(200)
             ->assertSee('Celular')
             ->assertSee('+591 71234567');
    }

    public function test_reporte_clientes_nuevos_pdf_incluye_el_celular_y_abre_sin_error(): void
    {
        $this->crearCliente('+59171234567');

        $response = $this->actingAs($this->admin())
             ->get('/reportes/clientes-nuevos/pdf');

        $response->assertStatus(200);
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    // ── Los 4 PDF generan sin error (smoke test) ──────────────────────────────

    public function test_los_cuatro_reportes_pdf_se_generan_sin_error(): void
    {
        $cliente   = $this->crearCliente();
        $membresia = Membresia::create(['tipo_membresia' => 'Mensual', 'precio' => 150]);
        $mc = MembresiaCliente::create([
            'id_cliente' => $cliente->id_cliente, 'id_membresia' => $membresia->id_membresia,
            'fecha_inicio' => now()->toDateString(), 'fecha_fin' => now()->addDays(30)->toDateString(),
            'precio_final' => 150, 'descuento' => 0, 'estado' => 'activa',
        ]);
        Pago::create([
            'id_cliente' => $cliente->id_cliente, 'id_membresia' => $membresia->id_membresia,
            'id_membresia_cliente' => $mc->id, 'monto' => 150,
            'fecha_pago' => now()->toDateString(), 'metodo_pago' => 'efectivo',
        ]);

        $admin = $this->admin();

        foreach ([
            '/reportes/clientes-estado/pdf',
            '/reportes/clientes-nuevos/pdf',
            '/reportes/clientes-membresia-vencida/pdf',
            '/reportes/pagos-por-mes/pdf',
        ] as $ruta) {
            $resp = $this->actingAs($admin)->get($ruta);
            $resp->assertStatus(200);
            $this->assertStringStartsWith('%PDF', $resp->getContent(), "Fallo en {$ruta}");
        }
    }
}
