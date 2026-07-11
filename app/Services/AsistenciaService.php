<?php

namespace App\Services;

use App\Models\Asistencia;
use App\Models\Cliente;
use App\Models\MembresiaCliente;
use App\Models\Scopes\SedeScope;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Reglas de negocio compartidas para registrar la entrada de un cliente.
 * Las usan el agente biométrico (API) y la pantalla de registro por QR (web):
 * cliente activo, acceso a la sede, membresía vigente y anti-duplicado.
 */
class AsistenciaService
{
    public const OK                 = 'ok';
    public const CLIENTE_INACTIVO   = 'cliente_inactivo';
    public const SEDE_NO_AUTORIZADA = 'sede_no_autorizada';
    public const SIN_MEMBRESIA      = 'sin_membresia';
    public const MEMBRESIA_VENCIDA  = 'membresia_vencida';
    public const DUPLICADO          = 'duplicado';
    public const YA_MARCO_HOY       = 'ya_marco_hoy';
    public const MEMBRESIA_PENDIENTE_PAGO = 'membresia_pendiente_pago';

    public const MENSAJE_YA_MARCO_HOY = 'Ya registraste tu asistencia hoy. ¡Buen entrenamiento!';

    /**
     * @return array{permitido: bool, motivo: string, mensaje: string, asistencia: ?Asistencia, ventana: int}
     */
    public function registrarEntrada(Cliente $cliente, int $idSede, ?Carbon $fechaHora = null): array
    {
        $ventana = (int) config('biometria.ventana_duplicado_minutos', 5);

        if ($cliente->estado !== 'activo') {
            return $this->resultado(self::CLIENTE_INACTIVO, 'Cliente no encontrado o inactivo.', $ventana);
        }

        // Política de acceso por sede:
        //   - Sin filas en cliente_sede → acceso libre a cualquier sede.
        //   - Con filas → solo accede a las sedes listadas.
        $tieneRestricciones = DB::table('cliente_sede')
            ->where('id_cliente', $cliente->id_cliente)
            ->exists();

        if ($tieneRestricciones) {
            $tieneAcceso = DB::table('cliente_sede')
                ->where('id_cliente', $cliente->id_cliente)
                ->where('id_sede', $idSede)
                ->exists();

            if (!$tieneAcceso) {
                return $this->resultado(self::SEDE_NO_AUTORIZADA, 'El cliente no tiene acceso a esta sede.', $ventana);
            }
        }

        // Regla de negocio: solo se registra asistencia con membresía vigente.
        // El criterio vive en Cliente::membresiaMasReciente()/tieneMembresiaVigente()
        // y es el mismo que usan las rutinas y el portal del cliente.
        $ultimaMembresia = $cliente->membresiaMasReciente();

        if (!$ultimaMembresia) {
            return $this->resultado(
                self::SIN_MEMBRESIA,
                'No tienes una membresía activa. Pasa por recepción para adquirir una.',
                $ventana
            );
        }

        $vencida = Carbon::parse($ultimaMembresia->fecha_fin)->toDateString()
                   < now()->toDateString();

        if ($vencida) {
            return $this->resultado(
                self::MEMBRESIA_VENCIDA,
                'Tu membresía está vencida. Pasa por recepción para renovarla.',
                $ventana
            );
        }

        // "Sin pago no hay membresía activa": una membresía asignada pero sin
        // su pago vinculado no da acceso, aunque su fecha aún no haya vencido.
        if ($ultimaMembresia->estado !== MembresiaCliente::ESTADO_ACTIVA) {
            return $this->resultado(
                self::MEMBRESIA_PENDIENTE_PAGO,
                'Tu membresía está pendiente de pago. Pasa por recepción para completarlo y activarla.',
                $ventana
            );
        }

        // Regla: una sola asistencia por día calendario (hora local de la app)
        // en la sede. Caso informativo, no error.
        $yaMarcoHoy = Asistencia::withoutGlobalScope(SedeScope::class)
            ->where('id_cliente', $cliente->id_cliente)
            ->where('id_sede', $idSede)
            ->whereDate('fecha_hora_entrada', now()->toDateString())
            ->exists();

        if ($yaMarcoHoy) {
            return $this->resultado(self::YA_MARCO_HOY, self::MENSAJE_YA_MARCO_HOY, $ventana);
        }

        // Anti-duplicado por ventana de minutos (cubre además el caso borde
        // de dos marcaciones seguidas a ambos lados de la medianoche).
        $desde = now()->subMinutes($ventana);

        $yaExiste = Asistencia::withoutGlobalScope(SedeScope::class)
            ->where('id_cliente', $cliente->id_cliente)
            ->where('id_sede', $idSede)
            ->where('fecha_hora_entrada', '>=', $desde)
            ->exists();

        if ($yaExiste) {
            return $this->resultado(
                self::DUPLICADO,
                "El cliente ya registró asistencia en los últimos {$ventana} minutos.",
                $ventana
            );
        }

        $asistencia = Asistencia::create([
            'id_cliente'         => $cliente->id_cliente,
            'id_sede'            => $idSede,
            'fecha_hora_entrada' => ($fechaHora ?? now())->toDateTimeString(),
        ]);

        return $this->resultado(self::OK, 'Asistencia registrada.', $ventana, $asistencia);
    }

    private function resultado(string $motivo, string $mensaje, int $ventana, ?Asistencia $asistencia = null): array
    {
        return [
            'permitido'  => $motivo === self::OK,
            'motivo'     => $motivo,
            'mensaje'    => $mensaje,
            'asistencia' => $asistencia,
            'ventana'    => $ventana,
        ];
    }
}
