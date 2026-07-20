<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cliente;
use App\Models\Entrenador;
use App\Models\Asistencia;
use App\Models\Pago;
use App\Models\QrPago;
use App\Models\Rutina;
use App\Services\CarnetService;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DashboardController extends Controller
{
    // Dashboard general (administrador, recepcionista, entrenador)
    public function index()
    {
        // Se cargan TODOS (activos e inactivos) para poder filtrarlos en la vista.
        $clientes = Cliente::with(['persona', 'user'])->get();
        $entrenadores = Entrenador::with('persona')->get();

        // Aviso de vencimiento del QR de pago: solo relevante para el Admin
        // (único que puede configurarlo), se muestra en su dashboard.
        $qrPago = QrPago::actual();

        return view('dashboard.index', compact('clientes', 'entrenadores', 'qrPago'));
    }

    // Dashboard para cliente
    public function cliente(CarnetService $carnet)
    {
        $cliente = Cliente::with(['membresias', 'persona', 'sede'])
            ->where('id_usuario', Auth::id())
            ->first();

        if (!$cliente) {
            return redirect()->back()->with('error', 'No tienes un perfil de cliente asociado.');
        }

        // Membresía activa más reciente: vigente por fecha Y con su pago
        // registrado (estado = activa). Sin pago no cuenta como activa.
        $membresia = $cliente->membresias()
            ->wherePivot('fecha_fin', '>=', now())
            ->wherePivot('estado', 'activa')
            ->orderByPivot('fecha_fin', 'desc')
            ->first();

        $asistencias = Asistencia::where('id_cliente', $cliente->id_cliente)
            ->orderBy('fecha_hora_entrada', 'desc')
            ->get();

        $pagos = Pago::where('id_cliente', $cliente->id_cliente)
            ->orderBy('fecha_pago', 'desc')
            ->get();

        $qrCarnet = $cliente->qr_token ? $carnet->qrDataUri($cliente, 300) : null;

        return view('dashboard.cliente', compact('cliente', 'membresia', 'asistencias', 'pagos', 'qrCarnet'));
    }

    // Dashboard para entrenador
    public function entrenador()
    {
        $user = Auth::user();

        $entrenador = Entrenador::with('persona')->where('id_usuario', $user->id)->first();

        $rutinas = Rutina::with('grupoMuscular')
            ->withCount('ejercicios')
            ->where('id_usuario', $user->id)
            ->orderByDesc('fecha')
            ->get();
        $clientes   = Cliente::with('persona')->get();
        $asistenciasHoy = Asistencia::whereDate('fecha_hora_entrada', Carbon::today())->count();

        return view('dashboard.entrenador', compact('entrenador', 'rutinas', 'clientes', 'asistenciasHoy'));
    }

}
