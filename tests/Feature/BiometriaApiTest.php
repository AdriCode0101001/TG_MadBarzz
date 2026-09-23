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

    /**
     * Crea una membresía para el cliente con la fecha_fin indicada
     * (por defecto vigente: termina en 20 días).
     */
    private function crearMembresia(Cliente $cliente, ?string $fechaFin = null): void
    {
        $idMembresia = DB::table('membresias')->insertGetId([
            'tipo_membresia' => 'Mensual',
            'precio'         => 100,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        DB::table('membresia_cliente')->insert([
            'id_cliente'   => $cliente->id_cliente,
            'id_membresia' => $idMembresia,
            'fecha_inicio' => now()->subDays(10)->toDateString(),
            'fecha_fin'    => $fechaFin ?? now()->addDays(20)->toDateString(),
            'estado'       => 'activa', // pagada: da acceso
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);
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
        // No usamos assertDatabaseHas(['template' => ...]) acá: esa columna
        // ahora está cifrada (cast 'encrypted'), así que el valor crudo en
        // la BD nunca es igual al Base64 en claro — ver
        // test_template_se_guarda_cifrado_en_la_bd_pero_se_lee_en_claro.
        $this->assertSame(base64_encode('v2'), Huella::first()->template);
    }

    // ── 2b. Anti-duplicado: una huella no puede pertenecer a dos clientes ─────

    public function test_enrolar_rechaza_misma_plantilla_en_otro_cliente(): void
    {
        $sede     = $this->crearSede();
        $this->crearAgentToken($sede);
        $clienteA = $this->crearCliente($sede);
        $clienteB = $this->crearCliente($sede);

        $template = base64_encode('huella-fisica-unica');

        // A enrola su huella
        $this->postJson('/api/agente/enrolar', [
            'id_cliente' => $clienteA->id_cliente,
            'dedo'       => 'indice_derecho',
            'template'   => $template,
        ], $this->headers())->assertStatus(201);

        // B intenta enrolar exactamente la misma plantilla → rechazado
        $this->postJson('/api/agente/enrolar', [
            'id_cliente' => $clienteB->id_cliente,
            'dedo'       => 'indice_derecho',
            'template'   => $template,
        ], $this->headers())
             ->assertStatus(409)
             ->assertJsonFragment(['message' => 'Esta huella ya está registrada para otro cliente.']);

        // B no debe tener ninguna huella guardada
        $this->assertDatabaseMissing('huellas', ['id_cliente' => $clienteB->id_cliente]);
        $this->assertDatabaseCount('huellas', 1);
    }

    public function test_enrolar_permite_reenrolar_misma_plantilla_mismo_cliente(): void
    {
        $sede    = $this->crearSede();
        $this->crearAgentToken($sede);
        $cliente = $this->crearCliente($sede);

        $template = base64_encode('huella-fisica-unica');
        $base     = ['id_cliente' => $cliente->id_cliente, 'dedo' => 'indice_derecho', 'template' => $template];

        // Re-enrolar el MISMO dedo del MISMO cliente con la misma plantilla: actualiza, no rechaza
        $this->postJson('/api/agente/enrolar', $base, $this->headers())->assertStatus(201);
        $this->postJson('/api/agente/enrolar', $base, $this->headers())->assertStatus(201);

        $this->assertDatabaseCount('huellas', 1);
    }

    public function test_enrolar_permite_dos_dedos_distintos_del_mismo_cliente(): void
    {
        $sede    = $this->crearSede();
        $this->crearAgentToken($sede);
        $cliente = $this->crearCliente($sede);

        // Dos dedos distintos (plantillas distintas) del mismo cliente: ambos válidos
        $this->postJson('/api/agente/enrolar', [
            'id_cliente' => $cliente->id_cliente,
            'dedo'       => 'indice_derecho',
            'template'   => base64_encode('dedo-1'),
        ], $this->headers())->assertStatus(201);

        $this->postJson('/api/agente/enrolar', [
            'id_cliente' => $cliente->id_cliente,
            'dedo'       => 'pulgar_derecho',
            'template'   => base64_encode('dedo-2'),
        ], $this->headers())->assertStatus(201);

        $this->assertDatabaseCount('huellas', 2);
    }

    // ── 2c. Cifrado en reposo (Grupo 2 / Etapa 1 de la auditoría) ─────────────

    public function test_template_se_guarda_cifrado_en_la_bd_pero_se_lee_en_claro_via_el_modelo(): void
    {
        $sede    = $this->crearSede();
        $this->crearAgentToken($sede);
        $cliente = $this->crearCliente($sede);

        $templateEnClaro = base64_encode('plantilla-biometrica-real');

        $this->postJson('/api/agente/enrolar', [
            'id_cliente' => $cliente->id_cliente,
            'dedo'       => 'indice_derecho',
            'template'   => $templateEnClaro,
        ], $this->headers())->assertStatus(201);

        $huella = Huella::first();

        // Atributo del modelo: Eloquent descifra solo, se ve el texto plano de siempre.
        $this->assertSame($templateEnClaro, $huella->template);

        // Columna cruda en la BD: NUNCA debe ser igual al texto plano — si lo
        // fuera, el cast 'encrypted' no estaría cifrando de verdad.
        $crudo = DB::table('huellas')->where('id', $huella->id)->value('template');
        $this->assertNotSame($templateEnClaro, $crudo);

        // El crudo tiene forma de payload cifrado de Laravel (JSON con iv/value/mac en Base64).
        $payload = json_decode(base64_decode($crudo), true);
        $this->assertIsArray($payload);
        $this->assertArrayHasKey('iv', $payload);
        $this->assertArrayHasKey('value', $payload);
        $this->assertArrayHasKey('mac', $payload);

        // template_hash: SHA-256 determinístico del texto plano, usado por el
        // chequeo anti-duplicado en vez de comparar la columna cifrada.
        $this->assertSame(hash('sha256', $templateEnClaro), $huella->template_hash);
    }

    /**
     * Test de integridad de punta a punta (lo que pidió la auditoría): enrola
     * una huella y confirma que /api/agente/sincronizar devuelve EXACTAMENTE
     * el mismo Base64 que se mandó — el agente C# recibe lo mismo de
     * siempre, sin importar que el dato viaje cifrado dentro de la BD.
     */
    public function test_sincronizar_devuelve_el_mismo_template_que_se_enrolo(): void
    {
        $sede    = $this->crearSede();
        $this->crearAgentToken($sede);
        $cliente = $this->crearCliente($sede);

        $templateEnviado = base64_encode(random_bytes(512)); // simula un template binario real

        $this->postJson('/api/agente/enrolar', [
            'id_cliente'      => $cliente->id_cliente,
            'dedo'            => 'indice_derecho',
            'template'        => $templateEnviado,
            'version_formato' => 'ZKFinger_5.3.0.33',
        ], $this->headers())->assertStatus(201);

        $response = $this->getJson('/api/agente/sincronizar', $this->headers())->assertStatus(200);

        $huellaSincronizada = collect($response->json('data'))
            ->firstWhere('id_cliente', $cliente->id_cliente)['huellas'][0];

        $this->assertSame($templateEnviado, $huellaSincronizada['template']);
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

    // ── 3b. Listar clientes: estado de membresía y dedos enrolados ────────────

    public function test_listar_clientes_marca_membresia_activa_true_si_vigente(): void
    {
        $sede    = $this->crearSede();
        $this->crearAgentToken($sede);
        $cliente = $this->crearCliente($sede);
        $this->crearMembresia($cliente); // vigente: termina en 20 días

        $data = $this->getJson('/api/agente/clientes', $this->headers())
                     ->assertStatus(200)
                     ->json('data');

        $fila = collect($data)->firstWhere('id_cliente', $cliente->id_cliente);
        $this->assertNotNull($fila);
        $this->assertTrue($fila['membresia_activa']);
    }

    public function test_listar_clientes_marca_membresia_activa_false_si_vencida(): void
    {
        $sede    = $this->crearSede();
        $this->crearAgentToken($sede);
        $cliente = $this->crearCliente($sede);
        $this->crearMembresia($cliente, now()->subDays(3)->toDateString()); // venció hace 3 días

        $data = $this->getJson('/api/agente/clientes', $this->headers())
                     ->assertStatus(200)
                     ->json('data');

        $fila = collect($data)->firstWhere('id_cliente', $cliente->id_cliente);
        $this->assertNotNull($fila);
        $this->assertFalse($fila['membresia_activa']);
    }

    public function test_listar_clientes_marca_membresia_activa_false_sin_membresia(): void
    {
        $sede    = $this->crearSede();
        $this->crearAgentToken($sede);
        $cliente = $this->crearCliente($sede); // sin ninguna membresía

        $data = $this->getJson('/api/agente/clientes', $this->headers())
                     ->assertStatus(200)
                     ->json('data');

        $fila = collect($data)->firstWhere('id_cliente', $cliente->id_cliente);
        $this->assertNotNull($fila);
        $this->assertFalse($fila['membresia_activa']);
    }

    public function test_listar_clientes_incluye_dedos_enrolados(): void
    {
        $sede    = $this->crearSede();
        $this->crearAgentToken($sede);
        $cliente = $this->crearCliente($sede);

        Huella::create([
            'id_cliente' => $cliente->id_cliente,
            'dedo'       => 'indice_derecho',
            'template'   => base64_encode('plantilla'),
        ]);

        $data = $this->getJson('/api/agente/clientes', $this->headers())
                     ->assertStatus(200)
                     ->json('data');

        $fila = collect($data)->firstWhere('id_cliente', $cliente->id_cliente);
        $this->assertNotNull($fila);
        $this->assertEquals(['indice_derecho'], $fila['dedos_enrolados']);
    }

    // ── 4. Registrar asistencia valida acceso y evita duplicados ──────────────

    public function test_registrar_asistencia_crea_registro_correctamente(): void
    {
        $sede    = $this->crearSede();
        $this->crearAgentToken($sede);
        $cliente = $this->crearCliente($sede);
        $this->crearMembresia($cliente); // vigente

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
        $this->crearMembresia($cliente); // vigente

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

    // ── 5. Regla de negocio: membresía vigente obligatoria ────────────────────

    public function test_registrar_asistencia_rechaza_cliente_sin_membresia(): void
    {
        $sede    = $this->crearSede();
        $this->crearAgentToken($sede);
        $cliente = $this->crearCliente($sede); // sin ninguna membresía

        $payload = [
            'id_cliente' => $cliente->id_cliente,
            'id_sede'    => $sede->id_sede,
            'fecha_hora' => now()->toDateTimeString(),
        ];

        $this->postJson('/api/agente/asistencia', $payload, $this->headers())
             ->assertStatus(403)
             ->assertJsonFragment([
                 'permitido' => false,
                 'motivo'    => 'sin_membresia',
                 'mensaje'   => 'No tienes una membresía activa. Pasa por recepción para adquirir una.',
             ]);

        $this->assertDatabaseCount('asistencias', 0);
    }

    public function test_registrar_asistencia_rechaza_membresia_vencida(): void
    {
        $sede    = $this->crearSede();
        $this->crearAgentToken($sede);
        $cliente = $this->crearCliente($sede);
        $this->crearMembresia($cliente, now()->subDays(3)->toDateString()); // venció hace 3 días

        $payload = [
            'id_cliente' => $cliente->id_cliente,
            'id_sede'    => $sede->id_sede,
            'fecha_hora' => now()->toDateTimeString(),
        ];

        $this->postJson('/api/agente/asistencia', $payload, $this->headers())
             ->assertStatus(403)
             ->assertJsonFragment([
                 'permitido' => false,
                 'motivo'    => 'membresia_vencida',
                 'mensaje'   => 'Tu membresía está vencida. Pasa por recepción para renovarla.',
             ]);

        $this->assertDatabaseCount('asistencias', 0);
    }

    public function test_registrar_asistencia_acepta_membresia_que_vence_hoy(): void
    {
        $sede    = $this->crearSede();
        $this->crearAgentToken($sede);
        $cliente = $this->crearCliente($sede);
        $this->crearMembresia($cliente, now()->toDateString()); // fecha_fin = hoy → vigente

        $payload = [
            'id_cliente' => $cliente->id_cliente,
            'id_sede'    => $sede->id_sede,
            'fecha_hora' => now()->toDateTimeString(),
        ];

        $this->postJson('/api/agente/asistencia', $payload, $this->headers())
             ->assertStatus(201);

        $this->assertDatabaseCount('asistencias', 1);
    }

    public function test_registrar_asistencia_usa_membresia_mas_reciente(): void
    {
        $sede    = $this->crearSede();
        $this->crearAgentToken($sede);
        $cliente = $this->crearCliente($sede);
        // Tuvo una vencida, pero renovó: la más reciente está vigente
        $this->crearMembresia($cliente, now()->subMonths(2)->toDateString());
        $this->crearMembresia($cliente, now()->addDays(25)->toDateString());

        $payload = [
            'id_cliente' => $cliente->id_cliente,
            'id_sede'    => $sede->id_sede,
            'fecha_hora' => now()->toDateTimeString(),
        ];

        $this->postJson('/api/agente/asistencia', $payload, $this->headers())
             ->assertStatus(201);
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
