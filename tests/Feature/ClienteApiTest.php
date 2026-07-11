<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\MembresiaCliente;
use App\Models\Rol;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ClienteApiTest extends TestCase
{
    use RefreshDatabase;

    private function crearUsuarioConRol(string $nombreRol): User
    {
        $sede = Sede::create(['nombre' => 'Sede Test', 'direccion' => 'Calle 1']);
        $rol  = Rol::firstOrCreate(['nombre' => $nombreRol]);

        return User::factory()->create([
            'rol_id'  => $rol->id,
            'id_sede' => $sede->id_sede,
        ]);
    }

    private static int $personaSeq = 0;

    private function crearCliente(User $user): Cliente
    {
        self::$personaSeq++;
        $seq = self::$personaSeq;

        $personaId = DB::table('personas')->insertGetId([
            'nombre_completo'  => "Cliente {$seq}",
            'apellido_paterno' => 'López',
            'apellido_materno' => 'García',
            'ci'               => "1000{$seq}",
            'telefono'         => "7000000{$seq}",
            'genero'           => 'F',
            'fecha_nacimiento' => '1990-01-01',
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        return Cliente::create([
            'id_persona'     => $personaId,
            'id_usuario'     => $user->id,
            'estado'         => 'activo',
            'dias_asistidos' => 0,
            'id_sede'        => $user->id_sede,
        ]);
    }

    // ── Autenticación ──────────────────────────────────────────────────────

    public function test_login_con_credenciales_validas_devuelve_token(): void
    {
        $user = $this->crearUsuarioConRol('Cliente');

        $response = $this->postJson('/api/login', [
            'email'    => $user->email,
            'password' => 'password',
        ]);

        $response->assertOk()
                 ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email', 'rol']]);
    }

    public function test_login_con_credenciales_invalidas_devuelve_401(): void
    {
        $this->crearUsuarioConRol('Cliente');

        $response = $this->postJson('/api/login', [
            'email'    => 'nadie@example.com',
            'password' => 'wrong',
        ]);

        $response->assertStatus(401);
    }

    public function test_logout_elimina_token(): void
    {
        $user  = $this->crearUsuarioConRol('Cliente');
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)
             ->postJson('/api/logout')
             ->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    // ── Endpoints del cliente ──────────────────────────────────────────────

    public function test_membresia_sin_autenticacion_devuelve_401(): void
    {
        $this->getJson('/api/cliente/membresia')->assertStatus(401);
    }

    public function test_membresia_con_rol_administrador_devuelve_403(): void
    {
        $admin = $this->crearUsuarioConRol('Administrador');
        $token = $admin->createToken('test')->plainTextToken;

        $this->withToken($token)
             ->getJson('/api/cliente/membresia')
             ->assertStatus(403);
    }

    public function test_membresia_de_cliente_sin_membresia_activa_devuelve_null(): void
    {
        $user   = $this->crearUsuarioConRol('Cliente');
        $this->crearCliente($user);
        $token  = $user->createToken('test')->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/cliente/membresia');

        $response->assertOk()->assertJson(['membresia' => null]);
    }

    public function test_membresia_de_cliente_con_membresia_activa_devuelve_datos(): void
    {
        $user    = $this->crearUsuarioConRol('Cliente');
        $cliente = $this->crearCliente($user);
        $token   = $user->createToken('test')->plainTextToken;

        $membresiaId = DB::table('membresias')->insertGetId([
            'tipo_membresia' => 'Mensual',
            'precio'         => 100,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        MembresiaCliente::create([
            'id_cliente'   => $cliente->id_cliente,
            'id_membresia' => $membresiaId,
            'fecha_inicio' => now()->subDays(5),
            'fecha_fin'    => now()->addDays(25)->toDateString(),
            'precio_final' => 90,
            'descuento'    => 10,
            'id_sede'      => $user->id_sede,
            'estado'       => 'activa', // pagada: da acceso
        ]);

        $response = $this->withToken($token)->getJson('/api/cliente/membresia');

        $response->assertOk()
                 ->assertJsonPath('membresia.tipo', 'Mensual')
                 ->assertJsonPath('membresia.precio_final', 90);
    }

    public function test_pagos_solo_devuelve_los_del_cliente_autenticado(): void
    {
        $user1   = $this->crearUsuarioConRol('Cliente');
        $cliente1 = $this->crearCliente($user1);

        $user2   = $this->crearUsuarioConRol('Cliente');
        $cliente2 = $this->crearCliente($user2);

        $membresiaId = DB::table('membresias')->insertGetId([
            'tipo_membresia' => 'Mensual', 'precio' => 100,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::table('pagos')->insert([
            ['id_cliente' => $cliente1->id_cliente, 'id_membresia' => $membresiaId, 'monto' => 100, 'fecha_pago' => now(), 'metodo_pago' => 'efectivo', 'id_sede' => $user1->id_sede, 'created_at' => now(), 'updated_at' => now()],
            ['id_cliente' => $cliente2->id_cliente, 'id_membresia' => $membresiaId, 'monto' => 200, 'fecha_pago' => now(), 'metodo_pago' => 'efectivo', 'id_sede' => $user2->id_sede, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $token = $user1->createToken('test')->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/cliente/pagos');

        $response->assertOk();
        $this->assertCount(1, $response->json('pagos'));
        $this->assertEquals(100, $response->json('pagos.0.monto'));
    }

    public function test_asistencias_solo_devuelve_las_del_cliente_autenticado(): void
    {
        $user    = $this->crearUsuarioConRol('Cliente');
        $cliente = $this->crearCliente($user);
        $token   = $user->createToken('test')->plainTextToken;

        DB::table('asistencias')->insert([
            ['id_cliente' => $cliente->id_cliente, 'fecha_hora_entrada' => now(), 'id_sede' => $user->id_sede, 'created_at' => now(), 'updated_at' => now()],
            ['id_cliente' => $cliente->id_cliente, 'fecha_hora_entrada' => now()->subDay(), 'id_sede' => $user->id_sede, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $response = $this->withToken($token)->getJson('/api/cliente/asistencias');

        $response->assertOk();
        $this->assertCount(2, $response->json('asistencias'));
    }
}
