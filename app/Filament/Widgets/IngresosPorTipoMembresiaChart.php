<?php

namespace App\Filament\Widgets;

use App\Filament\Concerns\FiltraPorSede;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class IngresosPorTipoMembresiaChart extends ChartWidget
{
    use FiltraPorSede;

    protected static ?int $sort = 4;
    protected static ?string $heading = 'Ingresos por tipo de membresía (últimos 12 meses)';

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

    protected function getType(): string
    {
        return 'doughnut';
    }
}
