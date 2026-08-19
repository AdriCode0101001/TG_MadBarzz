<?php

namespace App\Http\Controllers;

use App\Models\Descuento;
use App\Models\MembresiaCliente;
use App\Models\Pago;
use App\Models\Cliente;
use App\Models\QrPago;
use App\Models\Scopes\SedeScope;
use App\Services\MembresiaService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use App\Models\Membresia;

class PagoController extends Controller
{
public function index(Request $request)
{
    $buscar = $request->input('buscar');

    $pagos = Pago::with(['cliente.persona', 'membresia', 'membresiaCliente'])
        ->when($buscar, function($query, $buscar) {

            // Buscar por cliente o membresía
            $query->whereHas('cliente.persona', function($q) use ($buscar) {
                $q->where('nombre_completo', 'like', "%{$buscar}%")
                  ->orWhere('apellido_paterno', 'like', "%{$buscar}%")
                  ->orWhere('apellido_materno', 'like', "%{$buscar}%");
            })
            ->orWhereHas('membresia', function($q) use ($buscar) {
                $q->where('tipo_membresia', 'like', "%{$buscar}%");
            });

            // Buscar por fechas flexibles
            if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $buscar, $matches)) {
                // Formato dd/mm/yyyy
                $fecha = "{$matches[3]}-{$matches[2]}-{$matches[1]}";
                $query->orWhereDate('fecha_pago', $fecha);
            } elseif (preg_match('/^(\d{2})\/(\d{2})$/', $buscar, $matches)) {
                // Formato dd/mm (ignora año)
                $dia = $matches[1];
                $mes = $matches[2];
                $query->orWhereRaw('DAY(fecha_pago) = ? AND MONTH(fecha_pago) = ?', [$dia, $mes]);
            } elseif (preg_match('/^(\d{2})\/(\d{4})$/', $buscar, $matches)) {
                // Formato mm/yyyy
                $mes = $matches[1];
                $anio = $matches[2];
                $query->orWhereRaw('MONTH(fecha_pago) = ? AND YEAR(fecha_pago) = ?', [$mes, $anio]);
            } elseif (preg_match('/^\d{4}$/', $buscar, $matches)) {
                // Solo año
                $anio = $buscar;
                $query->orWhereYear('fecha_pago', $anio);
            }
        })
        ->latest()
        ->get();

    return view('pagos.index', compact('pagos', 'buscar'));
}




    public function create(MembresiaService $servicio)
    {
        $clientes = Cliente::with('persona')->get();

        // Cada membresía lleva su fecha de fin prevista (si se registra hoy),
        // calculada por el MISMO servicio que usará el guardado.
        $membresias = Membresia::all()->each(function ($m) use ($servicio) {
            $m->fecha_fin_prevista = $servicio->calcularFechas($m)['fecha_fin'];
        });

        $descuentos = Descuento::activos()->orderBy('porcentaje')->get();

        // QR de pago único del gimnasio: solo se ofrece para mostrar si está
        // vigente (configurado y no vencido). Si no, se avisa al operador.
        $qrPago    = QrPago::actual();
        $qrVigente = $qrPago && $qrPago->estaVigente();

        // Aviso de ACUMULACIÓN para el operador: por cada cliente con membresía
        // vigente, hasta qué fecha llega y qué fin tendría la nueva (por tipo)
        // si se acumula. Es solo informativo; el servidor recalcula al guardar.
        $acumulacion = $this->infoAcumulacion($clientes, $membresias, $servicio);

        return view('pagos.create', compact('clientes', 'membresias', 'descuentos', 'qrVigente', 'acumulacion'));
    }

    /**
     * Mapa clienteId => ['hasta' => 'd/m/Y', 'nuevoFin' => [membresiaId => 'd/m/Y']]
     * SOLO para los clientes con membresía vigente. Usa la misma definición de
     * vigencia que Cliente::tieneMembresiaVigente (la más reciente por fecha_fin,
     * activa y no vencida), resuelta en una sola consulta sin SedeScope.
     */
    private function infoAcumulacion($clientes, $membresias, MembresiaService $servicio): array
    {
        $hoy = now()->toDateString();

        $recientes = MembresiaCliente::withoutGlobalScope(SedeScope::class)
            ->whereIn('id_cliente', $clientes->pluck('id_cliente'))
            ->orderByDesc('fecha_fin')
            ->get(['id_cliente', 'fecha_fin', 'estado'])
            ->groupBy('id_cliente')
            ->map(fn ($grupo) => $grupo->first());

        $info = [];

        foreach ($recientes as $idCliente => $mc) {
            $vigente = $mc->estado === MembresiaCliente::ESTADO_ACTIVA
                && Carbon::parse($mc->fecha_fin)->toDateString() >= $hoy;

            if (! $vigente) {
                continue;
            }

            $fin = Carbon::parse($mc->fecha_fin);

            $nuevoFin = [];
            foreach ($membresias as $m) {
                $nuevoFin[$m->id_membresia] = Carbon::parse(
                    $servicio->calcularFechasAcumuladas($m, $fin)['fecha_fin']
                )->format('d/m/Y');
            }

            $info[$idCliente] = [
                'hasta'    => $fin->format('d/m/Y'),
                'nuevoFin' => $nuevoFin,
            ];
        }

        return $info;
    }

    /**
     * Registro en UN SOLO PASO: se elige la membresía (tipo + descuento) y se
     * registra el pago a la vez. Al guardar, en una transacción, se crea la
     * asignación (membresia_cliente) y su pago vinculado, y la membresía
     * queda ACTIVA de inmediato (ya está pagada). "Sin pago no hay membresía
     * activa": por eso nace pendiente_pago y solo se activa tras crear el pago.
     *
     * Pago en 2 Cuotas (Etapa 2): "monto_pagado" es un campo NUEVO y opcional.
     * Si no se manda (o llega igual al precio final), es EXACTAMENTE el pago
     * de siempre — ni un byte de diferencia. Solo si llega MENOR al precio
     * final se toma el camino de pago parcial (activa igual, pero con
     * saldo_pendiente + fecha_limite_saldo guardados). A propósito NO se
     * reusa el nombre de campo "monto": ya existe un test que manda
     * monto=1 esperando que se IGNORE (como pasa siempre, porque el monto
     * jamás vino del cliente), y debía seguir ignorándose.
     */
    public function store(Request $request, MembresiaService $servicio)
    {
        $request->validate([
            'id_cliente'    => 'required|exists:clientes,id_cliente',
            'id_membresia'  => 'required|exists:membresias,id_membresia',
            'id_descuento'  => [
                'required',
                Rule::exists('descuentos', 'id')->where('activo', true)->whereNull('deleted_at'),
            ],
            // Solo Efectivo y QR (transferencia se retiró de las opciones nuevas)
            'metodo_pago'   => 'required|in:efectivo,QR',
            'observaciones' => 'nullable|string',
            // Un pago por QR EXIGE su comprobante; en efectivo es opcional. Se
            // valida en el servidor (fuente de verdad), no solo en el frontend.
            'imagen_qr'     => 'nullable|required_if:metodo_pago,QR|image|mimes:jpeg,png,jpg,webp|max:2048',
        ], [
            'imagen_qr.required_if' => 'Debes subir el comprobante para un pago por QR.',
        ]);

        $membresia = Membresia::findOrFail($request->id_membresia);
        $descuento = Descuento::activos()->findOrFail($request->id_descuento);
        // Sin SedeScope: la vigencia de la membresía es del cliente sin importar
        // en qué sede se registre el pago (mismo criterio que membresiaMasReciente).
        $cliente   = Cliente::withoutGlobalScope(SedeScope::class)->findOrFail($request->id_cliente);

        // Fechas y precio SIEMPRE calculados en el servidor (igual que en la
        // asignación de membresías): se ignora cualquier valor manipulado.
        // Si el cliente ya tiene una membresía vigente, la nueva se ACUMULA a
        // partir del fin de aquella (sin día de regalo); si no, arranca hoy con
        // regalo. La decisión es del servidor, no del formulario.
        $fechas      = $servicio->calcularFechasSegunCliente($membresia, $cliente);
        $precioFinal = $servicio->precioFinal($membresia, $descuento);

        // Sin monto_pagado en el envío: es el pago completo de siempre. Si
        // llega, el saldo SIEMPRE se calcula acá (precioFinal - montoPagado),
        // nunca se confía en un saldo mandado por el cliente.
        $montoPagado   = $request->filled('monto_pagado')
            ? round((float) $request->monto_pagado, 2)
            : $precioFinal;
        $esPagoParcial = $montoPagado < $precioFinal;

        // Límite razonable para la fecha del saldo: 3 meses. Cubre la duración
        // típica de las membresías (hasta trimestral) sin dejar fechas
        // absurdamente lejanas por error de tipeo.
        $limiteFechaSaldo = now()->addMonths(3)->toDateString();

        $request->validate(array_merge(
            [
                'monto_pagado' => ['nullable', 'numeric', function ($attribute, $value, $fail) use ($precioFinal) {
                    if ((float) $value <= 0) {
                        $fail('El monto pagado debe ser mayor a 0.');
                    } elseif ((float) $value > $precioFinal) {
                        $fail('El monto pagado no puede superar el precio de la membresía (Bs ' . number_format($precioFinal, 2) . ').');
                    }
                }],
            ],
            $esPagoParcial ? [
                'fecha_limite_saldo' => ['required', 'date', 'after:today', "before_or_equal:{$limiteFechaSaldo}"],
            ] : []
        ), [
            'fecha_limite_saldo.required'       => 'La fecha límite del saldo es obligatoria en un pago parcial.',
            'fecha_limite_saldo.after'          => 'La fecha límite del saldo debe ser posterior a hoy.',
            'fecha_limite_saldo.before_or_equal' => 'La fecha límite del saldo no puede superar los 3 meses.',
        ]);

        $rutaImagen = $request->hasFile('imagen_qr')
            ? $request->file('imagen_qr')->store('qrs', 'privado')
            : null;

        DB::transaction(function () use ($request, $membresia, $descuento, $fechas, $precioFinal, $rutaImagen, $montoPagado, $esPagoParcial) {
            $membresiaCliente = MembresiaCliente::create([
                'id_cliente'          => $request->id_cliente,
                'id_membresia'        => $membresia->id_membresia,
                'fecha_inicio'        => $fechas['fecha_inicio'],
                'fecha_fin'           => $fechas['fecha_fin'],
                'id_descuento'        => $descuento->id,
                'nombre_descuento'    => $descuento->nombre,
                'descuento'           => $descuento->porcentaje,
                'precio_final'        => $precioFinal,
                'estado'              => MembresiaCliente::ESTADO_PENDIENTE,
                'saldo_pendiente'     => $esPagoParcial ? round($precioFinal - $montoPagado, 2) : null,
                'fecha_limite_saldo'  => $esPagoParcial ? $request->fecha_limite_saldo : null,
            ]);

            Pago::create([
                'id_cliente'           => $request->id_cliente,
                'id_membresia'         => $membresia->id_membresia,
                'id_membresia_cliente' => $membresiaCliente->id,
                'monto'                => $montoPagado,
                'fecha_pago'           => now()->toDateString(), // SIEMPRE hoy
                'metodo_pago'          => $request->metodo_pago,
                'observaciones'        => $request->observaciones,
                'imagen_qr'            => $rutaImagen,
            ]);

            // El pago recién creado activa la membresía de inmediato — con
            // saldo pendiente o sin él, entra con huella igual.
            $membresiaCliente->update(['estado' => MembresiaCliente::ESTADO_ACTIVA]);
        });

        return redirect()->route('pagos.index')
            ->with('success', 'Pago registrado y membresía activada correctamente.');
    }

    /**
     * Pago del SALDO (cuota 2, Etapa 3): un Pago nuevo, vinculado a la MISMA
     * membresia_cliente, por el saldo EXACTO (no se acepta otro monto — no
     * se abren sub-cuotas). Salda la deuda y reactiva si estaba suspendida;
     * si ya estaba activa (pagó antes del vencimiento del saldo), sigue
     * activa. fecha_fin NUNCA se toca acá.
     *
     * saldo_pendiente pasa a 0 (no null): un 0 exacto es lo que le permite a
     * destroy() reconstruir el monto si este pago se borra por error.
     * fecha_limite_saldo se deja TAL CUAL estaba (no se limpia): es el único
     * dato que le permite a destroy() saber si, al revertir, corresponde
     * volver a 'activa' o a 'suspendida_saldo'. Ninguna otra parte del
     * sistema lee fecha_limite_saldo sin antes chequear saldo_pendiente > 0,
     * así que dejarla puesta en una membresía ya saldada no tiene efecto en
     * nada más.
     */
    public function pagarSaldo(Request $request, MembresiaCliente $membresiaCliente)
    {
        if (!$membresiaCliente->tieneSaldoPendiente()) {
            return redirect()->route('pagos.index')
                ->with('error', 'Esta membresía no tiene saldo pendiente.');
        }

        $request->validate([
            'metodo_pago'   => 'required|in:efectivo,QR',
            'observaciones' => 'nullable|string',
            'imagen_qr'     => 'nullable|required_if:metodo_pago,QR|image|mimes:jpeg,png,jpg,webp|max:2048',
        ], [
            'imagen_qr.required_if' => 'Debes subir el comprobante para un pago por QR.',
        ]);

        $rutaImagen = $request->hasFile('imagen_qr')
            ? $request->file('imagen_qr')->store('qrs', 'privado')
            : null;

        DB::transaction(function () use ($request, $membresiaCliente, $rutaImagen) {
            Pago::create([
                'id_cliente'           => $membresiaCliente->id_cliente,
                'id_membresia'         => $membresiaCliente->id_membresia,
                'id_membresia_cliente' => $membresiaCliente->id,
                'monto'                => $membresiaCliente->saldo_pendiente, // exacto, decide el servidor
                'fecha_pago'           => now()->toDateString(),
                'metodo_pago'          => $request->metodo_pago,
                'observaciones'        => $request->observaciones,
                'imagen_qr'            => $rutaImagen,
            ]);

            $membresiaCliente->update([
                'saldo_pendiente' => 0,
                'estado'          => MembresiaCliente::ESTADO_ACTIVA,
                // fecha_fin: SIN TOCAR — el diseño ya definió que los días
                // suspendida se pierden, la fecha original no se recalcula.
            ]);
        });

        return redirect()->route('pagos.index')
            ->with('success', 'Saldo pagado. La membresía quedó activa.');
    }

    /**
     * Sirve el comprobante (imagen_qr) desde el disco PRIVADO.
     * Ruta protegida por rol (personal que gestiona pagos); nunca es URL pública.
     */
    public function comprobante(Pago $pago)
    {
        abort_unless($pago->imagen_qr && Storage::disk('privado')->exists($pago->imagen_qr), 404);

        return Storage::disk('privado')->response($pago->imagen_qr);
    }

    /**
     * Comprobante para el propio cliente (solo puede ver los de SUS pagos).
     * Sin SedeScope: la propiedad del pago es el único control que importa aquí.
     */
    public function miComprobante($idPago)
    {
        $cliente = Cliente::where('id_usuario', Auth::id())->first();

        $pago = Pago::withoutGlobalScope(\App\Models\Scopes\SedeScope::class)->findOrFail($idPago);

        abort_unless($cliente && (int) $pago->id_cliente === (int) $cliente->id_cliente, 403);
        abort_unless($pago->imagen_qr && Storage::disk('privado')->exists($pago->imagen_qr), 404);

        return Storage::disk('privado')->response($pago->imagen_qr);
    }

    public function show($id)
    {
        $pago = Pago::with(['cliente.persona', 'membresia', 'membresiaCliente'])->findOrFail($id);
        return view('pagos.show', compact('pago'));
    }

    public function misPagos()
    {
        $cliente = Cliente::where('id_usuario', Auth::id())->first();

        if (!$cliente) {
            return redirect()->back()->with('error', 'No tienes un perfil de cliente asociado.');
        }

        $pagos = Pago::with('membresia')
                    ->where('id_cliente', $cliente->id_cliente)
                    ->orderBy('fecha_pago', 'desc')
                    ->get();

        return view('pagos.mis-pagos', compact('pagos', 'cliente'));
    }

