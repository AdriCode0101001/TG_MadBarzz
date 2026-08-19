<?php

namespace App\Http\Controllers;

use App\Jobs\EnviarPushAvisoJob;
use App\Models\Aviso;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Avisos: Administrador/Recepcionista los crea y quedan dirigidos a TODOS
 * los clientes de una — no es una notificación puntual (eso es
 * NotificacionController, WhatsApp a un cliente). El cliente los ve en su
 * portal (campanita, Etapa 2); acá solo se gestiona el envío y el historial.
 */
class AvisoController extends Controller
{
    public function index()
    {
        $avisos = Aviso::with('usuario')
            ->withCount('lecturas')
            ->orderByDesc('created_at')
            ->get();

        $totalClientes = Cliente::count();

        return view('avisos.index', compact('avisos', 'totalClientes'));
    }

    public function create()
    {
        return view('avisos.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'titulo' => 'required|string|max:150',
            'texto'  => 'required|string|max:2000',
            'imagen' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $ruta = $request->hasFile('imagen')
            ? $request->file('imagen')->store('avisos', 'public')
            : null;

        $aviso = Aviso::create([
            'titulo'     => $data['titulo'],
            'texto'      => $data['texto'],
            'imagen'     => $ruta,
            'id_usuario' => Auth::id(),
        ]);

        // En cola: el aviso ya quedó guardado (visible en la campanita) sin
        // esperar a que termine el push — si falla o tarda, no afecta esto.
        EnviarPushAvisoJob::dispatch($aviso);

        return redirect()->route('avisos.index')
            ->with('success', 'Aviso enviado a todos los clientes.');
    }

    /**
     * Retira el aviso (soft delete): deja de contar como enviado, pero se
     * conserva para el historial — no se borra el archivo de imagen, para
     * poder restaurarlo más adelante si hiciera falta.
     */
    public function destroy(Aviso $aviso)
    {
        $aviso->delete();

        return redirect()->route('avisos.index')
            ->with('success', 'Aviso retirado.');
    }
}
