<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Huella;
use App\Services\AsistenciaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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
            ->with([
                'persona:id_persona,nombre_completo,apellido_paterno,apellido_materno',
                'huellas:id,id_cliente,dedo',
            ])
            ->get(['id_cliente', 'id_persona'])
            ->map(fn ($c) => [
                'id_cliente'      => $c->id_cliente,
                'nombre_completo' => trim(
                    $c->persona->nombre_completo . ' ' .
                    $c->persona->apellido_paterno . ' ' .
                    $c->persona->apellido_materno
                ),
                // Dedos ya enrolados (códigos). El agente lo usa para separar
                // clientes PENDIENTES vs YA ENROLADOS y saber qué dedos faltan.
                'dedos_enrolados'  => $c->huellas->pluck('dedo')->values(),
                // Membresía vigente según el criterio central (vence hoy = vigente).
                // NO filtra aquí: el agente decide las secciones. Los clientes sin
                // membresía activa igual deben poder aparecer en "Ya enrolados".
                'membresia_activa' => $c->tieneMembresiaVigente(),
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

        // ── Anti-duplicado (defensa en profundidad) ───────────────────────────
        // El matching biométrico difuso (dos capturas del mismo dedo NUNCA son
        // idénticas byte a byte) solo puede hacerlo el agente con el SDK ZKFinger,
        // que ejecuta una identificación 1:N ANTES de enviar. Aquí, en el servidor
        // (sin SDK), aplicamos una comprobación exacta: si ESTA MISMA plantilla ya
        // pertenece a OTRO cliente, se rechaza. Esto cubre peticiones manipuladas o
        // reenviadas que copian el template de un tercero. NO detecta dos escaneos
        // distintos del mismo dedo (para eso está el chequeo biométrico del agente).
        $perteneceAOtro = Huella::where('template', $data['template'])
            ->where('id_cliente', '!=', $data['id_cliente'])
            ->exists();

        if ($perteneceAOtro) {
            return response()->json([
                'message' => 'Esta huella ya está registrada para otro cliente.',
            ], 409);
        }

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
     * Las reglas de negocio viven en AsistenciaService (compartidas con el registro por QR).
     */
    public function registrarAsistencia(Request $request, AsistenciaService $servicio): JsonResponse
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

        // Validar que el cliente exista y esté activo
        $cliente = Cliente::where('id_cliente', $data['id_cliente'])
                          ->where('estado', 'activo')
                          ->first();

        if (!$cliente) {
            return response()->json(['message' => 'Cliente no encontrado o inactivo.'], 404);
        }

        $resultado = $servicio->registrarEntrada(
            $cliente,
            (int) $agentToken->id_sede,
            Carbon::parse($data['fecha_hora'])
        );

        return match ($resultado['motivo']) {
            AsistenciaService::OK => response()->json([
                'message'       => $resultado['mensaje'],
                'id_asistencia' => $resultado['asistencia']->id_asistencia,
            ], 201),

            AsistenciaService::SEDE_NO_AUTORIZADA => response()->json([
                'message' => $resultado['mensaje'],
            ], 403),

            AsistenciaService::DUPLICADO => response()->json([
                'message'   => $resultado['mensaje'],
                'duplicado' => true,
            ], 409),

            AsistenciaService::YA_MARCO_HOY => response()->json([
                'message'   => $resultado['mensaje'],
                'motivo'    => AsistenciaService::YA_MARCO_HOY,
                'duplicado' => true,
            ], 409),

            // sin_membresia / membresia_vencida
            default => response()->json([
                'permitido' => false,
                'motivo'    => $resultado['motivo'],
                'mensaje'   => $resultado['mensaje'],
                'message'   => $resultado['mensaje'],
            ], 403),
        };
    }
}
