<?php

namespace App\Models\Concerns;

use App\Models\Scopes\SedeScope;
use App\Models\Sede;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Trait para modelos que pertenecen a una sede.
 *
 * Aplica automáticamente SedeScope en las consultas y asigna
 * la sede del usuario autenticado cuando se crea un nuevo registro.
 */
trait BelongsToSede
{
    protected static function bootBelongsToSede(): void
    {
        // Filtrado automático por sede en todas las consultas.
        static::addGlobalScope(new SedeScope());

        // Al crear, toma la sede del usuario autenticado si no viene explícita.
        static::creating(function (self $model) {
            if (empty($model->id_sede) && Auth::check() && Auth::user()->id_sede) {
                $model->id_sede = Auth::user()->id_sede;
            }
        });
    }

    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sede::class, 'id_sede', 'id_sede');
    }
}
