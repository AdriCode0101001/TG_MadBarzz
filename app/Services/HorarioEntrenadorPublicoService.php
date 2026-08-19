<?php

namespace App\Services;

use App\Models\HorarioEntrenador;

/**
 * Arma la grilla semanal de horarios de entrenadores para la HOME PÚBLICA
 * (Parte 2 del horario de entrenadores) a partir de lo que el Administrador
 * armó en el panel privado (tabla horarios_entrenadores, ver
 * HorarioEntrenadorController — Parte 1). Sin caché: consulta la BD en cada
 * visita, así que un cambio del admin se refleja al toque en la home.
 *
 * Mismo rango horario (07:00-21:00, franjas de 1 hora, lunes a sábado) que
 * la Parte 1, pero deliberadamente NO comparte código con
 * HorarioEntrenadorController — esa es la pantalla de gestión privada y no
 * se toca acá.
 */
class HorarioEntrenadorPublicoService
{
    public const DIAS = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado'];

    private const HORA_APERTURA = 7;
    private const HORA_CIERRE   = 21;

    /** Franjas de 1 hora dentro del rango de apertura, en formato "H:i" (ej. "07:00"). */
    public function franjas(): array
    {
        $franjas = [];
        for ($h = self::HORA_APERTURA; $h < self::HORA_CIERRE; $h++) {
            $franjas[] = sprintf('%02d:00', $h);
        }

        return $franjas;
    }

    /**
     * Filas = franjas horarias, columnas = días (mismo orden que self::DIAS).
     * Cada celda es el nombre real del entrenador asignado, o null si la
     * franja está sin asignar (la vista pública lo muestra como "Cerrado").
     *
     * @return array<string, array<int, string|null>> "07:00" => [1 => nombre|null, 2 => ...]
     */
    public function grid(int $idSede): array
    {
        $asignaciones = HorarioEntrenador::with('entrenador.persona')
            ->where('id_sede', $idSede)
            ->get()
            ->keyBy(fn (HorarioEntrenador $h) => $h->dia_semana . '_' . substr($h->hora_inicio, 0, 5));

        $grid = [];

        foreach ($this->franjas() as $hora) {
            $fila = [];
            foreach (array_keys(self::DIAS) as $dia) {
                $asignacion = $asignaciones->get($dia . '_' . $hora);
                $entrenador = optional($asignacion)->entrenador;
                $fila[$dia] = optional(optional($entrenador)->persona)->nombre_completo;
            }
            $grid[$hora] = $fila;
        }

        return $grid;
    }
}
