<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asistencia;
use App\Models\Cliente;
use App\Models\Huella;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BiometriaController extends Controller
{
    /**
     * GET /api/agente/clientes
     * Lista clientes activos con id y nombre completo para el agente.
     */
    public function listarClientes(): JsonResponse
    {
        $clientes = Cliente::where('estado', 'activo')
            ->with('persona:id_persona,nombre_completo,apellido_paterno,apellido_materno')
            ->get(['id_cliente', 'id_persona'])
            ->map(fn ($c) => [
                'id_cliente'      => $c->id_cliente,
                'nombre_completo' => trim(
                    $c->persona->nombre_completo . ' ' .
                    $c->persona->apellido_paterno . ' ' .
                    $c->persona->apellido_materno
                ),
            ]);

        return response()->json(['data' => $clientes]);
    }

    /**
     * POST /api/agente/enrolar
     * Guarda (o actualiza) la plantilla biométrica de un dedo.
     */
    public function enrolar(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id_cliente'      => ['required', 'integer', 'exists:clientes,id_cliente'],
            'dedo'            => ['required', 'string', 'max:50'],
            'template'        => ['required', 'string'],   // base64
            'version_formato' => ['nullable', 'string', 'max:50'],
        ]);

        $huella = Huella::updateOrCreate(
            ['id_cliente' => $data['id_cliente'], 'dedo' => $data['dedo']],
            ['template' => $data['template'], 'version_formato' => $data['version_formato'] ?? null]
        );

        return response()->json([
            'message' => 'Huella enrolada correctamente.',
            'id'      => $huella->id,
        ], 201);
    }

    /**
     * GET /api/agente/sincronizar
     * Devuelve las plantillas que la sede del agente debe reconocer.
     *
     * Política de acceso:
     *   - Si el cliente NO tiene filas en cliente_sede → acceso libre a cualquier sede.
     *   - Si tiene filas → solo accede a las sedes listadas.
     *
     * Se devuelven solo los clientes activos que tengan al menos una huella.
     */
    public function sincronizar(Request $request): JsonResponse
    {
        /** @var \App\Models\AgentToken $agentToken */
        $agentToken = $request->attributes->get('agentToken');
        $idSede     = $agentToken->id_sede;

        $clientes = Cliente::query()
            ->where('estado', 'activo')
            ->where(function ($q) use ($idSede) {
                // acceso libre (sin restricciones) O restringido a esta sede
                $q->whereNotExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('cliente_sede')
                        ->whereColumn('cliente_sede.id_cliente', 'clientes.id_cliente');
                })->orWhereExists(function ($sub) use ($idSede) {
                    $sub->select(DB::raw(1))
                        ->from('cliente_sede')
                        ->whereColumn('cliente_sede.id_cliente', 'clientes.id_cliente')
                        ->where('cliente_sede.id_sede', $idSede);
                });
            })
            ->with([
                'persona:id_persona,nombre_completo,apellido_paterno,apellido_materno',
                'huellas:id,id_cliente,dedo,template,version_formato',
                'membresiaActual',
            ])
            ->get();

        $payload = $clientes->filter(fn($c) => $c->huellas->isNotEmpty())
            ->map(function ($c) {
                $membresia = $c->membresiaActual;
                return [
                    'id_cliente'       => $c->id_cliente,
                    'nombre_completo'  => trim(
                        $c->persona->nombre_completo . ' ' .
                        $c->persona->apellido_paterno . ' ' .
                        $c->persona->apellido_materno
                    ),
                    'estado_membresia' => $membresia
                        ? ($membresia->fecha_fin >= now()->toDateString() ? 'al_dia' : 'vencida')
                        : 'sin_membresia',
                    'huellas'          => $c->huellas->map(fn($h) => [
                        'id'              => $h->id,
                        'dedo'            => $h->dedo,
                        'template'        => $h->template,
                        'version_formato' => $h->version_formato,
                    ]),
                ];
            })->values();

        return response()->json(['data' => $payload]);
    }

    /**
     * POST /api/agente/asistencia
     * Registra la entrada de un cliente, con validación de acceso y anti-duplicado.
     */
    public function registrarAsistencia(Request $request): JsonResponse
    {
        /** @var \App\Models\AgentToken $agentToken */
        $agentToken = $request->attributes->get('agentToken');

        $data = $request->validate([
            'id_cliente' => ['required', 'integer', 'exists:clientes,id_cliente'],
            'id_sede'    => ['required', 'integer'],
            'fecha_hora' => ['required', 'date'],
        ]);

        // El id_sede del body debe coincidir con el sede del token (seguridad)
        if ((int) $data['id_sede'] !== (int) $agentToken->id_sede) {
            return response()->json([
                'message' => 'El id_sede no coincide con la sede autorizada por el token.',
            ], 403);
        }

        $idCliente = $data['id_cliente'];
        $idSede    = $agentToken->id_sede;

        // Validar que el cliente exista y esté activo
        $cliente = Cliente::where('id_cliente', $idCliente)
                          ->where('estado', 'activo')
                          ->first();

        if (!$cliente) {
            return response()->json(['message' => 'Cliente no encontrado o inactivo.'], 404);
        }

        // Validar política de acceso por sede
        $tieneRestricciones = DB::table('cliente_sede')
            ->where('id_cliente', $idCliente)
            ->exists();

        if ($tieneRestricciones) {
            $tieneAcceso = DB::table('cliente_sede')
                ->where('id_cliente', $idCliente)
                ->where('id_sede', $idSede)
                ->exists();

            if (!$tieneAcceso) {
                return response()->json([
                    'message' => 'El cliente no tiene acceso a esta sede.',
                ], 403);
            }
        }

        // Anti-duplicado
        $ventana = config('biometria.ventana_duplicado_minutos', 5);
        $desde   = now()->subMinutes($ventana);

        $yaExiste = Asistencia::where('id_cliente', $idCliente)
            ->where('id_sede', $idSede)
            ->where('fecha_hora_entrada', '>=', $desde)
            ->exists();

        if ($yaExiste) {
            return response()->json([
                'message' => "El cliente ya registró asistencia en los últimos {$ventana} minutos.",
                'duplicado' => true,
            ], 409);
        }

        $asistencia = Asistencia::create([
            'id_cliente'         => $idCliente,
            'id_sede'            => $idSede,
            'fecha_hora_entrada' => $data['fecha_hora'],
        ]);

        return response()->json([
            'message'       => 'Asistencia registrada.',
            'id_asistencia' => $asistencia->id_asistencia,
        ], 201);
    }
}
