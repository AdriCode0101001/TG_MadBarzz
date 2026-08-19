<?php

namespace App\Http\Controllers;

use App\Models\Aviso;
use App\Models\AvisoCliente;
use App\Models\CalendarioMusculo;
use App\Models\Cliente;
use App\Models\PushSubscription;
use App\Models\Rutina;
use App\Services\CarnetService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ClientePortalController extends Controller
{
    /**
     * Mi Membresía: estado actual e historial del cliente autenticado.
     */
    public function miMembresia(): View|RedirectResponse
    {
        $cliente = Cliente::with('sede')->where('id_usuario', Auth::id())->first();

        if (!$cliente) {
            return redirect()->route('dashboard')->with('error', 'No tienes un perfil de cliente asociado.');
        }

        $vigente = $cliente->membresias()
            ->wherePivot('fecha_fin', '>=', now())
            ->wherePivot('estado', 'activa')
            ->orderByPivot('fecha_fin', 'desc')
            ->first();

        $historial = $cliente->membresias()
            ->orderByPivot('fecha_fin', 'desc')
            ->get();

        // Pago en 2 Cuotas (Etapa 6): $vigente exige estado='activa', así que
        // una membresía suspendida por saldo (suspendida_saldo) NUNCA cae ahí
        // — sin esto, el cliente vería el mismo "No tienes membresía activa"
        // genérico que alguien que nunca pagó nada. $masReciente (sin el
        // filtro de estado) es la única forma de distinguir ambos casos.
        $masReciente = $cliente->membresiaMasReciente();

        return view('portal.mi-membresia', compact('cliente', 'vigente', 'historial', 'masReciente'));
    }

    /**
     * Mi Rutina: qué músculo toca en la fecha elegida (hoy por defecto) y las
     * rutinas que los entrenadores subieron ese día, para que el cliente
     * elija de cuál seguir. Igual que Mi Membresía: el bloqueo es del
     * SERVIDOR — sin membresía vigente, el calendario y las rutinas ni
     * siquiera se consultan.
     */
    public function miRutina(Request $request): View|RedirectResponse
    {
        $cliente = Cliente::where('id_usuario', Auth::id())->first();

        if (!$cliente) {
            return redirect()->route('dashboard')->with('error', 'No tienes un perfil de cliente asociado.');
        }

        $membresiaVigente = $cliente->tieneMembresiaVigente();

        $hoy = Carbon::now('America/La_Paz')->startOfDay();
        $fecha = $hoy->copy();
        if ($request->filled('fecha')) {
            try {
                $fecha = Carbon::createFromFormat('Y-m-d', $request->query('fecha'))->startOfDay();
            } catch (\Exception) {
                $fecha = $hoy->copy();
            }
        }
        // No se pueden ver fechas futuras: se recorta a hoy.
        if ($fecha->gt($hoy)) {
            $fecha = $hoy->copy();
        }

        $datos = [
            'cliente'            => $cliente,
            'membresiaVigente'   => $membresiaVigente,
            'fecha'              => $fecha,
            'hoy'                => $hoy,
            'esDomingo'          => $fecha->dayOfWeekIso === 7,
            'calendario'         => null,
            'rutinasDelDia'      => collect(),
            'rutinaSeleccionada' => null,
        ];

        if (!$membresiaVigente) {
            return view('portal.mi-rutina', $datos);
        }

        $sedeId = $cliente->id_sede ?? 1;

        $calendario = CalendarioMusculo::with('grupoMuscular')
            ->where('id_sede', $sedeId)
            ->where('fecha', $fecha->toDateString())
            ->first();
        $datos['calendario'] = $calendario;

        if ($calendario) {
            $rutinasDelDia = Rutina::with('usuario')
                ->withCount('ejercicios')
                ->where('id_sede', $sedeId)
                ->where('fecha', $fecha->toDateString())
                ->get();
            $datos['rutinasDelDia'] = $rutinasDelDia;

            $idEntrenador = $request->query('entrenador');
            if ($idEntrenador) {
                $rutinaSeleccionada = $rutinasDelDia->firstWhere('id_usuario', (int) $idEntrenador);
                if ($rutinaSeleccionada) {
                    $rutinaSeleccionada->load('ejercicios.ejercicio');
                    $datos['rutinaSeleccionada'] = $rutinaSeleccionada;
                }
            }
        }

        return view('portal.mi-rutina', $datos);
    }

    /**
     * Descarga el carnet digital del cliente autenticado en PDF.
     * Solo puede descargar SU propio carnet: el cliente se resuelve
     * siempre a partir del usuario autenticado.
     */
    public function descargarCarnet(CarnetService $carnet): Response|RedirectResponse
    {
        $cliente = Cliente::with(['persona', 'sede'])
            ->where('id_usuario', Auth::id())
            ->first();

        if (!$cliente) {
            return back()->with('error', 'Perfil de cliente no encontrado.');
        }

        $membresia = $cliente->membresias()
            ->wherePivot('fecha_fin', '>=', now())
            ->wherePivot('estado', 'activa')
            ->orderByPivot('fecha_fin', 'desc')
            ->first();

        $nombreCompleto = trim(
            ($cliente->persona->nombre_completo ?? '') . ' ' .
            ($cliente->persona->apellido_paterno ?? '') . ' ' .
            ($cliente->persona->apellido_materno ?? '')
        );

        $pdf = Pdf::loadView('dashboard.carnet_pdf', [
            'cliente'        => $cliente,
            'nombreCompleto' => $nombreCompleto,
            'membresia'      => $membresia,
            'qrDataUri'      => $carnet->qrDataUri($cliente),
            'logoDataUri'    => $carnet->logoDataUri(),
        ])->setPaper([0, 0, 320, 500]); // formato carnet vertical

        $slug = \Illuminate\Support\Str::slug($nombreCompleto ?: ('cliente-' . $cliente->id_cliente));

        return $pdf->download("carnet-madbarzz-{$slug}.pdf");
    }

    public function vincularTelegram(Request $request): RedirectResponse
    {
        $request->validate([
            'telegram_chat_id' => 'required|string|max:50',
        ]);

        $cliente = Cliente::where('id_usuario', Auth::id())->first();

        if (!$cliente) {
            return back()->with('error', 'Perfil de cliente no encontrado.');
        }

        DB::table('personas')
            ->where('id_persona', $cliente->id_persona)
            ->update(['telegram_chat_id' => $request->telegram_chat_id]);

        return back()->with('success', 'Telegram vinculado correctamente. Ya recibirás notificaciones.');
    }

    public function desvincularTelegram(): RedirectResponse
    {
        $cliente = Cliente::where('id_usuario', Auth::id())->first();

        if (!$cliente) {
            return back()->with('error', 'Perfil de cliente no encontrado.');
        }

        DB::table('personas')
            ->where('id_persona', $cliente->id_persona)
            ->update(['telegram_chat_id' => null]);

        return back()->with('success', 'Telegram desvinculado.');
    }

    /**
     * Marca TODOS los avisos no leídos del cliente autenticado como leídos
     * (se llama al abrir la campanita, ver layouts/private.blade.php). Crea
     * las filas en aviso_cliente con firstOrCreate — idempotente: llamarlo
     * de nuevo con los mismos avisos ya leídos no duplica nada (y tampoco
     * rompe si, entre medio, llegó un aviso nuevo: ese va a quedar sin leer
     * hasta la próxima apertura).
     */
    public function marcarAvisosLeidos(): JsonResponse
    {
        $cliente = Cliente::where('id_usuario', Auth::id())->first();

        if (!$cliente) {
            return response()->json(['ok' => false], 422);
        }

        $idsNoLeidos = Aviso::whereDoesntHave('lecturas', function ($q) use ($cliente) {
            $q->where('id_cliente', $cliente->id_cliente);
        })->pluck('id');

        foreach ($idsNoLeidos as $idAviso) {
            AvisoCliente::firstOrCreate([
                'id_aviso'   => $idAviso,
                'id_cliente' => $cliente->id_cliente,
            ]);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Guarda (o actualiza) la suscripción Web Push del navegador/dispositivo
     * actual del cliente. Idempotente por "endpoint" (único): si el mismo
     * navegador ya estaba suscripto, se actualiza en vez de duplicar fila
     * — un cliente puede tener varias suscripciones a la vez (celular,
     * tablet...), cada una es su propio endpoint.
     *
     * Etapa 2: subscribeUser() (layouts/private.blade.php) le pega acá con
     * campos planos — el shape nativo del navegador ({endpoint, keys:{p256dh,
     * auth}}) se aplana ahí mismo antes de mandarlo, para no ensuciar esta
     * ruta con la forma anidada de PushSubscription.toJSON().
     */
    public function guardarSuscripcionPush(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => 'required|string|max:500',
            'p256dh'   => 'required|string',
            'auth'     => 'required|string',
        ]);

        $cliente = Cliente::where('id_usuario', Auth::id())->first();

        if (!$cliente) {
            return response()->json(['ok' => false], 422);
        }

        PushSubscription::updateOrCreate(
            ['endpoint' => $data['endpoint']],
            [
                'id_cliente' => $cliente->id_cliente,
                'p256dh'     => $data['p256dh'],
                'auth'       => $data['auth'],
            ]
        );

        return response()->json(['ok' => true]);
    }

    /**
     * Borra la suscripción Web Push de ESE endpoint puntual (el cliente
     * puede tener otras en otros dispositivos, que quedan intactas).
     */
    public function eliminarSuscripcionPush(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => 'required|string|max:500',
        ]);

        $cliente = Cliente::where('id_usuario', Auth::id())->first();

        if (!$cliente) {
            return response()->json(['ok' => false], 422);
        }

        PushSubscription::where('id_cliente', $cliente->id_cliente)
            ->where('endpoint', $data['endpoint'])
            ->delete();

        return response()->json(['ok' => true]);
    }
}
