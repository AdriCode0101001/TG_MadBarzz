<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Entrenador;
use App\Models\Persona;
use App\Models\Rol;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Foto de Entrenador: reusa la columna existente "users.foto" (mismo disco
 * 'privado' que ya usa el kiosko para el resto de los roles) — no se agrega
 * ninguna columna nueva. Lo único nuevo es una ruta pública dedicada
 * (entrenadores.foto_publica) que sirve esa foto SIN autenticación, para que
 * la sección de Entrenadores de la home pueda mostrarla. La sección de la
 * home (EntrenadorPublicoService) refleja sola cualquier alta/edición/baja
 * de entrenador o cambio de foto, sin tocar código.
 */
class EntrenadorFotoHomeTest extends TestCase
{
    use RefreshDatabase;

    private static int $seq = 0;

    protected function setUp(): void
    {
        parent::setUp();
        // EntrenadorController@store asume que el rol "Entrenador" ya existe
        // (lo busca con where()->first(), no firstOrCreate()) — en una BD de
        // test recién migrada no hay roles sembrados todavía.
        Rol::firstOrCreate(['nombre' => 'Entrenador']);
    }

    private function admin(): User
    {
        return User::factory()->create(['rol_id' => Rol::firstOrCreate(['nombre' => 'Administrador'])->id]);
    }

    private function payloadEntrenador(array $overrides = []): array
    {
        self::$seq++;
        $s = self::$seq;

        return array_merge([
            'nombre_completo'       => "Entrenador Test {$s}",
            'apellido_paterno'      => 'Apellido',
            'apellido_materno'      => 'Materno',
            'ci'                    => "CIENT{$s}",
            'telefono'              => "+59171000{$s}",
            'genero'                => 'masculino',
            'fecha_nacimiento'      => '1990-01-01',
            'name'                  => "entrenador{$s}",
            'email'                 => "entrenador{$s}@madbarzz.test",
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'especialidad'          => ['Calistenia'],
            'experiencia'           => '5 años',
            'disponibilidad'        => ['Lunes', 'Martes'],
            'estado'                => 'activo',
        ], $overrides);
    }

    // ── 1. Subir/reemplazar foto desde crear/editar Entrenador ───────────────

    public function test_administrador_puede_subir_foto_al_crear_un_entrenador(): void
    {
        Storage::fake('privado');
        $admin = $this->admin();

        $this->actingAs($admin)->post('/entrenadores', $this->payloadEntrenador([
            'foto' => UploadedFile::fake()->image('entrenador.jpg'),
        ]))->assertRedirect(route('entrenadores.index'));

        $entrenador = Entrenador::with('user')->firstOrFail();
        $this->assertNotNull($entrenador->user->foto);
        Storage::disk('privado')->assertExists($entrenador->user->foto);
    }

    public function test_la_foto_del_entrenador_es_accesible_publicamente_sin_autenticacion(): void
    {
        Storage::fake('privado');
        $admin = $this->admin();

        $this->actingAs($admin)->post('/entrenadores', $this->payloadEntrenador([
            'foto' => UploadedFile::fake()->image('entrenador.jpg'),
        ]));

        $entrenador = Entrenador::firstOrFail();

        // Sin actingAs: invitado real, la ruta debe ser pública.
        $this->get(route('entrenadores.foto_publica', $entrenador->id_entrenador))
             ->assertStatus(200);
    }

    public function test_administrador_puede_reemplazar_la_foto_de_un_entrenador(): void
    {
        Storage::fake('privado');
        $admin = $this->admin();

        $this->actingAs($admin)->post('/entrenadores', $this->payloadEntrenador([
            'foto' => UploadedFile::fake()->image('primera.jpg'),
        ]));

        $entrenador = Entrenador::with('user')->firstOrFail();
        $fotoAnterior = $entrenador->user->foto;

        $this->actingAs($admin)->put("/entrenadores/{$entrenador->id_entrenador}", $this->payloadEntrenador([
            'foto' => UploadedFile::fake()->image('nueva.jpg'),
        ]))->assertRedirect(route('entrenadores.index'));

        $entrenador->refresh();
        $entrenador->load('user');

        $this->assertNotSame($fotoAnterior, $entrenador->user->foto);
        Storage::disk('privado')->assertMissing($fotoAnterior); // no deja basura del archivo viejo
        Storage::disk('privado')->assertExists($entrenador->user->foto);
    }

    public function test_no_reenviar_archivo_al_editar_conserva_la_foto_existente(): void
    {
        Storage::fake('privado');
        $admin = $this->admin();

        $this->actingAs($admin)->post('/entrenadores', $this->payloadEntrenador([
            'foto' => UploadedFile::fake()->image('primera.jpg'),
        ]));

        $entrenador = Entrenador::with('user')->firstOrFail();
        $foto = $entrenador->user->foto;

        // Edita otros datos sin volver a adjuntar "foto"
        $this->actingAs($admin)->put("/entrenadores/{$entrenador->id_entrenador}", $this->payloadEntrenador([
            'experiencia' => '10 años',
        ]));

        $entrenador->refresh();
        $entrenador->load('user');
        $this->assertSame($foto, $entrenador->user->foto);
        Storage::disk('privado')->assertExists($foto);
    }

