<?php

namespace Tests\Feature\Performance;

use App\Models\Administrador;
use App\Models\Cliente;
use App\Models\Entrenador;
use App\Models\Membresia;
use App\Models\MembresiaCliente;
use App\Models\Persona;
use App\Models\Recepcionista;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Revisión de production-readiness — Tanda 2, Arreglo 1: los listados de
 * Clientes, Pagos, Personas, Usuarios, Entrenadores, Recepcionistas,
 * Administradores y Membresías de Cliente usaban ->get() sin paginar. Con
 * el volumen actual (decenas de registros) no se nota, pero con años de
 * datos esas pantallas cargarían TODA la tabla en una sola respuesta.
 */
class PaginacionListadosTest extends TestCase
{
    use RefreshDatabase;

    private static int $seq = 0;

    private function seq(): int
    {
        return ++self::$seq;
    }

    private function admin(): User
    {
        return User::factory()->create(['rol_id' => Rol::firstOrCreate(['nombre' => 'Administrador'])->id]);
    }

    private function persona(string $prefix, string $ciBuscable = null): Persona
    {
        $s = $this->seq();

        return Persona::create([
            'nombre_completo'  => $ciBuscable ? 'Encontrable Unico' : "{$prefix} {$s}",
            'apellido_paterno' => 'Test',
            'apellido_materno' => 'Paginacion',
            'ci'               => $ciBuscable ?? "CIPAG{$prefix}{$s}",
            'telefono'         => "+5917" . str_pad((string) $s, 8, '0', STR_PAD_LEFT),
            'genero'           => 'masculino',
            'fecha_nacimiento' => '1990-01-01',
        ]);
    }

    private function usuario(string $rol): User
    {
        return User::factory()->create(['rol_id' => Rol::firstOrCreate(['nombre' => $rol])->id]);
    }

    // ── Clientes ─────────────────────────────────────────────────────────────

    public function test_clientes_index_pagina_y_no_trae_todo_de_una(): void
    {
        for ($i = 1; $i <= 25; $i++) {
            $persona = $this->persona('Cliente');
            $user    = $this->usuario('Cliente');
            Cliente::create([
                'id_persona' => $persona->id_persona, 'id_usuario' => $user->id,
                'estado' => 'activo', 'dias_asistidos' => 0,
            ]);
        }

        $response = $this->actingAs($this->admin())->get('/clientes');

        $response->assertStatus(200);
        $clientes = $response->viewData('clientes');
        $this->assertTrue(method_exists($clientes, 'total'), 'clientes debería ser un paginador.');
        $this->assertSame(25, $clientes->total());
        $this->assertLessThanOrEqual(20, $clientes->count());
    }

    /**
     * Nota: ClienteController (y Persona/Entrenador/Recepcionista/
     * Administrador) buscan con whereRaw("CONCAT(...)"), sintaxis de MySQL
     * que SQLite (la base de los tests) no soporta — algo preexistente, no
     * introducido acá. Por eso esta prueba de "el filtro se conserva al
     * paginar" usa Usuarios y Pagos, cuyo buscador es LIKE simple
     * (portable), pero ejercita EXACTAMENTE el mismo mecanismo
     * (->when($buscar)->paginate()->withQueryString()) que every listado.
     */
    public function test_users_index_conserva_el_filtro_de_busqueda_al_paginar(): void
    {
        // 25 usuarios que matchean "buscable" (más de una página) + 5 que no.
        for ($i = 1; $i <= 25; $i++) {
            User::factory()->create([
                'rol_id' => Rol::firstOrCreate(['nombre' => 'Cliente'])->id,
                'name'   => "buscable.{$i}",
            ]);
        }
        for ($i = 1; $i <= 5; $i++) {
            User::factory()->create(['rol_id' => Rol::firstOrCreate(['nombre' => 'Cliente'])->id, 'name' => "otro.{$i}"]);
        }

        $pagina1 = $this->actingAs($this->admin())->get('/users?buscar=buscable')->viewData('usuarios');
        $this->assertSame(25, $pagina1->total());

        // Pedir la página 2 CON el mismo filtro: debe seguir devolviendo
        // solo resultados que matchean "buscable" (no los 5 "otro.*").
        $pagina2 = $this->actingAs($this->admin())->get('/users?buscar=buscable&page=2')->viewData('usuarios');
        $this->assertSame(25, $pagina2->total());
        $this->assertTrue($pagina2->every(fn ($u) => str_contains($u->name, 'buscable')));
    }

