<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Persona;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Dropdown de perfil (header compartido, layouts/private.blade.php): tenía
 * dos bugs en celular — (1) se posicionaba con rect.left + rect.width del
 * trigger, que en mobile es solo el avatar (el texto de usuario/rol se
 * oculta bajo 575px), haciendo que el menú de 200px se saliera por la
 * derecha; (2) fondo oscuro fijo (#222327) sin importar el tema. El fix usa
 * la clase dropdown-menu de la propia plantilla Gymove (theme-aware) +
 * clamp de posición dentro del viewport, alineado al borde derecho del
 * trigger. Estas pruebas confirman el fix y que nada más del header se rompió.
 */
class HeaderProfileDropdownTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(string $rol): User
    {
        return User::factory()->create(['rol_id' => Rol::firstOrCreate(['nombre' => $rol])->id]);
    }

    private function cliente(): User
    {
        $user = $this->usuario('Cliente');

        $persona = Persona::create([
            'nombre_completo'  => 'Cliente Header',
            'apellido_paterno' => 'Test',
            'apellido_materno' => 'Test',
            'ci'               => 'CIHEADER1',
            'telefono'         => '+59175100001',
            'genero'           => 'masculino',
            'fecha_nacimiento' => '1990-01-01',
        ]);

        Cliente::create([
            'id_persona'     => $persona->id_persona,
            'id_usuario'     => $user->id,
            'estado'         => 'activo',
            'dias_asistidos' => 0,
        ]);

        return $user;
    }

    // ── El fix quedó aplicado ────────────────────────────────────────────────

    public function test_el_dropdown_de_perfil_usa_la_clase_dropdown_menu_de_la_plantilla(): void
    {
        $admin = $this->usuario('Administrador');

        $html = $this->actingAs($admin)->get('/dashboard/index')->assertStatus(200)->getContent();

        $this->assertMatchesRegularExpression(
            '/<div id="mbProfileMenu" class="dropdown-menu"/',
            $html
        );
    }

    public function test_el_dropdown_de_perfil_ya_no_tiene_el_fondo_oscuro_fijo(): void
    {
        $admin = $this->usuario('Administrador');

        $html = $this->actingAs($admin)->get('/dashboard/index')->assertStatus(200)->getContent();

        // El color fijo #222327 sigue existiendo en mbAvisosMenu (no tocado
        // en esta tarea) — lo que importa es que YA NO esté en la línea del
        // div de perfil específicamente.
        preg_match('/<div id="mbProfileMenu"[^>]*>/', $html, $match);
        $this->assertNotEmpty($match, 'No se encontró el div mbProfileMenu.');
        $this->assertStringNotContainsString('#222327', $match[0]);
        $this->assertStringNotContainsString('background', $match[0]);
    }

    public function test_el_js_de_posicionamiento_alinea_al_borde_derecho_y_limita_al_viewport(): void
    {
        $admin = $this->usuario('Administrador');

        $html = $this->actingAs($admin)->get('/dashboard/index')->assertStatus(200)->getContent();

        $this->assertStringContainsString('rect.right - anchoMenu', $html);
        $this->assertStringContainsString('window.innerWidth', $html);
    }

    // ── Nada más del header se rompió, para ningún rol ──────────────────────

    public function test_administrador_ve_el_header_con_perfil_y_logout_intactos(): void
    {
        $admin = $this->usuario('Administrador');

        $this->actingAs($admin)->get('/dashboard/index')
             ->assertStatus(200)
             ->assertSee('Perfil')
             ->assertSee('Cerrar sesión');
    }

    public function test_recepcionista_ve_el_header_con_perfil_y_logout_intactos(): void
    {
        $recepcionista = $this->usuario('Recepcionista');

        $this->actingAs($recepcionista)->get('/dashboard/index')
             ->assertStatus(200)
             ->assertSee('Perfil')
             ->assertSee('Cerrar sesión');
    }

    public function test_entrenador_ve_el_header_con_perfil_y_logout_intactos(): void
    {
        $entrenador = $this->usuario('Entrenador');

        $this->actingAs($entrenador)->get('/dashboard/entrenador')
             ->assertStatus(200)
             ->assertSee('Perfil')
             ->assertSee('Cerrar sesión');
    }

    public function test_cliente_ve_el_header_con_perfil_y_logout_intactos(): void
    {
        $cliente = $this->cliente();

        $this->actingAs($cliente)->get('/dashboard/cliente')
             ->assertStatus(200)
             ->assertSee('Perfil')
             ->assertSee('Cerrar sesión');
    }

    // ── Avisos (mismo mecanismo, no debía tocarse) sigue intacto ────────────

    public function test_el_dropdown_de_avisos_no_fue_tocado(): void
    {
        $cliente = $this->cliente();

        $html = $this->actingAs($cliente)->get('/dashboard/cliente')->assertStatus(200)->getContent();

        $this->assertStringContainsString('id="mbAvisosMenu"', $html);
        $this->assertStringContainsString('mbToggleAvisos(event)', $html);
        // La función mbToggleAvisos sigue cerrando el menú de perfil igual que antes
        $this->assertStringContainsString("profileMenu.style.display = 'none'", $html);
    }
}
