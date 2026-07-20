<?php

namespace App\Filament\Widgets;

use App\Filament\Concerns\FiltraPorSede;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Js;

class IngresosPorTipoMembresiaChart extends ChartWidget
{
    use FiltraPorSede;

    protected static ?int $sort = 4;
    protected static ?string $heading = 'Ingresos por tipo de membresía (últimos 12 meses)';

    /** Cuántas veces se adquirió cada tipo (histórico completo), para el tooltip. */
    private array $conteosPorTipo = [];

    protected function getData(): array
    {
        $filas = $this->filtrarPorSede(
                DB::table('pagos')
                    ->leftJoin('membresias', 'membresias.id_membresia', '=', 'pagos.id_membresia'),
                'pagos.id_sede'
            )
            ->where('pagos.fecha_pago', '>=', now()->subMonths(12)->toDateString())
            ->selectRaw("COALESCE(membresias.tipo_membresia, 'Otros') as tipo, SUM(pagos.monto) as total")
            ->groupBy('tipo')
            ->orderByDesc('total')
            ->get();

        // Histórico COMPLETO (no solo 12 meses) de cuántas veces se adquirió
        // cada tipo de membresía: cuenta filas de membresia_cliente, no pagos
        // (una membresía "Otros" —pago sin membresía asociada— no aplica aquí).
        $this->conteosPorTipo = $this->filtrarPorSede(
                DB::table('membresia_cliente')
                    ->join('membresias', 'membresias.id_membresia', '=', 'membresia_cliente.id_membresia')
                    ->whereNull('membresia_cliente.deleted_at'),
                'membresia_cliente.id_sede'
            )
            ->selectRaw('membresias.tipo_membresia as tipo, COUNT(*) as cantidad')
            ->groupBy('tipo')
            ->pluck('cantidad', 'tipo')
            ->all();

        $paleta = ['#6366f1', '#8b5cf6', '#10b981', '#f59e0b', '#ef4444', '#06b6d4', '#ec4899', '#84cc16'];

        return [
            'datasets' => [
                [
                    'label'           => 'Ingresos (Bs)',
                    'data'            => $filas->pluck('total')->map(fn ($t) => (float) $t)->all(),
                    'backgroundColor' => array_slice(
                        array_merge(...array_fill(0, (int) ceil(max($filas->count(), 1) / count($paleta)), $paleta)),
                        0,
                        max($filas->count(), 1)
                    ),
                ],
            ],
            'labels' => $filas->pluck('tipo')->all(),
        ];
    }

    protected function getOptions(): array | RawJs | null
    {
        // Se genera junto con los datos del gráfico (mismo request), así el
        // mapa de conteos que usa el tooltip queda listo antes de leerlo aquí.
        $this->getCachedData();

        // JSON.parse('...') es la forma segura de Laravel de incrustar datos
        // dentro de un atributo HTML (x-data="..."): escapa comillas/otros
        // caracteres para que no rompan el atributo, a diferencia de un
        // json_encode() a pelo dentro de este RawJs.
        $conteosJs = Js::from($this->conteosPorTipo)->toHtml();

        return RawJs::make(<<<JS
        {
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            const conteos = {$conteosJs};
                            const tipo = context.label ?? '';
                            const ingreso = context.parsed ?? 0;
                            const cantidad = conteos[tipo] ?? 0;
                            const monto = new Intl.NumberFormat('es-BO', {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2,
                            }).format(ingreso);
                            const etiquetaClientes = cantidad === 1 ? 'cliente' : 'clientes';
                            return tipo + ': Bs ' + monto + ' · ' + cantidad + ' ' + etiquetaClientes;
                        },
                    },
                },
            },
        }
        JS);
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
