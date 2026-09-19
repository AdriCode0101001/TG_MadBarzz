<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Hallazgo 6.7 de la auditoría de seguridad (Media): POST /forgot-password
 * no tenía throttle de ruta (a diferencia de login/register), y la
 * respuesta distinguía "te enviamos un enlace" de "no encontramos ese
 * usuario" — permitía enumerar qué emails están registrados. Ahora tiene
 * throttle:password-email (5/min por IP, igual que register) y la
 * respuesta es siempre la misma, exista o no el usuario.
 */
class ForgotPasswordEnumeracionTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_respuesta_es_neutra_para_un_email_que_existe(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $response = $this->post('/forgot-password', ['email' => $user->email]);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('status');
        $this->assertStringContainsString('Si existe una cuenta', session('status'));
    }

    public function test_la_respuesta_es_igual_de_neutra_para_un_email_que_no_existe(): void
    {
        Notification::fake();

        $response = $this->post('/forgot-password', ['email' => 'no.existe.nadie@test.com']);

        // Antes esto devolvía un error en 'email' ("No podemos encontrar un
        // usuario..."), revelando que el email no está registrado.
        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('status');
        $this->assertStringContainsString('Si existe una cuenta', session('status'));
    }

    public function test_los_mensajes_son_identicos_exista_o_no_el_usuario(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $conUsuario = $this->post('/forgot-password', ['email' => $user->email]);
        $sinUsuario = $this->post('/forgot-password', ['email' => 'otro.inexistente@test.com']);

        $this->assertSame($conUsuario->getSession()->get('status'), $sinUsuario->getSession()->get('status'));
    }

    public function test_forgot_password_tiene_throttle_por_ip(): void
    {
        Notification::fake();

        for ($i = 1; $i <= 5; $i++) {
            $this->post('/forgot-password', ['email' => "correo{$i}@test.com"])
                ->assertStatus(302);
        }

        // El 6to request desde la misma IP en el mismo minuto: 429.
        $this->post('/forgot-password', ['email' => 'correo6@test.com'])
            ->assertStatus(429);
    }
}
