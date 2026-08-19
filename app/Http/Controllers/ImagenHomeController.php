<?php

namespace App\Http\Controllers;

use App\Models\ConfiguracionImagen;
use App\Services\ImagenHomeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * Imágenes de contenido de la home pública: solo el Administrador las sube o
 * reemplaza, una por una, por su "clave" fija (ver ImagenHomeService::CLAVES).
 * Se guardan en el disco 'public' (accesible por URL directa, a diferencia
 * del QR de pago que usa el disco 'privado'). Si una clave no tiene imagen
 * subida, la home sigue mostrando el placeholder de siempre.
 */
class ImagenHomeController extends Controller
{
    public function index()
    {
        $configuradas = ConfiguracionImagen::whereIn('clave', array_keys(ImagenHomeService::CLAVES))
            ->get()
            ->keyBy('clave');

        return view('configuracion.imagenes_home', [
            'claves'      => ImagenHomeService::CLAVES,
            'configuradas' => $configuradas,
        ]);
    }

    /**
     * "imagen" es opcional (nullable): permite guardar SOLO un cambio de
     * "posicion" (reencuadre arriba/centro/abajo) sin tener que volver a
     * elegir el archivo. Al menos uno de los dos debe venir en la request.
     */
    public function update(Request $request, string $clave)
    {
        abort_unless(array_key_exists($clave, ImagenHomeService::CLAVES), 404);

        $permiteReencuadre = ImagenHomeService::permiteReencuadre($clave);

        $data = $request->validate([
            'imagen'   => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'posicion' => $permiteReencuadre
                ? 'nullable|in:' . implode(',', ImagenHomeService::POSICIONES_VALIDAS)
                : 'prohibited',
        ]);

        if (!$request->hasFile('imagen') && !($permiteReencuadre && $request->filled('posicion'))) {
            return back()->withErrors(['imagen' => 'Elegí una imagen para subir.']);
        }

        $cambios = [];

        if ($request->hasFile('imagen')) {
            $anterior = ConfiguracionImagen::where('clave', $clave)->first();
            if ($anterior && $anterior->ruta) {
                Storage::disk('public')->delete($anterior->ruta);
            }

            $cambios['ruta']       = $request->file('imagen')->store('home', 'public');
            $cambios['id_usuario'] = Auth::id();
        }

        if ($permiteReencuadre && $request->filled('posicion')) {
            $cambios['posicion'] = $request->posicion;
        }

        ConfiguracionImagen::updateOrCreate(['clave' => $clave], $cambios);

        return redirect()->route('configuracion.imagenes_home.index')
            ->with('success', 'Imagen actualizada correctamente.');
    }
}
