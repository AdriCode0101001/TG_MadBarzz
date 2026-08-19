<?php

namespace Tests\Feature;

use App\Models\Aviso;
use App\Models\AvisoCliente;
use App\Models\Cliente;
use App\Models\Persona;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Avisos: Administrador/Recepcionista crean UNA fila que queda dirigida a
 * TODOS los clientes (sin fan-out) — el cliente los lee desde su portal
 * (campanita, Etapa 2, todavía no construida). Distinto del módulo
 * "Notificaciones" existente (WhatsApp a un cliente puntual).
 */
class AvisoTest extends TestCase
{
    use RefreshDatabase;

    private static int $seq = 0;

    private function usuario(string $rol): User
    {
        return User::factory()->create(['rol_id' => Rol::firstOrCreate(['nombre' => $rol])->id]);
    }

    private function crearCliente(): Cliente
    {
        self::$seq++;
        $s = self::$seq;

        $persona = Persona::create([
            'nombre_completo'  => "Cliente Aviso {$s}",
            'apellido_paterno' => 'Test',
            'apellido_materno' => 'Test',
            'ci'               => "CIAVISO{$s}",
            'telefono'         => "+59172000{$s}",
            'genero'           => 'masculino',
            'fecha_nacimiento' => '1990-01-01',
        ]);

        $user = User::factory()->create(['rol_id' => Rol::firstOrCreate(['nombre' => 'Cliente'])->id]);

        return Cliente::create([
            'id_persona'     => $persona->id_persona,
            'id_usuario'     => $user->id,
            'estado'         => 'activo',
            'dias_asistidos' => 0,
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'titulo' => 'Feriado la próxima semana',
            'texto'  => 'El lunes 5 el gimnasio permanece cerrado por feriado nacional.',
        ], $overrides);
    }

    // ── 1. Administrador/Recepcionista pueden crear un aviso ─────────────────

    public function test_administrador_puede_crear_un_aviso_sin_imagen(): void
    {
        $admin = $this->usuario('Administrador');

        $this->actingAs($admin)->post('/avisos', $this->payload())
             ->assertRedirect(route('avisos.index'));

        $aviso = Aviso::firstOrFail();
        $this->assertSame('Feriado la próxima semana', $aviso->titulo);
        $this->assertNull($aviso->imagen);
        $this->assertSame($admin->id, $aviso->id_usuario);
    }

    public function test_administrador_puede_crear_un_aviso_con_imagen(): void
    {
        Storage::fake('public');
        $admin = $this->usuario('Administrador');

        $this->actingAs($admin)->post('/avisos', $this->payload([
            'imagen' => UploadedFile::fake()->image('aviso.jpg'),
        ]))->assertRedirect(route('avisos.index'));

        $aviso = Aviso::firstOrFail();
        $this->assertNotNull($aviso->imagen);
        Storage::disk('public')->assertExists($aviso->imagen);
    }

    public function test_recepcionista_puede_crear_un_aviso(): void
    {
        $recepcionista = $this->usuario('Recepcionista');

        $this->actingAs($recepcionista)->post('/avisos', $this->payload())
             ->assertRedirect(route('avisos.index'));

        $this->assertDatabaseCount('avisos', 1);
    }

    // ── 2. Enviar a "todos" = UNA fila, sin fan-out ──────────────────────────

    public function test_crear_un_aviso_no_genera_filas_en_aviso_cliente(): void
    {
        $admin = $this->usuario('Administrador');
        $this->crearCliente();
        $this->crearCliente();
        $this->crearCliente();

        $this->actingAs($admin)->post('/avisos', $this->payload());

        $this->assertDatabaseCount('avisos', 1);
        $this->assertDatabaseCount('aviso_cliente', 0); // sin fan-out al crear
    }

    // ── 3. Validación ─────────────────────────────────────────────────────────

    public function test_rechaza_titulo_vacio(): void
    {
        $admin = $this->usuario('Administrador');

        $this->actingAs($admin)->post('/avisos', $this->payload(['titulo' => '']))
             ->assertSessionHasErrors('titulo');

        $this->assertDatabaseCount('avisos', 0);
    }

    public function test_rechaza_texto_vacio(): void
    {
        $admin = $this->usuario('Administrador');

        $this->actingAs($admin)->post('/avisos', $this->payload(['texto' => '']))
             ->assertSessionHasErrors('texto');

        $this->assertDatabaseCount('avisos', 0);
    }

    public function test_rechaza_un_archivo_que_no_es_imagen(): void
    {
        Storage::fake('public');
        $admin = $this->usuario('Administrador');

        $this->actingAs($admin)->post('/avisos', $this->payload([
            'imagen' => UploadedFile::fake()->create('documento.pdf', 100),
        ]))->assertSessionHasErrors('imagen');

        $this->assertDatabaseCount('avisos', 0);
    }

