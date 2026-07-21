<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        // BLOQUEO TEMPORAL (Etapa 0 del auto-registro, ver routes/auth.php):
        // POST /register ya no crea un usuario — redirige a la pantalla de
        // "registro próximamente" mientras se reescribe el flujo completo
        // (Persona + User + Cliente) en las Etapas 1-2. Quitar este skip
        // cuando se reactive el registro real.
        $this->markTestSkipped('Registro público bloqueado temporalmente (Etapa 0) — ver routes/auth.php.');

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }
}
