<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asistencia;
use App\Models\Cliente;
use App\Models\MembresiaCliente;
use App\Models\Pago;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClienteApiController extends Controller
{
    private function resolveCliente(Request $request): Cliente|JsonResponse
    {
        if (!$request->user()->hasRole('Cliente')) {
            return response()->json(['message' => 'Acceso denegado.'], 403);
        }

        $cliente = Cliente::with('persona')
            ->where('id_usuario', $request->user()->id)
            ->first();

        if (!$cliente) {
            return response()->json(['message' => 'Perfil de cliente no encontrado.'], 404);
        }

        return $cliente;
    }

    public function perfil(Request $request): JsonResponse
    {
        $result = $this->resolveCliente($request);

        if ($result instanceof JsonResponse) {
            return $result;
        }

        return response()->json([
            'nombre'         => $result->persona->nombre_completo ?? $request->user()->name,
            'email'          => $request->user()->email,
            'dias_asistidos' => $result->dias_asistidos,
            'estado'         => $result->estado,
            'telegram'       => $result->persona->telegram_chat_id ? 'vinculado' : 'no vinculado',
        ]);
    }

    public function membresia(Request $request): JsonResponse
    {
        $result = $this->resolveCliente($request);

        if ($result instanceof JsonResponse) {
            return $result;
        }

        $mc = MembresiaCliente::with('membresia')
            ->where('id_cliente', $result->id_cliente)
            ->where('fecha_fin', '>=', now())
            ->where('estado', 'activa')
            ->orderBy('fecha_fin', 'desc')
            ->first();

        if (!$mc) {
            return response()->json(['membresia' => null]);
        }

        return response()->json([
            'membresia' => [
                'tipo'             => $mc->membresia->tipo_membresia ?? null,
                'precio_base'      => $mc->membresia->precio ?? null,
                'precio_final'     => $mc->precio_final,
                'descuento'        => $mc->descuento,
                'nombre_descuento' => $mc->nombre_descuento,
                'fecha_inicio'     => $mc->fecha_inicio,
                'fecha_fin'        => $mc->fecha_fin,
            ],
        ]);
    }

    public function pagos(Request $request): JsonResponse
    {
        $result = $this->resolveCliente($request);

        if ($result instanceof JsonResponse) {
            return $result;
        }

        $pagos = Pago::where('id_cliente', $result->id_cliente)
            ->orderBy('fecha_pago', 'desc')
            ->get(['id_pago', 'monto', 'fecha_pago', 'metodo_pago', 'observaciones']);

        return response()->json(['pagos' => $pagos]);
    }

    public function asistencias(Request $request): JsonResponse
    {
        $result = $this->resolveCliente($request);

        if ($result instanceof JsonResponse) {
            return $result;
        }

        $asistencias = Asistencia::where('id_cliente', $result->id_cliente)
            ->orderBy('fecha_hora_entrada', 'desc')
            ->get(['id_asistencia', 'fecha_hora_entrada']);

        return response()->json(['asistencias' => $asistencias]);
    }
}