    public function test_rechaza_una_imagen_que_supera_el_tamano_maximo(): void
    {
        Storage::fake('public');
        $admin = $this->usuario('Administrador');

        $this->actingAs($admin)->post('/avisos', $this->payload([
            'imagen' => UploadedFile::fake()->image('grande.jpg')->size(3000),
        ]))->assertSessionHasErrors('imagen');

        $this->assertDatabaseCount('avisos', 0);
    }

    // ── 4. Permisos por ruta (no solo ocultar el menú) ───────────────────────

    public function test_entrenador_no_accede_a_avisos(): void
    {
        $entrenador = $this->usuario('Entrenador');

        $this->actingAs($entrenador)->get('/avisos')->assertStatus(403);
        $this->actingAs($entrenador)->get('/avisos/create')->assertStatus(403);
        $this->actingAs($entrenador)->post('/avisos', $this->payload())->assertStatus(403);

        $this->assertDatabaseCount('avisos', 0);
    }

    public function test_cliente_no_accede_a_avisos(): void
    {
        $rolCliente = Rol::firstOrCreate(['nombre' => 'Cliente']);
        $cliente = User::factory()->create(['rol_id' => $rolCliente->id]);

        $this->actingAs($cliente)->get('/avisos')->assertStatus(403);
        $this->actingAs($cliente)->get('/avisos/create')->assertStatus(403);
        $this->actingAs($cliente)->post('/avisos', $this->payload())->assertStatus(403);

        $this->assertDatabaseCount('avisos', 0);
    }

    public function test_invitado_no_accede_a_avisos(): void
    {
        $this->get('/avisos')->assertRedirect(route('login'));
        $this->get('/avisos/create')->assertRedirect(route('login'));
    }

    // ── 5. El listado ─────────────────────────────────────────────────────────

    public function test_el_listado_muestra_los_avisos_enviados_con_su_conteo_de_lecturas(): void
    {
        $admin = $this->usuario('Administrador');
        $cliente1 = $this->crearCliente();
        $cliente2 = $this->crearCliente();

        $this->actingAs($admin)->post('/avisos', $this->payload(['titulo' => 'Aviso de prueba']));
        $aviso = Aviso::firstOrFail();

        // Un cliente ya lo leyó (fila creada solo al leer)
        AvisoCliente::create(['id_aviso' => $aviso->id, 'id_cliente' => $cliente1->id_cliente]);

        $this->actingAs($admin)->get('/avisos')
             ->assertStatus(200)
             ->assertSee('Aviso de prueba')
             ->assertSee('1 / 2'); // 1 de 2 clientes lo leyeron
    }

    public function test_el_listado_esta_vacio_cuando_no_se_envio_nada(): void
    {
        $admin = $this->usuario('Administrador');

        $this->actingAs($admin)->get('/avisos')
             ->assertStatus(200)
             ->assertSee('Todavía no se envió ningún aviso');
    }

    // ── 6. Retirar un aviso (soft delete) ────────────────────────────────────

    public function test_administrador_puede_retirar_un_aviso(): void
    {
        $admin = $this->usuario('Administrador');
        $this->actingAs($admin)->post('/avisos', $this->payload());
        $aviso = Aviso::firstOrFail();

        $this->actingAs($admin)->delete("/avisos/{$aviso->id}")
             ->assertRedirect(route('avisos.index'));

        $this->assertSoftDeleted('avisos', ['id' => $aviso->id]);
        $this->actingAs($admin)->get('/avisos')->assertDontSee($aviso->titulo);
    }

    // ── 7. Métodos del modelo ─────────────────────────────────────────────────

    public function test_lo_leyo_cliente_y_cantidad_lecturas(): void
    {
        $admin = $this->usuario('Administrador');
        $cliente1 = $this->crearCliente();
        $cliente2 = $this->crearCliente();

        $this->actingAs($admin)->post('/avisos', $this->payload());
        $aviso = Aviso::firstOrFail();

        $this->assertFalse($aviso->loLeyoCliente($cliente1->id_cliente));
        $this->assertSame(0, $aviso->cantidadLecturas());

        AvisoCliente::firstOrCreate(['id_aviso' => $aviso->id, 'id_cliente' => $cliente1->id_cliente]);

        $this->assertTrue($aviso->fresh()->loLeyoCliente($cliente1->id_cliente));
        $this->assertFalse($aviso->fresh()->loLeyoCliente($cliente2->id_cliente));
        $this->assertSame(1, $aviso->fresh()->cantidadLecturas());
    }
}
