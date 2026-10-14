<?php

namespace App\Http\Controllers;

use App\Models\Recepcionista;
use App\Models\Persona;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class RecepcionistaController extends Controller
{
public function index(Request $request)
{
    $buscar = $request->input('buscar');

    $recepcionistas = Recepcionista::with(['persona', 'user'])
        ->when($buscar, function($query, $buscar) {
            $query->whereHas('persona', function($q) use ($buscar) {
                $q->whereRaw("CONCAT(nombre_completo, ' ', apellido_paterno, ' ', apellido_materno) LIKE ?", ["%{$buscar}%"])
                  ->orWhere('ci', 'LIKE', "%{$buscar}%");
            })
            ->orWhereHas('user', function($q) use ($buscar) {
                $q->where('email', 'LIKE', "%{$buscar}%");
            });
        })
        ->orderByDesc('id_recepcionista')
        ->paginate(20)
        ->withQueryString();

    return view('recepcionistas.index', compact('recepcionistas'));
}

    public function create()
    {
        return view('recepcionistas.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre_completo' => 'required|string|max:255',
            'apellido_paterno' => 'required|string|max:255',
            'apellido_materno' => 'required|string|max:255',
            'ci' => 'required|string|max:20|unique:personas,ci',
            'telefono' => 'nullable|regex:/^\+\d{7,15}$/|unique:personas,telefono',
            'genero' => 'required|in:masculino,femenino,otro',
            'fecha_nacimiento' => 'required|date',

            'name' => 'required|string|max:255|unique:users,name',
            'email' => 'required|email|unique:users,email',
            'password' => ['required', 'confirmed', Password::defaults()],

            'turno' => 'required|in:mañana,tarde,noche',
            'punto_atencion' => 'required|string|max:255',
            'estado' => 'required|in:activo,inactivo'
        ], [
            'telefono.regex'  => 'El teléfono debe estar en formato internacional (ej. +59171234567).',
        ]);

        $persona = Persona::create($request->only([
            'nombre_completo', 'apellido_paterno', 'apellido_materno', 'ci', 'telefono', 'genero', 'fecha_nacimiento'
        ]));

        $rolRecep = Rol::where('nombre', 'Recepcionista')->first();

        $usuario = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'rol_id'   => $rolRecep->id,
            // Personal: verificado desde que se crea, no requiere confirmar correo.
            'email_verified_at' => now(),
        ]);

        Recepcionista::create([
            'id_persona' => $persona->id_persona,
            'id_usuario' => $usuario->id,
            'turno' => $request->turno,
            'punto_atencion' => $request->punto_atencion,
            'estado' => $request->estado
        ]);

        return redirect()->route('recepcionistas.index')->with('success', 'Recepcionista creado correctamente.');
    }

    public function edit($id)
    {
        $recepcionista = Recepcionista::with(['persona', 'user'])->findOrFail($id);
        return view('recepcionistas.edit', compact('recepcionista'));
    }

    public function update(Request $request, $id)
    {
        $recepcionista = Recepcionista::with(['persona', 'user'])->findOrFail($id);

        $request->validate([
            'nombre_completo' => 'required|string|max:255',
            'apellido_paterno' => 'required|string|max:255',
            'apellido_materno' => 'required|string|max:255',
            'ci' => 'required|string|max:20|unique:personas,ci,' . $recepcionista->persona->id_persona . ',id_persona',
            'telefono' => 'nullable|regex:/^\+\d{7,15}$/|unique:personas,telefono,' . $recepcionista->persona->id_persona . ',id_persona',
            'genero' => 'required|in:masculino,femenino,otro',
            'fecha_nacimiento' => 'required|date',

            'name' => 'required|string|max:255|unique:users,name,' . $recepcionista->user->id,
            'email' => 'required|email|unique:users,email,' . $recepcionista->user->id,

            'turno' => 'required|in:mañana,tarde,noche',
            'punto_atencion' => 'required|string|max:255',
            'estado' => 'required|in:activo,inactivo',

            // Opcional: cambiar la contraseña desde la edición. Sin
            // 'confirmed': este formulario tiene un solo input de
            // contraseña (ver recepcionistas/edit.blade.php).
            'password' => ['nullable', 'string', Password::defaults()],
        ], [
            'telefono.regex'  => 'El teléfono debe estar en formato internacional (ej. +59171234567).',
        ]);

        // Actualiza persona
        $recepcionista->persona->update($request->only([
            'nombre_completo', 'apellido_paterno', 'apellido_materno', 'ci', 'telefono', 'genero', 'fecha_nacimiento'
        ]));

        // Actualiza usuario (sin cambiar contraseña si no se envía)
        $usuarioData = [
            'name' => $request->name,
            'email' => $request->email,
        ];

        if ($request->filled('password')) {
            $usuarioData['password'] = Hash::make($request->password);
        }

        $recepcionista->user->update($usuarioData);

        // Actualiza recepcionista
        $recepcionista->update([
            'turno' => $request->turno,
            'punto_atencion' => $request->punto_atencion,
            'estado' => $request->estado
        ]);

        return redirect()->route('recepcionistas.index')->with('success', 'Recepcionista actualizado correctamente.');
    }

        public function destroy($id)
        {
            $recepcionista = Recepcionista::findOrFail($id);
            $recepcionista->deleted_at = now(); // marca como eliminado
            $recepcionista->save();

            return redirect()->route('recepcionistas.index')->with('success', 'Recepcionista eliminado correctamente.');
        }


    // En tu RecepcionistaController.php
public function getRecepcionistaActivo()
{
    $recepcionista = Recepcionista::with('persona')
        ->where('estado', 'activo')
        ->first();
    
    return $recepcionista ? $recepcionista->persona->telefono : null;
}
}
