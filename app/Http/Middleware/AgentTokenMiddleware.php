<?php

namespace App\Http\Middleware;

use App\Models\AgentToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AgentTokenMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $header = $request->header('Authorization', '');

        if (!str_starts_with($header, 'Bearer ')) {
            return response()->json(['message' => 'Token de agente requerido.'], 401);
        }

        $plain = substr($header, 7);
        $hash  = hash('sha256', $plain);

        $agentToken = AgentToken::where('token', $hash)
                                ->where('activo', true)
                                ->with('sede')
                                ->first();

        if (!$agentToken) {
            return response()->json(['message' => 'Token de agente inválido o inactivo.'], 401);
        }

        $agentToken->update(['ultimo_uso' => now()]);

        $request->attributes->set('agentToken', $agentToken);

        return $next($request);
    }
}
