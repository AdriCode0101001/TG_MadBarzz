<?php

namespace App\Http\Controllers;

use App\Models\Entrenador;
use App\Models\Persona;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class EntrenadorController extends Controller
{
public function index(Request $request)
{
    $buscar = $request->input('buscar');

    $entrenadores = Entrenador::with(['persona', 'user'])
        ->when($buscar, function($query, $buscar) {
            $query->whereHas('persona', function($q) use ($buscar) {
                $q->whereRaw("CONCAT(nombre_completo, ' ', apellido_paterno, ' ', apellido_materno) LIKE ?", ["%{$buscar}%"])
                  ->orWhere('ci', 'LIKE', "%{$buscar}%");
            })
            ->orWhereHas('user', function($q) use ($buscar) {
                $q->where('email', 'LIKE', "%{$buscar}%");
            });
        })
        ->get();

    return view('entrenadores.index', compact('entrenadores'));
}

    public function create()
    {
        return view('entrenadores.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            // Persona
            'nombre_completo'  => 'required|string|max:255',
            'apellido_paterno' => 'required|string|max:255',
            'apellido_materno' => 'required|string|max:255',
            'ci'               => 'required|unique:personas,ci',
            'telefono'         => 'required|regex:/^\+\d{7,15}$/|unique:personas,telefono',
            'genero'           => 'required|in:masculino,femenino,otro',
            'fecha_nacimiento' => 'required|date',

            // Usuario
            'name'             => 'required|string|max:100',
            'email'            => 'required|email|unique:users,email',
            'password'         => ['required', 'string', 'confirmed', Password::defaults()],

            // Entrenador
            'especialidad'     => 'required|array|min:1',
            'experiencia'      => 'required|string|max:255',
            'disponibilidad'   => 'required|array|min:1',
            'estado'           => 'required|in:activo,inactivo',

            // Foto (opcional): misma columna/disco que usa "Usuarios del
            // Sistema" (users.foto, disco 'privado') — se sirve públicamente
            // para la home a través de una ruta dedicada (ver fotoPublica()).
            'foto'             => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $persona = Persona::create([
            'nombre_completo'  => $request->nombre_completo,
            'apellido_paterno' => $request->apellido_paterno,
            'apellido_materno' => $request->apellido_materno,
            'ci'               => $request->ci,
            'telefono'         => $request->telefono,
            'genero'           => $request->genero,
            'fecha_nacimiento' => $request->fecha_nacimiento,
        ]);

        $rolEnt = Rol::where('nombre', 'Entrenador')->first();

        $fotoPath = null;
        if ($request->hasFile('foto')) {
            $fotoPath = $request->file('foto')->store('fotos_perfil', 'privado');
        }

        $usuario = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'rol_id'   => $rolEnt->id,
            'foto'     => $fotoPath,
            // Personal: verificado desde que se crea, no requiere confirmar correo.
            'email_verified_at' => now(),
        ]);

        Entrenador::create([
            'id_persona'     => $persona->id_persona,
            'id_usuario'     => $usuario->id,
            'especialidad'   => implode(', ', $request->especialidad),
            'experiencia'    => $request->experiencia,
            'disponibilidad' => json_encode($request->disponibilidad),
            'estado'         => $request->estado,
        ]);

        return redirect()->route('entrenadores.index')->with('success', 'Entrenador registrado correctamente.');
    }

    public function edit($id)
    {
        $entrenador = Entrenador::with(['persona', 'user'])->findOrFail($id);

        // Pasar arrays para los selects múltiples
        $entrenador->disponibilidad = json_decode($entrenador->disponibilidad, true);
        $entrenador->especialidad = explode(', ', $entrenador->especialidad ?? '');

        return view('entrenadores.edit', compact('entrenador'));
    }

    public function update(Request $request, $id)
    {
        $entrenador = Entrenador::with(['persona', 'user'])->findOrFail($id);
        $persona = $entrenador->persona;
        $usuario = $entrenador->user;

        $request->validate([
            'nombre_completo'  => 'required|string|max:255',
            'apellido_paterno' => 'required|string|max:255',
            'apellido_materno' => 'required|string|max:255',
            'ci'               => 'required|unique:personas,ci,' . $persona->id_persona . ',id_persona',
            'telefono'         => 'required|regex:/^\+\d{7,15}$/|unique:personas,telefono,' . $persona->id_persona . ',id_persona',
            'genero'           => 'required|in:masculino,femenino,otro',
            'fecha_nacimiento' => 'required|date',

            'name'             => 'required|string|max:100',
            'email'            => 'required|email|unique:users,email,' . $usuario->id . ',id',

            'especialidad'     => 'required|array|min:1',
            'experiencia'      => 'required|string|max:255',
            'disponibilidad'   => 'required|array|min:1',
            'estado'           => 'required|in:activo,inactivo',

            'foto'             => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',

            // Opcional: cambiar la contraseña desde la edición. Sin
            // 'confirmed': este formulario tiene un solo input de
            // contraseña (ver entrenadores/edit.blade.php).
            'password'         => ['nullable', 'string', Password::defaults()],
        ]);

        $persona->update($request->only([
            'nombre_completo', 'apellido_paterno', 'apellido_materno',
            'ci', 'telefono', 'genero', 'fecha_nacimiento'
        ]));

        $usuarioData = [
            'name'  => $request->name,
            'email' => $request->email,
        ];

        if ($request->filled('password')) {
            $usuarioData['password'] = Hash::make($request->password);
        }

        if ($request->hasFile('foto')) {
            if ($usuario->foto) {
                Storage::disk('privado')->delete($usuario->foto);
            }
            $usuarioData['foto'] = $request->file('foto')->store('fotos_perfil', 'privado');
        }

        $usuario->update($usuarioData);

        $entrenador->update([
            'especialidad'   => implode(', ', $request->especialidad),
            'experiencia'    => $request->experiencia,
            'disponibilidad' => json_encode($request->disponibilidad),
            'estado'         => $request->estado,
        ]);

        return redirect()->route('entrenadores.index')->with('success', 'Entrenador actualizado correctamente.');
    }

    /**
     * Sirve la foto del entrenador para la home PÚBLICA (sin auth): el
     * archivo sigue viviendo en el disco 'privado' (misma columna/carpeta
     * que "Usuarios del Sistema" usa para el resto de los roles, incluido el
     * kiosko de asistencia), pero esta ruta es la única forma de leerlo sin
     * sesión, y solo entrega la imagen — nunca ningún otro dato del usuario.
     */
    public function fotoPublica(Entrenador $entrenador)
    {
        $foto = $entrenador->user?->foto;

        abort_unless($foto && Storage::disk('privado')->exists($foto), 404);

        return Storage::disk('privado')->response($foto);
    }

    public function destroy($id)
    {
        // Eliminación lógica
        $entrenador = Entrenador::findOrFail($id);
        $entrenador->deleted_at = now();
        $entrenador->save();

        return redirect()->route('entrenadores.index')->with('success', 'Entrenador eliminado correctamente.');
    }
}
