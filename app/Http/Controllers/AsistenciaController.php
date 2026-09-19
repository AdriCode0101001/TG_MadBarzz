<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use App\Models\Cliente;
use Illuminate\Support\Facades\Auth;

class AsistenciaController extends Controller
{
    // Mostrar asistencias del cliente autenticado
    public function misAsistencias()
    {
        // Buscar el cliente asociado al usuario autenticado
        $cliente = Cliente::where('id_usuario', Auth::id())->first();

        if (!$cliente) {
            return redirect()->back()->with('error', 'No tienes un perfil de cliente asociado.');
        }

        // Obtener las asistencias del cliente
        $asistencias = Asistencia::where('id_cliente', $cliente->id_cliente)
                                ->orderBy('fecha_hora_entrada', 'desc')
                                ->get();

        return view('asistencias.mis', compact('asistencias', 'cliente'));
    }
}
