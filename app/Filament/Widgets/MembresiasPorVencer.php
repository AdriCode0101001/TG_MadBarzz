<?php

namespace App\Filament\Widgets;

use App\Filament\Concerns\FiltraPorSede;
use App\Models\MembresiaCliente;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MembresiasPorVencer extends TableWidget
{
    use FiltraPorSede;

    protected static ?int $sort = 5;
    protected static ?string $heading = 'Membresías por vencer (próximos 7 días)';
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                $this->filtrarPorSede(
                    MembresiaCliente::query()
                        ->with(['cliente.persona', 'membresia', 'sede'])
                        ->whereBetween('fecha_fin', [now()->toDateString(), now()->addDays(7)->toDateString()])
                        // sin renovación posterior ya registrada
                        ->whereNotExists(function ($q) {
                            $q->select(DB::raw(1))
                              ->from('membresia_cliente as mc2')
                              ->whereColumn('mc2.id_cliente', 'membresia_cliente.id_cliente')
                              ->whereColumn('mc2.fecha_fin', '>', 'membresia_cliente.fecha_fin')
                              ->whereNull('mc2.deleted_at');
                        }),
                    'membresia_cliente.id_sede'
                )
                ->orderBy('fecha_fin')
            )
            ->columns([
                TextColumn::make('cliente_nombre')
                    ->label('Cliente')
                    ->state(fn (MembresiaCliente $record): string => trim(
                        ($record->cliente?->persona?->nombre_completo ?? '') . ' ' .
                        ($record->cliente?->persona?->apellido_paterno ?? '') . ' ' .
                        ($record->cliente?->persona?->apellido_materno ?? '')
                    ) ?: ('Cliente N° ' . $record->id_cliente)),

                TextColumn::make('membresia.tipo_membresia')
                    ->label('Membresía')
                    ->placeholder('—'),

                TextColumn::make('fecha_fin')
                    ->label('Vence')
                    ->date('d/m/Y'),

                TextColumn::make('dias_restantes')
                    ->label('Días restantes')
                    ->state(fn (MembresiaCliente $record): int => max(
                        0,
                        (int) now()->startOfDay()->diffInDays(Carbon::parse($record->fecha_fin)->startOfDay(), false)
                    ))
                    ->badge()
                    ->color(fn (int $state): string => $state <= 2 ? 'danger' : 'warning'),

                TextColumn::make('sede.nombre')
                    ->label('Sede')
                    ->placeholder('—'),
            ])
            ->paginated([5, 10, 25])
            ->emptyStateHeading('Sin vencimientos próximos')
            ->emptyStateDescription('Ninguna membresía vence en los próximos 7 días.');
    }
}
