<?php

namespace App\Services;

use Twilio\Rest\Client;
use Illuminate\Support\Facades\Log;

class WhatsappService
{
    protected $client;
    protected $from;

    public function __construct()
    {
        $this->client = new Client(
            config('services.twilio.sid'),
            config('services.twilio.auth_token')
        );
        $this->from = config('services.twilio.whatsapp_from');
    }

    /**
     * Enviar mensaje WhatsApp a un número específico
     *
     * @param string $to Número de destino en formato: whatsapp:+591XXXXXXXXXX
     * @param string $message Texto del mensaje
     * @return bool true si enviado, false si fallo
     */
    public function sendMessage(string $to, string $message): bool
    {
        try {
            $this->client->messages->create($to, [
                'from' => $this->from,
                'body' => $message,
            ]);
            return true;
        } catch (\Exception $e) {
            Log::error("Error enviando WhatsApp: " . $e->getMessage());
            return false;
        }
    }
}
