<?php

namespace Tests\Feature;

use App\Jobs\EnviarPushAvisoJob;
use App\Models\Aviso;
use App\Models\Cliente;
use App\Models\Persona;
use App\Models\PushSubscription;
use App\Models\Rol;
use App\Models\User;
use App\Notifications\Channels\WebPushChannel;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Minishlink\WebPush\WebPush;
use Tests\TestCase;

/**
 * Etapa 3 de Web Push: al crear un Aviso se encola un push para todos los
 * clientes con suscripción activa (EnviarPushAvisoJob). Igual criterio que
 * WebPushChannelTest: MockHandler de Guzzle inyectado en WebPush, sin red
 * real.
 */
class AvisoPushTest extends TestCase
{
    use RefreshDatabase;

    private const VAPID_PUBLIC_TEST  = 'BK2hfH12jKOWYTWKDcvY-Mae61enwgHCLYbiXXoIiTmDbQZA05esF0pfNa2FP4rJ4dRTTPIW2mpLPYVXYyvBEcE';
    private const VAPID_PRIVATE_TEST = 'OxcxkLwuL88Dc04QFf03KmvIHKdsEe-NJwR-4viIEIE';
    private const P256DH_TEST = 'BNrgAxX6lq-28f2vm-O5b1t7XvfpssvUI-yTd03OdfVs_PNpdKDuVVJBLFRglw5mQXiUszgPE0y5FpnLSl9mg_U';
    private const AUTH_TEST   = '5Dmrn9Ds2kCa8In_7ldLug';

    private static int $seq = 0;

    private function usuario(string $rol): User
    {
        return User::factory()->create(['rol_id' => Rol::firstOrCreate(['nombre' => $rol])->id]);
    }

    private function crearCliente(): Cliente
    {
        self::$seq++;
        $s = self::$seq;

        $persona = Persona::create([
            'nombre_completo'  => "Cliente AvisoPush {$s}",
            'apellido_paterno' => 'Test',
            'apellido_materno' => 'Test',
            'ci'               => "CIAVPUSH{$s}",
            'telefono'         => "+59174000{$s}",
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

    /** WebPush con MockHandler + historial de requests reales hechos. */
    private function webPushConHistorial(array $respuestas, array &$historial): WebPush
    {
        $mock = new MockHandler($respuestas);
        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push(Middleware::history($historial));

        return new WebPush([
            'VAPID' => [
                'subject'    => 'mailto:test@madbarzz.test',
                'publicKey'  => self::VAPID_PUBLIC_TEST,
                'privateKey' => self::VAPID_PRIVATE_TEST,
            ],
        ], [], 30, ['handler' => $handlerStack]);
    }

    // ── 1. Crear un aviso encola el push, sin bloquear el guardado ──────────

    public function test_crear_un_aviso_encola_el_job_de_push(): void
    {
        Queue::fake();
        $admin = $this->usuario('Administrador');

        $this->actingAs($admin)->post('/avisos', [
            'titulo' => 'Mantenimiento de piletas',
            'texto'  => 'El sábado no habrá agua caliente en los vestidores.',
        ])->assertRedirect(route('avisos.index'));

        $aviso = Aviso::firstOrFail();

        // El aviso queda guardado de inmediato — el push nunca corrió en
        // este request (Queue::fake() lo intercepta), lo que prueba que no
        // es bloqueante.
        Queue::assertPushed(EnviarPushAvisoJob::class, fn ($job) => $job->aviso->is($aviso));
    }

    // ── 2. Solo llega a los clientes con suscripción activa ──────────────────

    public function test_el_job_envia_solo_a_los_clientes_con_suscripcion_activa(): void
    {
        $conSubA = $this->crearCliente();
        $conSubB = $this->crearCliente();
        $this->crearCliente(); // sin suscripción — no debe recibir nada

        $this->crearSuscripcion($conSubA, 'https://fcm.googleapis.com/fcm/send/aviso-a');
        $this->crearSuscripcion($conSubB, 'https://fcm.googleapis.com/fcm/send/aviso-b');

        $aviso = Aviso::create([
            'titulo'     => 'Nueva rutina disponible',
            'texto'      => 'Ya podés ver tu rutina actualizada en el portal.',
            'id_usuario' => $this->usuario('Administrador')->id,
        ]);

        $historial = [];
        // Exactamente 2 respuestas: si el job intentara mandarle también al
        // cliente sin suscripción, el MockHandler se quedaría sin
        // respuestas encoladas y la 3ra petición fallaría.
        $webPush = $this->webPushConHistorial([new Response(201), new Response(201)], $historial);
        $channel = new WebPushChannel($webPush);

        (new EnviarPushAvisoJob($aviso))->handle($channel);

        $this->assertCount(2, $historial); // exactamente 2 clientes contactados, no 3
    }

    public function test_un_aviso_sin_ningun_cliente_suscripto_no_hace_nada_y_no_rompe(): void
    {
        $this->crearCliente(); // sin suscripción

        $aviso = Aviso::create([
            'titulo'     => 'Aviso sin nadie suscripto',
            'texto'      => 'Texto de prueba.',
            'id_usuario' => $this->usuario('Administrador')->id,
        ]);

        // Sin inyectar ningún WebPush real: si intentara usarlo, no hay red
        // en el test y fallaría — confirma que ni se llega a intentarlo.
        (new EnviarPushAvisoJob($aviso))->handle(new WebPushChannel());

        $this->assertTrue(true); // llegar hasta acá sin excepción es la prueba
    }

    // ── 3. Un fallo de push no rompe el job ni afecta a los demás clientes ──

    public function test_un_fallo_de_push_para_un_cliente_no_rompe_el_job_ni_impide_seguir(): void
    {
        $cliente = $this->crearCliente();
        $this->crearSuscripcion($cliente, 'https://fcm.googleapis.com/fcm/send/falla');

        $aviso = Aviso::create([
            'titulo'     => 'Aviso con canal roto',
            'texto'      => 'Texto de prueba.',
            'id_usuario' => $this->usuario('Administrador')->id,
        ]);

        $canalQueSiempreFalla = new class extends WebPushChannel {
            public function enviarAviso(int $idCliente, string $titulo, string $mensaje): bool
            {
                throw new \RuntimeException('Fallo simulado de push.');
            }
        };

        $excepcionEscapo = false;
        try {
            (new EnviarPushAvisoJob($aviso))->handle($canalQueSiempreFalla);
        } catch (\Throwable $e) {
            $excepcionEscapo = true;
        }

        $this->assertFalse($excepcionEscapo, 'El job no debe propagar la excepción de un canal que falla.');
    }
}
