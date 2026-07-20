<?php

namespace App\Http\Controllers;

use App\Models\GrupoMuscular;
use Illuminate\Http\Request;

/**
 * Catálogo de grupos musculares (base del futuro calendario de rutinas).
 * Solo el Administrador gestiona (crear/editar/borrar); el resto del
 * personal lo ve en solo lectura para saber qué músculos existen.
 */
class GrupoMuscularController extends Controller
{
    public function index()
    {
        $gruposMusculares = GrupoMuscular::orderBy('nombre')->get();

        return view('grupos_musculares.index', compact('gruposMusculares'));
    }

    public function create()
    {
        return view('grupos_musculares.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre'      => 'required|string|max:100',
            'descripcion' => 'nullable|string|max:255',
            'activo'      => 'nullable|boolean',
        ]);

        GrupoMuscular::create([
            'nombre'      => $data['nombre'],
            'descripcion' => $data['descripcion'] ?? null,
            'activo'      => $request->boolean('activo', true),
        ]);

        return redirect()->route('grupos_musculares.index')
            ->with('success', 'Grupo muscular creado correctamente.');
    }

    public function edit(GrupoMuscular $grupo_muscular)
    {
        return view('grupos_musculares.edit', ['grupoMuscular' => $grupo_muscular]);
    }

    public function update(Request $request, GrupoMuscular $grupo_muscular)
    {
        $data = $request->validate([
            'nombre'      => 'required|string|max:100',
            'descripcion' => 'nullable|string|max:255',
            'activo'      => 'nullable|boolean',
        ]);

        $grupo_muscular->update([
            'nombre'      => $data['nombre'],
            'descripcion' => $data['descripcion'] ?? null,
            'activo'      => $request->boolean('activo'),
        ]);

        return redirect()->route('grupos_musculares.index')
            ->with('success', 'Grupo muscular actualizado correctamente.');
    }

    public function destroy(GrupoMuscular $grupo_muscular)
    {
        $grupo_muscular->delete(); // soft delete: rutinas futuras conservan la referencia

        return redirect()->route('grupos_musculares.index')
            ->with('success', 'Grupo muscular eliminado correctamente.');
    }
}
