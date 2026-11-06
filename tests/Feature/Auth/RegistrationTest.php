<?php

namespace Tests\Feature\Auth;

use App\Models\Persona;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El auto-registro público está DESACTIVADO: los clientes los da de alta
 * únicamente el personal desde el panel interno. /register (GET y POST)
 * redirige al login y no crea ninguna cuenta.
 */
class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_register_redirige_al_login(): void
    {
        $this->get('/register')->assertRedirect(route('login', absolute: false));
    }

    public function test_post_register_no_crea_cuentas_y_redirige_al_login(): void
    {
        $personas = Persona::count();
        $usuarios = User::count();

        $this->post('/register', [
            'nombre_completo'       => 'Ana',
            'apellido_paterno'      => 'Gutierrez',
            'apellido_materno'      => 'Rojas',
            'ci'                    => 'CIREGTEST1',
            'telefono'              => '+59171234567',
            'genero'                => 'femenino',
            'fecha_nacimiento'      => '1998-05-20',
            'email'                 => 'nuevocliente@example.com',
            'password'              => 'Contrasena123',
            'password_confirmation' => 'Contrasena123',
            'acepto_terminos'       => '1',
        ])->assertRedirect(route('login', absolute: false));

        $this->assertGuest();
        $this->assertSame($personas, Persona::count());
        $this->assertSame($usuarios, User::count());
        $this->assertDatabaseMissing('users', ['email' => 'nuevocliente@example.com']);
    }

    public function test_pantalla_de_login_no_ofrece_registro(): void
    {
        $html = $this->get('/login')->assertOk()->getContent();

        $this->assertStringNotContainsString('Crear cuenta', $html);
        $this->assertStringNotContainsString('action="' . url('/register') . '"', $html);
        $this->assertStringNotContainsString('data-tab="register"', $html);
        $this->assertStringContainsString('Iniciar sesión', $html);
    }

    public function test_el_sitio_publico_no_enlaza_a_register(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString('/register', $html);
    }
}
