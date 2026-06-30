<?php

namespace App\Console\Commands;

use App\Jobs\EnviarNotificacionJob;
use App\Models\MembresiaCliente;
use App\Models\Notificacion;
use Illuminate\Console\Command;

class NotificacionesVencimientosCommand extends Command
{
    protected $signature = 'notificaciones:vencimientos
                            {--dias= : Días de anticipación (sobreescribe NOTIF_DIAS_AVISO del .env)}';

    protected $description = 'Detecta membresías próximas a vencer y encola un aviso por cliente.';

    public function handle(): int
    {
        $dias = (int) ($this->option('dias') ?? config('notificaciones.dias_aviso', 3));

        $fechaObjetivo = now()->addDays($dias)->toDateString();

        $this->info("Buscando membresías que vencen el {$fechaObjetivo} (en {$dias} días)...");

        $membresias = MembresiaCliente::with('cliente.persona', 'cliente.user')
            ->whereDate('fecha_fin', $fechaObjetivo)
            ->whereHas('cliente', fn ($q) => $q->where('estado', 'activo'))
            ->get();

        if ($membresias->isEmpty()) {
            $this->line('  Sin membresías próximas a vencer. Nada que hacer.');
            return self::SUCCESS;
        }

        $procesadas = 0;
        $omitidas   = 0;

        foreach ($membresias as $mc) {
            $cliente = $mc->cliente;

            // Evitar duplicados: saltar si ya se notificó a este cliente hoy
            $yaNotificado = Notificacion::where('id_cliente', $cliente->id_cliente)
                ->where('tipo', 'recordatorio_vencimiento')
                ->whereDate('created_at', today())
                ->exists();

            if ($yaNotificado) {
                $omitidas++;
                $this->line("  ↷ Omitido: cliente #{$cliente->id_cliente} ya notificado hoy.");
                continue;
            }

            $nombre  = trim(($cliente->persona->nombre_completo ?? '') . ' ' . ($cliente->persona->apellido_paterno ?? ''));
            $fecha   = \Carbon\Carbon::parse($mc->fecha_fin)->format('d/m/Y');
            $mensaje = "Hola {$nombre}, tu membresía en MadBarzz vence el {$fecha}. ¡Renuévala para seguir entrenando!";

            $notificacion = Notificacion::create([
                'id_cliente' => $cliente->id_cliente,
                'tipo'       => 'recordatorio_vencimiento',
                'mensaje'    => $mensaje,
                'estado'     => 'pendiente',
            ]);

            EnviarNotificacionJob::dispatch($notificacion);

            $procesadas++;
            $this->line("  → Encolado: {$nombre} (vence {$fecha})");
        }

        $this->info("Completado: {$procesadas} encoladas, {$omitidas} omitidas (ya notificadas hoy).");

        return self::SUCCESS;
    }
}
