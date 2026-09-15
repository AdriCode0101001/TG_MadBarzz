<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSede;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Una fila = "en esta fecha (de esta sede) se trabaja este grupo muscular".
 * Los domingos nunca tienen fila (el gimnasio cierra).
 */
class CalendarioMusculo extends Model
{
    use SoftDeletes, BelongsToSede, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['fecha', 'id_grupo_muscular', 'id_sede'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $event) => "CalendarioMusculo {$event}");
    }

    protected $table = 'calendario_musculos';

    protected $fillable = [
        'fecha',
        'id_grupo_muscular',
        'id_sede',
    ];

    protected $casts = [
        // Formato explícito Y-m-d: sin esto, Eloquent guarda "fecha" con hora
        // (00:00:00) y las comparaciones por string ('fecha' = '2026-07-20')
        // en el controlador dejan de calzar con lo almacenado.
        'fecha' => 'date:Y-m-d',
    ];

    public function grupoMuscular(): BelongsTo
    {
        return $this->belongsTo(GrupoMuscular::class, 'id_grupo_muscular');
    }
}
