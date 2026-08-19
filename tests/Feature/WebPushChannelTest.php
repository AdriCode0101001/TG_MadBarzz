<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Persona;
use App\Models\PushSubscription;
use App\Models\Rol;
use App\Models\User;
use App\Notifications\Channels\WebPushChannel;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Minishlink\WebPush\WebPush;
use Tests\TestCase;

/**
 * WebPushChannel (Etapa 1 de Web Push): $destino es el id_cliente — busca
 * TODAS sus suscripciones y manda a cada una. Las pruebas usan un
 * MockHandler de Guzzle inyectado en WebPush (ver
 * Minishlink\WebPush\WebPush::__construct, $clientOptions admite
 * 'handler'), así que no se hace ninguna llamada de red real.
 *
 * Las claves VAPID de acá son un par de PRUEBA generado aparte —
 * estructuralmente válidas (la librería las valida), pero no son las
 * reales del .env.
 */
class WebPushChannelTest extends TestCase
{
    use RefreshDatabase;

    private const VAPID_PUBLIC_TEST  = 'BK2hfH12jKOWYTWKDcvY-Mae61enwgHCLYbiXXoIiTmDbQZA05esF0pfNa2FP4rJ4dRTTPIW2mpLPYVXYyvBEcE';
    private const VAPID_PRIVATE_TEST = 'OxcxkLwuL88Dc04QFf03KmvIHKdsEe-NJwR-4viIEIE';

    // p256dh debe ser una clave pública EC sin comprimir de verdad (65 bytes,
    // prefijo 0x04) — el cifrado del payload la usa para ECDH real, así que
    // un string cualquiera rompe con "only uncompressed keys are supported".
    // auth es simplemente 16 bytes al azar en base64url, como en una
    // suscripción real.
    private const P256DH_TEST = 'BNrgAxX6lq-28f2vm-O5b1t7XvfpssvUI-yTd03OdfVs_PNpdKDuVVJBLFRglw5mQXiUszgPE0y5FpnLSl9mg_U';
    private const AUTH_TEST   = '5Dmrn9Ds2kCa8In_7ldLug';

    private static int $seq = 0;

    private function crearCliente(): Cliente
    {
        self::$seq++;
        $s = self::$seq;

        $persona = Persona::create([
            'nombre_completo'  => "Cliente Push {$s}",
            'apellido_paterno' => 'Test',
            'apellido_materno' => 'Test',
            'ci'               => "CIPUSH{$s}",
            'telefono'         => "+59176000{$s}",
            'genero'           => 'masculino',
            'fecha_nacimiento' => '1990-01-01',
        ]);

        $user = User::factory()->create(['rol_id' => Rol::firstOrCreate(['nombre' => 'Cliente'])->id]);

        return Cliente::create([
            'id_persona'     => $persona->id_persona,
            'id_usuario'     => $user->id,
            'estado'         => 'activo',
            'dias_asistidos' => 0,
        ]);
    }

    private function crearSuscripcion(Cliente $cliente, string $endpoint): PushSubscription
    {
        return PushSubscription::create([
            'id_cliente' => $cliente->id_cliente,
            'endpoint'   => $endpoint,
            'p256dh'     => self::P256DH_TEST,
            'auth'       => self::AUTH_TEST,
        ]);
    }

    /** WebPush con un MockHandler que devuelve, EN ORDEN, las respuestas indicadas. */
    private function webPushConRespuestas(array $respuestas): WebPush
    {
        $mock = new MockHandler($respuestas);
        $handlerStack = HandlerStack::create($mock);

        return new WebPush([
            'VAPID' => [
                'subject'    => 'mailto:test@madbarzz.test',
                'publicKey'  => self::VAPID_PUBLIC_TEST,
                'privateKey' => self::VAPID_PRIVATE_TEST,
            ],
        ], [], 30, ['handler' => $handlerStack]);
    }

    public function test_envia_correctamente_a_una_suscripcion_valida(): void
    {
        $cliente = $this->crearCliente();
        $this->crearSuscripcion($cliente, 'https://fcm.googleapis.com/fcm/send/endpoint-valido-1');

        $webPush = $this->webPushConRespuestas([new Response(201)]);
        $channel = new WebPushChannel($webPush);

        $resultado = $channel->enviar((string) $cliente->id_cliente, 'Tu membresía vence pronto.');

        $this->assertTrue($resultado);
        $this->assertDatabaseCount('push_subscriptions', 1); // sigue existiendo
    }

