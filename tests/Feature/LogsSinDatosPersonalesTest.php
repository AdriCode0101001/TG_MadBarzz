<?php

namespace Tests\Feature;

use App\Notifications\Channels\EmailChannel;
use App\Notifications\Channels\TelegramChannel;
use App\Notifications\Channels\WhatsAppCloudChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Hallazgo de la auditoría de privacidad (grupo 1, arreglo 1): los canales
 * de notificación logueaban el teléfono/chat_id/email en texto plano cuando
 * un envío fallaba. Estas pruebas fijan el comportamiento correcto: el log
 * identifica al cliente por id (o, si no hay id disponible, enmascara el
 * dato) y nunca deja el dato personal completo en el mensaje de log.
 */
class LogsSinDatosPersonalesTest extends TestCase
{
    use RefreshDatabase;

    public function test_whatsapp_cloud_channel_no_loguea_el_telefono_completo_si_falla(): void
    {
        config([
            'services.whatsapp_cloud.phone_number_id' => 'phone-id-test',
            'services.whatsapp_cloud.token'           => 'token-test',
        ]);
        Http::fake(['graph.facebook.com/*' => Http::response(['error' => 'bad request'], 400)]);
        Log::spy();

        (new WhatsAppCloudChannel())->enviar('71234567', 'mensaje', '123');

        Log::shouldHaveReceived('warning')
            ->withArgs(function (string $mensaje) {
                return str_contains($mensaje, 'cliente #123')
                    && !str_contains($mensaje, '59171234567');
            })
            ->once();
    }

    public function test_whatsapp_cloud_channel_enmascara_el_telefono_si_no_hay_id_de_cliente(): void
    {
        config([
            'services.whatsapp_cloud.phone_number_id' => 'phone-id-test',
            'services.whatsapp_cloud.token'           => 'token-test',
        ]);
        Http::fake(['graph.facebook.com/*' => Http::response(['error' => 'bad request'], 400)]);
        Log::spy();

        (new WhatsAppCloudChannel())->enviar('71234567', 'mensaje');

        Log::shouldHaveReceived('warning')
            ->withArgs(fn (string $mensaje) => !str_contains($mensaje, '59171234567'))
            ->once();
    }

    public function test_telegram_channel_no_loguea_el_chat_id_completo_si_falla(): void
    {
        config(['services.telegram.bot_token' => 'token-test']);
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => false], 400)]);
        Log::spy();

        (new TelegramChannel())->enviar('999888777', 'mensaje', '456');

        Log::shouldHaveReceived('warning')
            ->withArgs(function (string $mensaje) {
                return str_contains($mensaje, 'cliente #456')
                    && !str_contains($mensaje, '999888777');
            })
            ->once();
    }

    public function test_email_channel_no_loguea_el_correo_completo_si_falla(): void
    {
        Mail::shouldReceive('raw')->andThrow(new \RuntimeException('fallo simulado de envío'));
        Log::spy();

        (new EmailChannel())->enviar('cliente@ejemplo.com', 'mensaje', '789');

        Log::shouldHaveReceived('warning')
            ->withArgs(function (string $mensaje) {
                return str_contains($mensaje, 'cliente #789')
                    && !str_contains($mensaje, 'cliente@ejemplo.com');
            })
            ->once();
    }
}
