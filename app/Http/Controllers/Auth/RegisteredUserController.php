<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Sede;
use App\Services\AltaClienteService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Edad mínima para auto-registrarse sin intervención del personal: el
     * auto-registro web es SOLO para adultos (18+). Los menores de edad los
     * inscribe el personal presencialmente en el gimnasio (ClienteController,
     * que no tiene este piso — ahí hay un humano al lado y, para biometría de
     * menores, se exige consentimiento del tutor registrado).
     */
    private const EDAD_MINIMA = 18;

    /**
     * Display the registration view (pantalla combinada login + registro,
     * con la pestaña "Crear cuenta" activa).
     */
    public function create(): View
    {
        return view('auth.login-register', [
            'tabActiva' => 'register',
            'sedes'     => Sede::where('activo', true)->orderBy('nombre')->get(),
        ]);
    }

    /**
     * Registra un cliente COMPLETO (persona + user + cliente, sin
     * membresía) — mismas reglas de CI/teléfono que usa el alta del
     * personal (ClienteController), para que quede consistente. El rol
     * siempre es Cliente: no viene del formulario, lo fuerza
     * AltaClienteService.
     *
     * Etapa 4 (endurecimiento antes de reactivar el registro público):
     * además de lo de Etapa 1, se valida formato de nombre/CI, que la fecha
     * de nacimiento no sea futura ni corresponda a un menor de
     * EDAD_MINIMA años, y que la sede exista Y esté activa (antes solo se
     * chequeaba que existiera — se podía mandar el id de una sede
     * desactivada adivinando el número). El límite de intentos por minuto
     * vive en el propio middleware de la ruta ('throttle:register', ver
     * routes/auth.php y RateLimiter::for('register') en bootstrap/app.php).
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request, AltaClienteService $altaCliente): RedirectResponse
    {
        $datos = $request->validate([
            // Persona (mismas reglas que ClienteController::store, más el
            // formato de nombre/CI y los límites de fecha de nacimiento que
            // acá sí hacen falta porque no hay personal supervisando el alta)
            'nombre_completo'    => ['required', 'string', 'max:255', 'regex:/^[\pL\s\'\-]+$/u'],
            'apellido_paterno'   => ['required', 'string', 'max:255', 'regex:/^[\pL\s\'\-]+$/u'],
            'apellido_materno'   => ['required', 'string', 'max:255', 'regex:/^[\pL\s\'\-]+$/u'],
            'ci'                 => ['required', 'regex:/^[A-Za-z0-9\-]{5,15}$/', 'unique:personas,ci'],
            'telefono'           => ['required', 'regex:/^\+\d{7,15}$/', 'unique:personas,telefono'],
            'genero'             => ['required', 'in:masculino,femenino,otro'],
            'fecha_nacimiento'   => [
                'required',
                'date',
                'before:today',
                'after:1900-01-01',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (Carbon::parse($value)->age < self::EDAD_MINIMA) {
                        $fail('Debés tener al menos ' . self::EDAD_MINIMA . ' años para registrarte.');
                    }
                },
            ],

            // Sede elegida por el cliente (en el alta del personal la infiere
            // BelongsToSede de la sede del staff logueado; acá no hay staff).
            // "activo,1": no alcanza con que exista, tiene que estar activa —
            // si no, alcanzaba con adivinar el id de una sede dada de baja.
            'id_sede'            => ['required', 'integer', 'exists:sedes,id_sede,activo,1'],

            // Usuario (el username NO se valida: se genera automático, igual
            // que en el alta del personal)
            'email'              => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password'           => ['required', 'confirmed', Rules\Password::defaults()],

            // Grupo 3 legal, parte 1: sin esta casilla marcada, no hay
            // registro — 'accepted' exige exactamente "1"/"true"/"yes"/"on",
            // así que un checkbox SIN marcar (que no manda el campo) o
            // manipulado a mano con otro valor cae en 'required'/'accepted'
            // igual. Nunca viene premarcada (ver auth/login-register.blade.php).
            'acepto_terminos'    => ['required', 'accepted'],
        ], [
            'nombre_completo.regex'  => 'El nombre solo puede contener letras.',
            'apellido_paterno.regex' => 'El apellido paterno solo puede contener letras.',
            'apellido_materno.regex' => 'El apellido materno solo puede contener letras.',
            'ci.regex'               => 'La cédula de identidad no tiene un formato válido.',
            'fecha_nacimiento.before' => 'La fecha de nacimiento no puede ser futura.',
            'fecha_nacimiento.after'  => 'La fecha de nacimiento no es válida.',
            'id_sede.exists'          => 'La sede seleccionada no existe o no está activa.',
            'acepto_terminos.required' => 'Debés aceptar los Términos y Condiciones y la Política de Privacidad para registrarte.',
            'acepto_terminos.accepted' => 'Debés aceptar los Términos y Condiciones y la Política de Privacidad para registrarte.',
        ]);

        $cliente = $altaCliente->registrarDesdeFormularioPublico($datos);

        event(new Registered($cliente->user));

        Auth::login($cliente->user);

        return redirect(route('dashboard'));
    }
}
