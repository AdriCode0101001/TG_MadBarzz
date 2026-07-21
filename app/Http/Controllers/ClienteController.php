<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Persona;
use App\Models\Rol;
use App\Models\User;
use App\Models\MembresiaCliente;
use App\Models\Asistencia;
use App\Models\Pago;
use App\Services\UsernameService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ClienteController extends Controller
{
    // INDEX
public function index(Request $request)
{
    $buscar = $request->input('buscar');

    $clientes = Cliente::with(['persona', 'user'])
        ->when($buscar, function($query, $buscar) {
            $query->whereHas('persona', function($q) use ($buscar) {
                $q->whereRaw("CONCAT(nombre_completo, ' ', apellido_paterno, ' ', apellido_materno) LIKE ?", ["%{$buscar}%"])
                  ->orWhere('ci', 'LIKE', "%{$buscar}%");
            })->orWhereHas('user', function($q) use ($buscar) {
                $q->where('email', 'LIKE', "%{$buscar}%");
            });
        })
        ->get();

    return view('clientes.index', compact('clientes'));
}


    // CREATE
    public function create()
    {
        return view('clientes.create');
    }

    // STORE
    public function store(Request $request, UsernameService $usernames)
    {
        // El teléfono llega ya normalizado a formato internacional por el
        // middleware NormalizarTelefono (arma codigo+numero en el servidor).
        $request->validate([
            // Persona
            'nombre_completo'    => 'required|string|max:255',
            'apellido_paterno'   => 'required|string|max:255',
            'apellido_materno'   => 'required|string|max:255',
            'ci'                 => 'required|unique:personas,ci',
            'telefono'           => 'required|regex:/^\+\d{7,15}$/|unique:personas,telefono',
            'genero'             => 'required|in:masculino,femenino,otro',
            'fecha_nacimiento'   => 'required|date',

            // Usuario (el username NO se valida: se genera automáticamente)
            'email'              => 'required|email|unique:users,email',
            'password'           => 'required|string|min:6|confirmed',

            // Cliente (dias_asistidos y huella_digital ya no vienen del formulario)
            'estado'             => 'required|in:activo,inactivo',
        ]);

        $persona = Persona::create([
            'nombre_completo'    => $request->nombre_completo,
            'apellido_paterno'   => $request->apellido_paterno,
            'apellido_materno'   => $request->apellido_materno,
            'ci'                 => $request->ci,
            'telefono'           => $request->telefono,
            'genero'             => $request->genero,
            'fecha_nacimiento'   => $request->fecha_nacimiento,
        ]);

        $usuario = User::create([
            // Username generado: primer nombre.apellido paterno.inicial materno
            'name'     => $usernames->generar(
                $request->nombre_completo,
                $request->apellido_paterno,
                $request->apellido_materno
            ),
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'rol_id'   => Rol::firstOrCreate(['nombre' => 'Cliente'])->id,
            // Verificado de entrada: lo da de alta el personal en persona, no
            // hace falta que confirme su correo (a diferencia del
            // auto-registro público, ver AltaClienteService).
            'email_verified_at' => now(),
            // La tabla "users" tiene id_sede con default(1) a nivel de BD —
            // sin esto, el usuario del cliente quedaría con la sede por
            // defecto aunque Cliente.id_sede sea otra (BelongsToSede la toma
            // de Auth::user()->id_sede más abajo), y SedeScope lo dejaría
            // sin ver su propio registro en cuanto la sede real no sea la 1.
            'id_sede'  => Auth::user()->id_sede,
        ]);

        Cliente::create([
            'id_persona'     => $persona->id_persona,
            'dias_asistidos' => 0, // siempre 0 al crear: el cliente recién empieza
            'estado'         => $request->estado,
            'id_usuario'     => $usuario->id,
        ]);

        return redirect()->route('clientes.index')->with('success', 'Cliente, persona y usuario registrados correctamente.');
    }


    // EDIT
    public function edit($id)
    {
        $cliente = Cliente::with(['persona', 'user'])->findOrFail($id);

        if (!$cliente->persona || !$cliente->user) {
            return redirect()->route('clientes.index')->with('error', 'Este cliente no tiene relaciones completas.');
        }

        return view('clientes.edit', compact('cliente'));
    }

    // UPDATE
    public function update(Request $request, $id)
    {
        $cliente = Cliente::with(['persona', 'user'])->findOrFail($id);
        $persona = $cliente->persona;
        $usuario = $cliente->user;

        $request->validate([
            'nombre_completo'    => 'required|string|max:255',
            'apellido_paterno'   => 'required|string|max:255',
            'apellido_materno'   => 'required|string|max:255',
            'ci'                 => 'required|unique:personas,ci,' . $persona->id_persona . ',id_persona',
            'telefono'           => 'required|regex:/^\+\d{7,15}$/|unique:personas,telefono,' . $persona->id_persona . ',id_persona',
            'genero'             => 'required|in:masculino,femenino,otro',
            'fecha_nacimiento'   => 'required|date',

            // El username sí es editable aquí, pero debe ser único
            'name'               => 'required|string|max:100|unique:users,name,' . $usuario->id . ',id',
            'email'              => 'required|email|unique:users,email,' . $usuario->id . ',id',

            'dias_asistidos'     => 'required|integer|min:0',
            'estado'             => 'required|in:activo,inactivo',
        ]);

        $persona->update([
            'nombre_completo'    => $request->nombre_completo,
            'apellido_paterno'   => $request->apellido_paterno,
            'apellido_materno'   => $request->apellido_materno,
            'ci'                 => $request->ci,
            'telefono'           => $request->telefono,
            'genero'             => $request->genero,
            'fecha_nacimiento'   => $request->fecha_nacimiento,
        ]);

        $usuario->update([
            'name'  => $request->name,
            'email' => $request->email,
        ]);

        if ($request->filled('password')) {
            $usuario->update([
                'password' => Hash::make($request->password),
            ]);
        }

        $cliente->update([
            'dias_asistidos' => $request->dias_asistidos,
            'estado'         => $request->estado,
        ]);

        return redirect()->route('clientes.index')->with('success', 'Cliente actualizado correctamente.');
    }

    // DESTROY

    public function destroy(Cliente $cliente)
    {
        // Eliminar el cliente (soft delete)
        $cliente->delete();

        // Eliminar la persona asociada
        if ($cliente->persona) {
            $cliente->persona->delete();
        }

        // Eliminar el usuario asociado
        if ($cliente->user) {
            $cliente->user->delete();
        }

        return redirect()->route('clientes.index')->with('success', 'Cliente, persona y usuario eliminados lógicamente.');
    }

    // ==============================
    // SHOW (Perfil del cliente logueado)
    // ==============================

    public function show()
    {
        // Obtener el cliente asociado al usuario logueado
        $cliente = Cliente::with(['persona', 'user', 'membresias'])
                    ->where('id_usuario', Auth::id())
                    ->first();

        if (!$cliente) {
            return redirect()->back()->with('error', 'No tienes un perfil de cliente asociado.');
        }

        // Membresía activa más reciente (vigente por fecha y con pago registrado)
        $membresia = $cliente->membresias()
                            ->wherePivot('fecha_fin', '>=', now())
                            ->wherePivot('estado', 'activa')
                            ->orderByPivot('fecha_fin', 'desc')
                            ->first();

        // Asistencias
        $asistencias = Asistencia::where('id_cliente', $cliente->id)->get();

        // Pagos
        $pagos = Pago::where('id_cliente', $cliente->id)->get();

        return view('clientes.show', compact('cliente', 'membresia', 'asistencias', 'pagos'));
    }

}
