<?php

namespace App\Policies;

use App\Models\Rutina;
use App\Models\User;

class RutinaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Administrador') || $user->hasRole('Entrenador');
    }

    public function view(User $user, Rutina $rutina): bool
    {
        return $user->hasRole('Administrador') || $user->hasRole('Entrenador');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Administrador') || $user->hasRole('Entrenador');
    }

    public function update(User $user, Rutina $rutina): bool
    {
        return $user->hasRole('Administrador') || $user->hasRole('Entrenador');
    }

    public function delete(User $user, Rutina $rutina): bool
    {
        return $user->hasRole('Administrador');
    }
}
