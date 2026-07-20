<?php

namespace App\Http\Controllers;

use App\Models\Ejercicio;
use App\Models\GrupoMuscular;
use Illuminate\Http\Request;

/**
 * Listado de solo lectura del catálogo de ejercicios (para verificar que la
 * importación del dataset quedó bien). La pantalla de armar rutinas con
 * estos ejercicios es una etapa posterior.
 */
class EjercicioController extends Controller
{
    public function index(Request $request)
    {
        $query = Ejercicio::with('grupoMuscular')->orderBy('nombre');

        if ($request->filled('grupo')) {
            if ($request->query('grupo') === 'sin_grupo') {
                $query->whereNull('id_grupo_muscular');
            } else {
                $query->where('id_grupo_muscular', $request->query('grupo'));
            }
        }

        if ($request->filled('buscar')) {
            $query->where('nombre', 'like', '%' . $request->query('buscar') . '%');
        }

        $ejercicios = $query->paginate(60)->withQueryString();
        $gruposMusculares = GrupoMuscular::orderBy('nombre')->get();
        $totalSinGrupo = Ejercicio::whereNull('id_grupo_muscular')->count();

        return view('ejercicios.index', compact('ejercicios', 'gruposMusculares', 'totalSinGrupo'));
    }
}
