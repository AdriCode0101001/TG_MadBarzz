<?php

namespace App\Console\Commands;

use App\Models\Ejercicio;
use App\Models\GrupoMuscular;
use Illuminate\Console\Command;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;

/**
 * Importa el catálogo de ejercicios desde el dataset externo
 * (data/exercises.json + images/ + videos/) a la tabla `ejercicios`.
 *
 * Idempotente: reimportar no duplica (upsert por dataset_id) ni recopia
 * medios ya presentes en el disco "public".
 *
 * Las imágenes/GIFs son © Gym visual (ver NOTICE.md del dataset): se
 * importan como contenido de ARRANQUE para desarrollo/demo. La idea es que
 * los entrenadores los reemplacen por contenido propio, y que se
 * revisen/reemplacen antes de un lanzamiento público real.
 */
class ImportarEjercicios extends Command
{
    // Sin default: un path fijo de una máquina de desarrollo puntual no
    // tiene sentido en ningún otro entorno (ni otro dev, ni el servidor).
    // Sin --ruta=, handle() ya muestra un error claro pidiéndolo.
    protected $signature = 'ejercicios:importar
                            {--ruta= : Carpeta raíz del dataset (contiene data/, images/, videos/)}';

    protected $description = 'Importa el catálogo de ejercicios del dataset externo (texto + imagen + gif), mapeando al grupo muscular de MadBarzz.';

    /** @var array<string,int> nombre de grupo muscular => id */
    private array $gruposPorNombre = [];

    public function handle(): int
    {
        $raiz = rtrim((string) $this->option('ruta'), '\\/');
        $jsonPath = $raiz . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'exercises.json';
        $imagesDir = $raiz . DIRECTORY_SEPARATOR . 'images';
        $videosDir = $raiz . DIRECTORY_SEPARATOR . 'videos';

        if (!is_file($jsonPath)) {
            $this->error("No se encontró el dataset en: {$jsonPath}");
            $this->line('Usa --ruta=... para indicar la carpeta raíz del dataset (la que contiene data/, images/ y videos/).');
            return self::FAILURE;
        }

        $registros = json_decode(file_get_contents($jsonPath), true, flags: JSON_THROW_ON_ERROR);
        if (!is_array($registros)) {
            $this->error('El JSON del dataset no tiene el formato esperado (array de ejercicios).');
            return self::FAILURE;
        }

        $this->gruposPorNombre = GrupoMuscular::pluck('id', 'nombre')->all();
        if ($this->gruposPorNombre === []) {
            $this->warn('No hay grupos musculares en la BD todavía (corre el seeder de grupos_musculares primero). Todos los ejercicios quedarán sin id_grupo_muscular.');
        }

        $creados = 0;
        $actualizados = 0;
        $sinGrupo = 0;
        $porGrupo = [];

        $barra = $this->output->createProgressBar(count($registros));
        $barra->start();

        foreach ($registros as $r) {
            $idGrupo = $this->mapearGrupoMuscular($r['category'] ?? '', $r['target'] ?? '');

            $imagenRel = $this->copiarMedio($imagesDir, $r['image'] ?? null, 'ejercicios/images');
            $gifRel    = $this->copiarMedio($videosDir, $r['gif_url'] ?? null, 'ejercicios/gifs');

            $ejercicio = Ejercicio::withTrashed()->updateOrCreate(
                ['dataset_id' => $r['id'] ?? null],
                [
                    'nombre'             => $r['name'] ?? '(sin nombre)',
                    'category'           => $r['category'] ?? null,
                    'target'             => $r['target'] ?? null,
                    'muscle_group'       => $r['muscle_group'] ?? null,
                    'secondary_muscles'  => $r['secondary_muscles'] ?? [],
                    'equipment'          => $r['equipment'] ?? null,
                    'instrucciones'      => $r['instructions']['es'] ?? null,
                    'imagen'             => $imagenRel,
                    'gif'                => $gifRel,
                    'atribucion'         => $r['attribution'] ?? null,
                    'id_grupo_muscular'  => $idGrupo,
                    'origen'             => 'dataset',
                    'deleted_at'         => null,
                ]
            );

            $ejercicio->wasRecentlyCreated ? $creados++ : $actualizados++;

            if ($idGrupo === null) {
                $sinGrupo++;
            } else {
                $nombreGrupo = array_search($idGrupo, $this->gruposPorNombre, true);
                $porGrupo[$nombreGrupo] = ($porGrupo[$nombreGrupo] ?? 0) + 1;
            }

            $barra->advance();
        }

        $barra->finish();
        $this->newLine(2);

        $total = $creados + $actualizados;
        $this->info("Procesados: {$total}  (nuevos: {$creados}, actualizados: {$actualizados})");
        $this->newLine();
        $this->line('Por grupo muscular:');
        foreach ($porGrupo as $nombre => $cantidad) {
            $this->line("  - {$nombre}: {$cantidad}");
        }
        $this->line("  - (sin grupo): {$sinGrupo}");

        return self::SUCCESS;
    }

    /**
     * Mapeo acordado a los grupos musculares de MadBarzz. "Full body" se deja
     * sin regla automática (no hay categoría de dataset equivalente); los que
     * no encajan (waist, cardio, neck, lower arms) quedan con id null pero SÍ
     * se importan.
     */
    private function mapearGrupoMuscular(string $category, string $target): ?int
    {
        $nombreGrupo = match (true) {
            $category === 'chest'                                    => 'Pecho y tríceps',
            $category === 'upper arms' && $target === 'triceps'       => 'Pecho y tríceps',
            $category === 'back'                                      => 'Espalda y bíceps',
            $category === 'upper arms' && $target === 'biceps'        => 'Espalda y bíceps',
            in_array($category, ['upper legs', 'lower legs'], true)   => 'Pierna',
            $category === 'shoulders'                                 => 'Hombro',
            default                                                   => null,
        };

        return $nombreGrupo !== null ? ($this->gruposPorNombre[$nombreGrupo] ?? null) : null;
    }

    /**
     * Copia un archivo de medio del dataset al disco "public" (idempotente:
     * si ya existe, no lo vuelve a copiar) y devuelve la ruta relativa
     * guardable en BD, o null si el archivo de origen no existe.
     */
    private function copiarMedio(string $dirOrigen, ?string $rutaRelativaDataset, string $carpetaDestino): ?string
    {
        if (!$rutaRelativaDataset) {
            return null;
        }

        $nombreArchivo = basename($rutaRelativaDataset);
        $origenAbs = $dirOrigen . DIRECTORY_SEPARATOR . $nombreArchivo;

        if (!is_file($origenAbs)) {
            return null;
        }

        $destinoRel = $carpetaDestino . '/' . $nombreArchivo;

        if (!Storage::disk('public')->exists($destinoRel)) {
            Storage::disk('public')->putFileAs($carpetaDestino, new File($origenAbs), $nombreArchivo);
        }

        return $destinoRel;
    }
}
