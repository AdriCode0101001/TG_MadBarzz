<?php

namespace Tests\Feature;

use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El panel Filament (/estadisticas) es solo para Administradores,
 * reutilizando la sesión del login existente de la app.
 */
class FilamentPanelAccessTest extends TestCase
{
    use RefreshDatabase;

    private function usuarioConRol(string $rolNombre): User
    {
        $rol = Rol::firstOrCreate(['nombre' => $rolNombre]);

        return User::factory()->create(['rol_id' => $rol->id]);
    }

    public function test_invitado_es_redirigido_al_login_existente(): void
    {
        $this->get('/estadisticas')->assertRedirect(route('login'));
    }

    public function test_administrador_puede_entrar_al_panel(): void
    {
        $this->actingAs($this->usuarioConRol('Administrador'))
             ->get('/estadisticas')
             ->assertStatus(200);
    }

    public function test_recepcionista_no_puede_entrar(): void
    {
        $this->actingAs($this->usuarioConRol('Recepcionista'))
             ->get('/estadisticas')
             ->assertStatus(403);
    }

    public function test_entrenador_no_puede_entrar(): void
    {
        $this->actingAs($this->usuarioConRol('Entrenador'))
             ->get('/estadisticas')
             ->assertStatus(403);
    }

    public function test_cliente_no_puede_entrar(): void
    {
        $this->actingAs($this->usuarioConRol('Cliente'))
             ->get('/estadisticas')
             ->assertStatus(403);
    }
}
