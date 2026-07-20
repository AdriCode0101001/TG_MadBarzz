<?php

namespace Tests\Feature;

use App\Models\GrupoMuscular;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Catálogo de grupos musculares (etapa 1 del módulo de rutinas):
 * el Administrador gestiona el catálogo completo; Recepcionista y
 * Entrenador solo lo consultan (para saber qué músculos existen);
 * el Cliente no tiene acceso.
 */
class GrupoMuscularTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(string $rol): User
    {
        return User::factory()->create(['rol_id' => Rol::firstOrCreate(['nombre' => $rol])->id]);
    }

    // ── Administrador: CRUD completo ──────────────────────────────────────

    public function test_administrador_puede_crear_un_grupo_muscular(): void
    {
        $admin = $this->usuario('Administrador');

        $this->actingAs($admin)->post('/grupos_musculares', [
            'nombre'      => 'Pecho y tríceps',
            'descripcion' => 'Empuje de tren superior',
            'activo'      => '1',
        ])->assertRedirect(route('grupos_musculares.index'));

        $this->assertDatabaseHas('grupos_musculares', [
            'nombre'      => 'Pecho y tríceps',
            'descripcion' => 'Empuje de tren superior',
            'activo'      => 1,
        ]);
    }

    public function test_administrador_puede_editar_un_grupo_muscular(): void
    {
        $admin = $this->usuario('Administrador');
        $grupo = GrupoMuscular::create(['nombre' => 'Pierna', 'activo' => true]);

        $this->actingAs($admin)->put("/grupos_musculares/{$grupo->id}", [
            'nombre'      => 'Pierna completa',
            'descripcion' => 'Cuádriceps, isquios y glúteo',
            'activo'      => '0',
        ])->assertRedirect(route('grupos_musculares.index'));

        $this->assertDatabaseHas('grupos_musculares', [
            'id'          => $grupo->id,
            'nombre'      => 'Pierna completa',
            'descripcion' => 'Cuádriceps, isquios y glúteo',
            'activo'      => 0,
        ]);
    }

    public function test_administrador_puede_borrar_un_grupo_muscular(): void
    {
        $admin = $this->usuario('Administrador');
        $grupo = GrupoMuscular::create(['nombre' => 'Hombro', 'activo' => true]);

        $this->actingAs($admin)->delete("/grupos_musculares/{$grupo->id}")
            ->assertRedirect(route('grupos_musculares.index'));

        $this->assertSoftDeleted('grupos_musculares', ['id' => $grupo->id]);
    }

    public function test_el_nombre_es_obligatorio_al_crear(): void
    {
        $admin = $this->usuario('Administrador');

        $this->actingAs($admin)->post('/grupos_musculares', [
            'descripcion' => 'Sin nombre',
        ])->assertSessionHasErrors('nombre');

        $this->assertDatabaseCount('grupos_musculares', 0);
    }

    // ── Recepcionista y Entrenador: solo lectura ──────────────────────────

    public function test_recepcionista_y_entrenador_ven_el_catalogo_en_solo_lectura(): void
    {
        GrupoMuscular::create(['nombre' => 'Espalda y bíceps', 'activo' => true]);

        foreach (['Recepcionista', 'Entrenador'] as $rol) {
            $response = $this->actingAs($this->usuario($rol))->get('/grupos_musculares');

            $response->assertStatus(200)
                ->assertSee('Espalda y bíceps')
                ->assertDontSee(route('grupos_musculares.create'), false);
        }
    }

    public function test_recepcionista_no_puede_crear_un_grupo_muscular(): void
    {
        $this->actingAs($this->usuario('Recepcionista'))
            ->post('/grupos_musculares', ['nombre' => 'Full body'])
            ->assertStatus(403);

        $this->assertDatabaseCount('grupos_musculares', 0);
    }

    public function test_entrenador_no_puede_crear_un_grupo_muscular(): void
    {
        $this->actingAs($this->usuario('Entrenador'))
            ->post('/grupos_musculares', ['nombre' => 'Full body'])
            ->assertStatus(403);

        $this->assertDatabaseCount('grupos_musculares', 0);
    }

    // ── Cliente: sin acceso ────────────────────────────────────────────────

    public function test_cliente_no_accede_al_catalogo(): void
    {
        $this->actingAs($this->usuario('Cliente'))
            ->get('/grupos_musculares')
            ->assertStatus(403);
    }

    public function test_cliente_no_puede_crear_un_grupo_muscular(): void
    {
        $this->actingAs($this->usuario('Cliente'))
            ->post('/grupos_musculares', ['nombre' => 'Full body'])
            ->assertStatus(403);
    }
}
