<?php

namespace App\Services;

use App\Contracts\CanalNotificacion;
use App\Models\Cliente;
use App\Models\Notificacion;
use App\Notifications\Channels\EmailChannel;
use App\Notifications\Channels\TelegramChannel;
use App\Notifications\Channels\WhatsAppCloudChannel;
use Illuminate\Support\Facades\Log;

class NotificacionService
{
    /** @var array<string, class-string<CanalNotificacion>> */
    private array $canalesMap = [
        'email'    => EmailChannel::class,
        'telegram' => TelegramChannel::class,
        'whatsapp' => WhatsAppCloudChannel::class,
    ];

    /**
     * Envía la notificación al cliente a través de los canales activos configurados
     * y actualiza estado, fecha_envio y canal en el registro.
     */
    public function enviarACliente(Notificacion $notificacion): bool
    {
        $cliente = Cliente::with('persona', 'user')->find($notificacion->id_cliente);

        if (!$cliente) {
            Log::warning("NotificacionService: cliente #{$notificacion->id_cliente} no encontrado.");
            $notificacion->update(['estado' => 'fallido', 'fecha_envio' => now()]);
            return false;
        }

        $canalesActivos = config('notificaciones.canales', ['email']);
        $canalesUsados  = [];

        foreach ($canalesActivos as $nombre) {
            if (!isset($this->canalesMap[$nombre])) {
                continue;
            }

            $destino = $this->resolverDestino($nombre, $cliente);
            if (!$destino) {
                Log::info("NotificacionService: sin destino para canal '{$nombre}' en cliente #{$cliente->id_cliente}.");
                continue;
            }

            try {
                /** @var CanalNotificacion $canal */
                $canal = app($this->canalesMap[$nombre]);
                if ($canal->enviar($destino, $notificacion->mensaje)) {
                    $canalesUsados[] = $nombre;
                }
            } catch (\Exception $e) {
                Log::warning("NotificacionService: canal '{$nombre}' lanzó excepción para cliente #{$cliente->id_cliente}: {$e->getMessage()}");
            }
        }

        $enviado = count($canalesUsados) > 0;

        $notificacion->update([
            'estado'      => $enviado ? 'enviado' : 'fallido',
            'fecha_envio' => now(),
            'canal'       => $enviado ? implode(',', $canalesUsados) : null,
        ]);

        return $enviado;
    }

    private function resolverDestino(string $canal, Cliente $cliente): ?string
    {
        return match ($canal) {
            'email'    => $cliente->user?->email,
            'telegram' => $cliente->persona?->telegram_chat_id ?? null,
            'whatsapp' => $cliente->persona?->telefono ?? null,
            default    => null,
        };
    }
}
