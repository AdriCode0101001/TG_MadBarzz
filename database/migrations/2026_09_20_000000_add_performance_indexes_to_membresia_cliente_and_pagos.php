<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Revisión de production-readiness — Tanda 2 (rendimiento). Con el volumen
 * actual (pocas membresías/pagos por cliente) no se nota, pero es el
 * "camino caliente" del sistema: membresia_cliente.fecha_fin+estado se
 * consultan constantemente (tieneMembresiaVigente, dashboard, portal del
 * cliente, el widget "Membresías por vencer") y pagos.fecha_pago se filtra
 * en el buscador de Pagos y en los reportes por mes.
 *
 * Índice compuesto (fecha_fin, estado) en vez de dos índices sueltos:
 * revisando cómo se consulta (MembresiasPorVencer::class hace
 * whereBetween('fecha_fin', ...) SIN filtrar por id_cliente — un scan
 * global de la tabla, no por cliente), fecha_fin como columna líder sirve
 * ese rango directamente; estado como segunda columna sigue sirviendo a
 * las consultas que filtran ambas juntas (DashboardController,
 * ClientePortalController: wherePivot('fecha_fin',...)->wherePivot('estado',...)).
 * El filtrado POR CLIENTE (tieneMembresiaVigente -> membresiaMasReciente)
 * ya usa la foreign key id_cliente, que MySQL indexa sola — no hacía falta
 * repetirla acá.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('membresia_cliente', function (Blueprint $table) {
            $table->index(['fecha_fin', 'estado'], 'membresia_cliente_fecha_fin_estado_index');
        });

        Schema::table('pagos', function (Blueprint $table) {
            $table->index('fecha_pago', 'pagos_fecha_pago_index');
        });
    }

    public function down(): void
    {
        Schema::table('membresia_cliente', function (Blueprint $table) {
            $table->dropIndex('membresia_cliente_fecha_fin_estado_index');
        });

        Schema::table('pagos', function (Blueprint $table) {
            $table->dropIndex('pagos_fecha_pago_index');
        });
    }
};
