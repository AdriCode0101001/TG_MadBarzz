<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Persona;
use App\Models\PushSubscription;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Rutas del cliente para gestionar SU suscripción Web Push (Etapa 1, solo
 * backend — el botón real que las llama es la Etapa 2). Mismo grupo de
 * middleware que el resto del portal (role:Cliente + verified).
 */
class PushSubscriptionTest extends TestCase
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
            'nombre_completo'  => "Cliente Suscripcion {$s}",
            'apellido_paterno' => 'Test',
            'apellido_materno' => 'Test',
            'ci'               => "CISUB{$s}",
            'telefono'         => "+59177000{$s}",
            'genero'           => 'femenino',
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

    private function payload(string $endpoint, array $overrides = []): array
    {
        return array_merge([
            'endpoint' => $endpoint,
            'p256dh'   => 'clave-p256dh-' . uniqid(),
            'auth'     => 'clave-auth-' . uniqid(),
        ], $overrides);
    }

    // ── 1. Guardar una suscripción ────────────────────────────────────────────

    public function test_guardar_una_suscripcion_la_persiste(): void
    {
        $cliente = $this->crearCliente();

        $this->actingAs($cliente->user)
             ->postJson('/portal/push/suscribir', $this->payload('https://fcm.googleapis.com/fcm/send/abc123'))
             ->assertStatus(200)
             ->assertJson(['ok' => true]);

        $this->assertDatabaseCount('push_subscriptions', 1);
        $this->assertDatabaseHas('push_subscriptions', [
            'id_cliente' => $cliente->id_cliente,
            'endpoint'   => 'https://fcm.googleapis.com/fcm/send/abc123',
        ]);
    }

    public function test_guardar_el_mismo_endpoint_dos_veces_no_duplica(): void
    {
        $cliente = $this->crearCliente();
        $endpoint = 'https://fcm.googleapis.com/fcm/send/mismo-endpoint';

        $this->actingAs($cliente->user)->postJson('/portal/push/suscribir', $this->payload($endpoint))
             ->assertStatus(200);
        $this->actingAs($cliente->user)->postJson('/portal/push/suscribir', $this->payload($endpoint, ['p256dh' => 'clave-actualizada']))
             ->assertStatus(200);

        $this->assertDatabaseCount('push_subscriptions', 1);
        $this->assertDatabaseHas('push_subscriptions', [
            'endpoint' => $endpoint,
            'p256dh'   => 'clave-actualizada', // se actualizó, no se duplicó
        ]);
    }

    public function test_un_cliente_puede_tener_varias_suscripciones_distintas(): void
    {
        $cliente = $this->crearCliente();

        $this->actingAs($cliente->user)->postJson('/portal/push/suscribir', $this->payload('https://fcm.googleapis.com/fcm/send/celular'));
        $this->actingAs($cliente->user)->postJson('/portal/push/suscribir', $this->payload('https://fcm.googleapis.com/fcm/send/tablet'));

        $this->assertDatabaseCount('push_subscriptions', 2);
        $this->assertSame(2, PushSubscription::where('id_cliente', $cliente->id_cliente)->count());
    }

    public function test_rechaza_guardar_sin_los_campos_requeridos(): void
    {
        $cliente = $this->crearCliente();

        $this->actingAs($cliente->user)->postJson('/portal/push/suscribir', [])
             ->assertStatus(422);

        $this->assertDatabaseCount('push_subscriptions', 0);
    }

    // ── 2. Borrar una suscripción ──────────────────────────────────────────────

    public function test_borrar_la_suscripcion_la_elimina(): void
    {
        $cliente = $this->crearCliente();
        $endpoint = 'https://fcm.googleapis.com/fcm/send/a-borrar';

        $this->actingAs($cliente->user)->postJson('/portal/push/suscribir', $this->payload($endpoint));
        $this->assertDatabaseCount('push_subscriptions', 1);

        $this->actingAs($cliente->user)->postJson('/portal/push/desuscribir', ['endpoint' => $endpoint])
             ->assertStatus(200)
             ->assertJson(['ok' => true]);

        $this->assertDatabaseCount('push_subscriptions', 0);
    }

    public function test_borrar_una_suscripcion_no_afecta_las_otras_del_mismo_cliente(): void
    {
        $cliente = $this->crearCliente();

        $this->actingAs($cliente->user)->postJson('/portal/push/suscribir', $this->payload('https://fcm.googleapis.com/fcm/send/celular'));
        $this->actingAs($cliente->user)->postJson('/portal/push/suscribir', $this->payload('https://fcm.googleapis.com/fcm/send/tablet'));

        $this->actingAs($cliente->user)->postJson('/portal/push/desuscribir', ['endpoint' => 'https://fcm.googleapis.com/fcm/send/celular']);

        $this->assertDatabaseCount('push_subscriptions', 1);
        $this->assertDatabaseHas('push_subscriptions', ['endpoint' => 'https://fcm.googleapis.com/fcm/send/tablet']);
    }

    public function test_un_cliente_no_puede_borrar_la_suscripcion_de_otro_cliente(): void
    {
        $clienteA = $this->crearCliente();
        $clienteB = $this->crearCliente();
        $endpointDeA = 'https://fcm.googleapis.com/fcm/send/de-a';

        $this->actingAs($clienteA->user)->postJson('/portal/push/suscribir', $this->payload($endpointDeA));

        $this->actingAs($clienteB->user)->postJson('/portal/push/desuscribir', ['endpoint' => $endpointDeA]);

        // Sigue existiendo: el where('id_cliente', ...) del cliente B no la alcanza
        $this->assertDatabaseCount('push_subscriptions', 1);
    }

    // ── 3. Permisos: solo Cliente ──────────────────────────────────────────────

    public function test_administrador_recibe_403_en_ambas_rutas(): void
    {
        $admin = $this->usuario('Administrador');

        $this->actingAs($admin)->postJson('/portal/push/suscribir', $this->payload('https://x/y'))->assertStatus(403);
        $this->actingAs($admin)->postJson('/portal/push/desuscribir', ['endpoint' => 'https://x/y'])->assertStatus(403);
    }

    public function test_recepcionista_recibe_403_en_ambas_rutas(): void
    {
        $recepcionista = $this->usuario('Recepcionista');

        $this->actingAs($recepcionista)->postJson('/portal/push/suscribir', $this->payload('https://x/y'))->assertStatus(403);
        $this->actingAs($recepcionista)->postJson('/portal/push/desuscribir', ['endpoint' => 'https://x/y'])->assertStatus(403);
    }

    public function test_entrenador_recibe_403_en_ambas_rutas(): void
    {
        $entrenador = $this->usuario('Entrenador');

        $this->actingAs($entrenador)->postJson('/portal/push/suscribir', $this->payload('https://x/y'))->assertStatus(403);
        $this->actingAs($entrenador)->postJson('/portal/push/desuscribir', ['endpoint' => 'https://x/y'])->assertStatus(403);
    }

    public function test_invitado_recibe_401_en_ambas_rutas(): void
    {
        $this->postJson('/portal/push/suscribir', $this->payload('https://x/y'))->assertStatus(401);
        $this->postJson('/portal/push/desuscribir', ['endpoint' => 'https://x/y'])->assertStatus(401);
    }
}
