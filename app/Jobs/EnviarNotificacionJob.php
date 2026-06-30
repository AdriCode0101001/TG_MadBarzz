<?php

namespace App\Jobs;

use App\Models\Notificacion;
use App\Services\NotificacionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class EnviarNotificacionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Número máximo de intentos antes de marcar como fallido. */
    public int $tries = 3;

    /** Segundos de espera entre reintentos: 1 min → 5 min → 15 min. */
    public array $backoff = [60, 300, 900];

    public function __construct(public readonly Notificacion $notificacion) {}

    public function handle(NotificacionService $service): void
    {
        $service->enviarACliente($this->notificacion);
    }

    /** Se llama cuando se agotan todos los reintentos. */
    public function failed(\Throwable $e): void
    {
        Log::error("EnviarNotificacionJob: agotados los reintentos para notificacion #{$this->notificacion->id}: {$e->getMessage()}");

        $this->notificacion->update([
            'estado'      => 'fallido',
            'fecha_envio' => now(),
        ]);
    }
}
