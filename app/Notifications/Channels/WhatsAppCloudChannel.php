<?php

namespace App\Notifications\Channels;

use App\Contracts\CanalNotificacion;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppCloudChannel implements CanalNotificacion
{
    /**
     * Envía un mensaje de texto vía WhatsApp Cloud API (Meta).
     *
     * $destino = número de teléfono (ej: "71234567" o "+59171234567").
     * El canal agrega el prefijo de Bolivia (+591) si el número no tiene código de país.
     *
     * Configura en .env:
     *   WHATSAPP_CLOUD_PHONE_ID  → Phone Number ID del panel de Meta
     *   WHATSAPP_CLOUD_TOKEN     → Access Token permanente de Meta
     */
    public function enviar(string $destino, string $mensaje): bool
    {
        $phoneId = config('services.whatsapp_cloud.phone_number_id');
        $token   = config('services.whatsapp_cloud.token');

        if (!$phoneId || !$token) {
            Log::warning('WhatsAppCloudChannel: credenciales no configuradas (WHATSAPP_CLOUD_PHONE_ID / WHATSAPP_CLOUD_TOKEN).');
            return false;
        }

        // Normalizar número: solo dígitos, con código país Bolivia (591) si falta
        $numero = preg_replace('/\D/', '', $destino);
        if (!str_starts_with($numero, '591')) {
            $numero = '591' . $numero;
        }

        try {
            $response = Http::withToken($token)
                ->post("https://graph.facebook.com/v20.0/{$phoneId}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to'                => $numero,
                    'type'              => 'text',
                    'text'              => ['body' => $mensaje],
                ]);

            if (!$response->successful()) {
                Log::warning("WhatsAppCloudChannel: error para {$numero}: " . $response->body());
                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::warning("WhatsAppCloudChannel: excepción enviando a {$numero}: {$e->getMessage()}");
            return false;
        }
    }
}
