<?php

use App\Http\Middleware\AgentTokenMiddleware;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\NormalizarTelefono;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Alias para controlar acceso por rol
        $middleware->alias([
            'role'        => EnsureRole::class,
            'agent.token' => AgentTokenMiddleware::class,
        ]);

        // Arma `telefono` desde los campos del selector de país en el servidor
        // (los formularios de personas/clientes/personal ya no dependen de JS)
        $middleware->web(append: [
            NormalizarTelefono::class,
        ]);

        // Confía en el proxy local (túneles como Cloudflare/ngrok, o un
        // reverse proxy en producción) para que Laravel respete el header
        // X-Forwarded-Proto y genere URLs https:// en asset()/route()/etc.
        // Sin esto, detrás de un túnel HTTPS -> artisan serve HTTP, la app
        // sigue generando enlaces http:// y el navegador los bloquea como
        // contenido mixto: CSS/JS no cargan y la página se ve rota.
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->booted(function () {
        // Rate-limit: máximo 5 intentos de login por minuto por IP
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        // Etapa 4 del auto-registro: máximo 5 registros por minuto por IP.
        // El registro crea 3 filas en BD (persona+user+cliente) y dispara un
        // correo de verificación — más costoso que un intento de login
        // fallido, así que amerita el mismo límite (no uno más laxo) para
        // frenar altas masivas automatizadas. Mismo valor que 'login' para
        // no inventar un número nuevo sin motivo.
        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });
    })
    ->create();
