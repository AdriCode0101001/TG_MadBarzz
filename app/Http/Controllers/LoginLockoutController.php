<?php

namespace App\Http\Controllers;

use App\Models\LoginLockout;
use App\Services\LoginLockoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Bitácora de bloqueos de login (Seguridad): SOLO Administrador (protegido
 * por ruta con role:Administrador, no solo por el menú — ver routes/web.php).
 * Permite ver quién está bloqueado ahora mismo y desbloquearlo sin esperar
 * el tiempo. El bloqueo real lo maneja RateLimiter (ver LoginLockoutService);
 * esta pantalla solo lee/limpia login_lockouts, que es la memoria de esa
 * lógica.
 */
class LoginLockoutController extends Controller
{
    public function index(): View
    {
        $registros = LoginLockout::orderByDesc('locked_until')
            ->orderByDesc('updated_at')
            ->paginate(20);

        return view('login_lockouts.index', compact('registros'));
    }

    public function destroy(LoginLockout $login_lockout, LoginLockoutService $lockout): RedirectResponse
    {
        $login = $login_lockout->login;

        $lockout->desbloquear($login_lockout);

        return redirect()->route('login_lockouts.index')
            ->with('success', "Se desbloqueó a \"{$login}\" — ya puede volver a intentar iniciar sesión.");
    }
}
