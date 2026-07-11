<?php

namespace App\Filament\Concerns;

use Illuminate\Support\Facades\Auth;

/**
 * Mismo criterio que SedeScope, aplicado a mano en los widgets:
 * el panel solo lo usan Administradores (a quienes SedeScope no filtra),
 * así que aquí un admin global (sin id_sede) ve todas las sedes y un
 * admin con sede asignada ve solo la suya.
 */
trait FiltraPorSede
{
    protected function filtrarPorSede($query, string $columna = 'id_sede')
    {
        $idSede = Auth::user()?->id_sede;

        if ($idSede) {
            $query->where($columna, $idSede);
        }

        return $query;
    }
}
