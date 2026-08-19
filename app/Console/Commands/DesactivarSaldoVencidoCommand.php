<?php

namespace App\Console\Commands;

use App\Models\MembresiaCliente;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Pago en 2 Cuotas — Etapa 4: desactivación automática diaria de las
 * membresías cuyo saldo pendiente venció sin pagarse. Cambia SOLO el
 * "estado" a suspendida_saldo — fecha_fin nunca se toca (los días
 * suspendida se pierden, ya definido en el diseño de la funcionalidad).
 *
 * El bloqueo de acceso por huella/QR ya lo hace AsistenciaService con
 * "estado !== ESTADO_ACTIVA" (ver MEMBRESIA_PENDIENTE_PAGO): no hace falta
 * tocar nada ahí, cambiar el estado acá alcanza para cortar el acceso.
 */
class DesactivarSaldoVencidoCommand extends Command
{
    protected $signature = 'membresias:desactivar-saldo-vencido
                            {--dry-run : Solo mostrar/loguear qué se desactivaría, sin ejecutar ningún cambio}';

    protected $description = 'Suspende (suspendida_saldo) las membresías activas cuyo saldo pendiente venció sin pagarse.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        // Los tres filtros son necesarios: saldo real (>0), plazo YA vencido
        // (fecha_limite_saldo < hoy, no <=: el día del vencimiento todavía
        // cuenta como a tiempo, mismo criterio que usa el resto del sistema
        // para "vencida" en AsistenciaService/Cliente::tieneMembresiaVigente),
        // y todavía activa (si ya está suspendida, o pendiente_pago, o
        // cualquier otro estado, no hay nada que hacer acá).
        $membresias = MembresiaCliente::where('saldo_pendiente', '>', 0)
            ->where('fecha_limite_saldo', '<', now()->toDateString())
            ->where('estado', MembresiaCliente::ESTADO_ACTIVA)
            ->get();

        if ($membresias->isEmpty()) {
            $this->info('Sin membresías con saldo vencido. Nada que hacer.');
            return self::SUCCESS;
        }

        foreach ($membresias as $mc) {
            $detalle = sprintf(
                'cliente #%d, membresía #%d — saldo Bs %s, vencía el %s',
                $mc->id_cliente,
                $mc->id,
                $mc->saldo_pendiente,
                $mc->fecha_limite_saldo->format('d/m/Y')
            );

            if ($dryRun) {
                $this->line("  [DRY-RUN] Se desactivaría: {$detalle}");
                continue;
            }

            $mc->update(['estado' => MembresiaCliente::ESTADO_SUSPENDIDA_SALDO]);
            $this->line("  → Desactivada: {$detalle}");
        }

        $cantidad = $membresias->count();
        $resumen  = $dryRun
            ? "[DRY-RUN] Se desactivarían {$cantidad} membresías por saldo vencido (ninguna se tocó de verdad)."
            : "Desactivadas {$cantidad} membresías por saldo vencido.";

        $this->info($resumen);
        Log::info("membresias:desactivar-saldo-vencido: {$resumen}");

        return self::SUCCESS;
    }
}
