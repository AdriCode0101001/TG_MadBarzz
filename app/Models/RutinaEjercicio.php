<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Una fila = un ejercicio dentro de una Rutina, con sus series/repeticiones.
 */
class RutinaEjercicio extends Model
{
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