    public function test_rechaza_un_archivo_que_no_es_imagen_para_la_foto_del_entrenador(): void
    {
        Storage::fake('privado');
        $admin = $this->admin();

        $this->actingAs($admin)->post('/entrenadores', $this->payloadEntrenador([
            'foto' => UploadedFile::fake()->create('documento.pdf', 100),
        ]))->assertSessionHasErrors('foto');

        $this->assertDatabaseCount('entrenadores', 0);
    }

    // ── 2. Home pública: EntrenadorPublicoService ─────────────────────────────

    public function test_la_home_publica_muestra_los_entrenadores_activos_con_su_nombre_y_foto(): void
    {
        Storage::fake('privado');
        $admin = $this->admin();

        $this->actingAs($admin)->post('/entrenadores', $this->payloadEntrenador([
            'nombre_completo' => 'Carlos Fuerte',
            'foto'            => UploadedFile::fake()->image('carlos.jpg'),
        ]));

        $entrenador = Entrenador::firstOrFail();
        $urlFoto = route('entrenadores.foto_publica', $entrenador->id_entrenador);

        $this->get('/')
             ->assertStatus(200)
             ->assertSee('Carlos Fuerte')
             ->assertSee($urlFoto, false);
    }

    public function test_un_entrenador_sin_foto_muestra_su_inicial_en_la_home(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/entrenadores', $this->payloadEntrenador([
            'nombre_completo' => 'Beatriz Sin Foto',
        ]));

        $this->get('/')
             ->assertStatus(200)
             ->assertSee('Beatriz Sin Foto')
             ->assertSee('mb-marquee-avatar', false);
    }

    public function test_un_entrenador_inactivo_no_aparece_en_la_home(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/entrenadores', $this->payloadEntrenador([
            'nombre_completo' => 'Inactivo Nunca Visible',
            'estado'          => 'inactivo',
        ]));

        $this->get('/')->assertStatus(200)->assertDontSee('Inactivo Nunca Visible');
    }

    public function test_sin_entrenadores_activos_se_oculta_la_seccion_del_marquee(): void
    {
        $this->get('/')
             ->assertStatus(200)
             ->assertDontSee('marquee_text', false);
    }

    public function test_agregar_un_segundo_entrenador_activo_aparece_solo_en_la_home(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/entrenadores', $this->payloadEntrenador(['nombre_completo' => 'Primero Activo']));
        $this->get('/')->assertSee('Primero Activo')->assertDontSee('Segundo Activo');

        $this->actingAs($admin)->post('/entrenadores', $this->payloadEntrenador(['nombre_completo' => 'Segundo Activo']));
        $this->get('/')->assertSee('Primero Activo')->assertSee('Segundo Activo');
    }

    // ── 3. El uso existente de users.foto en el kiosko no se rompe ───────────

    public function test_el_uso_de_users_foto_en_el_kiosko_no_se_rompe(): void
    {
        $sede = Sede::create(['nombre' => 'Sede Test', 'direccion' => 'Calle 1', 'activo' => true]);

        $rolCliente = Rol::firstOrCreate(['nombre' => 'Cliente']);
        $user = User::factory()->create([
            'rol_id'  => $rolCliente->id,
            'id_sede' => $sede->id_sede,
            'foto'    => 'fotos_perfil/cliente-existente.jpg',
        ]);

        $persona = Persona::create([
            'nombre_completo'  => 'Cliente Con Foto',
            'apellido_paterno' => 'Test',
            'apellido_materno' => 'Test',
            'ci'               => 'CIKIOSKO1',
            'telefono'         => '+59171009999',
            'genero'           => 'masculino',
            'fecha_nacimiento' => '1990-01-01',
        ]);

        $cliente = Cliente::create([
            'id_persona'     => $persona->id_persona,
            'id_usuario'     => $user->id,
            'estado'         => 'activo',
            'dias_asistidos' => 0,
            'id_sede'        => $sede->id_sede,
        ]);

        $operador = User::factory()->create([
            'rol_id'  => Rol::firstOrCreate(['nombre' => 'Recepcionista'])->id,
            'id_sede' => $sede->id_sede,
        ]);

        $this->actingAs($operador)
             ->postJson('/asistencias-qr', ['qr_token' => $cliente->qr_token])
             ->assertStatus(200)
             ->assertJsonPath('cliente.foto', asset('fotos_perfil/cliente-existente.jpg'));
    }

    // ── 4. La pantalla de gestión de imágenes de la home sigue funcionando ───

    public function test_la_pantalla_de_imagenes_de_la_home_sigue_funcionando(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
             ->get('/configuracion/imagenes-home')
             ->assertStatus(200)
             ->assertSee('Hero / Carrusel')
             ->assertDontSee('Marquee de Entrenadores'); // ya no gestiona imágenes fijas: son fotos reales
    }
}
