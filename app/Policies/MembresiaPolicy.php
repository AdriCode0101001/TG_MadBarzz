<?php

namespace App\Policies;

use App\Models\Membresia;
use App\Models\User;

class MembresiaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Administrador') || $user->hasRole('Recepcionista');
    }

    public function view(User $user, Membresia $membresia): bool
    {
        return $user->hasRole('Administrador') || $user->hasRole('Recepcionista');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Administrador');
    }

    public function update(User $user, Membresia $membresia): bool
    {
        return $user->hasRole('Administrador');
    }

    public function delete(User $user, Membresia $membresia): bool
    {
        return $user->hasRole('Administrador');
    }
}
