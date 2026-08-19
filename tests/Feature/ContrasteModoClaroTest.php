<?php

namespace Tests\Feature;

use App\Models\Administrador;
use App\Models\Entrenador;
use App\Models\Persona;
use App\Models\Promocion;
use App\Models\Recepcionista;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Contraste en modo claro: varios textos usaban colores pensados para fondo
 * oscuro fijo (#e2e8f0, #94a3b8) dentro de contenedores que en realidad SÍ
 * siguen el tema (.card, vía mb-skin-filament.css se pone blanco en modo
 * claro) — o quedaban pintados oscuros por la regla genérica h1..h6 de
 * mb-skin-filament.css mientras estaban sobre un banner con fondo oscuro
 * FIJO (mb-cli-hero, mb-hero-admin). El fix reemplaza esos colores fijos por
 * las variables de tema (--mbf-text / --mbf-text-muted / --mbf-border) que
 * ya existían en mb-skin-filament.css, o agrega la excepción puntual para
 * los héroes de fondo fijo (mismo patrón ya usado para el hero de Admin).
 */
class ContrasteModoClaroTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['rol_id' => Rol::firstOrCreate(['nombre' => 'Administrador'])->id]);
    }

    private function persona(string $ci): Persona
    {
        return Persona::create([
            'nombre_completo'  => 'Contraste Test',
            'apellido_paterno' => 'Claro',
            'apellido_materno' => 'Oscuro',
            'ci'               => $ci,
            'telefono'         => '+59176500001',
            'genero'           => 'masculino',
            'fecha_nacimiento' => '1990-01-01',
        ]);
    }

    // ── 1. El hero de Cliente/Entrenador (mb-cli-hero__nombre) ──────────────

    public function test_mb_skin_filament_css_tiene_la_excepcion_de_color_para_el_hero_de_cliente(): void
    {
        $css = file_get_contents(public_path('assets_private/css/mb-skin-filament.css'));

        $this->assertStringContainsString('.mb-cli-hero__nombre', $css);
    }

    // ── 2. Entrenadores: nombre, CI, experiencia, separador ──────────────────

    public function test_entrenadores_index_usa_variables_de_tema_en_vez_de_color_fijo(): void
    {
        $user     = User::factory()->create(['rol_id' => Rol::firstOrCreate(['nombre' => 'Entrenador'])->id]);
        $persona  = $this->persona('CICONTRASTE1');
        Entrenador::create([
            'id_persona' => $persona->id_persona, 'id_usuario' => $user->id,
            'especialidad' => 'Calistenia', 'experiencia' => '5 años',
            'disponibilidad' => [], 'estado' => 'activo',
        ]);

        $html = $this->actingAs($this->admin())->get('/entrenadores')->assertStatus(200)->getContent();

        $this->assertStringContainsString('var(--mbf-text', $html);
        $this->assertStringContainsString('var(--mbf-text-muted', $html);
        $this->assertStringContainsString('var(--mbf-border', $html);
        $this->assertStringContainsString('Contraste Test Claro Oscuro', $html);
    }

    // ── 3. Recepcionistas: mismo patrón ───────────────────────────────────────

    public function test_recepcionistas_index_usa_variables_de_tema_en_vez_de_color_fijo(): void
    {
        $user    = User::factory()->create(['rol_id' => Rol::firstOrCreate(['nombre' => 'Recepcionista'])->id]);
        $persona = $this->persona('CICONTRASTE2');
        Recepcionista::create([
            'id_persona' => $persona->id_persona, 'id_usuario' => $user->id,
            'turno' => 'mañana', 'punto_atencion' => 'Caja 1', 'estado' => 'activo',
        ]);

        $html = $this->actingAs($this->admin())->get('/recepcionistas')->assertStatus(200)->getContent();

        $this->assertStringContainsString('var(--mbf-text', $html);
        $this->assertStringContainsString('var(--mbf-border', $html);
        $this->assertStringContainsString('Contraste Test Claro Oscuro', $html);
    }

    // ── 4. Promociones: título y fechas ───────────────────────────────────────

    public function test_promociones_index_usa_variables_de_tema_en_vez_de_color_fijo(): void
    {
        $admin = $this->admin();
        $persona = $this->persona('CICONTRASTE3');
        $administrador = Administrador::create([
            'id_persona' => $persona->id_persona, 'id_usuario' => $admin->id,
            'nivel_acceso' => 'gestion', 'area_responsable' => 'General', 'estado' => 'activo',
        ]);

        Promocion::create([
            'id_administrador' => $administrador->id_administrador,
            'titulo'       => 'Promo Contraste',
            'descripcion'  => 'Prueba de contraste',
            'fecha_inicio' => now()->toDateString(),
            'fecha_fin'    => now()->addDays(10)->toDateString(),
            'estado'       => 'activa',
        ]);

        $html = $this->actingAs($admin)->get('/promociones')->assertStatus(200)->getContent();

        $this->assertStringContainsString('var(--mbf-text', $html);
        $this->assertStringContainsString('var(--mbf-text-muted', $html);
        $this->assertStringContainsString('Promo Contraste', $html);
    }
}
