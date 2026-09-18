<?php

namespace App\Services;

use App\Models\LoginLockout;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Bloqueo progresivo de login por intentos fallidos, por login (email o
 * username) + IP — nunca global, para que el fallo de un usuario no
 * bloquee a los demás.
 *
 * El bloqueo REAL lo sigue haciendo Illuminate\Support\Facades\RateLimiter
 * (el mismo que ya usaba LoginRequest): acá se usa como una llave
 * "bloqueado hasta tal hora" con TTL a medida — RateLimiter::hit($key,
 * $segundos) fija el bloqueo por exactamente $segundos, y
 * RateLimiter::tooManyAttempts($key, 1) simplemente pregunta "¿hay un
 * bloqueo vigente en esta llave?".
 *
 * Lo que RateLimiter NO puede recordar por sí solo es la ESCALADA (que el
 * próximo bloqueo sea el doble del anterior): su contador se pierde apenas
 * expira el TTL. Por eso existe LoginLockout (tabla login_lockouts): guarda
 * los intentos acumulados y el escalón de la progresión, y es lo que
 * permite calcular 3 -> 6 -> 12 -> 15 min tope, y lo que el Admin puede
 * listar/limpiar para desbloquear manualmente.
 */
class LoginLockoutService
{
    /** Intentos fallidos seguidos antes del PRIMER bloqueo. */
    public const INTENTOS_INICIALES = 5;

    /** Intentos fallidos adicionales, tras cada bloqueo, para escalar al siguiente. */
    public const INTENTOS_POR_ESCALON = 3;

    /** Duración del primer bloqueo (segundos). */
    public const SEGUNDOS_BLOQUEO_INICIAL = 180; // 3 min

    /** Tope máximo de bloqueo, sin importar cuánto siga escalando (segundos). */
    public const SEGUNDOS_BLOQUEO_MAXIMO = 900; // 15 min

    /**
     * Llave de RateLimiter para un login+IP dados. Misma fórmula que ya
     * usaba LoginRequest::throttleKey() — se mantiene acá para que
     * LoginRequest y el resto de la app calculen siempre la misma llave.
     */
    public function throttleKey(string $login, string $ip): string
    {
        return Str::transliterate(Str::lower($login).'|'.$ip);
    }

    public function estaBloqueado(string $login, string $ip): bool
    {
        return RateLimiter::tooManyAttempts($this->throttleKey($login, $ip), 1);
    }

    public function segundosRestantes(string $login, string $ip): int
    {
        return max(0, RateLimiter::availableIn($this->throttleKey($login, $ip)));
    }

    /**
     * Registra un intento fallido. Si con este intento se alcanza el
     * umbral del escalón actual, activa (o extiende) el bloqueo en
     * RateLimiter con la duración que corresponda y avanza el escalón.
     */
    public function registrarFallo(string $login, string $ip): void
    {
        $registro = LoginLockout::firstOrCreate(
            ['login' => $this->normalizarLogin($login), 'ip' => $ip],
            ['attempts' => 0, 'lockout_level' => 0]
        );

        $registro->attempts++;

        $umbral = self::INTENTOS_INICIALES + ($registro->lockout_level * self::INTENTOS_POR_ESCALON);

        if ($registro->attempts >= $umbral) {
            $segundos = $this->segundosParaEscalon($registro->lockout_level);

            $registro->lockout_level++;
            $registro->locked_until = now()->addSeconds($segundos);

            RateLimiter::hit($this->throttleKey($login, $ip), $segundos);
        }

        $registro->save();
    }

    /** Duración de bloqueo para el escalón dado (0-based): 3 -> 6 -> 12 -> 15 (tope). */
    public function segundosParaEscalon(int $escalon): int
    {
        return min(
            self::SEGUNDOS_BLOQUEO_INICIAL * (2 ** $escalon),
            self::SEGUNDOS_BLOQUEO_MAXIMO
        );
    }

    /** Login exitoso: se resetea todo (RateLimiter y el contador/escalón). */
    public function registrarExito(string $login, string $ip): void
    {
        RateLimiter::clear($this->throttleKey($login, $ip));

        LoginLockout::where('login', $this->normalizarLogin($login))
            ->where('ip', $ip)
            ->delete();
    }

    /**
     * Desbloqueo MANUAL del Admin: limpia el bloqueo y el contador de un
     * registro puntual (una fila login+IP), sin esperar el tiempo.
     */
    public function desbloquear(LoginLockout $registro): void
    {
        RateLimiter::clear($this->throttleKey($registro->login, $registro->ip));

        $registro->delete();
    }

    private function normalizarLogin(string $login): string
    {
        return mb_strtolower(trim($login));
    }
}
