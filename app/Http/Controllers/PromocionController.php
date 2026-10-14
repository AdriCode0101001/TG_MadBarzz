<?php

namespace App\Http\Controllers;

use App\Models\Promocion;
use App\Models\Administrador;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PromocionController extends Controller
{
    # =================== INDEX ===================
    public function index()
    {
        $promociones = Promocion::with('administrador.persona')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('promociones.index', compact('promociones'));
    }

    # =================== CREATE ===================
    public function create()
    {
        return view('promociones.create');
    }

    # =================== STORE ===================
    public function store(Request $request)
    {
        $request->validate([
            'titulo' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'estado' => 'required|in:activa,inactiva,finalizada',
            'imagen' => 'nullable|image|mimes:jpg,png,jpeg|max:2048',
        ]);

        // Perfil de Administrador del usuario logueado — OPCIONAL: un usuario con
        // rol Administrador puede no tener fila en `administradores` (ej. el admin
        // de demo). La promoción se crea igual; el autor queda en la bitácora
        // (causer = usuario logueado) aunque id_administrador sea null.
        $admin = Administrador::where('id_usuario', Auth::id())->first();

        $data = $request->only(['titulo', 'descripcion', 'fecha_inicio', 'fecha_fin', 'estado']);

        // Subida de imagen
        if ($request->hasFile('imagen')) {
            $filename = time().'_'.$request->file('imagen')->getClientOriginalName();
            $request->file('imagen')->storeAs('promociones', $filename, 'public');
            $data['imagen'] = 'promociones/'.$filename;
        }

        $data['id_administrador'] = $admin?->id_administrador;

        Promocion::create($data);

        return redirect()->route('promociones.index')->with('success', 'Promoción registrada correctamente.');
    }

    # =================== EDIT ===================
    public function edit(Promocion $promocion)
    {
        return view('promociones.edit', compact('promocion'));
    }

    # =================== UPDATE ===================
    public function update(Request $request, Promocion $promocion)
    {
        $request->validate([
            'titulo' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'estado' => 'required|in:activa,inactiva,finalizada',
            'imagen' => 'nullable|image|mimes:jpg,png,jpeg|max:2048',
        ]);

        $data = $request->all();

        // Subir nueva imagen y eliminar la antigua si existe
        if ($request->hasFile('imagen')) {
            if ($promocion->imagen && Storage::disk('public')->exists($promocion->imagen)) {
                Storage::disk('public')->delete($promocion->imagen);
            }
            $filename = time().'_'.$request->file('imagen')->getClientOriginalName();
            $request->file('imagen')->storeAs('promociones', $filename, 'public');
            $data['imagen'] = 'promociones/'.$filename;
        }

        $promocion->update($data);

        return redirect()->route('promociones.index')->with('success', 'Promoción actualizada correctamente.');
    }

    # =================== DESTROY ===================
    public function destroy(Promocion $promocion)
    {
        // Eliminación lógica: cambia a inactiva en lugar de borrar
        $promocion->update(['estado' => 'inactiva']);

        return redirect()->route('promociones.index')->with('success', 'Promoción eliminada correctamente.');
    }
}
