<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cliente;
use App\Models\Entrenador;
use App\Models\Asistencia;
use App\Models\Pago;
use App\Models\Rutina;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DashboardController extends Controller
{
    // Dashboard general (administrador)
    public function index()
    {
        // Se cargan TODOS (activos e inactivos) para poder filtrarlos en la vista.
        $clientes = Cliente::with(['persona', 'user'])->get();
        $entrenadores = Entrenador::with('persona')->get();

        return view('dashboard.index', compact('clientes', 'entrenadores'));
    }

    // Dashboard para cliente
    public function cliente()
    {
        $cliente = Cliente::with(['membresias', 'persona'])
            ->where('id_usuario', Auth::id())
            ->first();

        if (!$cliente) {
            return redirect()->back()->with('error', 'No tienes un perfil de cliente asociado.');
        }

        // Membresía activa más reciente
        $membresia = $cliente->membresias()
            ->wherePivot('fecha_fin', '>=', now())
            ->orderByPivot('fecha_fin', 'desc')
            ->first();

        $asistencias = Asistencia::where('id_cliente', $cliente->id_cliente)
            ->orderBy('fecha_hora_entrada', 'desc')
            ->get();

        $pagos = Pago::where('id_cliente', $cliente->id_cliente)
            ->orderBy('fecha_pago', 'desc')
            ->get();

        $rutinas = Rutina::with('entrenador.persona')->get();

        return view('dashboard.cliente', compact('cliente', 'membresia', 'asistencias', 'pagos', 'rutinas'));
    }

    // Dashboard para entrenador
    public function entrenador()
    {
        $user = Auth::user();

        $entrenador = Entrenador::with('persona')->where('id_usuario', $user->id)->first();

        $rutinas    = Rutina::where('id_entrenador', optional($entrenador)->id_entrenador)->latest()->get();
        $clientes   = Cliente::with('persona')->get();
        $asistenciasHoy = Asistencia::whereDate('fecha_hora_entrada', Carbon::today())->count();

        return view('dashboard.entrenador', compact('entrenador', 'rutinas', 'clientes', 'asistenciasHoy'));
    }

}
