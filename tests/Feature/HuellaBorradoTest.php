<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Huella;
use App\Models\Persona;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

/**
 * Grupo 2 / Etapa 3 de la auditoría de privacidad: borrado (derecho al
 * olvido) de huellas dactilares. El Administrador puede borrar la huella
 * de un cliente; el borrado es REAL (forceDelete, no soft-delete); la
 * huella borrada desaparece de /api/agente/sincronizar; queda registrada
 * en la bitácora sin el template; y el resto de la biometría (cifrado,
 * otras huellas) sigue funcionando igual.
 */
class HuellaBorradoTest extends TestCase
{
    use RefreshDatabase;

    private static int $seq = 0;

    private function admin(): User
    {
        return User::factory()->create(['rol_id' => Rol::firstOrCreate(['nombre' => 'Administrador'])->id]);
    }

    private function usuarioConRol(string $rolNombre): User
    {
        return User::factory()->create(['rol_id' => Rol::firstOrCreate(['nombre' => $rolNombre])->id]);
    }

    private function crearCliente(): Cliente
    {
        self::$seq++;
        $s = self::$seq;

        $persona = Persona::create([
            'nombre_completo' => "Cliente Huella {$s}", 'apellido_paterno' => 'Test', 'apellido_materno' => 'Test',
            'ci' => "CIHB{$s}", 'telefono' => "+591711133{$s}", 'genero' => 'masculino',
            'fecha_nacimiento' => '1990-01-01',
        ]);
        $user = User::factory()->create(['rol_id' => Rol::firstOrCreate(['nombre' => 'Cliente'])->id]);

        return Cliente::create([
            'id_persona' => $persona->id_persona, 'id_usuario' => $user->id,
            'estado' => 'activo', 'dias_asistidos' => 0,
        ]);
    }

    private function crearHuella(Cliente $cliente, string $dedo = 'indice_derecho'): Huella
    {
        return Huella::create([
            'id_cliente' => $cliente->id_cliente,
            'dedo'       => $dedo,
            'template'   => base64_encode("template-{$dedo}-" . uniqid()),
        ]);
    }

    // ── 1. El Admin puede borrar una huella ───────────────────────────────────

    public function test_el_administrador_puede_borrar_una_huella_especifica(): void
    {
        $cliente = $this->crearCliente();
        $huella  = $this->crearHuella($cliente, 'pulgar_derecho');

        $this->actingAs($this->admin())
             ->delete(route('clientes.huellas.destroy', [$cliente, $huella]))
             ->assertRedirect();

        $this->assertDatabaseMissing('huellas', ['id' => $huella->id]);
    }

    // ── 2. Borrado REAL, no soft-delete ───────────────────────────────────────

    public function test_el_borrado_es_real_no_soft_delete(): void
    {
        $cliente = $this->crearCliente();
        $huella  = $this->crearHuella($cliente);

        $this->actingAs($this->admin())
             ->delete(route('clientes.huellas.destroy', [$cliente, $huella]));

        // Ni con withTrashed() aparece: no es una baja lógica.
        $this->assertNull(Huella::withTrashed()->find($huella->id));
        $this->assertDatabaseCount('huellas', 0);
    }

    public function test_borrar_todas_las_huellas_de_un_cliente_es_borrado_real(): void
    {
        $cliente = $this->crearCliente();
        $h1 = $this->crearHuella($cliente, 'indice_derecho');
        $h2 = $this->crearHuella($cliente, 'pulgar_derecho');

        $this->actingAs($this->admin())
             ->delete(route('clientes.huellas.destroy_todas', $cliente))
             ->assertRedirect();

        $this->assertDatabaseCount('huellas', 0);
        $this->assertNull(Huella::withTrashed()->find($h1->id));
        $this->assertNull(Huella::withTrashed()->find($h2->id));
    }

    // ── 3. Sincronización con el agente ───────────────────────────────────────

    public function test_una_huella_borrada_ya_no_aparece_en_sincronizar(): void
    {
        $sede = \App\Models\Sede::create(['nombre' => 'Sede Huella', 'direccion' => 'Calle 1', 'activo' => true]);
        \App\Models\AgentToken::create([
            'id_sede' => $sede->id_sede, 'nombre' => 'Agente Test',
            'token' => hash('sha256', 'token-hb'), 'activo' => true,
        ]);

        $cliente = $this->crearCliente();
        $cliente->update(['id_sede' => $sede->id_sede]);
        $huellaABorrar   = $this->crearHuella($cliente, 'indice_derecho');
        $huellaQueQueda  = $this->crearHuella($cliente, 'pulgar_derecho');

        $this->actingAs($this->admin())
             ->delete(route('clientes.huellas.destroy', [$cliente, $huellaABorrar]));

        $respuesta = $this->getJson('/api/agente/sincronizar', ['Authorization' => 'Bearer token-hb'])
                          ->assertStatus(200);

        $huellasDelCliente = collect($respuesta->json('data'))
            ->firstWhere('id_cliente', $cliente->id_cliente)['huellas'] ?? [];

        $dedosSincronizados = collect($huellasDelCliente)->pluck('dedo')->all();

        $this->assertNotContains('indice_derecho', $dedosSincronizados);
        $this->assertContains('pulgar_derecho', $dedosSincronizados);
    }

