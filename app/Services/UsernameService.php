<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;

/**
 * Genera nombres de usuario únicos para el alta de clientes:
 * primer nombre + "." + apellido paterno + "." + inicial del apellido materno,
 * en minúsculas, sin tildes ni espacios (ej. "Jhon Crespo Coria" → jhon.crespo.c).
 * Si ya existe, agrega un número incremental: jhon.crespo.c2, jhon.crespo.c3…
 */
class UsernameService
{
    public function generar(string $nombre, string $apellidoPaterno, string $apellidoMaterno = ''): string
    {
        $primerNombre = Str::of($nombre)->squish()->explode(' ')->first() ?? '';

        $partes = array_filter([
            Str::slug($primerNombre, ''),
            Str::slug($apellidoPaterno, ''),
            mb_substr(Str::slug($apellidoMaterno, ''), 0, 1),
        ]);

        $base = implode('.', $partes) ?: 'usuario';

        $username = $base;
        $sufijo   = 2;

        // withTrashed: un username de un usuario dado de baja tampoco se reutiliza
        while (User::withTrashed()->where('name', $username)->exists()) {
            $username = $base . $sufijo;
            $sufijo++;
        }

        return $username;
    }
}
