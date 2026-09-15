<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Una fila = un ejercicio dentro de una Rutina, con sus series/repeticiones.
 */
class RutinaEjercicio extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['id_rutina', 'id_ejercicio', 'series', 'repeticiones', 'orden', 'nota', 'media_propio'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $event) => "RutinaEjercicio {$event}");
    }

    protected $table = 'rutina_ejercicios';

    protected $fillable = [
        'id_rutina',
        'id_ejercicio',
        'series',
        'repeticiones',
        'orden',
        'nota',
        'media_propio',
    ];

    protected $casts = [
        'series' => 'integer',
        'orden'  => 'integer',
    ];

    public function rutina(): BelongsTo
    {
        return $this->belongsTo(Rutina::class, 'id_rutina');
    }

    public function ejercicio(): BelongsTo
    {
        return $this->belongsTo(Ejercicio::class, 'id_ejercicio');
    }
}
