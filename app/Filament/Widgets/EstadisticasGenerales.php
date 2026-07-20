<?php

namespace App\Filament\Widgets;

use App\Filament\Concerns\FiltraPorSede;
use App\Models\Cliente;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class EstadisticasGenerales extends StatsOverviewWidget
{
    use FiltraPorSede;

    protected static ?int $sort = 1;

    private function bs(float $monto): string
    {
        return 'Bs ' . number_format($monto, 2, ',', '.');
    }

    protected function getStats(): array
    {
        // ── Clientes con membresía vigente (activos) vs sin ella (inactivos) ──
        // Mismo criterio central que asistencia/rutinas/biometría:
        // Cliente::tieneMembresiaVigente(), no el campo estado. Eloquent
        // (no DB::table) para reutilizar exactamente ese método, y porque
        // así también excluye clientes con soft-delete automáticamente.
        $clientes  = $this->filtrarPorSede(Cliente::query(), 'id_sede')->get();
        $total     = $clientes->count();
        $activos   = $clientes->filter(fn (Cliente $c) => $c->tieneMembresiaVigente())->count();
        $inactivos = $total - $activos;
        $pct       = $total > 0 ? round($activos * 100 / $total) : 0;

        // ── Ingresos del mes vs mes anterior ──
        $ingresosMes = (float) $this->filtrarPorSede(DB::table('pagos'))
            ->whereBetween('fecha_pago', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('monto');

        $ingresosMesAnterior = (float) $this->filtrarPorSede(DB::table('pagos'))
            ->whereBetween('fecha_pago', [
                now()->subMonthNoOverflow()->startOfMonth(),
                now()->subMonthNoOverflow()->endOfMonth(),
            ])
            ->sum('monto');

        if ($ingresosMesAnterior > 0) {
            $variacion  = round(($ingresosMes - $ingresosMesAnterior) * 100 / $ingresosMesAnterior);
            $descIngresos = sprintf(
                '%s%d%% vs %s (%s)',
                $variacion >= 0 ? '+' : '',
                $variacion,
                now()->subMonthNoOverflow()->translatedFormat('F'),
                $this->bs($ingresosMesAnterior)
            );
            $subioIngresos = $variacion >= 0;
        } else {
            $descIngresos  = 'Mes anterior sin pagos registrados';
            $subioIngresos = true;
        }

        // ── Caja de hoy ──
        $cajaHoy = (float) $this->filtrarPorSede(DB::table('pagos'))
            ->whereDate('fecha_pago', now()->toDateString())
            ->sum('monto');

        // ── Membresías por vencer (próximos 7 días, sin renovación posterior) ──
        $porVencer = $this->filtrarPorSede(
                DB::table('membresia_cliente')->whereNull('deleted_at')
            )
            ->whereBetween('fecha_fin', [now()->toDateString(), now()->addDays(7)->toDateString()])
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                  ->from('membresia_cliente as mc2')
                  ->whereColumn('mc2.id_cliente', 'membresia_cliente.id_cliente')
                  ->whereColumn('mc2.fecha_fin', '>', 'membresia_cliente.fecha_fin')
                  ->whereNull('mc2.deleted_at');
            })
            ->count();

        return [
            Stat::make('Clientes activos', "{$activos} de {$total}")
                ->description("{$pct}% activos · {$inactivos} inactivos")
                ->descriptionIcon('heroicon-m-users')
                ->color($pct >= 50 ? 'success' : 'warning'),

            Stat::make('Ingresos del mes', $this->bs($ingresosMes))
                ->description($descIngresos)
                ->descriptionIcon($subioIngresos ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($subioIngresos ? 'success' : 'danger'),

            Stat::make('Caja de hoy', $this->bs($cajaHoy))
                ->description('Pagos del ' . now()->format('d/m/Y'))
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('info'),

            Stat::make('Membresías por vencer', (string) $porVencer)
                ->description('En los próximos 7 días')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($porVencer > 0 ? 'warning' : 'success'),
        ];
    }
}
