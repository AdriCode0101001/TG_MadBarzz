<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Sede;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view (pantalla combinada login + registro, con la
     * pestaña "Iniciar sesión" activa — el registro necesita las sedes
     * activas aunque el usuario no haya abierto esa pestaña todavía).
     */
    public function create(): View
    {
        return view('auth.login-register', [
            'tabActiva' => 'login',
            'sedes'     => Sede::where('activo', true)->orderBy('nombre')->get(),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        if (Auth::user()->hasRole('Cliente')) {
            return redirect()->route('dashboard.cliente');
        }

        return redirect()->route('dashboard');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
