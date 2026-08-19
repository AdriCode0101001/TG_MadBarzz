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
 * Avisos — Etapa 3: marcar como leído al abrir la campanita
 * (POST /portal/avisos/marcar-leidos) + imagen en el dropdown.
 */
class AvisoMarcarLeidoTest extends TestCase
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
            'nombre_completo'  => "Cliente Leido {$s}",
            'apellido_paterno' => 'Test',
            'apellido_materno' => 'Test',
            'ci'               => "CILEIDO{$s}",
            'telefono'         => "+59174000{$s}",
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

    private function crearAviso(string $titulo = 'Aviso de prueba', ?string $imagen = null): Aviso
    {
        $admin = $this->usuario('Administrador');

        return Aviso::create([
            'titulo'     => $titulo,
            'texto'      => 'Texto de prueba para el aviso.',
            'imagen'     => $imagen,
            'id_usuario' => $admin->id,
        ]);
    }

    // ── 1. Marcar como leído al abrir ─────────────────────────────────────────

    public function test_abrir_la_campanita_marca_todos_los_avisos_como_leidos(): void
    {
        $cliente = $this->crearCliente();
        $this->crearAviso('Aviso uno');
        $this->crearAviso('Aviso dos');
        $this->crearAviso('Aviso tres');

        $this->actingAs($cliente->user)
             ->postJson('/portal/avisos/marcar-leidos')
             ->assertStatus(200)
             ->assertJson(['ok' => true]);

        $this->assertDatabaseCount('aviso_cliente', 3);

        // El contador (mismo cálculo que el composer) debe quedar en 0
        $noLeidos = Aviso::whereDoesntHave('lecturas', function ($q) use ($cliente) {
            $q->where('id_cliente', $cliente->id_cliente);
        })->count();
        $this->assertSame(0, $noLeidos);
    }

    public function test_el_badge_desaparece_tras_marcar_como_leido_sin_recargar(): void
    {
        $cliente = $this->crearCliente();
        $this->crearAviso();

        // Antes de marcar como leído: el badge está
        $this->actingAs($cliente->user)
             ->get('/dashboard/cliente')
             ->assertSee('id="mbAvisosBadge"', false);

        $this->actingAs($cliente->user)->postJson('/portal/avisos/marcar-leidos');

        // Después (nueva carga, simula lo que haría el fetch + la próxima visita): sin badge
        $this->actingAs($cliente->user)
             ->get('/dashboard/cliente')
             ->assertDontSee('id="mbAvisosBadge"', false);
    }

    // ── 2. Idempotencia ────────────────────────────────────────────────────────

    public function test_abrir_la_campanita_dos_veces_no_duplica_filas_ni_falla(): void
    {
        $cliente = $this->crearCliente();
        $this->crearAviso('Aviso uno');
        $this->crearAviso('Aviso dos');

        $this->actingAs($cliente->user)->postJson('/portal/avisos/marcar-leidos')->assertStatus(200);
        $this->actingAs($cliente->user)->postJson('/portal/avisos/marcar-leidos')
             ->assertStatus(200)
             ->assertJson(['ok' => true]);

        $this->assertDatabaseCount('aviso_cliente', 2); // no se duplicó nada
    }

    // ── 3. Un aviso nuevo vuelve a aparecer como no leído ────────────────────

    public function test_un_aviso_nuevo_despues_de_marcar_leidos_vuelve_a_aparecer_como_no_leido(): void
    {
        $cliente = $this->crearCliente();
        $this->crearAviso('Aviso viejo');

        $this->actingAs($cliente->user)->postJson('/portal/avisos/marcar-leidos')->assertStatus(200);
        $this->assertDatabaseCount('aviso_cliente', 1);

        // Llega un aviso nuevo DESPUÉS de haber marcado todo como leído
        $this->crearAviso('Aviso nuevo');

        $noLeidos = Aviso::whereDoesntHave('lecturas', function ($q) use ($cliente) {
            $q->where('id_cliente', $cliente->id_cliente);
        })->count();
        $this->assertSame(1, $noLeidos);

        $this->actingAs($cliente->user)
             ->get('/dashboard/cliente')
             ->assertSee('id="mbAvisosBadge"', false);
    }

    // ── 4. Permisos: el endpoint es solo para Cliente ────────────────────────

    public function test_administrador_no_puede_llamar_el_endpoint_de_marcar_leidos(): void
    {
        $admin = $this->usuario('Administrador');

        $this->actingAs($admin)->postJson('/portal/avisos/marcar-leidos')->assertStatus(403);
    }

    public function test_recepcionista_no_puede_llamar_el_endpoint_de_marcar_leidos(): void
    {
        $recepcionista = $this->usuario('Recepcionista');

        $this->actingAs($recepcionista)->postJson('/portal/avisos/marcar-leidos')->assertStatus(403);
    }

    public function test_entrenador_no_puede_llamar_el_endpoint_de_marcar_leidos(): void
    {
        $entrenador = $this->usuario('Entrenador');

        $this->actingAs($entrenador)->postJson('/portal/avisos/marcar-leidos')->assertStatus(403);
    }

    public function test_invitado_no_puede_llamar_el_endpoint_de_marcar_leidos(): void
    {
        $this->postJson('/portal/avisos/marcar-leidos')->assertStatus(401);
    }

    // ── 5. Imagen en el dropdown ──────────────────────────────────────────────

    public function test_la_imagen_del_aviso_se_muestra_en_el_dropdown_cuando_existe(): void
    {
        Storage::fake('public');
        $cliente = $this->crearCliente();

        $ruta = UploadedFile::fake()->image('aviso.jpg')->store('avisos', 'public');
        $this->crearAviso('Aviso con imagen', $ruta);

        $urlEsperada = Storage::disk('public')->url($ruta);

        $this->actingAs($cliente->user)
             ->get('/dashboard/cliente')
             ->assertStatus(200)
             ->assertSee($urlEsperada, false);
    }

    public function test_un_aviso_sin_imagen_muestra_el_icono_por_defecto(): void
    {
        $cliente = $this->crearCliente();
        $this->crearAviso('Aviso sin imagen');

        $this->actingAs($cliente->user)
             ->get('/dashboard/cliente')
             ->assertStatus(200)
             ->assertSee('fa-solid fa-bullhorn text-muted', false);
    }

    // ── 6. Detalle completo (overlay) ────────────────────────────────────────

    public function test_el_dropdown_incluye_los_datos_para_el_detalle_completo_del_aviso(): void
    {
        $cliente = $this->crearCliente();
        $this->crearAviso('Título completo del aviso');

        $response = $this->actingAs($cliente->user)->get('/dashboard/cliente');

        $response->assertStatus(200);
        $response->assertSee('mbAbrirAvisoDetalle', false);
        $response->assertSee('id="mbAvisoDetalleOverlay"', false);
        $response->assertSee('data-titulo="Título completo del aviso"', false);
    }

    public function test_otros_roles_no_tienen_el_overlay_de_detalle_de_avisos(): void
    {
        $this->crearAviso();
        $admin = $this->usuario('Administrador');

        $this->actingAs($admin)
             ->get('/dashboard/index')
             ->assertDontSee('id="mbAvisoDetalleOverlay"', false);
    }

    // ── 7. Nada se rompe para los otros roles ────────────────────────────────

    public function test_el_panel_de_los_otros_roles_sigue_intacto_despues_de_esta_etapa(): void
    {
        $this->crearAviso();

        $admin = $this->usuario('Administrador');
        $recepcionista = $this->usuario('Recepcionista');
        $entrenador = $this->usuario('Entrenador');

        $this->actingAs($admin)->get('/dashboard/index')->assertStatus(200);
        $this->actingAs($recepcionista)->get('/dashboard/index')->assertStatus(200);
        $this->actingAs($entrenador)->get('/dashboard/entrenador')->assertStatus(200);
    }
}
