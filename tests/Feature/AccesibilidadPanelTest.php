<?php

namespace Tests\Feature;

use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Grupo 1 de la auditoría de privacidad/accesibilidad, arreglo 3: varios
 * formularios create/edit del panel tenían <label> sin for/id asociado a su
 * input (WCAG 3.3.2 / 1.3.1). Estas pruebas fijan la corrección en las dos
 * vistas que el propietario nombró como ejemplo.
 */
class AccesibilidadPanelTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['rol_id' => Rol::firstOrCreate(['nombre' => 'Administrador'])->id]);
    }

    public function test_clientes_create_asocia_labels_con_sus_inputs(): void
    {
        $html = $this->actingAs($this->admin())->get('/clientes/create')->assertStatus(200)->getContent();

        $this->assertStringContainsString('for="nombre_completo"', $html);
        $this->assertStringContainsString('id="nombre_completo"', $html);
        $this->assertStringContainsString('for="ci"', $html);
        $this->assertStringContainsString('id="ci"', $html);
        $this->assertStringContainsString('for="fecha_nacimiento"', $html);
        $this->assertStringContainsString('id="fecha_nacimiento"', $html);
    }

    public function test_entrenadores_create_asocia_labels_con_sus_inputs(): void
    {
        Rol::firstOrCreate(['nombre' => 'Entrenador']);

        $html = $this->actingAs($this->admin())->get('/entrenadores/create')->assertStatus(200)->getContent();

        $this->assertStringContainsString('for="nombre_completo"', $html);
        $this->assertStringContainsString('id="nombre_completo"', $html);
        $this->assertStringContainsString('for="ci"', $html);
        $this->assertStringContainsString('id="ci"', $html);
    }
}
