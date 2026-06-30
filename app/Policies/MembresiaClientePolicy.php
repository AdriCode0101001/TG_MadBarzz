<?php

namespace App\Policies;

use App\Models\MembresiaCliente;
use App\Models\User;

class MembresiaClientePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Administrador') || $user->hasRole('Recepcionista');
    }

    public function view(User $user, MembresiaCliente $membresiaCliente): bool
    {
        return $user->hasRole('Administrador') || $user->hasRole('Recepcionista');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Administrador') || $user->hasRole('Recepcionista');
    }

    public function update(User $user, MembresiaCliente $membresiaCliente): bool
    {
        return $user->hasRole('Administrador') || $user->hasRole('Recepcionista');
    }

    public function delete(User $user, MembresiaCliente $membresiaCliente): bool
    {
        return $user->hasRole('Administrador');
    }
}
