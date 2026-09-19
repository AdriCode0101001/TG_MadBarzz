<?php

namespace Tests\Feature\Security;

use App\Models\Rol;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Hallazgo 6.5 de la auditoría de seguridad (Media): la contraseña mínima
 * era de 6 caracteres en varios formularios (alta de personal, alta de
 * cliente). Se centralizó en Rules\Password::defaults() (AppServiceProvider)
 * con mínimo 8, y se migraron todos los `min:6` sueltos a esa misma regla.
 */
class PasswordMinimoTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'rol_id'  => Rol::firstOrCreate(['nombre' => 'Administrador'])->id,
            'id_sede' => Sede::firstOrCreate(['nombre' => 'Sede Principal'])->id_sede,
        ]);
    }

    private function datosPersonaBase(string $ci, string $telefono): array
    {
        return [
            'nombre_completo'  => 'Persona Prueba',
            'apellido_paterno' => 'Test',
            'apellido_materno' => 'Password',
            'ci'               => $ci,
            'telefono'         => $telefono,
            'genero'           => 'masculino',
            'fecha_nacimiento' => '1990-01-01',
        ];
    }

    // ── Alta de Usuario (users.store) ───────────────────────────────────────

    public function test_crear_usuario_rechaza_contrasena_de_menos_de_8_caracteres(): void
    {
        $this->actingAs($this->admin())
            ->post(route('users.store'), [
                'name'                  => 'usuario.corto',
                'email'                 => 'corto@test.com',
                'password'              => '1234567', // 7 caracteres
                'password_confirmation' => '1234567',
                'rol_id'                => Rol::firstOrCreate(['nombre' => 'Recepcionista'])->id,
            ])
            ->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('users', ['email' => 'corto@test.com']);
    }

    public function test_crear_usuario_acepta_contrasena_de_8_caracteres(): void
    {
        $this->actingAs($this->admin())
            ->post(route('users.store'), [
                'name'                  => 'usuario.valido',
                'email'                 => 'valido@test.com',
                'password'              => '12345678', // 8 caracteres exactos
                'password_confirmation' => '12345678',
                'rol_id'                => Rol::firstOrCreate(['nombre' => 'Recepcionista'])->id,
            ])
            ->assertSessionDoesntHaveErrors('password');

        $this->assertDatabaseHas('users', ['email' => 'valido@test.com']);
    }

    // ── Alta de Cliente (clientes.store) ────────────────────────────────────

    public function test_crear_cliente_rechaza_contrasena_de_menos_de_8_caracteres(): void
    {
        $this->actingAs($this->admin())
            ->post(route('clientes.store'), array_merge(
                $this->datosPersonaBase('CIPW1', '+59171000001'),
                [
                    'email'                 => 'clientecorto@test.com',
                    'password'              => 'corta12',
                    'password_confirmation' => 'corta12',
                    'estado'                => 'activo',
                ]
            ))
            ->assertSessionHasErrors('password');
    }

    public function test_crear_cliente_acepta_contrasena_de_8_caracteres(): void
    {
        $this->actingAs($this->admin())
            ->post(route('clientes.store'), array_merge(
                $this->datosPersonaBase('CIPW2', '+59171000002'),
                [
                    'email'                 => 'clientevalido@test.com',
                    'password'              => 'valida12',
                    'password_confirmation' => 'valida12',
                    'estado'                => 'activo',
                ]
            ))
            ->assertSessionDoesntHaveErrors('password');
    }

    // ── Registro público (RegisteredUserController — ya usaba Password::defaults()) ─

    public function test_registro_publico_ya_exigia_minimo_8_via_password_defaults(): void
    {
        $sede = Sede::firstOrCreate(['nombre' => 'Sede Principal'], ['activo' => true]);

        $this->post(route('register'), [
            'nombre_completo'    => 'Cliente Publico',
            'apellido_paterno'   => 'Test',
            'apellido_materno'   => 'Registro',
            'ci'                 => 'CIPUB1',
            'telefono'           => '+59171000009',
            'genero'             => 'masculino',
            'fecha_nacimiento'   => '1995-01-01',
            'id_sede'            => $sede->id_sede,
            'email'              => 'registropublico@test.com',
            'password'           => 'corta1',
            'password_confirmation' => 'corta1',
        ])->assertSessionHasErrors('password');
    }

    // ── El mensaje de error es claro en español ─────────────────────────────

    public function test_el_mensaje_de_error_de_contrasena_corta_es_claro_en_espanol(): void
    {
        $response = $this->actingAs($this->admin())
            ->post(route('users.store'), [
                'name'                  => 'usuario.mensaje',
                'email'                 => 'mensaje@test.com',
                'password'              => 'corta1',
                'password_confirmation' => 'corta1',
                'rol_id'                => Rol::firstOrCreate(['nombre' => 'Recepcionista'])->id,
            ]);

        $response->assertSessionHasErrors('password');
        $mensaje = session('errors')->first('password');

        $this->assertStringContainsString('contraseña', $mensaje);
        $this->assertStringContainsString('8', $mensaje);
    }
}
