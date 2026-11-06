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
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ClienteController extends Controller
{
    // INDEX
public function index(Request $request)
{
    $buscar = $request->input('buscar');

    // withCount('huellas'): la columna "Huella" del listado necesita saber
    // si el cliente tiene alguna enrolada (y cuántas) — con withCount es UNA
    // sola consulta agregada para las 20 filas de la página, en vez de una
    // consulta por cliente (N+1) si cada fila preguntara $cliente->huellas
    // por su cuenta.
    $clientes = Cliente::with(['persona', 'user'])
        ->withCount('huellas')
        ->when($buscar, function($query, $buscar) {
            $query->whereHas('persona', function($q) use ($buscar) {
                $q->whereRaw("CONCAT(nombre_completo, ' ', apellido_paterno, ' ', apellido_materno) LIKE ?", ["%{$buscar}%"])
                  ->orWhere('ci', 'LIKE', "%{$buscar}%");
            })->orWhereHas('user', function($q) use ($buscar) {
                $q->where('email', 'LIKE', "%{$buscar}%");
            });
        })
        ->orderByDesc('id_cliente')
        ->paginate(20)
        ->withQueryString();

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
            'password'           => ['required', 'string', 'confirmed', Password::defaults()],

            // Cliente (dias_asistidos y huella_digital ya no vienen del formulario)
            'estado'             => 'required|in:activo,inactivo',

            // Grupo 3 legal, parte 2: consentimiento de huella (adulto) o del
            // tutor (menor) — ver aplicarConsentimientoHuella(). Ninguno de
            // los dos checkboxes es obligatorio en sí (el cliente se puede
            // crear sin huella habilitada), pero SI se tilda "el tutor
            // autoriza", los datos del tutor pasan a ser obligatorios.
            'consentimiento_huella' => ['nullable', 'boolean'],
            'consentimiento_tutor'  => ['nullable', 'boolean'],
            'tutor_nombre'          => ['nullable', 'string', 'max:255', 'required_if:consentimiento_tutor,1'],
            'tutor_ci'              => ['nullable', 'regex:/^[A-Za-z0-9\-]{5,15}$/', 'required_if:consentimiento_tutor,1'],
        ], [
            'tutor_nombre.required_if' => 'El nombre del tutor es obligatorio para autorizar la huella de un menor.',
            'tutor_ci.required_if'     => 'La CI del tutor es obligatoria para autorizar la huella de un menor.',
            'tutor_ci.regex'           => 'La cédula de identidad del tutor no tiene un formato válido.',
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
            // hace falta que confirme su correo (el
            // auto-registro público está desactivado).
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
            ...$this->datosConsentimientoHuella($request, new Cliente(), $request->fecha_nacimiento),
        ]);

        return redirect()->route('clientes.index')->with('success', 'Cliente, persona y usuario registrados correctamente.');
    }


    // EDIT
    public function edit($id)
    {
        // 'huellas' cargada para la sección de biometría (borrado, Admin
        // únicamente) — ver clientes/edit.blade.php y HuellaController.
        $cliente = Cliente::with(['persona', 'user', 'huellas'])->findOrFail($id);

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

            // Opcional acá (cambiar contraseña de un cliente desde su
            // edición): si se manda, respeta la misma política mínima que
            // el resto de los formularios de contraseña. Sin 'confirmed':
            // este formulario no tiene campo de confirmación (un solo
            // input "Nueva contraseña", ver clientes/edit.blade.php).
            'password'           => ['nullable', 'string', Password::defaults()],

            // Grupo 3 legal, parte 2 — ver el mismo bloque en store().
            'consentimiento_huella' => ['nullable', 'boolean'],
            'consentimiento_tutor'  => ['nullable', 'boolean'],
            'tutor_nombre'          => ['nullable', 'string', 'max:255', 'required_if:consentimiento_tutor,1'],
            'tutor_ci'              => ['nullable', 'regex:/^[A-Za-z0-9\-]{5,15}$/', 'required_if:consentimiento_tutor,1'],
        ], [
            'tutor_nombre.required_if' => 'El nombre del tutor es obligatorio para autorizar la huella de un menor.',
            'tutor_ci.required_if'     => 'La CI del tutor es obligatoria para autorizar la huella de un menor.',
            'tutor_ci.regex'           => 'La cédula de identidad del tutor no tiene un formato válido.',
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
            ...$this->datosConsentimientoHuella($request, $cliente, $request->fecha_nacimiento),
        ]);

        return redirect()->route('clientes.index')->with('success', 'Cliente actualizado correctamente.');
    }

    /**
     * Grupo 3 legal, parte 2: arma los 4 campos de consentimiento de huella
     * a partir del formulario, según el cliente sea adulto o menor (por
     * $fechaNacimiento, el valor que se está guardando AHORA — no el que
     * ya tenía la persona en la BD, para que un cambio de fecha de
     * nacimiento en el mismo submit ya se tenga en cuenta).
     *
     * - Adulto: usa el checkbox "consentimiento_huella" del cliente mismo.
     * - Menor: usa el checkbox "consentimiento_tutor" — un menor no puede
     *   autorizar por sí mismo, así que NO se le pide/guarda un
     *   "consentimiento_huella" propio; el consentimiento del tutor CUBRE
     *   la autorización general (puedeEnrolarHuella() en el modelo exige
     *   los dos campos igual, pero acá se setean juntos).
     * - Si el checkbox correspondiente no viene marcado, el/los campo(s)
     *   quedan en null — remueve el consentimiento si antes lo tenía
     *   (revocación explícita) en vez de mantenerlo "pegado".
     * - Si YA había un consentimiento con fecha, esa fecha se conserva
     *   (no se pisa con "ahora" en cada edición sin cambios reales).
     */
    private function datosConsentimientoHuella(Request $request, Cliente $cliente, ?string $fechaNacimiento): array
    {
        $esMenor = $fechaNacimiento && Carbon::parse($fechaNacimiento)->age < Cliente::EDAD_MINIMA_ADULTO;

        if ($esMenor) {
            $tutorAutoriza = $request->boolean('consentimiento_tutor');

            return [
                'tutor_nombre'             => $tutorAutoriza ? $request->tutor_nombre : null,
                'tutor_ci'                 => $tutorAutoriza ? $request->tutor_ci : null,
                'consentimiento_tutor_en'  => $tutorAutoriza ? ($cliente->consentimiento_tutor_en ?? now()) : null,
                'consentimiento_huella_en' => $tutorAutoriza ? ($cliente->consentimiento_huella_en ?? now()) : null,
            ];
        }

        $clienteAutoriza = $request->boolean('consentimiento_huella');

        return [
            'tutor_nombre'             => null,
            'tutor_ci'                 => null,
            'consentimiento_tutor_en'  => null,
            'consentimiento_huella_en' => $clienteAutoriza ? ($cliente->consentimiento_huella_en ?? now()) : null,
        ];
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
    // SHOW (detalle del cliente elegido en el listado, para el STAFF)
    // ==============================

    /**
     * "Ver detalle" desde el listado de clientes (Admin/Recepcionista/
     * Entrenador): muestra los datos del cliente de la URL — NO del usuario
     * logueado. Antes hacía Cliente::where('id_usuario', Auth::id()) (un
     * resabio de antes de que existiera el portal del cliente), así que
     * siempre fallaba para el staff, que no tiene ficha de Cliente propia.
     * El portal real del cliente (donde SÍ corresponde Auth::id()) es
     * ClientePortalController — no confundir, no se toca acá.
     *
     * $cliente llega resuelto por route-model binding contra la PK real de
     * Cliente (id_cliente) y ya pasa por el global scope SedeScope: un
     * Recepcionista/Entrenador de otra sede recibe 404 acá mismo (Admin ve
     * cualquier sede), igual que en index/edit.
     */
    public function show(Cliente $cliente)
    {
        $cliente->load(['persona', 'user', 'membresias']);

        // Membresía activa más reciente (vigente por fecha y con pago registrado)
        $membresia = $cliente->membresias()
                            ->wherePivot('fecha_fin', '>=', now())
                            ->wherePivot('estado', 'activa')
                            ->orderByPivot('fecha_fin', 'desc')
                            ->first();

        // Asistencias
        $asistencias = Asistencia::where('id_cliente', $cliente->id_cliente)->get();

        // Pagos
        $pagos = Pago::where('id_cliente', $cliente->id_cliente)->get();

        return view('clientes.show', compact('cliente', 'membresia', 'asistencias', 'pagos'));
    }

}
