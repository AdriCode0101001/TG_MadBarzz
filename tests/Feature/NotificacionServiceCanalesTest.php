<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Notificacion;
use App\Models\Persona;
use App\Models\Rol;
use App\Models\User;
use App\Services\NotificacionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Regresión: el canal 'push' quedó REGISTRADO en NotificacionService desde
 * la Etapa 1 de Web Push, y la Etapa 3 lo activó en NOTIF_CANALES. Estas
 * pruebas confirman que Email y Telegram siguen funcionando exactamente
 * igual que antes de sumar 'push' (el aislamiento por canal y sus fallos se
 * prueban en detalle en VencimientoPushTest).
 */
class NotificacionServiceCanalesTest extends TestCase
{
    use RefreshDatabase;

    private function crearClienteConTodosLosDestinos(): Cliente
    {
        $persona = Persona::create([
            'nombre_completo'  => 'Cliente Canales',
            'apellido_paterno' => 'Test',
            'apellido_materno' => 'Test',
            'ci'               => 'CICANALES1',
            'telefono'         => '+59178001111',
            'genero'           => 'masculino',
            'fecha_nacimiento' => '1990-01-01',
            'telegram_chat_id' => '999888777',
        ]);

        $user = User::factory()->create([
            'rol_id' => Rol::firstOrCreate(['nombre' => 'Cliente'])->id,
            'email'  => 'cliente-canales@madbarzz.test',
        ]);

        return Cliente::create([
            'id_persona'     => $persona->id_persona,
            'id_usuario'     => $user->id,
            'estado'         => 'activo',
            'dias_asistidos' => 0,
        ]);
    }

    public function test_email_y_telegram_siguen_funcionando_igual_que_antes(): void
    {
        Mail::fake();
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true], 200)]);

        config(['notificaciones.canales' => ['email', 'telegram']]); // sin 'push'

        $cliente = $this->crearClienteConTodosLosDestinos();

        $notificacion = Notificacion::create([
            'id_cliente' => $cliente->id_cliente,
            'tipo'       => 'recordatorio_vencimiento',
            'mensaje'    => 'Tu membresía vence pronto.',
            'estado'     => 'pendiente',
        ]);

        $enviado = app(NotificacionService::class)->enviarACliente($notificacion);

        $this->assertTrue($enviado);

        // Nota: EmailChannel usa Mail::raw(), y Mail::fake() (MailFake::raw())
        // es un no-op a propósito en el propio Laravel — no queda nada que
        // inspeccionar ahí. La prueba real de que el canal 'email' funcionó
        // es que NotificacionService lo cuenta como usado más abajo
        // ("canal" == 'email,telegram'), igual que antes de esta etapa.
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'api.telegram.org') &&
                   $request['chat_id'] === '999888777';
        });

        $notificacion->refresh();
        $this->assertSame('enviado', $notificacion->estado);
        $this->assertSame('email,telegram', $notificacion->canal);
    }

    public function test_el_canal_push_no_se_ejecuta_si_no_esta_en_notif_canales(): void
    {
        Mail::fake();

        // 'push' deliberadamente afuera — como está hoy en .env (Etapa 3
        // todavía no lo activa). Si NotificacionService igual intentara
        // usar el canal push, quedaría registrado en "canal" más abajo.
        config(['notificaciones.canales' => ['email']]);

        $cliente = $this->crearClienteConTodosLosDestinos();

        $notificacion = Notificacion::create([
            'id_cliente' => $cliente->id_cliente,
            'tipo'       => 'recordatorio_vencimiento',
            'mensaje'    => 'mensaje',
            'estado'     => 'pendiente',
        ]);

        app(NotificacionService::class)->enviarACliente($notificacion);

        $notificacion->refresh();
        $this->assertSame('email', $notificacion->canal); // nunca aparece "push"
    }

    public function test_el_canal_push_esta_activo_por_defecto_desde_la_etapa_3(): void
    {
        // Refleja el estado real de .env en esta etapa: NOTIF_CANALES=email,telegram,push
        $canalesActivos = config('notificaciones.canales');

        $this->assertContains('push', $canalesActivos);
    }
}
