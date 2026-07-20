<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSede;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Rutina de UN entrenador/admin para UN día (fecha), con el grupo muscular
 * de ese día y sus ejercicios (rutina_ejercicios). Única por (id_usuario,
 * fecha) — ver migración para el porqué de id_usuario en vez de
 * id_entrenador.
 */
class Rutina extends Model
{
    use SoftDeletes, BelongsToSede;

    protected $table = 'rutinas';

    protected $fillable = [
        'fecha',
        'id_grupo_muscular',
        'id_usuario',
        'id_sede',
    ];

    protected $casts = [
        // Formato explícito Y-m-d: sin esto Eloquent guarda "fecha" con hora
        // y las comparaciones por string ('fecha' = '2026-07-20') dejan de
        // calzar con lo almacenado (mismo bug ya resuelto en calendario_musculos).
        'fecha' => 'date:Y-m-d',
    ];

    public function grupoMuscular(): BelongsTo
    {
        return $this->belongsTo(GrupoMuscular::class, 'id_grupo_muscular');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }

    public function ejercicios(): HasMany
    {
        return $this->hasMany(RutinaEjercicio::class, 'id_rutina')->orderBy('orden');
    }
}
