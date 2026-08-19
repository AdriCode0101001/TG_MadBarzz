<?php

namespace App\Console\Commands;

use App\Jobs\EnviarNotificacionJob;
use App\Models\MembresiaCliente;
use App\Models\Notificacion;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Pago en 2 Cuotas — Etapa 5: aviso "te vence el saldo en N días". Comando
 * HERMANO de NotificacionesVencimientosCommand (sin tocarlo) — mismo patrón
 * exacto (Notificacion + EnviarNotificacionJob en cola, mismo Job, mismos
 * canales email/telegram/push vía NotificacionService), pero con su propio
 * tipo ('recordatorio_saldo_cuota') para no mezclarse con el aviso de
 * vencimiento de membresía, y su propio dedupe (mismo mecanismo: tipo +
 * cliente + día).
 */
class NotificacionesSaldoPendienteCommand extends Command
{
    /** Días de anticipación por defecto: "faltan 3 días para tu saldo". */
    public const DIAS_ANTICIPACION = 3;

    /** Tipo propio — a propósito distinto de 'recordatorio_vencimiento'. */
    public const TIPO = 'recordatorio_saldo_cuota';

    protected $signature = 'notificaciones:saldo-pendiente
                            {--dias= : Días de anticipación (por defecto 3)}';

    protected $description = 'Avisa a los clientes cuyo saldo pendiente de una membresía vence en N días.';

    public function handle(): int
    {
        $dias = (int) ($this->option('dias') ?? self::DIAS_ANTICIPACION);

        $fechaObjetivo = now()->addDays($dias)->toDateString();

        $this->info("Buscando saldos pendientes que vencen el {$fechaObjetivo} (en {$dias} días)...");

        $membresias = MembresiaCliente::with('cliente.persona', 'cliente.user')
            ->where('saldo_pendiente', '>', 0)
            ->whereDate('fecha_limite_saldo', $fechaObjetivo)
            ->whereHas('cliente', fn ($q) => $q->where('estado', 'activo'))
            ->get();

        if ($membresias->isEmpty()) {
            $this->line('  Sin saldos próximos a vencer. Nada que hacer.');
            return self::SUCCESS;
        }

        $procesadas = 0;
        $omitidas   = 0;

        foreach ($membresias as $mc) {
            $cliente = $mc->cliente;

            // Mismo dedupe que notificaciones:vencimientos, con el tipo
            // PROPIO de este aviso — así un cliente puede recibir el aviso
            // de vencimiento de membresía Y el de saldo el mismo día, cada
            // uno independiente, sin que uno bloquee al otro.
            $yaNotificado = Notificacion::where('id_cliente', $cliente->id_cliente)
                ->where('tipo', self::TIPO)
                ->whereDate('created_at', today())
                ->exists();

            if ($yaNotificado) {
                $omitidas++;
                $this->line("  ↷ Omitido: cliente #{$cliente->id_cliente} ya notificado hoy.");
                continue;
            }

            $nombre  = trim(($cliente->persona->nombre_completo ?? '') . ' ' . ($cliente->persona->apellido_paterno ?? ''));
            $fecha   = Carbon::parse($mc->fecha_limite_saldo)->format('d/m/Y');
            $monto   = number_format((float) $mc->saldo_pendiente, 2);
            $mensaje = "Hola {$nombre}, tu saldo pendiente de Bs {$monto} en MadBarzz vence el {$fecha}. Acércate a pagar para no perder el acceso.";

            $notificacion = Notificacion::create([
                'id_cliente' => $cliente->id_cliente,
                'tipo'       => self::TIPO,
                'mensaje'    => $mensaje,
                'estado'     => 'pendiente',
            ]);

            EnviarNotificacionJob::dispatch($notificacion);

            $procesadas++;
            $this->line("  → Encolado: {$nombre} (saldo Bs {$monto}, vence {$fecha})");
        }

        $this->info("Completado: {$procesadas} encoladas, {$omitidas} omitidas (ya notificadas hoy).");

        return self::SUCCESS;
    }
}
