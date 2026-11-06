<?php

namespace Tests\Feature;

use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Quien entra a la home pública con la sesión iniciada (p. ej. desde "Ver sitio
 * web" del panel) ve un botón para volver al panel; un visitante no.
 */
class VolverAlPanelTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(string $rol): User
    {
        return User::factory()->create(['rol_id' => Rol::firstOrCreate(['nombre' => $rol])->id]);
    }

    public function test_un_visitante_sin_sesion_no_ve_el_boton(): void
    {
        $this->get('/')->assertOk()->assertDontSee('mb-volver-panel', false)->assertDontSee('Volver al panel');
    }

    public function test_el_personal_con_sesion_ve_el_boton_hacia_el_panel(): void
    {
        foreach (['Administrador', 'Recepcionista', 'Entrenador', 'Cliente'] as $rol) {
            $html = $this->actingAs($this->usuario($rol))->get('/')->assertOk()->getContent();

            $this->assertStringContainsString('class="mb-volver-panel"', $html, $rol);
            $this->assertStringContainsString('href="' . route('dashboard') . '"', $html, $rol);
        }
    }

    public function test_el_enlace_del_boton_lleva_al_panel_de_cada_rol(): void
    {
        $this->actingAs($this->usuario('Administrador'))->get(route('dashboard'))->assertRedirect(route('dashboard.index'));
        $this->actingAs($this->usuario('Cliente'))->get(route('dashboard'))->assertRedirect(route('dashboard.cliente'));
    }
}
