<?php

namespace App\Http\Controllers;

use App\Models\Descuento;
use Illuminate\Http\Request;

/**
 * Administración de descuentos aplicables a membresías (solo Administrador).
 */
class DescuentoController extends Controller
{
    public function index()
    {
        $descuentos = Descuento::orderBy('porcentaje')->get();

        return view('descuentos.index', compact('descuentos'));
    }

    public function create()
    {
        return view('descuentos.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre'     => 'required|string|max:100',
            'porcentaje' => 'required|numeric|min:0|max:100',
            'activo'     => 'nullable|boolean',
        ]);

        Descuento::create([
            'nombre'     => $data['nombre'],
            'porcentaje' => $data['porcentaje'],
            'activo'     => $request->boolean('activo', true),
        ]);

        return redirect()->route('descuentos.index')
            ->with('success', 'Descuento creado correctamente.');
    }

    public function edit(Descuento $descuento)
    {
        return view('descuentos.edit', compact('descuento'));
    }

    public function update(Request $request, Descuento $descuento)
    {
        $data = $request->validate([
            'nombre'     => 'required|string|max:100',
            'porcentaje' => 'required|numeric|min:0|max:100',
            'activo'     => 'nullable|boolean',
        ]);

        $descuento->update([
            'nombre'     => $data['nombre'],
            'porcentaje' => $data['porcentaje'],
            'activo'     => $request->boolean('activo'),
        ]);

        return redirect()->route('descuentos.index')
            ->with('success', 'Descuento actualizado correctamente.');
    }

    public function destroy(Descuento $descuento)
    {
        $descuento->delete(); // soft delete: las asignaciones antiguas conservan sus datos

        return redirect()->route('descuentos.index')
            ->with('success', 'Descuento eliminado correctamente.');
    }
}
