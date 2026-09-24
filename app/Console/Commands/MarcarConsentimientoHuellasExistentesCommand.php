<?php

namespace App\Console\Commands;

use App\Models\Cliente;
use Illuminate\Console\Command;

/**
 * Grupo 3 legal, parte 2: antes de exigir consentimiento_huella_en para
 * enrolar, ya había huellas enroladas (~11 en dev) sin ese dato. Invalidar
 * esas huellas (o dejarlas sin poder re-sincronizarse) sería peor que el
 * problema que se está resolviendo, así que este comando marca como
 * "consentimiento registrado" a los clientes que YA TIENEN al menos una
 * huella — con la fecha de HOY (fecha de la migración, no la fecha real en
 * que se tomó la huella, que no quedó registrada en ningún lado) y deja
 * anotado en el propio dato que es una migración retroactiva.
 *
 * Para clientes menores con huella ya enrolada: también se marca
 * consentimiento_tutor_en, pero SIN datos del tutor (tutor_nombre/tutor_ci
 * quedan vacíos) — no hay forma de inventarlos. Quedan visibles en el panel
 * como "consentimiento migrado, sin datos del tutor" para que el personal
 * los complete.
 *
 * PENDIENTE PARA EL OPERADOR (gestión, no código): estos consentimientos
 * "migrados" deberían regularizarse en papel/verbalmente con cada cliente
 * (o su tutor, si es menor) en su próxima visita al gimnasio — este comando
 * solo evita que las huellas ya capturadas queden inutilizables de un día
 * para el otro.
 *
 * Seguro de correr más de una vez: solo toca clientes con huella Y sin
 * consentimiento_huella_en todavía.
 */
class MarcarConsentimientoHuellasExistentesCommand extends Command
{
    private const NOTA_MIGRACION = 'Consentimiento migrado automáticamente (Grupo 3 legal, parte 2): '
        . 'el cliente ya tenía una huella enrolada antes de exigir este dato. Regularizar en papel con el cliente/tutor.';

    protected $signature = 'clientes:marcar-consentimiento-huellas-existentes
                            {--dry-run : Solo mostrar qué se marcaría, sin escribir nada}';

    protected $description = 'Marca consentimiento_huella_en (y consentimiento_tutor_en si es menor) para clientes que ya tienen huella enrolada, de una sola corrida.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $ahora  = now();

        $clientes = Cliente::with('persona')
            ->whereHas('huellas')
            ->whereNull('consentimiento_huella_en')
            ->get();

        if ($clientes->isEmpty()) {
            $this->info('No hay clientes con huella enrolada y sin consentimiento — nada que migrar.');
            return self::SUCCESS;
        }

        $menores = 0;

        foreach ($clientes as $cliente) {
            $esMenor = $cliente->esMenorDeEdad();
            $etiqueta = $esMenor ? '(menor — sin datos del tutor, a completar)' : '(adulto)';

            if ($dryRun) {
                $this->line("  [DRY-RUN] Se marcaría cliente #{$cliente->id_cliente} {$etiqueta}");
                if ($esMenor) {
                    $menores++;
                }
                continue;
            }

            $datos = ['consentimiento_huella_en' => $ahora];

            if ($esMenor) {
                $datos['consentimiento_tutor_en'] = $ahora;
                $menores++;
            }

            $cliente->update($datos);

            $this->line("  → Marcado cliente #{$cliente->id_cliente} {$etiqueta}");
        }

        $resumen = $dryRun
            ? "[DRY-RUN] Se marcarían {$clientes->count()} clientes ({$menores} menores, sin datos de tutor)."
            : "Marcados {$clientes->count()} clientes ({$menores} menores, sin datos de tutor). " . self::NOTA_MIGRACION;

        $this->info($resumen);

        if (!$dryRun && $menores > 0) {
            $this->warn("⚠ {$menores} cliente(s) menor(es) quedaron con consentimiento del tutor migrado SIN nombre/CI del tutor — completalos desde el panel cuando puedas.");
        }

        return self::SUCCESS;
    }
}