    public function test_borrar_la_unica_huella_de_un_cliente_lo_saca_de_sincronizar(): void
    {
        $sede = \App\Models\Sede::create(['nombre' => 'Sede Huella 2', 'direccion' => 'Calle 1', 'activo' => true]);
        \App\Models\AgentToken::create([
            'id_sede' => $sede->id_sede, 'nombre' => 'Agente Test 2',
            'token' => hash('sha256', 'token-hb2'), 'activo' => true,
        ]);

        $cliente = $this->crearCliente();
        $cliente->update(['id_sede' => $sede->id_sede]);
        $huella = $this->crearHuella($cliente);

        $this->actingAs($this->admin())
             ->delete(route('clientes.huellas.destroy', [$cliente, $huella]));

        $respuesta = $this->getJson('/api/agente/sincronizar', ['Authorization' => 'Bearer token-hb2'])
                          ->assertStatus(200);

        // Sin huellas, BiometriaController::sincronizar ya filtra al cliente entero.
        $this->assertEmpty(
            collect($respuesta->json('data'))->where('id_cliente', $cliente->id_cliente)
        );
    }

    // ── 4. Otros roles NO pueden borrar huellas ───────────────────────────────

    public function test_recepcionista_no_puede_borrar_una_huella(): void
    {
        $cliente = $this->crearCliente();
        $huella  = $this->crearHuella($cliente);

        $this->actingAs($this->usuarioConRol('Recepcionista'))
             ->delete(route('clientes.huellas.destroy', [$cliente, $huella]))
             ->assertStatus(403);

        $this->assertDatabaseHas('huellas', ['id' => $huella->id]);
    }

    public function test_entrenador_no_puede_borrar_una_huella(): void
    {
        $cliente = $this->crearCliente();
        $huella  = $this->crearHuella($cliente);

        $this->actingAs($this->usuarioConRol('Entrenador'))
             ->delete(route('clientes.huellas.destroy', [$cliente, $huella]))
             ->assertStatus(403);

        $this->assertDatabaseHas('huellas', ['id' => $huella->id]);
    }

    public function test_cliente_no_puede_borrar_su_propia_huella(): void
    {
        $cliente = $this->crearCliente();
        $huella  = $this->crearHuella($cliente);

        $this->actingAs($cliente->user)
             ->delete(route('clientes.huellas.destroy', [$cliente, $huella]))
             ->assertStatus(403);

        $this->assertDatabaseHas('huellas', ['id' => $huella->id]);
    }

    public function test_invitado_sin_sesion_no_puede_borrar_una_huella(): void
    {
        $cliente = $this->crearCliente();
        $huella  = $this->crearHuella($cliente);

        $this->delete(route('clientes.huellas.destroy', [$cliente, $huella]))
             ->assertRedirect(route('login'));

        $this->assertDatabaseHas('huellas', ['id' => $huella->id]);
    }

    // ── 5. Bitácora ────────────────────────────────────────────────────────────

    public function test_el_borrado_queda_en_la_bitacora_sin_el_template(): void
    {
        $admin   = $this->admin();
        $cliente = $this->crearCliente();
        $huella  = $this->crearHuella($cliente, 'medio_izquierdo');
        $templateEnClaro = $huella->template;

        $this->actingAs($admin)
             ->delete(route('clientes.huellas.destroy', [$cliente, $huella]));

        $actividad = Activity::where('subject_type', Huella::class)
            ->where('subject_id', $huella->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($actividad, 'Debe existir una fila de bitácora para el borrado de la huella.');
        $this->assertSame($admin->id, $actividad->causer_id);
        $this->assertSame('deleted', $actividad->event);

        $props = $actividad->properties->toArray();

        // El template (dato biométrico) NUNCA debe aparecer en la bitácora.
        $propsJson = json_encode($props);
        $this->assertStringNotContainsString($templateEnClaro, $propsJson);
        $this->assertArrayNotHasKey('template', $props['old'] ?? []);
        $this->assertArrayNotHasKey('template_hash', $props['old'] ?? []);

        // Sí debe identificar qué se borró y de qué cliente.
        $this->assertSame('medio_izquierdo', $props['old']['dedo'] ?? null);
        $this->assertSame($cliente->id_cliente, $props['old']['id_cliente'] ?? null);
    }

    public function test_borrar_todas_deja_una_fila_de_bitacora_por_huella(): void
    {
        $admin   = $this->admin();
        $cliente = $this->crearCliente();
        $h1 = $this->crearHuella($cliente, 'indice_derecho');
        $h2 = $this->crearHuella($cliente, 'pulgar_derecho');

        $this->actingAs($admin)->delete(route('clientes.huellas.destroy_todas', $cliente));

        $this->assertSame(
            2,
            Activity::where('subject_type', Huella::class)
                ->whereIn('subject_id', [$h1->id, $h2->id])
                ->count()
        );
    }

    // ── 6. El resto de la biometría sigue intacto ─────────────────────────────

    public function test_borrar_una_huella_no_afecta_el_cifrado_ni_la_lectura_de_las_demas(): void
    {
        $cliente   = $this->crearCliente();
        $huellaVa  = $this->crearHuella($cliente, 'indice_derecho');
        $huellaQueda = $this->crearHuella($cliente, 'pulgar_derecho');
        $templateQueQueda = $huellaQueda->template;

        $this->actingAs($this->admin())
             ->delete(route('clientes.huellas.destroy', [$cliente, $huellaVa]));

        // La que quedó sigue legible en claro vía el modelo (cast 'encrypted' intacto).
        $this->assertSame($templateQueQueda, Huella::find($huellaQueda->id)->template);

        // Y su columna cruda sigue cifrada (no se rompió nada del cast).
        $crudo = \Illuminate\Support\Facades\DB::table('huellas')->where('id', $huellaQueda->id)->value('template');
        $this->assertNotSame($templateQueQueda, $crudo);
    }
}