    public function test_elimina_la_suscripcion_cuando_el_endpoint_devuelve_410(): void
    {
        $cliente = $this->crearCliente();
        $suscripcion = $this->crearSuscripcion($cliente, 'https://fcm.googleapis.com/fcm/send/endpoint-vencido');

        $webPush = $this->webPushConRespuestas([new Response(410)]);
        $channel = new WebPushChannel($webPush);

        $resultado = $channel->enviar((string) $cliente->id_cliente, 'mensaje');

        $this->assertFalse($resultado); // esa suscripción era la única y falló
        $this->assertDatabaseMissing('push_subscriptions', ['id' => $suscripcion->id]);
    }

    public function test_elimina_la_suscripcion_cuando_el_endpoint_devuelve_404(): void
    {
        $cliente = $this->crearCliente();
        $suscripcion = $this->crearSuscripcion($cliente, 'https://fcm.googleapis.com/fcm/send/endpoint-inexistente');

        $webPush = $this->webPushConRespuestas([new Response(404)]);
        $channel = new WebPushChannel($webPush);

        $channel->enviar((string) $cliente->id_cliente, 'mensaje');

        $this->assertDatabaseMissing('push_subscriptions', ['id' => $suscripcion->id]);
    }

    public function test_un_cliente_con_varias_suscripciones_recibe_en_todas_y_solo_se_borra_la_vencida(): void
    {
        $cliente = $this->crearCliente();
        $this->crearSuscripcion($cliente, 'https://fcm.googleapis.com/fcm/send/celular');
        $vieja = $this->crearSuscripcion($cliente, 'https://fcm.googleapis.com/fcm/send/notebook-viejo');

        // Orden de respuestas = orden en que se crearon las suscripciones.
        $webPush = $this->webPushConRespuestas([new Response(201), new Response(410)]);
        $channel = new WebPushChannel($webPush);

        $resultado = $channel->enviar((string) $cliente->id_cliente, 'mensaje');

        $this->assertTrue($resultado); // al menos una tuvo éxito
        $this->assertDatabaseCount('push_subscriptions', 1);
        $this->assertDatabaseMissing('push_subscriptions', ['id' => $vieja->id]);
    }

    public function test_cliente_sin_ninguna_suscripcion_devuelve_false_sin_llamar_a_webpush(): void
    {
        $cliente = $this->crearCliente();

        // Sin inyectar ningún WebPush: si intentara usarlo de verdad,
        // fallaría (no hay red en el test) — confirma que ni se llega ahí.
        $channel = new WebPushChannel();

        $resultado = $channel->enviar((string) $cliente->id_cliente, 'mensaje');

        $this->assertFalse($resultado);
    }

    public function test_sin_claves_vapid_configuradas_devuelve_false_sin_romper(): void
    {
        config(['services.webpush.public_key' => null, 'services.webpush.private_key' => null]);

        $cliente = $this->crearCliente();
        $this->crearSuscripcion($cliente, 'https://fcm.googleapis.com/fcm/send/algun-endpoint');

        $channel = new WebPushChannel(); // sin inyectar: fuerza a intentar construir uno real

        $resultado = $channel->enviar((string) $cliente->id_cliente, 'mensaje');

        $this->assertFalse($resultado);
    }

    /**
     * enviarAviso() (Etapa 3, usado por EnviarPushAvisoJob) comparte la
     * misma limpieza de suscripciones vencidas que enviar() — ambos pasan
     * por el mismo enviarPayload() privado.
     */
    public function test_enviaraviso_tambien_elimina_la_suscripcion_cuando_el_endpoint_devuelve_410(): void
    {
        $cliente = $this->crearCliente();
        $suscripcion = $this->crearSuscripcion($cliente, 'https://fcm.googleapis.com/fcm/send/aviso-vencido');

        $webPush = $this->webPushConRespuestas([new Response(410)]);
        $channel = new WebPushChannel($webPush);

        $resultado = $channel->enviarAviso($cliente->id_cliente, 'Título del aviso', 'Extracto del aviso.');

        $this->assertFalse($resultado);
        $this->assertDatabaseMissing('push_subscriptions', ['id' => $suscripcion->id]);
    }
}
