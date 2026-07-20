<?php

namespace Tests\Feature;

use App\Models\Ejercicio;
use App\Models\GrupoMuscular;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Comando `ejercicios:importar`: lee el dataset externo (texto + imagen +
 * gif) y lo mapea al catálogo de grupos musculares de MadBarzz. Usa un
 * dataset de prueba en tests/Fixtures (NO el dataset real de Downloads: el
 * comando debe ser portable y estos tests no pueden depender de una carpeta
 * fuera del repo que solo existe en una máquina).
 */
class ImportarEjerciciosTest extends TestCase
{
    use RefreshDatabase;

    private function rutaFixture(): string
    {
        return base_path('tests/Fixtures/exercises-dataset');
    }

    private function crearGruposMusculares(): void
    {
        foreach (['Pecho y tríceps', 'Espalda y bíceps', 'Pierna', 'Hombro', 'Full body'] as $nombre) {
            GrupoMuscular::create(['nombre' => $nombre, 'activo' => true]);
        }
    }

    public function test_importa_los_9_ejercicios_del_fixture(): void
    {
        Storage::fake('public');
        $this->crearGruposMusculares();

        Artisan::call('ejercicios:importar', ['--ruta' => $this->rutaFixture()]);

        $this->assertSame(9, Ejercicio::count());
    }

    public function test_mapea_cada_ejercicio_al_grupo_muscular_correcto(): void
    {
        Storage::fake('public');
        $this->crearGruposMusculares();

        Artisan::call('ejercicios:importar', ['--ruta' => $this->rutaFixture()]);

        $grupo = fn (string $datasetId) => Ejercicio::where('dataset_id', $datasetId)->first()->grupoMuscular?->nombre;

        $this->assertSame('Pecho y tríceps', $grupo('0001')); // chest
        $this->assertSame('Pecho y tríceps', $grupo('0002')); // upper arms + triceps
        $this->assertSame('Espalda y bíceps', $grupo('0003')); // back
        $this->assertSame('Espalda y bíceps', $grupo('0004')); // upper arms + biceps
        $this->assertSame('Pierna', $grupo('0005')); // upper legs
        $this->assertSame('Pierna', $grupo('0006')); // lower legs
        $this->assertSame('Hombro', $grupo('0007')); // shoulders
        $this->assertNull($grupo('0008')); // waist: no mapea, pero se importa
        $this->assertNull($grupo('0009')); // cardio: no mapea, pero se importa

        // "Full body" no tiene regla automática: nunca se asigna sola por el comando.
        $this->assertSame(0, Ejercicio::where('id_grupo_muscular', GrupoMuscular::where('nombre', 'Full body')->value('id'))->count());
    }

    public function test_no_descarta_los_ejercicios_sin_grupo(): void
    {
        Storage::fake('public');
        $this->crearGruposMusculares();

        Artisan::call('ejercicios:importar', ['--ruta' => $this->rutaFixture()]);

        $this->assertDatabaseHas('ejercicios', ['dataset_id' => '0008', 'id_grupo_muscular' => null]);
        $this->assertDatabaseHas('ejercicios', ['dataset_id' => '0009', 'id_grupo_muscular' => null]);
        $this->assertSame(2, Ejercicio::whereNull('id_grupo_muscular')->count());
    }

    public function test_guarda_instrucciones_en_espanol_y_la_atribucion(): void
    {
        Storage::fake('public');
        $this->crearGruposMusculares();

        Artisan::call('ejercicios:importar', ['--ruta' => $this->rutaFixture()]);

        $ej = Ejercicio::where('dataset_id', '0001')->first();

        $this->assertSame('Túmbate en un banco...', $ej->instrucciones);
        $this->assertSame('© Gym visual — https://gymvisual.com/', $ej->atribucion);
        $this->assertSame('dataset', $ej->origen);
        $this->assertSame(['triceps', 'shoulders'], $ej->secondary_muscles);
    }

    public function test_copia_la_imagen_y_el_gif_al_disco_public(): void
    {
        Storage::fake('public');
        $this->crearGruposMusculares();

        Artisan::call('ejercicios:importar', ['--ruta' => $this->rutaFixture()]);

        $ej = Ejercicio::where('dataset_id', '0001')->first();

        $this->assertSame('ejercicios/images/0001-aaa0001.jpg', $ej->imagen);
        $this->assertSame('ejercicios/gifs/0001-aaa0001.gif', $ej->gif);
        Storage::disk('public')->assertExists($ej->imagen);
        Storage::disk('public')->assertExists($ej->gif);
    }

    public function test_reimportar_no_duplica_filas(): void
    {
        Storage::fake('public');
        $this->crearGruposMusculares();

        Artisan::call('ejercicios:importar', ['--ruta' => $this->rutaFixture()]);
        Artisan::call('ejercicios:importar', ['--ruta' => $this->rutaFixture()]);

        $this->assertSame(9, Ejercicio::count());
        $salida = Artisan::output();
        $this->assertStringContainsString('nuevos: 0', $salida);
        $this->assertStringContainsString('actualizados: 9', $salida);
    }

    public function test_sin_ruta_valida_falla_con_mensaje_claro(): void
    {
        $codigo = Artisan::call('ejercicios:importar', ['--ruta' => 'C:\ruta\que\no\existe']);

        $this->assertNotSame(0, $codigo);
        $this->assertStringContainsString('No se encontró el dataset', Artisan::output());
    }
}
