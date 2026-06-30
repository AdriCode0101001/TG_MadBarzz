<?php

namespace App\Policies;

use App\Models\Asistencia;
use App\Models\User;

class AsistenciaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Administrador') || $user->hasRole('Recepcionista');
    }

    public function view(User $user, Asistencia $asistencia): bool
    {
        return $user->hasRole('Administrador') || $user->hasRole('Recepcionista');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Administrador') || $user->hasRole('Recepcionista');
    }

    public function update(User $user, Asistencia $asistencia): bool
    {
        return $user->hasRole('Administrador') || $user->hasRole('Recepcionista');
    }

    public function delete(User $user, Asistencia $asistencia): bool
    {
        return $user->hasRole('Administrador');
    }
}
