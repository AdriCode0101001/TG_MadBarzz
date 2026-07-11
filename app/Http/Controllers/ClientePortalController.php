<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Rutina;
use App\Services\CarnetService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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

        return view('portal.mi-membresia', compact('cliente', 'vigente', 'historial'));
    }

    /**
     * Mis Rutinas: las rutinas del gimnasio solo se muestran a clientes
     * con membresía vigente; sin ella se invita a pagar en recepción.
     */
    public function misRutinas(): View|RedirectResponse
    {
        $cliente = Cliente::where('id_usuario', Auth::id())->first();

        if (!$cliente) {
            return redirect()->route('dashboard')->with('error', 'No tienes un perfil de cliente asociado.');
        }

        // Mismo criterio de "membresía al día" que usa el control de asistencia.
        // El bloqueo es del SERVIDOR: sin membresía vigente, las rutinas ni
        // siquiera se consultan ni se envían a la vista.
        $membresiaVigente = $cliente->tieneMembresiaVigente();

        $rutinas = $membresiaVigente
            ? Rutina::with('entrenador.persona')->latest()->get()
            : collect();

        return view('portal.mis-rutinas', compact('cliente', 'membresiaVigente', 'rutinas'));
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
}
