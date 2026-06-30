<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class SedeScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        // Sin sesión activa (comandos Artisan, tests sin login) no filtra.
        if (! Auth::check()) {
            return;
        }

        $user = Auth::user();

        // Administradores globales ven datos de todas las sedes.
        if ($user->hasRole('Administrador')) {
            return;
        }

        // Cualquier otro rol solo ve los registros de su propia sede.
        if ($user->id_sede) {
            $builder->where($model->qualifyColumn('id_sede'), $user->id_sede);
        }
    }
}
