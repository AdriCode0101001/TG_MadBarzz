<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LoginLockoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthApiController extends Controller
{
    /**
     * Login de la app móvil. Comparte el MISMO bloqueo progresivo por
     * login+IP que el login web (LoginRequest/LoginLockoutService,
     * hallazgo 6.1 de la auditoría): antes este endpoint no tenía ningún
     * freno, así que un atacante podía hacer fuerza bruta ilimitada acá
     * saltándose el bloqueo del navegador. Al compartir la llave, 5 fallos
     * combinados entre ambos canales ya bloquean los dos, y el
     * desbloqueo manual del Admin (pantalla "Bloqueos de Login") libera
     * también este login.
     */
    public function login(Request $request, LoginLockoutService $lockout): JsonResponse
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $login = $request->string('email')->toString();
        $ip    = $request->ip();

        if ($lockout->estaBloqueado($login, $ip)) {
            $segundos = $lockout->segundosRestantes($login, $ip);

            return response()->json([
                'message'             => trans('auth.throttle', [
                    'seconds' => $segundos,
                    'minutes' => (int) ceil($segundos / 60),
                ]),
                'retry_after_seconds' => $segundos,
            ], 429)->header('Retry-After', (string) $segundos);
        }

        if (!Auth::attempt(['email' => $login, 'password' => $request->string('password')->toString()])) {
            $lockout->registrarFallo($login, $ip);

            return response()->json(['message' => 'Credenciales incorrectas.'], 401);
        }

        $lockout->registrarExito($login, $ip);

        $user  = Auth::user();
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'rol'   => $user->rol->nombre ?? null,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sesión cerrada.']);
    }
}
