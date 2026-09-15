<?php

namespace Tests\Feature;

use App\Models\Descuento;
use App\Models\Persona;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

/**
 * Bitácora de Auditoría — Etapa 3: la PANTALLA del admin sobre lo que ya
 * registran las Etapas 1 y 2. Solo Administrador accede (por ruta); el
 * listado se lee en español, paginado, con filtros combinables y detalle
 * antes→después.
 */
class BitacoraPanelTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(string $rol, array $overrides = []): User
    {
        return User::factory()->create(array_merge(['rol_id' => Rol::firstOrCreate(['nombre' => $rol])->id], $overrides));
    }

    private function admin(array $overrides = []): User
    {
        return $this->usuario('Administrador', $overrides);
    }

    /** Un Administrador con Persona vinculada, para que la frase muestre su nombre real. */
    private function adminConNombre(string $nombre = 'Marcos Olea'): User
    {
        $user = $this->admin();

        $persona = Persona::create([
            'nombre_completo'  => $nombre,
            'apellido_paterno' => 'Test',
            'apellido_materno' => 'Bitacora',
            'ci'               => 'CIADM' . $user->id,
            'telefono'         => '+59170000' . $user->id,
            'genero'           => 'masculino',
            'fecha_nacimiento' => '1985-01-01',
        ]);

        DB::table('administradores')->insert([
            'id_persona'       => $persona->id_persona,
            'id_usuario'       => $user->id,
            'nivel_acceso'     => 'superadmin',
            'area_responsable' => 'Gerencia',
            'estado'           => 'activo',
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        return $user;
    }

    // ── 1. Acceso: SOLO Administrador ────────────────────────────────────

    public function test_el_administrador_accede_a_la_bitacora(): void
    {
        $this->actingAs($this->admin())
            ->get(route('bitacora.index'))
            ->assertStatus(200);
    }

    public function test_otros_roles_reciben_403(): void
    {
        foreach (['Recepcionista', 'Entrenador', 'Cliente'] as $rol) {
            $this->actingAs($this->usuario($rol))
                ->get(route('bitacora.index'))
                ->assertStatus(403);
        }
    }

    public function test_un_invitado_no_autenticado_no_entra(): void
    {
        $this->get(route('bitacora.index'))->assertRedirect(route('login'));
    }

    // ── 2. Listado legible en español ────────────────────────────────────

    public function test_el_listado_muestra_una_frase_legible_con_quien_accion_y_entidad(): void
    {
        $admin = $this->adminConNombre('Marcos Olea');
        $this->actingAs($admin);

        $descuento = Descuento::create(['nombre' => 'Promo bitácora', 'porcentaje' => 10, 'activo' => true]);

        $response = $this->get(route('bitacora.index'));

        $response->assertStatus(200);
        $response->assertSee('Marcos Olea', false);
        $response->assertSee('Administrador', false);
        $response->assertSee('creó', false);
        $response->assertSee('el descuento', false);
        $response->assertSee('#' . $descuento->id, false);
    }

    public function test_un_causer_null_se_muestra_como_sistema_automatico(): void
    {
        $this->actingAs($this->admin());

        Activity::create([
            'log_name'     => 'default',
            'description'  => 'Descuento created',
            'subject_type' => Descuento::class,
            'subject_id'   => 1,
            'causer_type'  => null,
            'causer_id'    => null,
            'event'        => 'created',
            'properties'   => ['attributes' => ['nombre' => 'Automático', 'porcentaje' => 5, 'activo' => true]],
        ]);

        $this->get(route('bitacora.index'))
            ->assertStatus(200)
            ->assertSee('Sistema (automático)', false);
    }

    // ── 3. Paginación ─────────────────────────────────────────────────────

    public function test_el_listado_esta_paginado(): void
    {
        $this->actingAs($this->admin());

        for ($i = 1; $i <= 25; $i++) {
            Descuento::create(['nombre' => "Promo {$i}", 'porcentaje' => 1, 'activo' => true]);
        }

        $pagina1 = $this->get(route('bitacora.index'));
        $pagina1->assertStatus(200);
        // 25 "created" de Descuento (25 filas de bitácora): la página 1 no las trae todas.
        $pagina1->assertSee('Promo 25', false); // el más reciente, primero por orden descendente
        $pagina1->assertDontSee('Promo 1 ', false);

        $pagina2 = $this->get(route('bitacora.index', ['page' => 2]));
        $pagina2->assertStatus(200);
    }

    // ── 4. Filtros combinables ────────────────────────────────────────────

    public function test_los_filtros_por_usuario_accion_entidad_y_fecha_se_combinan(): void
    {
        $admin1 = $this->adminConNombre('Filtro Uno');
        $admin2 = $this->adminConNombre('Filtro Dos');

        $this->actingAs($admin1);
        $descuentoA = Descuento::create(['nombre' => 'Filtrable A', 'porcentaje' => 10, 'activo' => true]);
        Activity::where('subject_type', Descuento::class)->where('subject_id', $descuentoA->id)
            ->update(['created_at' => Carbon::parse('2026-01-10 10:00:00')]);

        $this->actingAs($admin2);
        $descuentoA->update(['porcentaje' => 20]); // "updated" por admin2
        Activity::where('subject_type', Descuento::class)->where('subject_id', $descuentoA->id)->where('event', 'updated')
            ->update(['created_at' => Carbon::parse('2026-02-15 10:00:00')]);

        // Filtrar por usuario: solo admin1 -> solo el "created" (el <select> de
        // usuarios sí lista a "Filtro Dos" como opción; lo que no debe aparecer
        // es SU frase de auditoría).
        $porUsuario = $this->actingAs($admin1)->get(route('bitacora.index', ['causer_id' => $admin1->id]));
        $porUsuario->assertSee('Filtro Uno Test (Administrador) creó', false);
        $porUsuario->assertDontSee('Filtro Dos Test (Administrador) editó', false);

        // Filtrar por acción: updated -> no debe verse el "creó"
        $porAccion = $this->get(route('bitacora.index', ['evento' => 'updated']));
        $porAccion->assertSee('editó', false);
        $porAccion->assertDontSee('creó', false);

        // Filtrar por entidad: Descuento -> aparecen ambos eventos de Descuento
        $porEntidad = $this->get(route('bitacora.index', ['entidad' => Descuento::class]));
        $porEntidad->assertSee('el descuento', false);

        // Filtrar por rango de fechas: solo enero -> el "created", no el "updated" de febrero
        $porFecha = $this->get(route('bitacora.index', ['desde' => '2026-01-01', 'hasta' => '2026-01-31']));
        $porFecha->assertSee('creó', false);
        $porFecha->assertDontSee('editó', false);

        // Combinado: usuario=admin2 + acción=updated -> debe verse
        $combinado = $this->get(route('bitacora.index', ['causer_id' => $admin2->id, 'evento' => 'updated']));
        $combinado->assertSee('Filtro Dos', false);
        $combinado->assertSee('editó', false);
    }

    // ── 5. Detalle antes → después ────────────────────────────────────────

    public function test_el_detalle_muestra_los_valores_antes_y_despues_en_una_edicion(): void
    {
        $this->actingAs($this->admin());

        $descuento = Descuento::create(['nombre' => 'Con detalle', 'porcentaje' => 10, 'activo' => true]);
        Activity::query()->delete();
        $descuento->update(['porcentaje' => 30]);

        $response = $this->get(route('bitacora.index'));

        $response->assertStatus(200);
        $response->assertSee('10.00', false);
        $response->assertSee('30.00', false);
    }

    public function test_el_detalle_de_un_creado_muestra_los_valores_nuevos(): void
    {
        $this->actingAs($this->admin());

        Descuento::create(['nombre' => 'Nuevo de una', 'porcentaje' => 42, 'activo' => true]);

        $this->get(route('bitacora.index'))
            ->assertStatus(200)
            ->assertSee('Nuevo de una', false)
            ->assertSee('42.00', false);
    }
}
