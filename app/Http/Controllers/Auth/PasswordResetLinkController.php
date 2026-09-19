<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * Hallazgo 6.7 de la auditoría de seguridad: antes esta respuesta
     * distinguía "te enviamos un enlace" de "no encontramos ese usuario"
     * (passwords.sent vs passwords.user), lo que permitía enumerar qué
     * emails están registrados probando uno por uno. Ahora la respuesta es
     * SIEMPRE la misma, exista o no el usuario — Password::sendResetLink()
     * igual solo manda el correo real si el usuario existe, esto solo
     * cambia qué ve quien pregunta.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        Password::sendResetLink($request->only('email'));

        return back()->with(
            'status',
            'Si existe una cuenta con ese correo, te enviamos un enlace para restablecer la contraseña.'
        );
    }
}
