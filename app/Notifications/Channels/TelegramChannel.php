<?php

namespace App\Notifications\Channels;

use App\Contracts\CanalNotificacion;
use App\Support\Redactor;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramChannel implements CanalNotificacion
{
    /**
     * Envía un mensaje a un chat de Telegram.
     *
     * $destino = telegram_chat_id del usuario (lo obtiene al hacer /start al bot).
     * Configura TELEGRAM_BOT_TOKEN en .env.
     */
    public function enviar(string $destino, string $mensaje, ?string $idCliente = null): bool
    {
        $token = config('services.telegram.bot_token');

        if (!$token) {
            Log::warning('TelegramChannel: TELEGRAM_BOT_TOKEN no configurado.');
            return false;
        }

        try {
            $response = Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id'    => $destino,
                'text'       => $mensaje,
                'parse_mode' => 'HTML',
            ]);

            if (!$response->successful() || !$response->json('ok')) {
                $identificador = Redactor::identificarParaLog($idCliente, $destino);
                Log::warning("TelegramChannel: respuesta inesperada para {$identificador}: " . $response->body());
                return false;
            }

            return true;
        } catch (\Exception $e) {
            $identificador = Redactor::identificarParaLog($idCliente, $destino);
            Log::warning("TelegramChannel: excepción enviando a {$identificador}: {$e->getMessage()}");
            return false;
        }
    }
}
