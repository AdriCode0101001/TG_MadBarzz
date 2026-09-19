<?php

namespace Tests\Feature\Performance;

use App\Models\Cliente;
use App\Models\Membresia;
use App\Models\Pago;
use App\Models\Persona;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Revisión de production-readiness — Tanda 2, Arreglo 2: índices en
 * membresia_cliente.fecha_fin+estado y pagos.fecha_pago, y reescritura del
 * filtro de fecha de PagoController (mm/yyyy y yyyy) para que use rangos
 * en vez de MONTH()/YEAR() envolviendo la columna, de forma que el índice
 * nuevo sirva de verdad.
 */
class IndicesRendimientoTest extends TestCase
{
    use RefreshDatabase;

    private static int $seq = 0;

    private function seq(): int
    {
        return ++self::$seq;
    }

    private function admin(): User
    {
        return User::factory()->create(['rol_id' => Rol::firstOrCreate(['nombre' => 'Administrador'])->id]);
    }

    private function cliente(): Cliente
    {
        $s = $this->seq();

        $persona = Persona::create([
            'nombre_completo' => "Cliente Indice {$s}", 'apellido_paterno' => 'Test', 'apellido_materno' => 'Indices',
            'ci' => "CIIDX{$s}", 'telefono' => "+59176" . str_pad((string) $s, 6, '0', STR_PAD_LEFT),
            'genero' => 'masculino', 'fecha_nacimiento' => '1990-01-01',
        ]);
        $user = User::factory()->create(['rol_id' => Rol::firstOrCreate(['nombre' => 'Cliente'])->id]);

        return Cliente::create([
            'id_persona' => $persona->id_persona, 'id_usuario' => $user->id,
            'estado' => 'activo', 'dias_asistidos' => 0,
        ]);
    }

    // ── Los índices existen (la migración corrió bien) ──────────────────────

    public function test_el_indice_compuesto_de_membresia_cliente_existe(): void
    {
        $indices = collect(Schema::getIndexes('membresia_cliente'));

        $encontrado = $indices->contains(function ($idx) {
            $columnas = array_map('strtolower', $idx['columns'] ?? []);

            return $columnas === ['fecha_fin', 'estado'];
        });

        $this->assertTrue($encontrado, 'No se encontró el índice compuesto (fecha_fin, estado) en membresia_cliente.');
    }

    public function test_el_indice_de_pagos_fecha_pago_existe(): void
    {
        $indices = collect(Schema::getIndexes('pagos'));

        $encontrado = $indices->contains(fn ($idx) => in_array('fecha_pago', $idx['columns'] ?? []));

        $this->assertTrue($encontrado, 'No se encontró el índice de fecha_pago en pagos.');
    }

    // ── El filtro de fecha de Pagos sigue devolviendo lo correcto ───────────

    private function pagoEn(Cliente $cliente, int $idMembresia, string $fecha): Pago
    {
        return Pago::create([
            'id_cliente' => $cliente->id_cliente, 'id_membresia' => $idMembresia,
            'monto' => 100, 'fecha_pago' => $fecha, 'metodo_pago' => 'efectivo',
        ]);
    }

    public function test_filtro_mm_yyyy_reescrito_a_rango_devuelve_solo_ese_mes(): void
    {
        $cliente   = $this->cliente();
        $membresia = Membresia::create(['tipo_membresia' => 'Mensual', 'precio' => 100]);

        $dentro1 = $this->pagoEn($cliente, $membresia->id_membresia, '2026-03-01');
        $dentro2 = $this->pagoEn($cliente, $membresia->id_membresia, '2026-03-31');
        $fueraAntes = $this->pagoEn($cliente, $membresia->id_membresia, '2026-02-28');
        $fueraDespues = $this->pagoEn($cliente, $membresia->id_membresia, '2026-04-01');
        $otroAnio = $this->pagoEn($cliente, $membresia->id_membresia, '2025-03-15');

        $pagos = $this->actingAs($this->admin())
            ->get('/pagos?buscar=' . urlencode('03/2026'))
            ->viewData('pagos');

        $ids = $pagos->pluck('id_pago')->all();

        $this->assertContains($dentro1->id_pago, $ids);
        $this->assertContains($dentro2->id_pago, $ids);
        $this->assertNotContains($fueraAntes->id_pago, $ids);
        $this->assertNotContains($fueraDespues->id_pago, $ids);
        $this->assertNotContains($otroAnio->id_pago, $ids);
    }

    public function test_filtro_solo_anio_reescrito_a_rango_devuelve_solo_ese_anio(): void
    {
        $cliente   = $this->cliente();
        $membresia = Membresia::create(['tipo_membresia' => 'Mensual', 'precio' => 100]);

        $dentroInicio = $this->pagoEn($cliente, $membresia->id_membresia, '2026-01-01');
        $dentroFin    = $this->pagoEn($cliente, $membresia->id_membresia, '2026-12-31');
        $fuera        = $this->pagoEn($cliente, $membresia->id_membresia, '2025-12-31');

        $pagos = $this->actingAs($this->admin())
            ->get('/pagos?buscar=2026')
            ->viewData('pagos');

        $ids = $pagos->pluck('id_pago')->all();

        $this->assertContains($dentroInicio->id_pago, $ids);
        $this->assertContains($dentroFin->id_pago, $ids);
        $this->assertNotContains($fuera->id_pago, $ids);
    }

    // NOTA: el formato dd/mm (cualquier año, ver PagoController) sigue
    // usando DAY()/MONTH() vía whereRaw — no se pudo reescribir a un rango
    // sargable porque debe matchear ESE día en TODOS los años, algo que un
    // único rango no puede expresar. No se tocó ese branch (fuera de
    // alcance de esta tanda), y no se puede probar acá porque DAY()/MONTH()
    // son funciones de MySQL que SQLite (la base de los tests) no tiene —
    // limitación preexistente del entorno de test, no de este cambio.

    public function test_filtro_fecha_exacta_dd_mm_yyyy_sigue_funcionando(): void
    {
        $cliente   = $this->cliente();
        $membresia = Membresia::create(['tipo_membresia' => 'Mensual', 'precio' => 100]);

        $exacto = $this->pagoEn($cliente, $membresia->id_membresia, '2026-05-20');
        $otro   = $this->pagoEn($cliente, $membresia->id_membresia, '2026-05-21');

        $pagos = $this->actingAs($this->admin())
            ->get('/pagos?buscar=' . urlencode('20/05/2026'))
            ->viewData('pagos');

        $ids = $pagos->pluck('id_pago')->all();

        $this->assertContains($exacto->id_pago, $ids);
        $this->assertNotContains($otro->id_pago, $ids);
    }
}
