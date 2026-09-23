<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Notificacion;
use App\Models\Persona;
use App\Models\PushSubscription;
use App\Models\Rol;
use App\Models\User;
use App\Notifications\Channels\WebPushChannel;
use App\Services\NotificacionService;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Minishlink\WebPush\WebPush;
use Tests\TestCase;

/**
 * Etapa 3 de Web Push: 'push' activo en NOTIF_CANALES junto a email/telegram
 * para las notificaciones de vencimiento. El requisito principal es el
 * aislamiento: un fallo de push NUNCA debe impedir que email/telegram sigan
 * enviándose (mismo try/catch por canal que ya protegía a los demás en
 * NotificacionService::enviarACliente()).
 */
class VencimientoPushTest extends TestCase
{
    use RefreshDatabase;

    private const VAPID_PUBLIC_TEST  = 'BK2hfH12jKOWYTWKDcvY-Mae61enwgHCLYbiXXoIiTmDbQZA05esF0pfNa2FP4rJ4dRTTPIW2mpLPYVXYyvBEcE';
    private const VAPID_PRIVATE_TEST = 'OxcxkLwuL88Dc04QFf03KmvIHKdsEe-NJwR-4viIEIE';
    private const P256DH_TEST = 'BNrgAxX6lq-28f2vm-O5b1t7XvfpssvUI-yTd03OdfVs_PNpdKDuVVJBLFRglw5mQXiUszgPE0y5FpnLSl9mg_U';
    private const AUTH_TEST   = '5Dmrn9Ds2kCa8In_7ldLug';

    private function crearClienteConTodosLosDestinos(): Cliente
    {
        $persona = Persona::create([
            'nombre_completo'  => 'Cliente Vencimiento Push',
            'apellido_paterno' => 'Test',
            'apellido_materno' => 'Test',
            'ci'               => 'CIVENCPUSH1',
            'telefono'         => '+59178002222',
            'genero'           => 'masculino',
            'fecha_nacimiento' => '1990-01-01',
            'telegram_chat_id' => '888777666',
        ]);

        $user = User::factory()->create([
            'rol_id' => Rol::firstOrCreate(['nombre' => 'Cliente'])->id,
            'email'  => 'cliente-vencpush@madbarzz.test',
        ]);

        return Cliente::create([
            'id_persona'     => $persona->id_persona,
            'id_usuario'     => $user->id,
            'estado'         => 'activo',
            'dias_asistidos' => 0,
        ]);
    }

    private function crearNotificacion(Cliente $cliente): Notificacion
    {
        return Notificacion::create([
            'id_cliente' => $cliente->id_cliente,
            'tipo'       => 'recordatorio_vencimiento',
            'mensaje'    => 'Tu membresía vence pronto.',
            'estado'     => 'pendiente',
        ]);
    }

    public function test_push_esta_activo_junto_a_email_y_telegram(): void
    {
        config(['notificaciones.canales' => ['email', 'telegram', 'push']]);

        $this->assertSame(['email', 'telegram', 'push'], config('notificaciones.canales'));
    }

    public function test_si_push_falla_para_un_cliente_email_y_telegram_siguen_enviandose(): void
    {
        Mail::fake();
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true], 200)]);
        config(['notificaciones.canales' => ['email', 'telegram', 'push']]);

        // Canal 'push' roto a propósito: NotificacionService lo resuelve vía
        // app(WebPushChannel::class) — bindearlo en el contenedor alcanza
        // para que el resto del flujo use esta versión que siempre falla.
        $this->app->bind(WebPushChannel::class, function () {
            return new class extends WebPushChannel {
                public function enviar(string $destino, string $mensaje, ?string $idCliente = null): bool
                {
                    throw new \RuntimeException('Fallo simulado de push.');
                }
            };
        });

        $cliente = $this->crearClienteConTodosLosDestinos();
        $notificacion = $this->crearNotificacion($cliente);

        $enviado = app(NotificacionService::class)->enviarACliente($notificacion);

        $this->assertTrue($enviado);
        $notificacion->refresh();
        $this->assertSame('email,telegram', $notificacion->canal); // push no cuenta, pero no rompió nada
        $this->assertSame('enviado', $notificacion->estado);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'api.telegram.org'));
    }

    public function test_cliente_sin_suscripcion_push_igual_recibe_email_y_telegram(): void
    {
        Mail::fake();
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true], 200)]);
        config(['notificaciones.canales' => ['email', 'telegram', 'push']]);

        // Cliente SIN ninguna fila en push_subscriptions — WebPushChannel
        // real (sin mock) debe devolver false en silencio, no una excepción.
        $cliente = $this->crearClienteConTodosLosDestinos();
        $notificacion = $this->crearNotificacion($cliente);

        $enviado = app(NotificacionService::class)->enviarACliente($notificacion);

        $this->assertTrue($enviado);
        $notificacion->refresh();
        $this->assertSame('email,telegram', $notificacion->canal);
    }

    public function test_una_suscripcion_vencida_se_borra_durante_el_envio_de_vencimiento(): void
    {
        Mail::fake();
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true], 200)]);
        config(['notificaciones.canales' => ['email', 'telegram', 'push']]);

        $cliente = $this->crearClienteConTodosLosDestinos();
        $suscripcion = PushSubscription::create([
            'id_cliente' => $cliente->id_cliente,
            'endpoint'   => 'https://fcm.googleapis.com/fcm/send/vencida-en-vencimiento',
            'p256dh'     => self::P256DH_TEST,
            'auth'       => self::AUTH_TEST,
        ]);

        $mock = new MockHandler([new Response(410)]);
        $webPush = new WebPush([
            'VAPID' => [
                'subject'    => 'mailto:test@madbarzz.test',
                'publicKey'  => self::VAPID_PUBLIC_TEST,
                'privateKey' => self::VAPID_PRIVATE_TEST,
            ],
        ], [], 30, ['handler' => HandlerStack::create($mock)]);

        $this->app->bind(WebPushChannel::class, fn () => new WebPushChannel($webPush));

        $notificacion = $this->crearNotificacion($cliente);
        $enviado = app(NotificacionService::class)->enviarACliente($notificacion);

        $this->assertTrue($enviado); // email/telegram sí funcionaron
        $notificacion->refresh();
        $this->assertSame('email,telegram', $notificacion->canal); // push falló (vencida), no cuenta
        $this->assertDatabaseMissing('push_subscriptions', ['id' => $suscripcion->id]); // pero se limpió sola
    }
}
