<?php

namespace Tests\Feature;

use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Matriz de permisos por rol:
 *  - Administrador: acceso total, incluida la GESTIÓN del catálogo
 *    (crear/editar/borrar tipos de membresía y descuentos).
 *  - Recepcionista: clientes, pagos, asistencias, asignar membresías, catálogo SOLO LECTURA. NO arma rutinas.
 *  - Entrenador: clientes (sin borrar), arma SUS rutinas, asistencias,
 *    registra pagos (sin editar/borrar), asigna membresías, catálogo SOLO
 *    LECTURA. NO gestiona personal.
 *  - Cliente: solo su portal; rutas de administración → 403.
 *
 * assertNotForbidden() = el middleware dejó pasar (cualquier cosa menos 403).
 */
class RolMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $rolNombre): User
    {
        $rol = Rol::firstOrCreate(['nombre' => $rolNombre]);
        return User::factory()->create(['rol_id' => $rol->id]);
    }

    /** Aserta que la respuesta NO es un 403 del middleware de rol. */
    private function assertNotForbidden($response): void
    {
        $this->assertNotEquals(403, $response->status(),
            "Se esperaba acceso permitido pero el middleware devolvió 403.");
    }

    // ===================================================================
    // CLIENTE: 403 en todas las rutas de administración (no romper)
    // ===================================================================

    #[\PHPUnit\Framework\Attributes\DataProvider('rutasBloqueadasCliente')]
    public function test_cliente_recibe_403(string $ruta): void
    {
        $this->actingAs($this->makeUser('Cliente'));
        $this->get($ruta)->assertStatus(403);
    }

    public static function rutasBloqueadasCliente(): array
    {
        return [
            'clientes'     => ['/clientes'],
            'pagos'        => ['/pagos'],
            'users'        => ['/users'],
            'rutinas'      => ['/rutinas'],
            'entrenadores' => ['/entrenadores'],
            'membresias'          => ['/membresias'],
            'descuentos'          => ['/descuentos'],
            'grupos_musculares'   => ['/grupos_musculares'],
            'calendario_musculos' => ['/calendario-musculos'],
            'ejercicios'          => ['/ejercicios'],
            'reportes'            => ['/reportes/clientes-estado'],
        ];
    }

    // ===================================================================
    // ADMINISTRADOR: acceso total (incluida gestión del catálogo)
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
            'asistencias-qr'    => ['/asistencias-qr'],
            'users'             => ['/users'],
            'roles'             => ['/roles'],
            'personas'          => ['/personas'],
            'administradores'   => ['/administradores'],
            'recepcionistas'    => ['/recepcionistas'],
            'entrenadores'      => ['/entrenadores'],
            'membresias'                 => ['/membresias'],
            'membresias-crear'           => ['/membresias/create'],
            'descuentos'                 => ['/descuentos'],
            'descuentos-crear'           => ['/descuentos/create'],
            'grupos_musculares'          => ['/grupos_musculares'],
            'grupos_musculares-crear'    => ['/grupos_musculares/create'],
            'calendario_musculos'        => ['/calendario-musculos'],
            'ejercicios'                 => ['/ejercicios'],
            'membresia_cliente'          => ['/membresia_cliente'],
            'notificaciones'    => ['/notificaciones'],
            'rutinas'           => ['/rutinas'],
            'promociones'       => ['/promociones'],
            'reporte-estado'    => ['/reportes/clientes-estado'],
            'reporte-nuevos'    => ['/reportes/clientes-nuevos'],
            'reporte-pagos'     => ['/reportes/pagos-por-mes'],
        ];
    }

    // ===================================================================
    // RECEPCIONISTA: sus rutas permitidas (incluye personal y catálogo)
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
            'clientes'           => ['/clientes'],
            'clientes-crear'     => ['/clientes/create'],
            'pagos'              => ['/pagos'],
            'pagos-crear'        => ['/pagos/create'],
            'asistencias-qr'     => ['/asistencias-qr'],
            'notificaciones'     => ['/notificaciones'],
            'membresias'         => ['/membresias'],
            'descuentos'         => ['/descuentos'],
            'grupos_musculares'  => ['/grupos_musculares'],
            'membresia_cliente'  => ['/membresia_cliente'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('rutasBloqueadasRecepcionista')]
    public function test_recepcionista_recibe_403(string $metodo, string $ruta): void
    {
        $this->actingAs($this->makeUser('Recepcionista'));
        $this->call($metodo, $ruta)->assertStatus(403);
    }

    public static function rutasBloqueadasRecepcionista(): array
    {
        return [
            'users'                 => ['GET', '/users'],
            'roles'                 => ['GET', '/roles'],
            'administradores'       => ['GET', '/administradores'],
            'reportes'              => ['GET', '/reportes/clientes-estado'],
            'promociones'           => ['GET', '/promociones'],
            'catalogo-membresia-crear'  => ['GET', '/membresias/create'],
            'catalogo-membresia-editar' => ['GET', '/membresias/1/edit'],
            'catalogo-membresia-borrar' => ['DELETE', '/membresias/1'],
            'catalogo-descuento-crear'  => ['GET', '/descuentos/create'],
            'catalogo-descuento-editar' => ['GET', '/descuentos/1/edit'],
            'catalogo-descuento-borrar' => ['DELETE', '/descuentos/1'],
            'catalogo-grupo_muscular-crear'  => ['GET', '/grupos_musculares/create'],
            'calendario_musculos'       => ['GET', '/calendario-musculos'],
            'ejercicios'                => ['GET', '/ejercicios'],
            // Etapa 3: armar rutinas es solo de Administrador y Entrenador
            'rutinas'                   => ['GET', '/rutinas'],
            'borrar-entrenador'         => ['DELETE', '/entrenadores/1'],
            'borrar-recepcionista'      => ['DELETE', '/recepcionistas/1'],
            // Gestión de PERSONAL: solo Administrador (ver GestionPersonalSoloAdminTest)
            'personal-entrenadores'          => ['GET', '/entrenadores'],
            'personal-entrenadores-crear'    => ['GET', '/entrenadores/create'],
            'personal-recepcionistas'        => ['GET', '/recepcionistas'],
            'personal-recepcionistas-crear'  => ['GET', '/recepcionistas/create'],
        ];
    }

    // ===================================================================
    // ENTRENADOR: sus rutas permitidas (clientes, pagos, catálogo lectura)
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
            'rutinas'           => ['/rutinas'],
            'clientes'          => ['/clientes'],        // hoy fallaba: debe poder
            'clientes-crear'    => ['/clientes/create'],
            'pagos'             => ['/pagos'],           // hoy fallaba: debe poder
            'pagos-crear'       => ['/pagos/create'],
            'asistencias-qr'    => ['/asistencias-qr'],
            'membresias'        => ['/membresias'],
            'descuentos'        => ['/descuentos'],
            'grupos_musculares' => ['/grupos_musculares'],
            'membresia_cliente' => ['/membresia_cliente'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('rutasBloqueadasEntrenador')]
    public function test_entrenador_recibe_403(string $metodo, string $ruta): void
    {
        $this->actingAs($this->makeUser('Entrenador'));
        $this->call($metodo, $ruta)->assertStatus(403);
    }

    public static function rutasBloqueadasEntrenador(): array
    {
        return [
            'users'    => ['GET', '/users'],
            'reportes' => ['GET', '/reportes/clientes-estado'],
            // NO gestiona personal
            'personal-entrenadores'   => ['GET', '/entrenadores'],
            'personal-recepcionistas' => ['GET', '/recepcionistas'],
            // Catálogo solo lectura
            'catalogo-membresia-crear'  => ['GET', '/membresias/create'],
            'catalogo-membresia-borrar' => ['DELETE', '/membresias/1'],
            'catalogo-descuento-crear'  => ['GET', '/descuentos/create'],
            'catalogo-descuento-borrar' => ['DELETE', '/descuentos/1'],
            'catalogo-grupo_muscular-crear'  => ['GET', '/grupos_musculares/create'],
            'calendario_musculos'       => ['GET', '/calendario-musculos'],
            'ejercicios'                => ['GET', '/ejercicios'],
            // Pagos: solo registra, no edita ni borra
            'editar-pago' => ['GET', '/pagos/1/edit'],
        ];
    }

    // Estos dos usan model binding (que resuelve antes del middleware de rol),
    // así que necesitan un registro real para comprobar el 403 y no un 404.

    public function test_entrenador_no_puede_borrar_clientes(): void
    {
        $cliente = $this->crearClienteReal();

        $this->actingAs($this->makeUser('Entrenador'));
        $this->delete('/clientes/' . $cliente->id_cliente)->assertStatus(403);
        $this->assertDatabaseHas('clientes', ['id_cliente' => $cliente->id_cliente, 'deleted_at' => null]);
    }

    public function test_entrenador_no_puede_borrar_pagos(): void
    {
        $cliente = $this->crearClienteReal();

        $idMembresia = \Illuminate\Support\Facades\DB::table('membresias')->insertGetId([
            'tipo_membresia' => 'Mensual', 'precio' => 150,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $idPago = \Illuminate\Support\Facades\DB::table('pagos')->insertGetId([
            'id_cliente'   => $cliente->id_cliente,
            'id_membresia' => $idMembresia,
            'monto'        => 150,
            'fecha_pago'   => now()->toDateString(),
            'metodo_pago'  => 'efectivo',
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        $this->actingAs($this->makeUser('Entrenador'));
        $this->delete('/pagos/' . $idPago)->assertStatus(403);
        $this->assertDatabaseHas('pagos', ['id_pago' => $idPago]);
    }

    // El controlador de grupos musculares usa binding implícito de modelo
    // (a diferencia de membresias/descuentos), así que /edit y destroy
    // necesitan una fila real para comprobar el 403 y no un 404.

    public function test_recepcionista_no_puede_editar_ni_borrar_grupos_musculares(): void
    {
        $grupo = \App\Models\GrupoMuscular::create(['nombre' => 'Pierna', 'activo' => true]);

        $this->actingAs($this->makeUser('Recepcionista'));
        $this->get("/grupos_musculares/{$grupo->id}/edit")->assertStatus(403);
        $this->delete("/grupos_musculares/{$grupo->id}")->assertStatus(403);
        $this->assertDatabaseHas('grupos_musculares', ['id' => $grupo->id, 'deleted_at' => null]);
    }

    public function test_entrenador_no_puede_editar_ni_borrar_grupos_musculares(): void
    {
        $grupo = \App\Models\GrupoMuscular::create(['nombre' => 'Hombro', 'activo' => true]);

        $this->actingAs($this->makeUser('Entrenador'));
        $this->get("/grupos_musculares/{$grupo->id}/edit")->assertStatus(403);
        $this->delete("/grupos_musculares/{$grupo->id}")->assertStatus(403);
        $this->assertDatabaseHas('grupos_musculares', ['id' => $grupo->id, 'deleted_at' => null]);
    }

    private function crearClienteReal(): \App\Models\Cliente
    {
        $rol  = Rol::firstOrCreate(['nombre' => 'Cliente']);
        $user = User::factory()->create(['rol_id' => $rol->id]);

        $personaId = \Illuminate\Support\Facades\DB::table('personas')->insertGetId([
            'nombre_completo'  => 'Cliente Matriz',
            'apellido_paterno' => 'Permisos',
            'apellido_materno' => 'Test',
            'ci'               => 'CIMTX' . uniqid(),
            'telefono'         => '+59170000000',
            'genero'           => 'M',
            'fecha_nacimiento' => '1990-01-01',
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        return \App\Models\Cliente::create([
            'id_persona'     => $personaId,
            'id_usuario'     => $user->id,
            'estado'         => 'activo',
            'dias_asistidos' => 0,
        ]);
    }
}
