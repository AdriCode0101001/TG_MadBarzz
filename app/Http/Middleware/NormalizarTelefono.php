<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Arma y normaliza el campo `telefono` en el servidor, SIN depender de
 * JavaScript. El componente <x-phone-input> envía dos campos con name propio
 * (telefono_codigo y telefono_numero); aquí se combinan en `telefono`, que es
 * lo que validan y guardan todos los controladores.
 *
 * Robustez extra: si solo llega `telefono` con un número boliviano pelado de
 * 8 dígitos (empieza en 6 o 7), se le antepone +591.
 */
class NormalizarTelefono
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->has('telefono_numero')) {
            $numero = $this->limpiar($request->input('telefono_numero'));
            $codigo = trim((string) $request->input('telefono_codigo', '+591'));

            $telefono = match (true) {
                $numero === ''                  => null,
                str_starts_with($numero, '+')   => $numero, // ya viene internacional completo
                default                         => $codigo . $numero,
            };

            $request->merge(['telefono' => $telefono]);
        } elseif ($request->filled('telefono')) {
            $telefono = $this->limpiar($request->input('telefono'));

            if (!str_starts_with($telefono, '+') && preg_match('/^[67]\d{7}$/', $telefono)) {
                $telefono = '+591' . $telefono;
            }

            $request->merge(['telefono' => $telefono]);
        }

        return $next($request);
    }

    private function limpiar(?string $valor): string
    {
        return preg_replace('/[\s\-().]/', '', (string) $valor);
    }
}
