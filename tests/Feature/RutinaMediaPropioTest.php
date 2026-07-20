<?php

namespace Tests\Feature;

use App\Models\CalendarioMusculo;
use App\Models\Ejercicio;
use App\Models\GrupoMuscular;
use App\Models\Rol;
use App\Models\Rutina;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Media propio del entrenador: cada entrenador puede subir SU propio
 * gif/foto/video de un ejercicio en SU rutina (rutina_ejercicios.media_propio),
 * sin pisar el catálogo (ejercicios.gif) ni el media de otro entrenador. El
 * cliente ve el media propio si existe, si no el del catálogo.
 */
class RutinaMediaPropioTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(string $rol): User
    {
        return User::factory()->create(['rol_id' => Rol::firstOrCreate(['nombre' => $rol])->id]);
    }

    private function proximoLunes(): Carbon
    {
        return Carbon::now('America/La_Paz')->next(Carbon::MONDAY);
    }

    private function asignarCalendario(Carbon $fecha, GrupoMuscular $grupo): CalendarioMusculo
    {
        return CalendarioMusculo::create([
            'fecha' => $fecha->toDateString(),
            'id_grupo_muscular' => $grupo->id,
            'id_sede' => 1,
        ]);
    }

    private function crearEjercicio(GrupoMuscular $grupo, string $nombre = 'Press banca', ?string $gif = null): Ejercicio
    {
        return Ejercicio::create([
            'nombre' => $nombre,
            'category' => 'chest',
            'target' => 'pectorals',
            'equipment' => 'barbell',
            'gif' => $gif,
            'atribucion' => $gif ? '© Gym visual — https://gymvisual.com/' : null,
            'id_grupo_muscular' => $grupo->id,
            'origen' => 'dataset',
        ]);
    }

    // ── Subir media propio ──────────────────────────────────────────────────

    public function test_entrenador_sube_media_propio_a_un_ejercicio_de_su_rutina(): void
    {
        Storage::fake('public');

        $grupo = GrupoMuscular::create(['nombre' => 'Pecho y tríceps', 'activo' => true]);
        $fecha = $this->proximoLunes();
        $this->asignarCalendario($fecha, $grupo);
        $ej = $this->crearEjercicio($grupo);
        $entrenador = $this->usuario('Entrenador');

        $archivo = UploadedFile::fake()->image('mi-tecnica.gif');

        $this->actingAs($entrenador)
            ->put(route('rutinas.edit', $fecha->toDateString()), [
                'ejercicios' => [
                    ['id_ejercicio' => $ej->id, 'series' => 4, 'repeticiones' => '10', 'media' => $archivo],
                ],
            ])
            ->assertRedirect();

        $rutina = Rutina::where('id_usuario', $entrenador->id)->first();
        $fila = $rutina->ejercicios()->first();

        $this->assertNotNull($fila->media_propio);
        $this->assertStringStartsWith("rutinas/{$rutina->id}/", $fila->media_propio);
        Storage::disk('public')->assertExists($fila->media_propio);
    }

    // ── El cliente ve el media propio si existe, el del catálogo si no ─────

    public function test_cliente_ve_el_media_propio_cuando_existe_y_el_del_catalogo_cuando_no(): void
    {
        Storage::fake('public');

        // Fija "hoy" en un lunes real: el portal del cliente recorta cualquier
        // fecha futura a "hoy", así que sin esto la fecha de la rutina podría
        // quedar por delante del "hoy" real de la máquina que corre el test.
        $this->travelTo(Carbon::create(2026, 7, 20, 10, 0, 0, 'America/La_Paz'));

        $grupo = GrupoMuscular::create(['nombre' => 'Pierna', 'activo' => true]);
        $fecha = Carbon::parse('2026-07-20');
        $this->asignarCalendario($fecha, $grupo);

        $ejConPropio = $this->crearEjercicio($grupo, 'Sentadilla', 'ejercicios/gifs/catalogo-sentadilla.gif');
        $ejSinPropio = $this->crearEjercicio($grupo, 'Zancadas', 'ejercicios/gifs/catalogo-zancadas.gif');

        $entrenador = $this->usuario('Entrenador');
        $archivo = UploadedFile::fake()->image('mi-sentadilla.gif');

        $this->actingAs($entrenador)->put(route('rutinas.edit', $fecha->toDateString()), [
            'ejercicios' => [
                ['id_ejercicio' => $ejConPropio->id, 'series' => 4, 'repeticiones' => '10', 'media' => $archivo],
                ['id_ejercicio' => $ejSinPropio->id, 'series' => 3, 'repeticiones' => '12'],
            ],
        ]);

        $rutina = Rutina::where('id_usuario', $entrenador->id)->first();
        $filaPropio = $rutina->ejercicios()->where('id_ejercicio', $ejConPropio->id)->first();

        // Cliente con membresía vigente, mismo grupo/sede.
        [$cliente, $sede] = [$this->crearClienteVigente(), null];

        $respuesta = $this->actingAs($cliente)
            ->get('/mi-rutina?fecha=' . $fecha->toDateString() . '&entrenador=' . $entrenador->id)
            ->assertStatus(200);

        // El ejercicio CON media propio muestra la ruta subida por el entrenador, no la del catálogo.
        $respuesta->assertSee(asset('storage/' . $filaPropio->media_propio), false);
        $respuesta->assertDontSee(asset('storage/ejercicios/gifs/catalogo-sentadilla.gif'), false);

        // El ejercicio SIN media propio muestra el gif del catálogo.
        $respuesta->assertSee(asset('storage/ejercicios/gifs/catalogo-zancadas.gif'), false);
    }

    private function crearClienteVigente(): User
    {
        $rol = Rol::firstOrCreate(['nombre' => 'Cliente']);
        $user = User::factory()->create(['rol_id' => $rol->id, 'id_sede' => 1]);

        $personaId = \Illuminate\Support\Facades\DB::table('personas')->insertGetId([
            'nombre_completo'  => 'Cliente MediaPropio',
            'apellido_paterno' => 'Test',
            'apellido_materno' => 'Rutina',
            'ci'               => 'CIMEDIA' . $user->id,
            'telefono'         => '+59174000099',
            'genero'           => 'M',
            'fecha_nacimiento' => '1990-01-01',
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        $idCliente = \Illuminate\Support\Facades\DB::table('clientes')->insertGetId([
            'id_persona'     => $personaId,
            'id_usuario'     => $user->id,
            'estado'         => 'activo',
            'dias_asistidos' => 0,
            'id_sede'        => 1,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        $idMembresia = \Illuminate\Support\Facades\DB::table('membresias')->insertGetId([
            'tipo_membresia' => 'Mensual', 'precio' => 100,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        \Illuminate\Support\Facades\DB::table('membresia_cliente')->insert([
            'id_cliente'   => $idCliente,
            'id_membresia' => $idMembresia,
            'fecha_inicio' => now()->subDays(5)->toDateString(),
            'fecha_fin'    => now()->addDays(25)->toDateString(),
            'estado'       => 'activa',
            'created_at'   => now(), 'updated_at' => now(),
        ]);

        return $user;
    }

    // ── Validación de formato y tamaño en servidor ──────────────────────────

    public function test_rechaza_un_formato_de_archivo_no_permitido(): void
    {
        Storage::fake('public');

        $grupo = GrupoMuscular::create(['nombre' => 'Hombro', 'activo' => true]);
        $fecha = $this->proximoLunes();
        $this->asignarCalendario($fecha, $grupo);
        $ej = $this->crearEjercicio($grupo);
        $entrenador = $this->usuario('Entrenador');

        $archivo = UploadedFile::fake()->create('rutina.pdf', 500, 'application/pdf');

        $this->actingAs($entrenador)
            ->put(route('rutinas.edit', $fecha->toDateString()), [
                'ejercicios' => [
                    ['id_ejercicio' => $ej->id, 'series' => 3, 'repeticiones' => '10', 'media' => $archivo],
                ],
            ])
            ->assertSessionHasErrors('ejercicios.0.media');

        $this->assertDatabaseCount('rutina_ejercicios', 0);
    }

    public function test_rechaza_un_archivo_que_supera_el_tamano_maximo(): void
    {
        Storage::fake('public');

        $grupo = GrupoMuscular::create(['nombre' => 'Espalda y bíceps', 'activo' => true]);
        $fecha = $this->proximoLunes();
        $this->asignarCalendario($fecha, $grupo);
        $ej = $this->crearEjercicio($grupo);
        $entrenador = $this->usuario('Entrenador');

        // 20480 KB (20MB) es el límite; 21000 KB lo supera.
        $archivo = UploadedFile::fake()->create('video-largo.mp4', 21000, 'video/mp4');

        $this->actingAs($entrenador)
            ->put(route('rutinas.edit', $fecha->toDateString()), [
                'ejercicios' => [
                    ['id_ejercicio' => $ej->id, 'series' => 3, 'repeticiones' => '10', 'media' => $archivo],
                ],
            ])
            ->assertSessionHasErrors('ejercicios.0.media');

        $this->assertDatabaseCount('rutina_ejercicios', 0);
    }

    public function test_acepta_un_video_corto_dentro_del_limite(): void
    {
        Storage::fake('public');

        $grupo = GrupoMuscular::create(['nombre' => 'Pierna', 'activo' => true]);
        $fecha = $this->proximoLunes();
        $this->asignarCalendario($fecha, $grupo);
        $ej = $this->crearEjercicio($grupo);
        $entrenador = $this->usuario('Entrenador');

        $archivo = UploadedFile::fake()->create('video-corto.mp4', 5000, 'video/mp4');

        $this->actingAs($entrenador)
            ->put(route('rutinas.edit', $fecha->toDateString()), [
                'ejercicios' => [
                    ['id_ejercicio' => $ej->id, 'series' => 3, 'repeticiones' => '10', 'media' => $archivo],
                ],
            ])
            ->assertRedirect();

        $rutina = Rutina::where('id_usuario', $entrenador->id)->first();
        $this->assertNotNull($rutina->ejercicios()->first()->media_propio);
    }

    // ── Aislamiento entre entrenadores ──────────────────────────────────────

    public function test_un_entrenador_no_puede_tocar_el_media_de_la_rutina_de_otro(): void
    {
        Storage::fake('public');

        $grupo = GrupoMuscular::create(['nombre' => 'Pierna', 'activo' => true]);
        $fecha = $this->proximoLunes();
        $this->asignarCalendario($fecha, $grupo);
        $ej = $this->crearEjercicio($grupo, 'Sentadilla');

        $entrenadorA = $this->usuario('Entrenador');
        $entrenadorB = $this->usuario('Entrenador');

        $archivoA = UploadedFile::fake()->image('de-a.gif');
        $this->actingAs($entrenadorA)->put(route('rutinas.edit', $fecha->toDateString()), [
            'ejercicios' => [['id_ejercicio' => $ej->id, 'series' => 3, 'repeticiones' => '10', 'media' => $archivoA]],
        ]);
        $rutinaA = Rutina::where('id_usuario', $entrenadorA->id)->first();
        $mediaA = $rutinaA->ejercicios()->first()->media_propio;

        // B arma SU PROPIA rutina el mismo día (la ruta no permite indicar la rutina de otro).
        $archivoB = UploadedFile::fake()->image('de-b.gif');
        $this->actingAs($entrenadorB)->put(route('rutinas.edit', $fecha->toDateString()), [
            'ejercicios' => [['id_ejercicio' => $ej->id, 'series' => 5, 'repeticiones' => '5', 'media' => $archivoB]],
        ]);
        $rutinaB = Rutina::where('id_usuario', $entrenadorB->id)->first();
        $mediaB = $rutinaB->ejercicios()->first()->media_propio;

        $this->assertNotSame($mediaA, $mediaB);
        Storage::disk('public')->assertExists($mediaA);
        Storage::disk('public')->assertExists($mediaB);

        // El media de A sigue intacto en la BD tras el guardado de B.
        $this->assertDatabaseHas('rutina_ejercicios', [
            'id_rutina' => $rutinaA->id, 'id_ejercicio' => $ej->id, 'media_propio' => $mediaA,
        ]);

        // B tampoco puede borrar la rutina de A (regla ya existente, reafirmada acá).
        $this->actingAs($entrenadorB)
            ->delete(route('rutinas.destroy', $rutinaA))
            ->assertStatus(403);
        Storage::disk('public')->assertExists($mediaA);
    }

    // ── Reemplazar / quitar / conservar al reeditar (delete+recreate) ──────

    public function test_no_reenviar_archivo_conserva_el_media_propio_existente(): void
    {
        Storage::fake('public');

        $grupo = GrupoMuscular::create(['nombre' => 'Pecho y tríceps', 'activo' => true]);
        $fecha = $this->proximoLunes();
        $this->asignarCalendario($fecha, $grupo);
        $ej = $this->crearEjercicio($grupo);
        $entrenador = $this->usuario('Entrenador');

        $archivo = UploadedFile::fake()->image('original.gif');
        $this->actingAs($entrenador)->put(route('rutinas.edit', $fecha->toDateString()), [
            'ejercicios' => [['id_ejercicio' => $ej->id, 'series' => 3, 'repeticiones' => '10', 'media' => $archivo]],
        ]);
        $rutina = Rutina::where('id_usuario', $entrenador->id)->first();
        $mediaOriginal = $rutina->ejercicios()->first()->media_propio;

        // Reeditar SIN tocar el archivo (solo cambia series): el media debe seguir igual.
        $this->actingAs($entrenador)->put(route('rutinas.edit', $fecha->toDateString()), [
            'ejercicios' => [['id_ejercicio' => $ej->id, 'series' => 5, 'repeticiones' => '8']],
        ]);

        $rutina->refresh();
        $filaActual = $rutina->ejercicios()->first();
        $this->assertSame($mediaOriginal, $filaActual->media_propio);
        $this->assertSame(5, $filaActual->series);
        Storage::disk('public')->assertExists($mediaOriginal);
    }

    public function test_subir_un_nuevo_archivo_reemplaza_y_borra_el_anterior(): void
    {
        Storage::fake('public');

        $grupo = GrupoMuscular::create(['nombre' => 'Hombro', 'activo' => true]);
        $fecha = $this->proximoLunes();
        $this->asignarCalendario($fecha, $grupo);
        $ej = $this->crearEjercicio($grupo);
        $entrenador = $this->usuario('Entrenador');

        $archivo1 = UploadedFile::fake()->image('v1.gif');
        $this->actingAs($entrenador)->put(route('rutinas.edit', $fecha->toDateString()), [
            'ejercicios' => [['id_ejercicio' => $ej->id, 'series' => 3, 'repeticiones' => '10', 'media' => $archivo1]],
        ]);
        $rutina = Rutina::where('id_usuario', $entrenador->id)->first();
        $mediaV1 = $rutina->ejercicios()->first()->media_propio;

        $archivo2 = UploadedFile::fake()->image('v2.gif');
        $this->actingAs($entrenador)->put(route('rutinas.edit', $fecha->toDateString()), [
            'ejercicios' => [['id_ejercicio' => $ej->id, 'series' => 3, 'repeticiones' => '10', 'media' => $archivo2]],
        ]);

        $rutina->refresh();
        $mediaV2 = $rutina->ejercicios()->first()->media_propio;

        $this->assertNotSame($mediaV1, $mediaV2);
        Storage::disk('public')->assertMissing($mediaV1);
        Storage::disk('public')->assertExists($mediaV2);
    }

    public function test_quitar_media_vuelve_al_catalogo_y_borra_el_archivo(): void
    {
        Storage::fake('public');

        $grupo = GrupoMuscular::create(['nombre' => 'Pierna', 'activo' => true]);
        $fecha = $this->proximoLunes();
        $this->asignarCalendario($fecha, $grupo);
        $ej = $this->crearEjercicio($grupo);
        $entrenador = $this->usuario('Entrenador');

        $archivo = UploadedFile::fake()->image('propio.gif');
        $this->actingAs($entrenador)->put(route('rutinas.edit', $fecha->toDateString()), [
            'ejercicios' => [['id_ejercicio' => $ej->id, 'series' => 3, 'repeticiones' => '10', 'media' => $archivo]],
        ]);
        $rutina = Rutina::where('id_usuario', $entrenador->id)->first();
        $media = $rutina->ejercicios()->first()->media_propio;
        Storage::disk('public')->assertExists($media);

        $this->actingAs($entrenador)->put(route('rutinas.edit', $fecha->toDateString()), [
            'ejercicios' => [['id_ejercicio' => $ej->id, 'series' => 3, 'repeticiones' => '10', 'quitar_media' => 1]],
        ]);

        $rutina->refresh();
        $this->assertNull($rutina->ejercicios()->first()->media_propio);
        Storage::disk('public')->assertMissing($media);
    }

    public function test_quitar_el_ejercicio_de_la_rutina_borra_su_media_propio(): void
    {
        Storage::fake('public');

        $grupo = GrupoMuscular::create(['nombre' => 'Pecho y tríceps', 'activo' => true]);
        $fecha = $this->proximoLunes();
        $this->asignarCalendario($fecha, $grupo);
        $ej1 = $this->crearEjercicio($grupo, 'Press banca');
        $ej2 = $this->crearEjercicio($grupo, 'Fondos');
        $entrenador = $this->usuario('Entrenador');

        $archivo = UploadedFile::fake()->image('press.gif');
        $this->actingAs($entrenador)->put(route('rutinas.edit', $fecha->toDateString()), [
            'ejercicios' => [
                ['id_ejercicio' => $ej1->id, 'series' => 3, 'repeticiones' => '10', 'media' => $archivo],
                ['id_ejercicio' => $ej2->id, 'series' => 3, 'repeticiones' => '10'],
            ],
        ]);
        $rutina = Rutina::where('id_usuario', $entrenador->id)->first();
        $media = $rutina->ejercicios()->where('id_ejercicio', $ej1->id)->first()->media_propio;
        Storage::disk('public')->assertExists($media);

        // Reguardar SIN ej1 (lo quitó de la rutina).
        $this->actingAs($entrenador)->put(route('rutinas.edit', $fecha->toDateString()), [
            'ejercicios' => [
                ['id_ejercicio' => $ej2->id, 'series' => 3, 'repeticiones' => '10'],
            ],
        ]);

        Storage::disk('public')->assertMissing($media);
        $this->assertDatabaseMissing('rutina_ejercicios', ['id_rutina' => $rutina->id, 'id_ejercicio' => $ej1->id]);
    }

    public function test_borrar_la_rutina_borra_los_archivos_media_propio_de_sus_ejercicios(): void
    {
        Storage::fake('public');

        $grupo = GrupoMuscular::create(['nombre' => 'Hombro', 'activo' => true]);
        $fecha = $this->proximoLunes();
        $this->asignarCalendario($fecha, $grupo);
        $ej = $this->crearEjercicio($grupo);
        $entrenador = $this->usuario('Entrenador');

        $archivo = UploadedFile::fake()->image('a-borrar.gif');
        $this->actingAs($entrenador)->put(route('rutinas.edit', $fecha->toDateString()), [
            'ejercicios' => [['id_ejercicio' => $ej->id, 'series' => 3, 'repeticiones' => '10', 'media' => $archivo]],
        ]);
        $rutina = Rutina::where('id_usuario', $entrenador->id)->first();
        $media = $rutina->ejercicios()->first()->media_propio;
        Storage::disk('public')->assertExists($media);

        $this->actingAs($entrenador)
            ->delete(route('rutinas.destroy', $rutina))
            ->assertRedirect(route('rutinas.index'));

        Storage::disk('public')->assertMissing($media);
    }
}