public function edit($id)
{
    $pago = Pago::with(['cliente.persona', 'membresia'])->findOrFail($id);
    $clientes = Cliente::with('persona')->get();
    $membresias = Membresia::all(); // <--- Aquí traemos todas las membresías

    return view('pagos.edit', compact('pago', 'clientes', 'membresias'));
}


    public function update(Request $request, Pago $pago)
    {
        $validated = $request->validate([
            'id_cliente'    => 'required|exists:clientes,id_cliente',
            'id_membresia'  => 'required|exists:membresias,id_membresia',
            'monto'         => 'required|numeric|min:0',
            // La fecha del pago no se edita: se conserva la del registro original.
            // Métodos: solo Efectivo y QR (los históricos con transferencia no se tocan).
            'metodo_pago'   => 'required|in:efectivo,QR',
            'observaciones' => 'nullable|string',
            'imagen_qr'     => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($request->hasFile('imagen_qr')) {
            if ($pago->imagen_qr) {
                Storage::disk('privado')->delete($pago->imagen_qr);
            }
            $validated['imagen_qr'] = $request->file('imagen_qr')->store('qrs', 'privado');
        }

        $pago->update($validated);

        return redirect()->route('pagos.index')->with('success', 'Pago actualizado correctamente.');
    }

    /**
     * Al eliminar un pago, qué le pasa a la membresía depende de CUÁL de sus
     * pagos se borra (Pago en 2 Cuotas, Etapa 3 — hoy una membresía tiene
     * como máximo 2: la cuota inicial que la activó, y opcionalmente el
     * saldo). Se distinguen por orden cronológico (id_pago), no hace falta
     * un campo "tipo" nuevo:
     *
     *   - Se borra el PRIMER pago (el único, o la cuota inicial): sin él no
     *     hay nada acreditado — vuelve a pendiente_pago TOTAL, limpiando
     *     saldo_pendiente/fecha_limite_saldo (si quedara un pago de saldo
     *     posterior sin borrar también, es un caso fuera de flujo normal:
     *     un operador borrando pagos fuera de orden — no se intenta adivinar
     *     nada ahí, mismo criterio "vuelve a cero" de siempre).
     *   - Se borra el SEGUNDO pago (el del saldo): se deshace SOLO eso — el
     *     pago inicial y la membresía siguen en pie. El saldo vuelve a ser
     *     exactamente lo que ese pago cubría, y el estado vuelve a lo que
     *     correspondía según la fecha límite (activa si todavía no se había
     *     cumplido, suspendida_saldo si ya estaba vencida).
     */
    public function destroy(Pago $pago)
    {
        DB::transaction(function () use ($pago) {
            if ($pago->id_membresia_cliente) {
                $membresiaCliente = MembresiaCliente::find($pago->id_membresia_cliente);

                if ($membresiaCliente) {
                    $primerPago = $membresiaCliente->pagos()->orderBy('id_pago')->first();
                    $esElPagoInicial = $primerPago && $primerPago->id_pago === $pago->id_pago;

                    if ($esElPagoInicial) {
                        $membresiaCliente->update([
                            'estado'             => MembresiaCliente::ESTADO_PENDIENTE,
                            'saldo_pendiente'    => null,
                            'fecha_limite_saldo' => null,
                        ]);
                    } else {
                        $seguiaVigenteElPlazo = $membresiaCliente->fecha_limite_saldo === null
                            || $membresiaCliente->fecha_limite_saldo->toDateString() >= now()->toDateString();

                        $membresiaCliente->update([
                            'saldo_pendiente' => $pago->monto,
                            'estado'          => $seguiaVigenteElPlazo
                                ? MembresiaCliente::ESTADO_ACTIVA
                                : MembresiaCliente::ESTADO_SUSPENDIDA_SALDO,
                        ]);
                    }
                }
            }

            $pago->delete();
        });

        return redirect()->route('pagos.index')
            ->with('success', 'Pago eliminado correctamente.');
    }
}
