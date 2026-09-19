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
        // Límite POR IP en /login: es un respaldo contra un diluvio de
        // requests (o alguien probando muchos emails distintos desde la
        // misma IP) — NO es la defensa principal contra fuerza bruta a una
        // cuenta puntual, de eso se encarga el bloqueo progresivo por
        // login+IP (LoginLockoutService, en LoginRequest), que escala
        // 3->6->12->15 min.
        //
        // Antes este límite era 5/min, la MISMA cifra que el umbral del
        // bloqueo progresivo. En un gimnasio, varias PCs/celulares suelen
        // compartir la IP pública del mismo router: ese 5/min sumaba los
        // intentos de TODO el personal (sin distinguir usuario), así que
        // alcanzaba con que 2-3 personas fallaran una vez cada una para
        // trabar a los demás con un 429 genérico, antes de que el bloqueo
        // progresivo (que sí distingue por usuario) llegara a actuar.
        // Subirlo a 30/min le da lugar de sobra a varias personas
        // legítimas de la misma conexión sin abrir la puerta a un ataque
        // real: un script insistiendo igual pega contra este techo, y
        // cualquier intento dirigido a UNA cuenta puntual lo frena el
        // bloqueo progresivo mucho antes (a los 5 intentos a esa cuenta).
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(30)->by($request->ip());
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

        // Hallazgo 6.7 de la auditoría de seguridad: POST /forgot-password
        // no tenía NINGÚN límite de ruta (a diferencia de login/register),
        // así que alguien podía iterar muchos emails distintos sin freno
        // (además de disparar el envío real de correos). Mismo criterio que
        // 'register': 5/min por IP.
        RateLimiter::for('password-email', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });
    })
    ->create();
