<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSede;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Horario BASE semanal de entrenadores (Parte 1: gestión del Administrador).
 * Una fila por día de la semana (1=lunes..6=sábado) + franja de 1 hora +
 * sede. No está ligado a una fecha calendario concreta — es la plantilla
 * semanal que se repite, editable en cualquier momento.
 */
class HorarioEntrenador extends Model
{
    use SoftDeletes, BelongsToSede, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['dia_semana', 'hora_inicio', 'hora_fin', 'id_entrenador', 'id_sede'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $event) => "HorarioEntrenador {$event}");
    }

    protected $table = 'horarios_entrenadores';

    protected $fillable = [
        'dia_semana',
        'hora_inicio',
        'hora_fin',
        'id_entrenador',
        'id_sede',
    ];

    public function entrenador(): BelongsTo
    {
        return $this->belongsTo(Entrenador::class, 'id_entrenador', 'id_entrenador');
    }
}
