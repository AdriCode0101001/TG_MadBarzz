<?php

namespace App\Filament\Widgets;

use App\Filament\Concerns\FiltraPorSede;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AsistenciasPorDiaChart extends ChartWidget
{
    use FiltraPorSede;

    protected static ?int $sort = 2;
    protected static ?string $heading = 'Asistencias por día (últimos 30 días)';
    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $desde = now()->subDays(29)->startOfDay();

        // Agrupación en PHP para no depender de funciones de fecha del motor de BD
        $porDia = $this->filtrarPorSede(DB::table('asistencias'))
            ->where('fecha_hora_entrada', '>=', $desde->toDateTimeString())
            ->pluck('fecha_hora_entrada')
            ->countBy(fn ($fh) => Carbon::parse($fh)->toDateString());

        $labels = [];
        $datos  = [];

        for ($dia = $desde->copy(); $dia->lte(now()); $dia->addDay()) {
            $labels[] = $dia->format('d/m');
            $datos[]  = $porDia[$dia->toDateString()] ?? 0;
        }

        return [
            'datasets' => [
                [
                    'label'           => 'Asistencias',
                    'data'            => $datos,
                    'fill'            => true,
                    'tension'         => 0.3,
                    'borderColor'     => '#6366f1',
                    'backgroundColor' => 'rgba(99, 102, 241, .18)',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks'       => ['precision' => 0],
                ],
            ],
        ];
    }
}
