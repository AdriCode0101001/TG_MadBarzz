<?php

namespace Tests\Feature;

use App\Models\AgentToken;
use App\Models\Asistencia;
use App\Models\Cliente;
use App\Models\Huella;
use App\Models\Rol;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BiometriaApiTest extends TestCase
{
    use RefreshDatabase;

    private static int $seq = 0;

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function crearSede(string $nombre = 'Sede Test'): Sede
    {
        return Sede::create(['nombre' => $nombre, 'direccion' => 'Calle 1', 'activo' => true]);
    }

    private function crearAgentToken(Sede $sede, string $plain = 'token-plano-test'): AgentToken
    {
        return AgentToken::create([
            'id_sede' => $sede->id_sede,
            'nombre'  => 'Agente Test',
            'token'   => hash('sha256', $plain),
            'activo'  => true,
        ]);
    }

    private function crearCliente(Sede $sede, string $estado = 'activo'): Cliente
    {
        self::$seq++;
        $s = self::$seq;

        $rol    = Rol::firstOrCreate(['nombre' => 'Cliente']);
        $user   = User::factory()->create(['rol_id' => $rol->id, 'id_sede' => $sede->id_sede]);

        $personaId = DB::table('personas')->insertGetId([
            'nombre_completo'  => "Cliente {$s}",
            'apellido_paterno' => 'Test',
            'apellido_materno' => 'Test',
            'ci'               => "CI{$s}",
            'telefono'         => "+5917000000{$s}",
            'genero'           => 'M',
            'fecha_nacimiento' => '1990-01-01',
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        return Cliente::create([
            'id_persona'     => $personaId,
            'id_usuario'     => $user->id,
            'estado'         => $estado,
            'dias_asistidos' => 0,
            'id_sede'        => $sede->id_sede,
        ]);
    }

    private function headers(string $plain = 'token-plano-test'): array
    {
        return ['Authorization' => "Bearer {$plain}"];
    }

    // ── 1. Token inválido es rechazado ────────────────────────────────────────

    public function test_token_invalido_devuelve_401(): void
    {
        $this->postJson('/api/agente/enrolar', [], ['Authorization' => 'Bearer token-falso'])
             ->assertStatus(401)
             ->assertJsonFragment(['message' => 'Token de agente inválido o inactivo.']);
    }

    public function test_sin_header_authorization_devuelve_401(): void
    {
        $this->postJson('/api/agente/enrolar', [])
             ->assertStatus(401)
             ->assertJsonFragment(['message' => 'Token de agente requerido.']);
    }

    public function test_token_inactivo_devuelve_401(): void
    {
        $sede = $this->crearSede();
        AgentToken::create([
            'id_sede' => $sede->id_sede,
            'nombre'  => 'Agente Inactivo',
            'token'   => hash('sha256', 'token-inactivo'),
            'activo'  => false,
        ]);

        $this->postJson('/api/agente/enrolar', [], ['Authorization' => 'Bearer token-inactivo'])
             ->assertStatus(401);
    }

    // ── 2. Enrolar guarda la huella ───────────────────────────────────────────

    public function test_enrolar_guarda_huella_en_bd(): void
    {
        $sede     = $this->crearSede();
        $this->crearAgentToken($sede);
        $cliente  = $this->crearCliente($sede);

        $payload = [
            'id_cliente'      => $cliente->id_cliente,
            'dedo'            => 'indice_derecho',
            'template'        => base64_encode('plantilla-binaria-fake'),
            'version_formato' => 'ZKFinger10',
        ];

        $this->postJson('/api/agente/enrolar', $payload, $this->headers())
             ->assertStatus(201)
             ->assertJsonFragment(['message' => 'Huella enrolada correctamente.']);

        $this->assertDatabaseHas('huellas', [
            'id_cliente'      => $cliente->id_cliente,
            'dedo'            => 'indice_derecho',
            'version_formato' => 'ZKFinger10',
        ]);
    }

    public function test_enrolar_actualiza_template_si_dedo_ya_existe(): void
    {
        $sede    = $this->crearSede();
        $this->crearAgentToken($sede);
        $cliente = $this->crearCliente($sede);

        $base = ['id_cliente' => $cliente->id_cliente, 'dedo' => 'pulgar_derecho'];

        $this->postJson('/api/agente/enrolar', $base + ['template' => base64_encode('v1')], $this->headers())
             ->assertStatus(201);

        $this->postJson('/api/agente/enrolar', $base + ['template' => base64_encode('v2')], $this->headers())
             ->assertStatus(201);

        $this->assertDatabaseCount('huellas', 1);
        $this->assertDatabaseHas('huellas', ['template' => base64_encode('v2')]);
    }

    // ── 3. Sincronizar respeta la política de acceso ──────────────────────────

    public function test_sincronizar_devuelve_cliente_sin_restricciones(): void
    {
        $sede    = $this->crearSede();
        $this->crearAgentToken($sede);
        $cliente = $this->crearCliente($sede);

        Huella::create([
            'id_cliente' => $cliente->id_cliente,
            'dedo'       => 'indice_derecho',
            'template'   => base64_encode('plantilla'),
        ]);

        $response = $this->getJson('/api/agente/sincronizar', $this->headers())
                         ->assertStatus(200);

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($cliente->id_cliente, $data[0]['id_cliente']);
    }

    public function test_sincronizar_excluye_cliente_restringido_a_otra_sede(): void
    {
        $sede1 = $this->crearSede('Sede 1');
        $sede2 = $this->crearSede('Sede 2');
        $this->crearAgentToken($sede1);  // agente en sede 1

        $cliente = $this->crearCliente($sede1);

        Huella::create([
            'id_cliente' => $cliente->id_cliente,
            'dedo'       => 'indice_derecho',
            'template'   => base64_encode('plantilla'),
        ]);

        // Restringir al cliente solo a sede 2
        DB::table('cliente_sede')->insert([
            'id_cliente' => $cliente->id_cliente,
            'id_sede'    => $sede2->id_sede,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->getJson('/api/agente/sincronizar', $this->headers())
                         ->assertStatus(200);

        $this->assertEmpty($response->json('data'));
    }

    public function test_sincronizar_incluye_cliente_restringido_a_esta_sede(): void
    {
        $sede1 = $this->crearSede('Sede 1');
        $sede2 = $this->crearSede('Sede 2');
        $this->crearAgentToken($sede1);

        $cliente = $this->crearCliente($sede1);

        Huella::create([
            'id_cliente' => $cliente->id_cliente,
            'dedo'       => 'indice_derecho',
            'template'   => base64_encode('plantilla'),
        ]);

        // Restringir al cliente a sede1 (la del agente)
        DB::table('cliente_sede')->insert([
            'id_cliente' => $cliente->id_cliente,
            'id_sede'    => $sede1->id_sede,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->getJson('/api/agente/sincronizar', $this->headers())
                         ->assertStatus(200);

        $this->assertCount(1, $response->json('data'));
    }

    public function test_sincronizar_excluye_clientes_sin_huellas(): void
    {
        $sede = $this->crearSede();
        $this->crearAgentToken($sede);
        $this->crearCliente($sede);  // cliente activo sin huellas

        $response = $this->getJson('/api/agente/sincronizar', $this->headers())
                         ->assertStatus(200);

        $this->assertEmpty($response->json('data'));
    }

    // ── 4. Registrar asistencia valida acceso y evita duplicados ──────────────

    public function test_registrar_asistencia_crea_registro_correctamente(): void
    {
        $sede    = $this->crearSede();
        $this->crearAgentToken($sede);
        $cliente = $this->crearCliente($sede);

        $payload = [
            'id_cliente' => $cliente->id_cliente,
            'id_sede'    => $sede->id_sede,
            'fecha_hora' => now()->toDateTimeString(),
        ];

        $this->postJson('/api/agente/asistencia', $payload, $this->headers())
             ->assertStatus(201)
             ->assertJsonFragment(['message' => 'Asistencia registrada.']);

        $this->assertDatabaseHas('asistencias', [
            'id_cliente' => $cliente->id_cliente,
            'id_sede'    => $sede->id_sede,
        ]);
    }

    public function test_registrar_asistencia_rechaza_duplicado_en_ventana(): void
    {
        $sede    = $this->crearSede();
        $this->crearAgentToken($sede);
        $cliente = $this->crearCliente($sede);

        $payload = [
            'id_cliente' => $cliente->id_cliente,
            'id_sede'    => $sede->id_sede,
            'fecha_hora' => now()->toDateTimeString(),
        ];

        $this->postJson('/api/agente/asistencia', $payload, $this->headers())->assertStatus(201);

        // Segunda marcación dentro de la ventana
        $this->postJson('/api/agente/asistencia', $payload, $this->headers())
             ->assertStatus(409)
             ->assertJsonFragment(['duplicado' => true]);

        $this->assertDatabaseCount('asistencias', 1);
    }

    public function test_registrar_asistencia_rechaza_cliente_con_acceso_restringido(): void
    {
        $sede1 = $this->crearSede('Sede 1');
        $sede2 = $this->crearSede('Sede 2');
        $this->crearAgentToken($sede1);
        $cliente = $this->crearCliente($sede1);

        // Restringir al cliente a sede2 únicamente
        DB::table('cliente_sede')->insert([
            'id_cliente' => $cliente->id_cliente,
            'id_sede'    => $sede2->id_sede,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $payload = [
            'id_cliente' => $cliente->id_cliente,
            'id_sede'    => $sede1->id_sede,
            'fecha_hora' => now()->toDateTimeString(),
        ];

        $this->postJson('/api/agente/asistencia', $payload, $this->headers())
             ->assertStatus(403)
             ->assertJsonFragment(['message' => 'El cliente no tiene acceso a esta sede.']);
    }

    public function test_registrar_asistencia_rechaza_si_id_sede_no_coincide_con_token(): void
    {
        $sede1 = $this->crearSede('Sede 1');
        $sede2 = $this->crearSede('Sede 2');
        $this->crearAgentToken($sede1);
        $cliente = $this->crearCliente($sede2);

        // El agente es de sede1 pero envía sede2 en el body → debe rechazarse
        $payload = [
            'id_cliente' => $cliente->id_cliente,
            'id_sede'    => $sede2->id_sede,
            'fecha_hora' => now()->toDateTimeString(),
        ];

        $this->postJson('/api/agente/asistencia', $payload, $this->headers())
             ->assertStatus(403);
    }

    public function test_registrar_asistencia_rechaza_cliente_inactivo(): void
    {
        $sede    = $this->crearSede();
        $this->crearAgentToken($sede);
        $cliente = $this->crearCliente($sede, 'inactivo');

        $payload = [
            'id_cliente' => $cliente->id_cliente,
            'id_sede'    => $sede->id_sede,
            'fecha_hora' => now()->toDateTimeString(),
        ];

        $this->postJson('/api/agente/asistencia', $payload, $this->headers())
             ->assertStatus(404);
    }
}