    public function test_pagos_index_conserva_el_filtro_de_busqueda_al_paginar(): void
    {
        // Un cliente cuyo nombre matchea "Buscable", con 25 pagos (más de
        // una página) — y otro cliente con pagos que NO deben aparecer.
        $clienteBuscable = $this->crearClienteCon($this->persona('BuscableUnico'));
        $clienteAjeno    = $this->crearClienteCon($this->persona('SinRelacion'));
        $membresia       = Membresia::create(['tipo_membresia' => 'Mensual', 'precio' => 100]);

        for ($i = 1; $i <= 25; $i++) {
            \App\Models\Pago::create([
                'id_cliente' => $clienteBuscable->id_cliente, 'id_membresia' => $membresia->id_membresia,
                'monto' => 100, 'fecha_pago' => now()->subDays($i)->toDateString(), 'metodo_pago' => 'efectivo',
            ]);
        }
        \App\Models\Pago::create([
            'id_cliente' => $clienteAjeno->id_cliente, 'id_membresia' => $membresia->id_membresia,
            'monto' => 100, 'fecha_pago' => now()->toDateString(), 'metodo_pago' => 'efectivo',
        ]);

        $filtro = 'buscar=' . urlencode('BuscableUnico');

        $pagina1 = $this->actingAs($this->admin())->get("/pagos?{$filtro}")->viewData('pagos');
        $this->assertSame(25, $pagina1->total());

        $pagina2 = $this->actingAs($this->admin())->get("/pagos?{$filtro}&page=2")->viewData('pagos');
        $this->assertSame(25, $pagina2->total());
        $this->assertTrue($pagina2->every(
            fn ($pago) => $pago->id_cliente === $clienteBuscable->id_cliente
        ));
    }

    // ── Pagos ────────────────────────────────────────────────────────────────

    public function test_pagos_index_pagina(): void
    {
        $cliente   = $this->crearClienteCon($this->persona('PagoCliente'));
        $membresia = Membresia::create(['tipo_membresia' => 'Mensual', 'precio' => 100]);

        for ($i = 1; $i <= 23; $i++) {
            \App\Models\Pago::create([
                'id_cliente' => $cliente->id_cliente, 'id_membresia' => $membresia->id_membresia,
                'monto' => 100, 'fecha_pago' => now()->subDays($i)->toDateString(), 'metodo_pago' => 'efectivo',
            ]);
        }

        $response = $this->actingAs($this->admin())->get('/pagos');

        $response->assertStatus(200);
        $pagos = $response->viewData('pagos');
        $this->assertSame(23, $pagos->total());
        $this->assertLessThanOrEqual(20, $pagos->count());
    }

    public function test_pagos_index_sigue_ordenado_por_mas_reciente_primero(): void
    {
        $cliente   = $this->crearClienteCon($this->persona('PagoOrden'));
        $membresia = Membresia::create(['tipo_membresia' => 'Mensual', 'precio' => 100]);

        // ->latest() por defecto ordena por created_at: se viaja en el
        // tiempo entre los dos create() para que queden inequívocamente
        // distinguibles (si no, ambos podrían caer en el mismo segundo).
        \Illuminate\Support\Carbon::setTestNow(now()->subMinute());
        $viejo = \App\Models\Pago::create([
            'id_cliente' => $cliente->id_cliente, 'id_membresia' => $membresia->id_membresia,
            'monto' => 100, 'fecha_pago' => now()->subDays(10)->toDateString(), 'metodo_pago' => 'efectivo',
        ]);

        \Illuminate\Support\Carbon::setTestNow(now()->addMinute());
        $reciente = \App\Models\Pago::create([
            'id_cliente' => $cliente->id_cliente, 'id_membresia' => $membresia->id_membresia,
            'monto' => 100, 'fecha_pago' => now()->toDateString(), 'metodo_pago' => 'efectivo',
        ]);
        \Illuminate\Support\Carbon::setTestNow();

        $pagos = $this->actingAs($this->admin())->get('/pagos')->viewData('pagos');

        $this->assertSame($reciente->id_pago, $pagos->first()->id_pago);
        $this->assertNotSame($viejo->id_pago, $pagos->first()->id_pago);
    }

    // ── Personas ─────────────────────────────────────────────────────────────

    public function test_personas_index_pagina(): void
    {
        for ($i = 1; $i <= 24; $i++) {
            $this->persona('PersonaSuelta');
        }

        $response = $this->actingAs($this->admin())->get('/personas');

        $response->assertStatus(200);
        $personas = $response->viewData('personas');
        $this->assertSame(24, $personas->total());
        $this->assertLessThanOrEqual(20, $personas->count());
    }

