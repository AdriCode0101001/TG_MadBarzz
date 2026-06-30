<?php

namespace Tests\Feature;

use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifica que el middleware EnsureRole:
 *  - bloquea con 403 a roles sin permiso
 *  - permite el acceso a los roles legítimos de cada ruta
 *
 * assertNotForbidden() = el middleware dejó pasar (puede ser 200, 302, 404, 500…
 *   cualquier cosa menos 403, que es la señal de que el middleware bloqueó).
 */
class RolMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $rolNombre): User
    {
        $rol = Rol::create(['nombre' => $rolNombre]);
        return User::factory()->create(['rol_id' => $rol->id]);
    }

    /** Aserta que la respuesta NO es un 403 del middleware de rol. */
    private function assertNotForbidden($response): void
    {
        $this->assertNotEquals(403, $response->status(),
            "Se esperaba acceso permitido pero el middleware devolvió 403.");
    }

    // ===================================================================
    // BLOQUEOS: Cliente recibe 403 en rutas que NO le corresponden
    // ===================================================================

    public function test_cliente_recibe_403_en_clientes_index(): void
    {
        $this->actingAs($this->makeUser('Cliente'));
        $this->get('/clientes')->assertStatus(403);
    }

    public function test_cliente_recibe_403_en_pagos_index(): void
    {
        $this->actingAs($this->makeUser('Cliente'));
        $this->get('/pagos')->assertStatus(403);
    }

    public function test_cliente_recibe_403_en_users_index(): void
    {
        $this->actingAs($this->makeUser('Cliente'));
        $this->get('/users')->assertStatus(403);
    }

    public function test_cliente_recibe_403_en_asistencias(): void
    {
        $this->actingAs($this->makeUser('Cliente'));
        $this->get('/asistencias')->assertStatus(403);
    }

    public function test_cliente_recibe_403_en_rutinas(): void
    {
        $this->actingAs($this->makeUser('Cliente'));
        $this->get('/rutinas')->assertStatus(403);
    }

    public function test_cliente_recibe_403_en_entrenadores(): void
    {
        $this->actingAs($this->makeUser('Cliente'));
        $this->get('/entrenadores')->assertStatus(403);
    }

    public function test_cliente_recibe_403_en_reportes(): void
    {
        $this->actingAs($this->makeUser('Cliente'));
        $this->get('/reportes/clientes-estado')->assertStatus(403);
    }

    // ===================================================================
    // BLOQUEOS: Recepcionista recibe 403 en rutas solo de Administrador
    // ===================================================================

    public function test_recepcionista_recibe_403_en_users(): void
    {
        $this->actingAs($this->makeUser('Recepcionista'));
        $this->get('/users')->assertStatus(403);
    }

    public function test_recepcionista_recibe_403_en_roles(): void
    {
        $this->actingAs($this->makeUser('Recepcionista'));
        $this->get('/roles')->assertStatus(403);
    }

    public function test_recepcionista_recibe_403_en_administradores(): void
    {
        $this->actingAs($this->makeUser('Recepcionista'));
        $this->get('/administradores')->assertStatus(403);
    }

    public function test_recepcionista_recibe_403_en_reportes(): void
    {
        $this->actingAs($this->makeUser('Recepcionista'));
        $this->get('/reportes/clientes-estado')->assertStatus(403);
    }

    public function test_recepcionista_recibe_403_en_promociones(): void
    {
        $this->actingAs($this->makeUser('Recepcionista'));
        $this->get('/promociones')->assertStatus(403);
    }

    // ===================================================================
    // BLOQUEOS: Entrenador recibe 403 en rutas que NO le corresponden
    // ===================================================================

    public function test_entrenador_recibe_403_en_clientes(): void
    {
        $this->actingAs($this->makeUser('Entrenador'));
        $this->get('/clientes')->assertStatus(403);
    }

    public function test_entrenador_recibe_403_en_pagos(): void
    {
        $this->actingAs($this->makeUser('Entrenador'));
        $this->get('/pagos')->assertStatus(403);
    }

    public function test_entrenador_recibe_403_en_users(): void
    {
        $this->actingAs($this->makeUser('Entrenador'));
        $this->get('/users')->assertStatus(403);
    }

    public function test_entrenador_recibe_403_en_reportes(): void
    {
        $this->actingAs($this->makeUser('Entrenador'));
        $this->get('/reportes/clientes-estado')->assertStatus(403);
    }

    // ===================================================================
    // ACCESOS PERMITIDOS: Administrador llega a todas sus rutas
    // ===================================================================

    #[\PHPUnit\Framework\Attributes\DataProvider('rutasAdministrador')]
    public function test_administrador_puede_acceder(string $ruta): void
    {
        $this->actingAs($this->makeUser('Administrador'));
        $this->assertNotForbidden($this->get($ruta));
    }

    public static function rutasAdministrador(): array
    {
        return [
            'clientes'          => ['/clientes'],
            'pagos'             => ['/pagos'],
            'asistencias'       => ['/asistencias'],
            'users'             => ['/users'],
            'roles'             => ['/roles'],
            'personas'          => ['/personas'],
            'administradores'   => ['/administradores'],
            'recepcionistas'    => ['/recepcionistas'],
            'entrenadores'      => ['/entrenadores'],
            'membresias'        => ['/membresias'],
            'membresia_cliente' => ['/membresia_cliente'],
            'notificaciones'    => ['/notificaciones'],
            'rutinas'           => ['/rutinas'],
            'promociones'       => ['/promociones'],
            'reporte-estado'    => ['/reportes/clientes-estado'],
            'reporte-nuevos'    => ['/reportes/clientes-nuevos'],
            'reporte-pagos'     => ['/reportes/pagos-por-mes'],
        ];
    }

    // ===================================================================
    // ACCESOS PERMITIDOS: Recepcionista llega a SUS rutas
    // ===================================================================

    #[\PHPUnit\Framework\Attributes\DataProvider('rutasRecepcionista')]
    public function test_recepcionista_puede_acceder(string $ruta): void
    {
        $this->actingAs($this->makeUser('Recepcionista'));
        $this->assertNotForbidden($this->get($ruta));
    }

    public static function rutasRecepcionista(): array
    {
        return [
            'clientes'          => ['/clientes'],
            'pagos'             => ['/pagos'],
            'asistencias'       => ['/asistencias'],
            'notificaciones'    => ['/notificaciones'],
            'membresias'        => ['/membresias'],
            'membresia_cliente' => ['/membresia_cliente'],
            'recepcionistas'    => ['/recepcionistas'],
        ];
    }

    // ===================================================================
    // ACCESOS PERMITIDOS: Entrenador llega a SUS rutas
    // ===================================================================

    #[\PHPUnit\Framework\Attributes\DataProvider('rutasEntrenador')]
    public function test_entrenador_puede_acceder(string $ruta): void
    {
        $this->actingAs($this->makeUser('Entrenador'));
        $this->assertNotForbidden($this->get($ruta));
    }

    public static function rutasEntrenador(): array
    {
        return [
            'rutinas'     => ['/rutinas'],
            'entrenadores' => ['/entrenadores'],
        ];
    }
}
