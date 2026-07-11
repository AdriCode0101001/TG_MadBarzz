<?php

namespace App\Http\Middleware;

use Filament\Http\Middleware\Authenticate as FilamentAuthenticate;

/**
 * Autenticación del panel Filament reutilizando el login existente de la app:
 * los invitados van al /login de Breeze (el panel no tiene login propio).
 * El control de rol (solo Administrador) lo hace User::canAccessPanel().
 */
class FilamentAdminAuthenticate extends FilamentAuthenticate
{
    protected function redirectTo($request): ?string
    {
        return route('login');
    }
}
