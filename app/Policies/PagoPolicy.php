<?php

namespace App\Policies;

use App\Models\Pago;
use App\Models\User;

class PagoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Administrador') || $user->hasRole('Recepcionista');
    }

    public function view(User $user, Pago $pago): bool
    {
        return $user->hasRole('Administrador') || $user->hasRole('Recepcionista');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Administrador') || $user->hasRole('Recepcionista');
    }

    public function update(User $user, Pago $pago): bool
    {
        return $user->hasRole('Administrador') || $user->hasRole('Recepcionista');
    }

    public function delete(User $user, Pago $pago): bool
    {
        return $user->hasRole('Administrador');
    }
}
