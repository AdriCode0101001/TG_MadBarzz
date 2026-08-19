<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Activitylog\Models\Activity;

/**
 * Bitácora de Auditoría — Etapa 1: limpieza puntual de los ~117 registros
 * YA guardados antes de esta corrección. NO borra ninguna fila del
 * historial (eso perdería el rastro de que la acción ocurrió) — solo
 * reemplaza el VALOR de los campos sensibles (huella_digital, qr_token)
 * dentro del JSON "properties" de cada fila afectada, en 'attributes' y en
 * 'old', dejando todo lo demás intacto.
 *
 * Confirmado contra la base real antes de escribir esto: 21 filas de
 * Cliente tienen la clave "huella_digital" en properties (todas con valor
 * null en este entorno — pero la clave existía, y en producción podría no
 * estarlo); 20 filas tienen "qr_token" con su UUID real expuesto.
 */
class LimpiarCamposSensiblesBitacoraCommand extends Command
{
    /** Campos a redactar, por modelo (subject_type => [campos]). */
    private const CAMPOS_SENSIBLES = [
        \App\Models\Cliente::class => ['huella_digital', 'qr_token'],
    ];

    private const MARCA_REDACTADO = '[dato sensible removido — Bitácora Etapa 1]';

    protected $signature = 'bitacora:limpiar-campos-sensibles
                            {--dry-run : Solo mostrar cuántas filas se modificarían, sin escribir nada}';

    protected $description = 'Redacta campos sensibles (huella_digital, qr_token) ya guardados en activity_log, sin borrar ninguna fila.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $totalModificadas = 0;

        foreach (self::CAMPOS_SENSIBLES as $subjectType => $campos) {
            $query = Activity::where('subject_type', $subjectType)
                ->where(function ($q) use ($campos) {
                    foreach ($campos as $campo) {
                        $q->orWhereJsonContainsKey("properties->attributes->{$campo}")
                          ->orWhereJsonContainsKey("properties->old->{$campo}");
                    }
                });

            $filas = $query->get();

            foreach ($filas as $fila) {
                $props = $fila->properties->toArray();
                $cambio = false;

                foreach (['attributes', 'old'] as $seccion) {
                    foreach ($campos as $campo) {
                        if (array_key_exists($campo, $props[$seccion] ?? [])) {
                            $props[$seccion][$campo] = self::MARCA_REDACTADO;
                            $cambio = true;
                        }
                    }
                }

                if (!$cambio) {
                    continue;
                }

                $totalModificadas++;

                if ($dryRun) {
                    $this->line("  [DRY-RUN] Se redactaría activity_log #{$fila->id} ({$subjectType}#{$fila->subject_id})");
                    continue;
                }

                $fila->properties = $props;
                $fila->saveQuietly(); // sin disparar eventos propios de Activity
                $this->line("  → Redactada activity_log #{$fila->id} ({$subjectType}#{$fila->subject_id})");
            }
        }

        $resumen = $dryRun
            ? "[DRY-RUN] Se redactarían {$totalModificadas} filas (ninguna se tocó de verdad)."
            : "Redactadas {$totalModificadas} filas. Ninguna fila fue eliminada.";

        $this->info($resumen);

        return self::SUCCESS;
    }
}
