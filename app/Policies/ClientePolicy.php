<?php

namespace App\Policies;

use App\Models\Cliente;
use App\Models\User;

class ClientePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Administrador') || $user->hasRole('Recepcionista');
    }

    public function view(User $user, Cliente $cliente): bool
    {
        if ($user->hasRole('Administrador') || $user->hasRole('Recepcionista')) {
            return true;
        }
        // Un cliente solo puede verse a sí mismo
        return $user->hasRole('Cliente') && $cliente->id_usuario === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Administrador') || $user->hasRole('Recepcionista');
    }

    public function update(User $user, Cliente $cliente): bool
    {
        return $user->hasRole('Administrador') || $user->hasRole('Recepcionista');
    }

    public function delete(User $user, Cliente $cliente): bool
    {
        return $user->hasRole('Administrador');
    }
}
