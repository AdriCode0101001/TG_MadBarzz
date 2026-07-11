<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\Descuento;
use App\Models\Membresia;
use Illuminate\Support\Carbon;

/**
 * Reglas de negocio para asignar membresías:
 *  - fecha_inicio: SIEMPRE la fecha del registro (el servidor la impone).
 *  - fecha_fin: duración según tipo_membresia + 1 DÍA DE REGALO.
 *  - precio_final: precio de la membresía menos el descuento elegido.
 *
 * ACUMULACIÓN: si el cliente ya tiene una membresía vigente al pagar otra, la
 * nueva NO reemplaza ni pisa la vigente: arranca el día siguiente al fin de la
 * vigente y NO lleva día de regalo (el regalo solo aplica al arrancar desde
 * cero). Así el cliente nunca pierde los días que le quedaban.
 *
 * El controlador ignora cualquier fecha enviada desde el formulario y
 * usa exclusivamente lo calculado aquí.
 */
class MembresiaService
{
    /** Día extra de regalo que se suma a toda membresía que arranca desde cero. */
    public const DIAS_REGALO = 1;

    /**
     * @return array{fecha_inicio: string, fecha_fin: string}
     */
    public function calcularFechas(Membresia $membresia, ?Carbon $inicio = null): array
    {
        $inicio = ($inicio ?? now())->copy()->startOfDay();

        $fin = $this->sumarDuracion($inicio->copy(), $membresia->tipo_membresia)
            ->addDays(self::DIAS_REGALO);

        return [
            'fecha_inicio' => $inicio->toDateString(),
            'fecha_fin'    => $fin->toDateString(),
        ];
    }

    /**
     * Fechas cuando la membresía se ACUMULA sobre una vigente: arranca el día
     * siguiente al fin de la vigente y su duración se cuenta desde ahí.
     * SIN día de regalo (ya lo tuvo la primera membresía).
     *
     * @return array{fecha_inicio: string, fecha_fin: string}
     */
    public function calcularFechasAcumuladas(Membresia $membresia, Carbon $finVigente): array
    {
        $inicio = $finVigente->copy()->startOfDay()->addDay();

        $fin = $this->sumarDuracion($inicio->copy(), $membresia->tipo_membresia);

        return [
            'fecha_inicio' => $inicio->toDateString(),
            'fecha_fin'    => $fin->toDateString(),
        ];
    }

    /**
     * Decide las fechas de la NUEVA membresía según el estado del cliente al
     * momento de pagar. El SERVIDOR es la fuente de verdad de esta decisión:
     *  - con membresía vigente → acumula (arranca tras el fin actual, sin regalo)
     *  - sin membresía vigente  → flujo normal (arranca hoy, con día de regalo)
     *
     * @return array{fecha_inicio: string, fecha_fin: string, acumulada: bool}
     */
    public function calcularFechasSegunCliente(Membresia $membresia, Cliente $cliente): array
    {
        if ($cliente->tieneMembresiaVigente()) {
            $vigente = $cliente->membresiaMasReciente();

            return $this->calcularFechasAcumuladas(
                $membresia,
                Carbon::parse($vigente->fecha_fin)
            ) + ['acumulada' => true];
        }

        return $this->calcularFechas($membresia) + ['acumulada' => false];
    }

    public function precioFinal(Membresia $membresia, ?Descuento $descuento = null): float
    {
        $porcentaje = (float) ($descuento?->porcentaje ?? 0);

        return round((float) $membresia->precio * (1 - $porcentaje / 100), 2);
    }

    /**
     * Duración según el tipo de membresía. Los meses se suman sin desbordar
     * (31 de enero + 1 mes = 28/29 de febrero, no 3 de marzo).
     */
    private function sumarDuracion(Carbon $fecha, string $tipo): Carbon
    {
        $tipo = mb_strtolower(trim($tipo));

        return match (true) {
            str_contains($tipo, 'anual')      => $fecha->addMonthsNoOverflow(12),
            str_contains($tipo, 'semestral')  => $fecha->addMonthsNoOverflow(6),
            str_contains($tipo, 'trimestral') => $fecha->addMonthsNoOverflow(3),
            str_contains($tipo, 'quincenal')  => $fecha->addDays(15),
            str_contains($tipo, 'semanal')    => $fecha->addDays(7),
            str_contains($tipo, 'diari')      => $fecha->addDay(),
            // Mensual y cualquier tipo futuro no reconocido: un mes.
            default                           => $fecha->addMonthsNoOverflow(1),
        };
    }
}