    // ── Usuarios ─────────────────────────────────────────────────────────────

    public function test_users_index_pagina(): void
    {
        for ($i = 1; $i <= 22; $i++) {
            $this->usuario('Cliente');
        }

        $response = $this->actingAs($this->admin())->get('/users');

        $response->assertStatus(200);
        $usuarios = $response->viewData('usuarios');
        $this->assertGreaterThanOrEqual(22, $usuarios->total());
        $this->assertLessThanOrEqual(20, $usuarios->count());
    }

    // ── Entrenadores ─────────────────────────────────────────────────────────

    public function test_entrenadores_index_pagina(): void
    {
        for ($i = 1; $i <= 21; $i++) {
            $persona = $this->persona('Entrenador');
            $user    = $this->usuario('Entrenador');
            Entrenador::create([
                'id_persona' => $persona->id_persona, 'id_usuario' => $user->id,
                'especialidad' => ['Calistenia'], 'experiencia' => '1 año',
                'disponibilidad' => ['lunes'], 'estado' => 'activo',
            ]);
        }

        $response = $this->actingAs($this->admin())->get('/entrenadores');

        $response->assertStatus(200);
        $entrenadores = $response->viewData('entrenadores');
        $this->assertSame(21, $entrenadores->total());
        $this->assertLessThanOrEqual(20, $entrenadores->count());
    }

    // ── Recepcionistas ───────────────────────────────────────────────────────

    public function test_recepcionistas_index_pagina(): void
    {
        for ($i = 1; $i <= 21; $i++) {
            $persona = $this->persona('Recepcionista');
            $user    = $this->usuario('Recepcionista');
            Recepcionista::create([
                'id_persona' => $persona->id_persona, 'id_usuario' => $user->id,
                'turno' => 'mañana', 'punto_atencion' => 'Caja 1', 'estado' => 'activo',
            ]);
        }

        $response = $this->actingAs($this->admin())->get('/recepcionistas');

        $response->assertStatus(200);
        $recepcionistas = $response->viewData('recepcionistas');
        $this->assertSame(21, $recepcionistas->total());
        $this->assertLessThanOrEqual(20, $recepcionistas->count());
    }

    // ── Administradores ──────────────────────────────────────────────────────

    public function test_administradores_index_pagina(): void
    {
        for ($i = 1; $i <= 21; $i++) {
            $persona = $this->persona('AdminSuelto');
            $user    = $this->usuario('Administrador');
            Administrador::create([
                'id_persona' => $persona->id_persona, 'id_usuario' => $user->id,
                'nivel_acceso' => 'gestion', 'area_responsable' => 'Test', 'estado' => 'activo',
            ]);
        }

        $response = $this->actingAs($this->admin())->get('/administradores');

        $response->assertStatus(200);
        $administradores = $response->viewData('administradores');
        $this->assertSame(21, $administradores->total());
        $this->assertLessThanOrEqual(20, $administradores->count());
    }

    // ── Membresías de Cliente ────────────────────────────────────────────────

    public function test_membresia_cliente_index_pagina(): void
    {
        $cliente   = $this->crearClienteCon($this->persona('MCCliente'));
        $membresia = Membresia::create(['tipo_membresia' => 'Mensual', 'precio' => 100]);

        for ($i = 1; $i <= 21; $i++) {
            MembresiaCliente::create([
                'id_cliente' => $cliente->id_cliente, 'id_membresia' => $membresia->id_membresia,
                'fecha_inicio' => now()->subMonths($i)->toDateString(),
                'fecha_fin'    => now()->subMonths($i)->addDays(30)->toDateString(),
                'precio_final' => 100, 'descuento' => 0, 'estado' => 'activa',
            ]);
        }

        $response = $this->actingAs($this->admin())->get('/membresia_cliente');

        $response->assertStatus(200);
        $membresias = $response->viewData('membresiasClientes');
        $this->assertSame(21, $membresias->total());
        $this->assertLessThanOrEqual(20, $membresias->count());
    }

    // ── Helper ───────────────────────────────────────────────────────────────

    private function crearClienteCon(Persona $persona): Cliente
    {
        $user = $this->usuario('Cliente');

        return Cliente::create([
            'id_persona' => $persona->id_persona, 'id_usuario' => $user->id,
            'estado' => 'activo', 'dias_asistidos' => 0,
        ]);
    }
}
