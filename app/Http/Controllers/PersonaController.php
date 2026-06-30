<?php

// app/Http/Controllers/PersonaController.php

namespace App\Http\Controllers;

use App\Models\Persona;
use Illuminate\Http\Request;

class PersonaController extends Controller
{
public function index(Request $request)
{
    $buscar = $request->input('buscar');

    $personas = Persona::query()
        ->when($buscar, function($query, $buscar) {
            $query->where(function($q) use ($buscar) {
                // Buscar por nombre completo
                $q->whereRaw("CONCAT(nombre_completo, ' ', apellido_paterno, ' ', apellido_materno) LIKE ?", ["%{$buscar}%"])
                  // O por CI
                  ->orWhere('ci', 'LIKE', "%{$buscar}%");
            });
        })
        ->get(); // SoftDeletes ya filtra los eliminados si está habilitado

    return view('personas.index', compact('personas'));
}

    public function create()
    {
        return view('personas.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre_completo' => 'required',
            'apellido_paterno' => 'required',
            'apellido_materno' => 'required',
            'ci' => 'required|unique:personas',
            'telefono' => 'required|regex:/^\+\d{7,15}$/|unique:personas',
            'genero' => 'required',
            'fecha_nacimiento' => 'required|date'
        ]);

        Persona::create($request->all());

        return redirect()->route('personas.index')->with('success', 'Persona registrada');
    }

    public function edit($id)
    {
        $persona = Persona::findOrFail($id);
        return view('personas.edit', compact('persona'));
    }



    public function update(Request $request, $id)
    {
        $persona = Persona::findOrFail($id);

        $request->validate([
            'nombre_completo'  => 'required',
            'apellido_paterno' => 'required',
            'apellido_materno' => 'required',
            'ci'               => 'required|unique:personas,ci,' . $persona->id_persona . ',id_persona',
            'telefono'         => 'nullable|regex:/^\+\d{7,15}$/|unique:personas,telefono,' . $persona->id_persona . ',id_persona',
            'genero'           => 'required',
            'fecha_nacimiento' => 'required|date',
        ]);

        $persona->update($request->only([
            'nombre_completo', 'apellido_paterno', 'apellido_materno',
            'ci', 'telefono', 'genero', 'fecha_nacimiento',
        ]));

        return redirect()->route('personas.index')->with('success', 'Persona actualizada');
    }


    public function destroy($id)
    {
        $persona = Persona::findOrFail($id);
        $persona->delete(); // ✅ esto NO elimina físicamente, solo pone deleted_at
        return redirect()->route('personas.index')->with('success', 'Persona eliminada lógicamente.');
    }

}

