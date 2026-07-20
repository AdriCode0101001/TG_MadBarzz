<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Services\AsistenciaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Registro de asistencia escaneando el QR del carnet del cliente.
 * Accesible solo para Administrador, Recepcionista y Entrenador (ver rutas).
 */
class AsistenciaQrController extends Controller
{
    public function index(): View
    {
        return view('asistencias.qr');
    }

    /**
     * Recibe el qr_token leído por la cámara y registra la asistencia
     * aplicando las mismas reglas de negocio que el agente biométrico.
     */
    public function registrar(Request $request, AsistenciaService $servicio): JsonResponse
    {
        $request->validate([
            'qr_token' => ['required', 'string', 'max:100'],
        ]);

        $cliente = Cliente::with(['persona', 'user'])
            ->where('qr_token', $request->qr_token)
            ->first();

        if (!$cliente) {
            return response()->json([
                'estado'  => 'no_valido',
                'mensaje' => 'Carnet no válido.',
            ]);
        }

        // La asistencia se registra en la sede del operador logueado;
        // si su usuario no tiene sede asignada, se usa la sede del cliente.
        $idSede = Auth::user()->id_sede ?? $cliente->id_sede;

        if (!$idSede) {
            return response()->json([
                'estado'  => 'error',
                'mensaje' => 'Tu usuario no tiene una sede asignada. Pide al administrador que te asigne una.',
            ]);
        }

        $resultado = $servicio->registrarEntrada($cliente, (int) $idSede);

        $estado = match ($resultado['motivo']) {
            AsistenciaService::OK                       => 'ok',
            AsistenciaService::SIN_MEMBRESIA             => 'sin_membresia',
            AsistenciaService::MEMBRESIA_VENCIDA         => 'vencida',
            AsistenciaService::MEMBRESIA_PENDIENTE_PAGO  => 'pendiente_pago', // rojo: falta registrar el pago
            AsistenciaService::DUPLICADO,
            AsistenciaService::YA_MARCO_HOY              => 'duplicado', // ámbar en pantalla
            default                                      => 'rechazado', // cliente inactivo / sede no autorizada
        };

        $nombre = trim(
            ($cliente->persona->nombre_completo ?? '') . ' ' .
            ($cliente->persona->apellido_paterno ?? '') . ' ' .
            ($cliente->persona->apellido_materno ?? '')
        ) ?: ('Cliente N° ' . $cliente->id_cliente);

        $iniciales = mb_strtoupper(
            mb_substr($cliente->persona->nombre_completo ?? '', 0, 1) .
            mb_substr($cliente->persona->apellido_paterno ?? '', 0, 1)
        ) ?: 'C';

        // Hora local de Bolivia (config('app.timezone')). Si hay un evento real
        // (registrada ahora, o "ya marcó hoy"/"duplicado" con su marca previa)
        // se informa SU fecha/hora; si no hubo marca (rechazo) no aplica.
        $momento = $resultado['asistencia']
            ? Carbon::parse($resultado['asistencia']->fecha_hora_entrada)
            : null;

        return response()->json([
            'estado'  => $estado,
            'mensaje' => $resultado['motivo'] === AsistenciaService::DUPLICADO
                ? 'Ya registró asistencia hace un momento.'
                : $resultado['mensaje'],
            'cliente' => [
                'nombre'    => $nombre,
                'iniciales' => $iniciales,
                'foto'      => $cliente->user?->foto ? asset($cliente->user->foto) : null,
            ],
            'fecha'   => $momento?->format('d/m/Y'),
            'hora'    => $momento?->format('H:i') ?? now()->format('H:i'),
        ]);
    }
}
