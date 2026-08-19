<?php

namespace App\Notifications\Channels;

use App\Contracts\CanalNotificacion;
use App\Models\PushSubscription;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

/**
 * Envía notificaciones Web Push (barra de notificaciones del celular, sin
 * app) a un cliente.
 *
 * A diferencia de los otros canales (un solo email/chat_id/teléfono),
 * $destino acá es el id_cliente: un cliente puede tener VARIAS
 * suscripciones (celular, tablet, reinstaló el navegador...), así que
 * adentro se buscan TODAS y se manda a cada una por separado. Se considera
 * "enviado" (true) si al menos una suscripción lo recibió.
 */
class WebPushChannel implements CanalNotificacion
{
    /**
     * Instancia de WebPush ya armada, para poder inyectar un cliente HTTP
     * simulado en los tests (ver Minishlink\WebPush\WebPush::__construct,
     * $clientOptions acepta un 'handler' de Guzzle). Si no se pasa
     * ninguna, se arma una real con las claves VAPID del .env.
     */
    public function __construct(private ?WebPush $webPush = null) {}

    public function enviar(string $destino, string $mensaje): bool
    {
        return $this->enviarPayload((int) $destino, [
            'title' => 'MadBarzz',
            'body'  => $mensaje,
            'icon'  => '/assets_private/images/favicon.png',
        ]);
    }

    /**
     * Variante para Avisos (Etapa 3): a diferencia de enviar() (contrato de
     * CanalNotificacion, título fijo "MadBarzz"), acá el título es el propio
     * título del aviso — por eso no pasa por NotificacionService ni por el
     * canalesMap, se llama directo desde EnviarPushAvisoJob.
     */
    public function enviarAviso(int $idCliente, string $titulo, string $mensaje): bool
    {
        return $this->enviarPayload($idCliente, [
            'title' => $titulo,
            'body'  => $mensaje,
            'icon'  => '/assets_private/images/favicon.png',
        ]);
    }

    private function enviarPayload(int $idCliente, array $payloadData): bool
    {
        $suscripciones = PushSubscription::where('id_cliente', $idCliente)->get();

        if ($suscripciones->isEmpty()) {
            return false;
        }

        $webPush = $this->webPush ?? $this->construirWebPush();

        if (!$webPush) {
            return false; // VAPID no configurada — ver log en construirWebPush()
        }

        $payload = json_encode($payloadData);

        $enviadoAlMenosUno = false;

        foreach ($suscripciones as $suscripcion) {
            $reporte = $webPush->sendOneNotification(
                new Subscription($suscripcion->endpoint, $suscripcion->p256dh, $suscripcion->auth, 'aes128gcm'),
                $payload
            );

            if ($reporte->isSuccess()) {
                $enviadoAlMenosUno = true;
                continue;
            }

            if ($reporte->isSubscriptionExpired()) {
                // El navegador ya no existe ahí (datos borrados, app/
                // navegador desinstalado, endpoint vencido) — se limpia
                // sola, sin esperar a que el cliente la desactive a mano.
                $suscripcion->delete();
                Log::info("WebPushChannel: suscripción #{$suscripcion->id} expirada, eliminada.");
                continue;
            }

            Log::warning("WebPushChannel: fallo enviando a suscripción #{$suscripcion->id}: {$reporte->getReason()}");
        }

        return $enviadoAlMenosUno;
    }

    private function construirWebPush(): ?WebPush
    {
        $publicKey  = config('services.webpush.public_key');
        $privateKey = config('services.webpush.private_key');
        $subject    = config('services.webpush.subject');

        if (!$publicKey || !$privateKey) {
            Log::warning('WebPushChannel: VAPID_PUBLIC_KEY/VAPID_PRIVATE_KEY no configuradas.');
            return null;
        }

        self::asegurarOpensslConfEnWindows();

        return new WebPush([
            'VAPID' => [
                'subject'    => $subject,
                'publicKey'  => $publicKey,
                'privateKey' => $privateKey,
            ],
        ]);
    }

    /**
     * En Windows (este entorno de desarrollo, XAMPP), openssl_pkey_new()
     * para claves EC — lo que exige firmar cada request VAPID — falla con
     * "Unable to create the key" si no encuentra openssl.cnf: PHP en
     * Windows no lo ubica solo, a diferencia de Linux. XAMPP lo trae
     * incluido pero no queda configurado por defecto. Sin esto, cualquier
     * envío real (no solo generar las claves una vez) fallaría acá. No
     * hace nada fuera de Windows, ni si la variable ya está seteada.
     */
    public static function asegurarOpensslConfEnWindows(): void
    {
        if (PHP_OS_FAMILY !== 'Windows' || getenv('OPENSSL_CONF')) {
            return;
        }

        foreach ([
            'C:\\xampp\\php\\extras\\openssl\\openssl.cnf',
            'C:\\xampp\\apache\\conf\\openssl.cnf',
        ] as $ruta) {
            if (is_file($ruta)) {
                putenv("OPENSSL_CONF={$ruta}");
                return;
            }
        }
    }
}
