<?php

namespace App\Filament\Widgets;

use App\Filament\Concerns\FiltraPorSede;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class HorasPicoChart extends ChartWidget
{
    use FiltraPorSede;

    protected static ?int $sort = 3;
    protected static ?string $heading = 'Horas pico (asistencias por hora, últimos 30 días)';

    protected function getData(): array
    {
        $desde = now()->subDays(29)->startOfDay();

        $porHora = $this->filtrarPorSede(DB::table('asistencias'))
            ->where('fecha_hora_entrada', '>=', $desde->toDateTimeString())
            ->pluck('fecha_hora_entrada')
            ->countBy(fn ($fh) => (int) Carbon::parse($fh)->format('G'));

        $labels = [];
        $datos  = [];

        foreach (range(0, 23) as $hora) {
            $labels[] = str_pad($hora, 2, '0', STR_PAD_LEFT) . ':00';
            $datos[]  = $porHora[$hora] ?? 0;
        }

        return [
            'datasets' => [
                [
                    'label'           => 'Asistencias',
                    'data'            => $datos,
                    'backgroundColor' => '#8b5cf6',
                    'borderRadius'    => 4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
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
