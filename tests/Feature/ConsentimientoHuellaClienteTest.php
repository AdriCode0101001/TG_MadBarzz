<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

/**
 * Grupo 3 legal, parte 2: consentimiento para el registro de la huella
 * (alta/edición del cliente por el personal). El checkbox del panel es
 * solo el dato — la protección real vive en BiometriaController::enrolar()
 * (ver BiometriaApiTest), pero este archivo cubre que el dato se guarde
 * bien para adultos y menores, y que la bitácora lo registre.
 */
class ConsentimientoHuellaClienteTest extends TestCase
{
    use RefreshDatabase;

    private static int $seq = 0;

    private function operador(): User
    {
        return User::factory()->create(['rol_id' => Rol::firstOrCreate(['nombre' => 'Recepcionista'])->id]);
    }

    private function payload(array $extra = []): array
    {
        self::$seq++;
        $s = self::$seq;

        return array_merge([
            'nombre_completo'       => 'Jhon',
            'apellido_paterno'      => 'Crespo',
            'apellido_materno'      => 'Coria',
            'ci'                    => "CICONS{$s}",
            'telefono'              => '6' . str_pad((string) $s, 7, '0', STR_PAD_LEFT),
            'genero'                => 'masculino',
            'fecha_nacimiento'      => '1995-03-10', // adulto
            'email'                 => "cliente{$s}@test.com",
            'password'              => 'secreto123',
            'password_confirmation' => 'secreto123',
            'estado'                => 'activo',
        ], $extra);
    }

    // ── Adulto ───────────────────────────────────────────────────────────────

    public function test_alta_de_adulto_sin_marcar_el_checkbox_no_guarda_consentimiento(): void
    {
        $this->actingAs($this->operador())->post('/clientes', $this->payload());

        $cliente = Cliente::latest('id_cliente')->first();
        $this->assertNull($cliente->consentimiento_huella_en);
    }

    public function test_alta_de_adulto_marcando_el_checkbox_guarda_consentimiento_con_fecha(): void
    {
        $antes = now();

        $this->actingAs($this->operador())
             ->post('/clientes', $this->payload(['consentimiento_huella' => '1']));

        $cliente = Cliente::latest('id_cliente')->first();
        $this->assertNotNull($cliente->consentimiento_huella_en);
        $this->assertTrue($cliente->consentimiento_huella_en->greaterThanOrEqualTo($antes->subSecond()));
        // Un adulto no tiene datos de tutor.
        $this->assertNull($cliente->tutor_nombre);
        $this->assertNull($cliente->consentimiento_tutor_en);
    }

    public function test_editar_un_adulto_desmarcando_el_checkbox_revoca_el_consentimiento(): void
    {
        $operador = $this->operador();
        $this->actingAs($operador)->post('/clientes', $this->payload(['email' => 'revoca@test.com', 'consentimiento_huella' => '1']));
        $cliente = Cliente::where('id_usuario', User::where('email', 'revoca@test.com')->first()->id)->firstOrFail();
        $this->assertNotNull($cliente->consentimiento_huella_en);

        $respuesta = $this->actingAs($operador)->put("/clientes/{$cliente->id_cliente}", [
            'nombre_completo'  => 'Jhon',
            'apellido_paterno' => 'Crespo',
            'apellido_materno' => 'Coria',
            'ci'               => $cliente->persona->ci,
            'telefono'         => $cliente->persona->telefono,
            'name'             => $cliente->user->name,
            'email'            => 'revoca@test.com',
            'genero'           => 'masculino',
            'fecha_nacimiento' => '1995-03-10',
            'dias_asistidos'   => 0,
            'estado'           => 'activo',
            // consentimiento_huella NO se manda: checkbox desmarcado.
        ]);

        $respuesta->assertSessionDoesntHaveErrors();
        $this->assertNull($cliente->fresh()->consentimiento_huella_en);
    }

    public function test_editar_conservando_el_checkbox_marcado_no_cambia_la_fecha_original(): void
    {
        $operador = $this->operador();
        $this->actingAs($operador)->post('/clientes', $this->payload(['email' => 'conserva@test.com', 'consentimiento_huella' => '1']));
        $cliente = Cliente::where('id_usuario', User::where('email', 'conserva@test.com')->first()->id)->firstOrFail();
        $fechaOriginal = $cliente->consentimiento_huella_en;

        $this->travel(2)->days();

        $respuesta = $this->actingAs($operador)->put("/clientes/{$cliente->id_cliente}", [
            'nombre_completo'       => 'Jhon',
            'apellido_paterno'      => 'Crespo',
            'apellido_materno'      => 'Coria',
            'ci'                    => $cliente->persona->ci,
            'telefono'              => $cliente->persona->telefono,
            'name'                  => $cliente->user->name,
            'email'                 => 'conserva@test.com',
            'genero'                => 'masculino',
            'fecha_nacimiento'      => '1995-03-10',
            'dias_asistidos'        => 0,
            'estado'                => 'activo',
            'consentimiento_huella' => '1',
        ]);

        $respuesta->assertSessionDoesntHaveErrors();
        $this->assertTrue($fechaOriginal->equalTo($cliente->fresh()->consentimiento_huella_en));
    }

