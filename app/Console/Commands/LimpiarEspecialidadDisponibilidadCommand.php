<?php

namespace App\Console\Commands;

use App\Models\Entrenador;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Revisión general de pantallas (detección de EntrenadorController::edit()
 * roto): antes de este arreglo, store()/update() guardaban especialidad
 * como texto separado por comas y disponibilidad como JSON — pero el
 * modelo Entrenador ya castea ambas columnas como 'array', así que
 * Eloquent las volvía a codificar ENCIMA de eso (JSON dentro de JSON).
 * edit() compensaba decodificando dos veces, así que "funcionaba" para
 * datos guardados por ese flujo, pero reventaba (TypeError) ante
 * cualquier registro con el array limpio que el propio cast del modelo
 * espera.
 *
 * Este comando, de una sola corrida, normaliza los registros viejos
 * (formato de texto/JSON doble) al array limpio que el modelo ya
 * castea solo. Seguro de correr más de una vez: un registro que ya
 * quedó como array limpio se detecta y se deja igual.
 */
class LimpiarEspecialidadDisponibilidadCommand extends Command
{
    protected $signature = 'entrenadores:limpiar-especialidad
                            {--dry-run : Solo mostrar qué se normalizaría, sin escribir nada}';

    protected $description = 'Normaliza especialidad/disponibilidad de Entrenador al formato de array limpio que el modelo castea.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $filas = DB::table('entrenadores')->select('id_entrenador', 'especialidad', 'disponibilidad')->get();

        $normalizados = 0;
        $yaLimpios    = 0;

        foreach ($filas as $fila) {
            $especialidadLimpia   = $this->normalizarEspecialidad($fila->especialidad);
            $disponibilidadLimpia = $this->normalizarDisponibilidad($fila->disponibilidad);

            $yaEstabaLimpia = $this->esArrayLimpio($fila->especialidad) && $this->esArrayLimpio($fila->disponibilidad);

            if ($yaEstabaLimpia) {
                $yaLimpios++;
                continue;
            }

            $normalizados++;

            if ($dryRun) {
                $this->line("  [DRY-RUN] Se normalizaría entrenador #{$fila->id_entrenador}: especialidad=" .
                    json_encode($especialidadLimpia) . ' disponibilidad=' . json_encode($disponibilidadLimpia));
                continue;
            }

            DB::table('entrenadores')->where('id_entrenador', $fila->id_entrenador)->update([
                'especialidad'   => json_encode($especialidadLimpia, JSON_UNESCAPED_UNICODE),
                'disponibilidad' => json_encode($disponibilidadLimpia, JSON_UNESCAPED_UNICODE),
            ]);

            $this->line("  → Normalizado entrenador #{$fila->id_entrenador}");
        }

        $resumen = $dryRun
            ? "[DRY-RUN] Se normalizarían {$normalizados}. Ya estaban limpios: {$yaLimpios}."
            : "Normalizados: {$normalizados}. Ya estaban limpios: {$yaLimpios}.";

        $this->info($resumen);

        return self::SUCCESS;
    }

    private function esArrayLimpio(?string $valor): bool
    {
        return is_array(json_decode((string) $valor, true));
    }

    /** "Yoga, Pilates" (posiblemente envuelto en JSON de más) → ['Yoga', 'Pilates']. */
    private function normalizarEspecialidad(?string $valor): array
    {
        $decodificado = json_decode((string) $valor, true);

        if (is_array($decodificado)) {
            return array_values($decodificado);
        }

        $texto = is_string($decodificado) ? $decodificado : (string) $valor;

        return array_values(array_filter(array_map('trim', explode(',', $texto))));
    }

    /** JSON doble-codificado → ['Lunes', 'Martes']. */
    private function normalizarDisponibilidad(?string $valor): array
    {
        $decodificado = json_decode((string) $valor, true);

        if (is_array($decodificado)) {
            return array_values($decodificado);
        }

        if (is_string($decodificado)) {
            $segundoNivel = json_decode($decodificado, true);
            if (is_array($segundoNivel)) {
                return array_values($segundoNivel);
            }
        }

        return [];
    }
}
