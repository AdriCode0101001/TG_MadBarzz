<?php

namespace App\Http\Controllers;

use App\Services\BitacoraService;
use Illuminate\Http\Request;

/**
 * Bitácora de Auditoría — Etapa 3: pantalla de solo lectura del historial de
 * activity_log (Etapas 1 y 2). Acceso restringido a Administrador por la
 * ruta (middleware role:Administrador en routes/web.php), no solo por el menú.
 */
class BitacoraController extends Controller
{
    public function index(Request $request, BitacoraService $bitacora)
    {
        $filtros = $request->only(['causer_id', 'evento', 'entidad', 'desde', 'hasta']);

        $actividades = $bitacora->paginar($filtros);
        $causers     = $bitacora->opcionesCausers();
        $entidades   = $bitacora->opcionesEntidades();

        return view('bitacora.index', compact('actividades', 'causers', 'entidades', 'filtros'));
    }
}