    // ── Menor ────────────────────────────────────────────────────────────────

    public function test_alta_de_menor_sin_datos_de_tutor_se_crea_igual_pero_sin_consentimiento(): void
    {
        $this->actingAs($this->operador())
             ->post('/clientes', $this->payload(['fecha_nacimiento' => now()->subYears(15)->toDateString()]))
             ->assertRedirect(route('clientes.index'));

        $cliente = Cliente::latest('id_cliente')->first();
        $this->assertNotNull($cliente);
        $this->assertNull($cliente->consentimiento_huella_en);
        $this->assertNull($cliente->consentimiento_tutor_en);
    }

    public function test_alta_de_menor_marcando_consentimiento_del_tutor_sin_sus_datos_falla_la_validacion(): void
    {
        $this->actingAs($this->operador())
             ->post('/clientes', $this->payload([
                 'fecha_nacimiento'     => now()->subYears(15)->toDateString(),
                 'consentimiento_tutor' => '1',
                 // tutor_nombre / tutor_ci NO se mandan.
             ]))
             ->assertSessionHasErrors(['tutor_nombre', 'tutor_ci']);

        $this->assertDatabaseCount('clientes', 0);
    }

    public function test_alta_de_menor_con_datos_de_tutor_completos_guarda_ambos_consentimientos(): void
    {
        $antes = now();

        $this->actingAs($this->operador())
             ->post('/clientes', $this->payload([
                 'fecha_nacimiento'     => now()->subYears(15)->toDateString(),
                 'consentimiento_tutor' => '1',
                 'tutor_nombre'         => 'Maria Perez',
                 'tutor_ci'             => 'TUTORCI9',
             ]))
             ->assertRedirect(route('clientes.index'));

        $cliente = Cliente::latest('id_cliente')->first();
        $this->assertSame('Maria Perez', $cliente->tutor_nombre);
        $this->assertSame('TUTORCI9', $cliente->tutor_ci);
        $this->assertNotNull($cliente->consentimiento_tutor_en);
        $this->assertTrue($cliente->consentimiento_tutor_en->greaterThanOrEqualTo($antes->subSecond()));
        // El consentimiento del tutor cubre la autorización general.
        $this->assertNotNull($cliente->consentimiento_huella_en);
    }

    public function test_un_adulto_no_puede_colar_datos_de_tutor(): void
    {
        // Aunque alguien mande estos campos a mano, para un adulto se ignoran:
        // no existe "consentimiento del tutor" de un adulto.
        $this->actingAs($this->operador())
             ->post('/clientes', $this->payload([
                 'consentimiento_tutor' => '1',
                 'tutor_nombre'         => 'Alguien',
                 'tutor_ci'             => 'CIALGUIEN1',
             ]));

        $cliente = Cliente::latest('id_cliente')->first();
        $this->assertNull($cliente->tutor_nombre);
        $this->assertNull($cliente->tutor_ci);
        $this->assertNull($cliente->consentimiento_tutor_en);
    }

    // ── Vistas renderizan bien ──────────────────────────────────────────────

    public function test_clientes_create_renderiza_con_la_seccion_de_consentimiento(): void
    {
        $html = $this->actingAs($this->operador())->get('/clientes/create')->assertStatus(200)->getContent();

        $this->assertStringContainsString('consentimiento_huella', $html);
        $this->assertStringContainsString('mb-consentimiento-menor', $html);
    }

    public function test_clientes_edit_renderiza_con_la_seccion_de_consentimiento(): void
    {
        $operador = $this->operador();
        $this->actingAs($operador)->post('/clientes', $this->payload(['email' => 'edit-view@test.com']));
        $cliente = Cliente::where('id_usuario', User::where('email', 'edit-view@test.com')->first()->id)->firstOrFail();

        $html = $this->actingAs($operador)
            ->get("/clientes/{$cliente->id_cliente}/edit")
            ->assertStatus(200)
            ->getContent();

        $this->assertStringContainsString('consentimiento_huella', $html);
    }

    // ── Bitácora ────────────────────────────────────────────────────────────

    public function test_el_alta_con_consentimiento_queda_en_la_bitacora(): void
    {
        $operador = $this->operador();

        $this->actingAs($operador)->post('/clientes', $this->payload(['consentimiento_huella' => '1']));

        $cliente = Cliente::latest('id_cliente')->first();

        $actividad = Activity::where('subject_type', Cliente::class)
            ->where('subject_id', $cliente->id_cliente)
            ->where('event', 'created')
            ->first();

        $this->assertNotNull($actividad);
        $this->assertSame($operador->id, $actividad->causer_id);

        $props = $actividad->properties->toArray();
        $this->assertArrayHasKey('consentimiento_huella_en', $props['attributes'] ?? []);
    }
}
