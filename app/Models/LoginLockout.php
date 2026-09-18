<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Registro de "memoria" del bloqueo progresivo de login (ver
 * LoginLockoutService). No es la fuente de verdad de si alguien está
 * bloqueado AHORA MISMO (eso lo decide RateLimiter): esta tabla guarda
 * cuántos intentos lleva y en qué escalón de la progresión está cada
 * combinación login+IP, y sirve para que el Admin vea/desbloquee.
 */
class LoginLockout extends Model
{
    protected $fillable = [
        'login',
        'ip',
        'attempts',
        'lockout_level',
        'locked_until',
    ];

    protected $casts = [
        'locked_until' => 'datetime',
    ];

    public function estaBloqueadoAhora(): bool
    {
        return $this->locked_until !== null && $this->locked_until->isFuture();
    }
}
