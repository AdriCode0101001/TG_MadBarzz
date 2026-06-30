<?php

namespace App\Http\Controllers;

use App\Models\Rutina;
use App\Models\Entrenador;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Cliente;
use Illuminate\Support\Facades\Auth;

class RutinaController extends Controller
{
    # =================== INDEX (ADMIN) ===================
public function index(Request $request)
{
    $buscar = $request->get('buscar');

    $rutinas = Rutina::with('entrenador.persona')
        ->when($buscar, function($query, $buscar) {

            // Buscar por título o entrenador
            $query->where('titulo', 'like', "%{$buscar}%")
                  ->orWhereHas('entrenador.persona', function($q) use ($buscar) {
                      $q->where('nombre_completo', 'like', "%{$buscar}%")
                        ->orWhere('apellido_paterno', 'like', "%{$buscar}%")
                        ->orWhere('apellido_materno', 'like', "%{$buscar}%");
                  });

            // Buscar por fechas flexibles
            if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $buscar, $matches)) {
                // Formato dd/mm/yyyy
                $fecha = "{$matches[3]}-{$matches[2]}-{$matches[1]}";
                $query->orWhereDate('fecha_subida', $fecha);
            } elseif (preg_match('/^(\d{2})\/(\d{2})$/', $buscar, $matches)) {
                // Formato dd/mm (ignora año)
                $dia = $matches[1];
                $mes = $matches[2];
                $query->orWhereRaw('DAY(fecha_subida) = ? AND MONTH(fecha_subida) = ?', [$dia, $mes]);
            } elseif (preg_match('/^(\d{2})\/(\d{4})$/', $buscar, $matches)) {
                // Formato mm/yyyy
                $mes = $matches[1];
                $anio = $matches[2];
                $query->orWhereRaw('MONTH(fecha_subida) = ? AND YEAR(fecha_subida) = ?', [$mes, $anio]);
            } elseif (preg_match('/^\d{4}$/', $buscar, $matches)) {
                // Solo año
                $anio = $buscar;
                $query->orWhereYear('fecha_subida', $anio);
            }
        })
        ->latest()
        ->get();

    return view('rutinas.index', compact('rutinas', 'buscar'));
}



    # =================== CREATE ===================
    public function create()
    {
        $entrenadores = Entrenador::with('persona')->get();
        return view('rutinas.create', compact('entrenadores'));
    }

    # =================== STORE ===================
    public function store(Request $request)
    {
        $request->validate([
            'id_entrenador' => 'required|exists:entrenadores,id_entrenador',
            'titulo'        => 'required|string|max:255',
            'descripcion'   => 'nullable|string',
            'archivo'       => 'nullable|file|mimes:pdf,jpeg,png,jpg|max:10240',
        ]);

        $data = $request->only(['id_entrenador', 'titulo', 'descripcion']);
        $data['fecha_subida'] = now();

        if ($request->hasFile('archivo')) {
            $filename = time() . '_' . $request->file('archivo')->getClientOriginalName();
            $request->file('archivo')->storeAs('rutinas', $filename, 'public');
            $data['archivo'] = 'rutinas/' . $filename;
        }

        Rutina::create($data);

        return redirect()->route('rutinas.index')->with('success', 'Rutina subida correctamente.');
    }

    # =================== EDIT ===================
    public function edit(Rutina $rutina)
    {
        $entrenadores = Entrenador::with('persona')->get();
        return view('rutinas.edit', compact('rutina', 'entrenadores'));
    }

    # =================== UPDATE ===================
    public function update(Request $request, Rutina $rutina)
    {
        $request->validate([
            'id_entrenador' => 'required|exists:entrenadores,id_entrenador',
            'titulo'        => 'required|string|max:255',
            'descripcion'   => 'nullable|string',
            'archivo'       => 'nullable|file|mimes:pdf,jpeg,png,jpg|max:10240',
        ]);

        $data = $request->only(['id_entrenador', 'titulo', 'descripcion']);

        if ($request->hasFile('archivo')) {
            if ($rutina->archivo && Storage::disk('public')->exists($rutina->archivo)) {
                Storage::disk('public')->delete($rutina->archivo);
            }

            $filename = time() . '_' . $request->file('archivo')->getClientOriginalName();
            $request->file('archivo')->storeAs('rutinas', $filename, 'public');
            $data['archivo'] = 'rutinas/' . $filename;
            $data['fecha_subida'] = now();
        }

        $rutina->update($data);

        return redirect()->route('rutinas.index')->with('success', 'Rutina actualizada correctamente.');
    }

    # =================== DESTROY ===================
    public function destroy(Rutina $rutina)
    {
        if ($rutina->archivo && Storage::disk('public')->exists($rutina->archivo)) {
            Storage::disk('public')->delete($rutina->archivo);
        }

        $rutina->delete();

        return redirect()->route('rutinas.index')->with('success', 'Rutina eliminada correctamente.');
    }

    # =================== SHOW (GENERAL) ===================
    public function show()
    {
        // 🔹 Ahora muestra TODAS las rutinas a los clientes, sin importar asignación
        $rutinas = Rutina::with('entrenador.persona')->latest()->get();

        return view('rutinas.show', compact('rutinas'));
    }


    # =================== VER RUTINA DETALLE ===================
    public function verRutina($id)
    {
        $rutina = Rutina::with('entrenador.persona')->findOrFail($id);
        return view('rutinas.verrutina', compact('rutina'));
    }
}
