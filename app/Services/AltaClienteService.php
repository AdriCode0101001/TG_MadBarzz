<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\Persona;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Crea Persona + User (rol Cliente) + Cliente coordinados, en una sola
 * transacción — usado por el auto-registro público (ver
 * RegisteredUserController::store()).
 *
 * El alta de cliente que hace el personal (ClienteController) NO usa este
 * servicio: mantiene su propia lógica intacta a propósito, porque difiere en
 * dos puntos: ahí el staff elige el "estado" del cliente, y la sede la toma
 * sola el trait BelongsToSede de la sede del staff logueado. En el registro
 * público no hay staff logueado (nadie para inferir sede) y el cliente
 * siempre arranca "activo", así que este servicio fija ambas cosas
 * explícitamente en vez de heredarlas.
 */
class AltaClienteService
{
    public function __construct(private readonly UsernameService $usernames)
    {
    }

    /**
     * @param  array{
     *     nombre_completo: string,
     *     apellido_paterno: string,
     *     apellido_materno: string,
     *     ci: string,
     *     telefono: string,
     *     genero: string,
     *     fecha_nacimiento: string,
     *     id_sede: int,
     *     email: string,
     *     password: string,
     * }  $datos  Ya validado por el llamador (ver RegisteredUserController::store()).
     */
    public function registrarDesdeFormularioPublico(array $datos): Cliente
    {
        return DB::transaction(function () use ($datos) {
            $persona = Persona::create([
                'nombre_completo'  => $datos['nombre_completo'],
                'apellido_paterno' => $datos['apellido_paterno'],
                'apellido_materno' => $datos['apellido_materno'],
                'ci'               => $datos['ci'],
                'telefono'         => $datos['telefono'],
                'genero'           => $datos['genero'],
                'fecha_nacimiento' => $datos['fecha_nacimiento'],
            ]);

            $usuario = User::create([
                // Username generado, igual que en el alta del personal: el
                // cliente no elige el suyo.
                'name'     => $this->usernames->generar(
                    $datos['nombre_completo'],
                    $datos['apellido_paterno'],
                    $datos['apellido_materno']
                ),
                'email'    => $datos['email'],
                'password' => Hash::make($datos['password']),
                'rol_id'   => Rol::firstOrCreate(['nombre' => 'Cliente'])->id,
                // La tabla "users" tiene id_sede con default(1) a nivel de BD
                // — sin esto, el usuario quedaría con la sede por defecto
                // aunque el cliente haya elegido otra, y SedeScope (que
                // filtra las consultas de Cliente por Auth::user()->id_sede)
                // lo dejaría sin ver su propio registro.
                'id_sede'  => $datos['id_sede'],
            ]);

            $cliente = Cliente::create([
                'id_persona'     => $persona->id_persona,
                'id_usuario'     => $usuario->id,
                'dias_asistidos' => 0,
                'estado'         => 'activo',
                'id_sede'        => $datos['id_sede'],
                // qr_token se autogenera solo (hook creating() del modelo).
            ]);

            $cliente->setRelation('user', $usuario);
            $cliente->setRelation('persona', $persona);

            return $cliente;
        });
    }
}
