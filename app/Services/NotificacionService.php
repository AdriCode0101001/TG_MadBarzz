<?php

namespace App\Services;

use App\Contracts\CanalNotificacion;
use App\Models\Cliente;
use App\Models\Notificacion;
use App\Notifications\Channels\EmailChannel;
use App\Notifications\Channels\TelegramChannel;
use App\Notifications\Channels\WebPushChannel;
use App\Notifications\Channels\WhatsAppCloudChannel;
use Illuminate\Support\Facades\Log;

class NotificacionService
{
    /**
     * @var array<string, class-string<CanalNotificacion>>
     *
     * 'push' activo desde la Etapa 3 de Web Push (NOTIF_CANALES=email,
     * telegram,push) para las notificaciones de vencimiento. Igual que los
     * demás canales, un fallo suyo se atrapa en el try/catch de
     * enviarACliente() y no afecta a los demás — email/telegram siguen
     * enviándose aunque push falle o el cliente no tenga suscripción.
     */
    private array $canalesMap = [
        'email'    => EmailChannel::class,
        'telegram' => TelegramChannel::class,
        'whatsapp' => WhatsAppCloudChannel::class,
        'push'     => WebPushChannel::class,
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
                if ($canal->enviar($destino, $notificacion->mensaje, (string) $cliente->id_cliente)) {
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
            // El "destino" es el id_cliente: WebPushChannel busca ADENTRO
            // todas sus suscripciones (puede tener varias, o ninguna — en
            // ese caso el propio canal devuelve false, no hace falta
            // chequear acá si tiene alguna cargada).
            'push'     => (string) $cliente->id_cliente,
            default    => null,
        };
    }
}
