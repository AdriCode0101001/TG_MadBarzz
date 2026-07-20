<?php

namespace Tests\Feature;

use App\Models\Ejercicio;
use App\Models\GrupoMuscular;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Listado de solo lectura del catálogo de ejercicios importado. Por ahora
 * (Etapa previa a rutinas) solo lo ve el Administrador.
 */
class EjercicioControllerTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(string $rol): User
    {
        return User::factory()->create(['rol_id' => Rol::firstOrCreate(['nombre' => $rol])->id]);
    }

    public function test_solo_administrador_ve_el_listado_de_ejercicios(): void
    {
        foreach (['Recepcionista', 'Entrenador', 'Cliente'] as $rol) {
            $this->actingAs($this->usuario($rol))
                ->get('/ejercicios')
                ->assertStatus(403);
        }

        $this->actingAs($this->usuario('Administrador'))
            ->get('/ejercicios')
            ->assertStatus(200);
    }

    public function test_el_listado_muestra_los_ejercicios_importados(): void
    {
        $grupo = GrupoMuscular::create(['nombre' => 'Pecho y tríceps', 'activo' => true]);
        Ejercicio::create([
            'dataset_id' => '0001', 'nombre' => 'barbell bench press', 'category' => 'chest',
            'target' => 'pectorals', 'equipment' => 'barbell', 'origen' => 'dataset',
            'id_grupo_muscular' => $grupo->id,
        ]);

        $this->actingAs($this->usuario('Administrador'))
            ->get('/ejercicios')
            ->assertStatus(200)
            ->assertSee('barbell bench press')
            ->assertSee('Pecho y tríceps');
    }

    public function test_filtro_por_grupo_muscular(): void
    {
        $pecho = GrupoMuscular::create(['nombre' => 'Pecho y tríceps', 'activo' => true]);
        $pierna = GrupoMuscular::create(['nombre' => 'Pierna', 'activo' => true]);
        Ejercicio::create(['nombre' => 'barbell bench press', 'origen' => 'dataset', 'id_grupo_muscular' => $pecho->id]);
        Ejercicio::create(['nombre' => 'barbell full squat', 'origen' => 'dataset', 'id_grupo_muscular' => $pierna->id]);

        $response = $this->actingAs($this->usuario('Administrador'))
            ->get('/ejercicios?grupo=' . $pecho->id);

        $response->assertSee('barbell bench press')->assertDontSee('barbell full squat');
    }

    public function test_filtro_sin_grupo(): void
    {
        $pecho = GrupoMuscular::create(['nombre' => 'Pecho y tríceps', 'activo' => true]);
        Ejercicio::create(['nombre' => 'barbell bench press', 'origen' => 'dataset', 'id_grupo_muscular' => $pecho->id]);
        Ejercicio::create(['nombre' => '3/4 sit-up', 'origen' => 'dataset', 'id_grupo_muscular' => null]);

        $response = $this->actingAs($this->usuario('Administrador'))
            ->get('/ejercicios?grupo=sin_grupo');

        $response->assertSee('3/4 sit-up')->assertDontSee('barbell bench press');
    }

    public function test_busqueda_por_nombre(): void
    {
        Ejercicio::create(['nombre' => 'barbell bench press', 'origen' => 'dataset']);
        Ejercicio::create(['nombre' => 'pull-up', 'origen' => 'dataset']);

        $response = $this->actingAs($this->usuario('Administrador'))
            ->get('/ejercicios?buscar=bench');

        $response->assertSee('barbell bench press')->assertDontSee('pull-up');
    }
}
