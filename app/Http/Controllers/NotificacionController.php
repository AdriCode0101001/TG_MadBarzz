<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Notificacion;
use App\Services\NotificacionService;
use Illuminate\Http\Request;

class NotificacionController extends Controller
{
    public function __construct(private NotificacionService $notificacionService) {}

    public function index()
    {
        $notificaciones = Notificacion::with('cliente.persona')->orderBy('created_at', 'desc')->get();
        return view('notificaciones.index', compact('notificaciones'));
    }

    public function create()
    {
        $clientes = Cliente::with('persona')->get();
        return view('notificaciones.create', compact('clientes'));
    }

    // Formulario de envío rápido (ruta notificaciones.form)
    public function showForm()
    {
        $clientes = Cliente::with('persona')->get();
        return view('notificaciones.send', compact('clientes'));
    }

    // Envío rápido desde el formulario send (ruta notificaciones.sendWhatsapp)
    public function sendWhatsapp(Request $request)
    {
        $request->validate([
            'id_cliente' => 'required|exists:clientes,id_cliente',
            'mensaje'    => 'required|string|max:500',
        ]);

        $notificacion = Notificacion::create([
            'id_cliente' => $request->id_cliente,
            'tipo'       => 'recordatorio_vencimiento',
            'mensaje'    => $request->mensaje,
            'estado'     => 'pendiente',
        ]);

        $enviado = $this->notificacionService->enviarACliente($notificacion);

        return redirect()->back()->with(
            $enviado ? 'success' : 'error',
            $enviado ? 'Mensaje enviado correctamente.' : 'Error al enviar el mensaje.'
        );
    }

    // Guardar y enviar desde el formulario create (ruta notificaciones.store)
    public function store(Request $request)
    {
        $request->validate([
            'id_cliente' => 'required|exists:clientes,id_cliente',
            'tipo'       => 'required|in:recordatorio_vencimiento,bienvenida,promocion',
            'mensaje'    => 'required|string',
        ]);

        $notificacion = Notificacion::create([
            'id_cliente' => $request->id_cliente,
            'tipo'       => $request->tipo,
            'mensaje'    => $request->mensaje,
            'estado'     => 'pendiente',
        ]);

        $enviado = $this->notificacionService->enviarACliente($notificacion);

        return redirect()->route('notificaciones.index')->with(
            'success',
            $enviado
                ? 'Notificación enviada correctamente.'
                : 'Notificación registrada, pero sin canal disponible para el cliente.'
        );
    }
}
