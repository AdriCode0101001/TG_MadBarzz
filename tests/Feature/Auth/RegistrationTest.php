<?php

namespace Tests\Feature\Auth;

use App\Models\Sede;
use App\Models\User;
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

    /**
     * El registro público (Etapa 0) ya fue reactivado: RegisteredUserController
     * crea un cliente COMPLETO (persona + user + cliente), no el User suelto
     * de Breeze. Cobertura más a fondo (duplicados, rol forzado, validación,
     * rate limit) vive en RegistroPublicoClienteTest, VerificacionEmailEtapa3Test
     * y RegistroSeguridadEtapa4Test — este test confirma el camino feliz
     * contra la ruta REAL /register (antes esos usaban una ruta de test
     * porque /register estaba bloqueado).
     */
    public function test_new_users_can_register(): void
    {
        $sede = Sede::create(['nombre' => 'Sede Registro', 'direccion' => 'Calle 1', 'activo' => true]);

        $response = $this->post('/register', [
            'nombre_completo'       => 'Ana',
            'apellido_paterno'      => 'Gutierrez',
            'apellido_materno'      => 'Rojas',
            'ci'                    => 'CIREGTEST1',
            'telefono'              => '71234567',
            'genero'                => 'femenino',
            'fecha_nacimiento'      => '1998-05-20',
            'id_sede'               => $sede->id_sede,
            'email'                 => 'nuevocliente@example.com',
            'password'              => 'Contrasena123',
            'password_confirmation' => 'Contrasena123',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        $usuario = User::where('email', 'nuevocliente@example.com')->firstOrFail();
        $this->assertTrue($usuario->hasRole('Cliente'));
        $this->assertNull($usuario->email_verified_at); // sin verificar hasta que confirme el correo
        $this->assertDatabaseHas('clientes', ['id_usuario' => $usuario->id]);
    }
}
