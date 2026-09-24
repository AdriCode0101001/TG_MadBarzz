<?php

namespace Tests\Feature;

use App\Models\Asistencia;
use App\Models\Cliente;
use App\Models\Membresia;
use App\Models\MembresiaCliente;
use App\Models\Pago;
use App\Models\Persona;
use App\Models\Rol;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Bug: ClienteController::show() ignoraba el {cliente} de la URL y
 * buscaba "el cliente del usuario logueado" (Auth::id()) — un resabio de
 * antes de que existiera el portal del cliente (ClientePortalController).
 * Como la ruta clientes.show solo la usa el STAFF (Admin/Recepcionista/
 * Entrenador, nunca el rol Cliente), esa búsqueda nunca encontraba nada y
 * "Ver detalle" siempre mostraba el error "no tiene un perfil de cliente
 * asociado". Estas pruebas fijan el comportamiento correcto: la pantalla
 * muestra al cliente de la URL, no al usuario logueado.
 */
class ClienteShowTest extends TestCase
{
    use RefreshDatabase;

    private static int $seq = 0;

    private function sede(string $nombre = 'Sede Show'): Sede
    {
        return Sede::create(['nombre' => $nombre, 'direccion' => 'Calle 1', 'activo' => true]);
    }

    private function staff(string $rolNombre, ?Sede $sede = null): User
    {
        $atributos = ['rol_id' => Rol::firstOrCreate(['nombre' => $rolNombre])->id];

        if ($sede) {
            $atributos['id_sede'] = $sede->id_sede;
        }

        return User::factory()->create($atributos);
    }

    private function crearCliente(Sede $sede, string $nombre = 'Cliente Detalle'): Cliente
    {
        self::$seq++;
        $s = self::$seq;

        $persona = Persona::create([
            'nombre_completo' => $nombre, 'apellido_paterno' => 'Apellido', 'apellido_materno' => 'Materno',
            'ci' => "CISHOW{$s}", 'telefono' => "+59171144{$s}", 'genero' => 'masculino',
            'fecha_nacimiento' => '1992-05-15',
        ]);
        $user = User::factory()->create([
            'rol_id'  => Rol::firstOrCreate(['nombre' => 'Cliente'])->id,
            'id_sede' => $sede->id_sede,
        ]);

        return Cliente::create([
            'id_persona' => $persona->id_persona, 'id_usuario' => $user->id,
            'estado' => 'activo', 'dias_asistidos' => 3, 'id_sede' => $sede->id_sede,
        ]);
    }

    // ── 1. El staff ve el detalle del cliente de la URL, no un error ─────────

    public function test_recepcionista_ve_el_detalle_del_cliente_elegido_en_el_listado(): void
    {
        $sede    = $this->sede();
        $staff   = $this->staff('Recepcionista', $sede);
        $cliente = $this->crearCliente($sede, 'Marco Villca');

        $response = $this->actingAs($staff)->get(route('clientes.show', $cliente->id_cliente));

        $response->assertOk();
        $response->assertSee('Marco Villca', false);
        $response->assertSee('CISHOW' . self::$seq, false);
        $response->assertDontSee('no tiene un perfil de cliente asociado', false);
    }

    public function test_entrenador_ve_el_detalle_del_cliente_elegido(): void
    {
        $sede    = $this->sede();
        $staff   = $this->staff('Entrenador', $sede);
        $cliente = $this->crearCliente($sede, 'Gabriela Sofía');

        $this->actingAs($staff)
             ->get(route('clientes.show', $cliente->id_cliente))
             ->assertOk()
             ->assertSee('Gabriela Sofía', false);
    }

    // ── 2. El admin ve el detalle de cualquier cliente ────────────────────────

    public function test_administrador_ve_el_detalle_de_cualquier_cliente_de_cualquier_sede(): void
    {
        $sedeA = $this->sede('Sede A');
        $sedeB = $this->sede('Sede B');
        $admin = $this->staff('Administrador'); // sin id_sede: ve todas igual

        $clienteA = $this->crearCliente($sedeA, 'Cliente De Sede A');
        $clienteB = $this->crearCliente($sedeB, 'Cliente De Sede B');

        $this->actingAs($admin)->get(route('clientes.show', $clienteA->id_cliente))
             ->assertOk()->assertSee('Cliente De Sede A', false);

        $this->actingAs($admin)->get(route('clientes.show', $clienteB->id_cliente))
             ->assertOk()->assertSee('Cliente De Sede B', false);
    }

    // ── 3. Muestra membresía, pagos y asistencias del cliente correcto ───────

    public function test_muestra_membresia_pagos_y_asistencias_del_cliente_correcto(): void
    {
        $sede    = $this->sede();
        $staff   = $this->staff('Administrador');
        $cliente = $this->crearCliente($sede, 'Cliente Con Datos');
        $otro    = $this->crearCliente($sede, 'Cliente Sin Relacion');

        $membresia = Membresia::create(['tipo_membresia' => 'Mensual', 'precio' => 150]);
        MembresiaCliente::create([
            'id_cliente' => $cliente->id_cliente, 'id_membresia' => $membresia->id_membresia,
            'fecha_inicio' => now()->subDays(5)->toDateString(), 'fecha_fin' => now()->addDays(25)->toDateString(),
            'estado' => 'activa', 'precio_final' => 150,
        ]);

        Pago::create([
            'id_cliente' => $cliente->id_cliente, 'id_membresia' => $membresia->id_membresia,
            'monto' => 150, 'fecha_pago' => now()->toDateString(), 'metodo_pago' => 'efectivo', 'id_sede' => $sede->id_sede,
        ]);
        // Pago de OTRO cliente: no debe aparecer en el detalle de $cliente.
        Pago::create([
            'id_cliente' => $otro->id_cliente, 'id_membresia' => $membresia->id_membresia,
            'monto' => 999, 'fecha_pago' => now()->toDateString(), 'metodo_pago' => 'qr', 'id_sede' => $sede->id_sede,
        ]);

        Asistencia::create(['id_cliente' => $cliente->id_cliente, 'fecha_hora_entrada' => now(), 'id_sede' => $sede->id_sede]);

        $response = $this->actingAs($staff)->get(route('clientes.show', $cliente->id_cliente));

        $response->assertOk();
        $response->assertSee('Mensual', false);
        $response->assertSee('150.00', false);
        $response->assertDontSee('999.00', false);
    }

    // ── 4. Sede: el staff de otra sede no puede ver el cliente (404) ─────────

    public function test_staff_de_otra_sede_no_puede_ver_el_detalle_del_cliente(): void
    {
        $sedePropia = $this->sede('Sede Propia');
        $sedeAjena  = $this->sede('Sede Ajena');
        $staff      = $this->staff('Recepcionista', $sedePropia);
        $clienteAjeno = $this->crearCliente($sedeAjena, 'Cliente De Otra Sede');

        $this->actingAs($staff)
             ->get(route('clientes.show', $clienteAjeno->id_cliente))
             ->assertStatus(404);
    }

    // ── 5. El portal del cliente sigue viendo SU propia info (no se tocó) ────

    public function test_el_portal_del_cliente_sigue_mostrando_su_propia_info(): void
    {
        $sede    = $this->sede();
        $cliente = $this->crearCliente($sede, 'Cliente Portal Propio');

        $membresia = Membresia::create(['tipo_membresia' => 'Trimestral', 'precio' => 400]);
        MembresiaCliente::create([
            'id_cliente' => $cliente->id_cliente, 'id_membresia' => $membresia->id_membresia,
            'fecha_inicio' => now()->subDays(2)->toDateString(), 'fecha_fin' => now()->addDays(88)->toDateString(),
            'estado' => 'activa', 'precio_final' => 400,
        ]);

        $this->actingAs($cliente->user)
             ->get(route('portal.membresia'))
             ->assertOk()
             ->assertSee('Trimestral', false);
    }

    public function test_el_rol_cliente_no_puede_entrar_a_clientes_show_del_staff(): void
    {
        $sede    = $this->sede();
        $cliente = $this->crearCliente($sede);

        // clientes.show es del staff (role:Administrador,Recepcionista,Entrenador);
        // un usuario con rol Cliente no debe poder entrar ahí, ni siquiera al suyo propio.
        $this->actingAs($cliente->user)
             ->get(route('clientes.show', $cliente->id_cliente))
             ->assertStatus(403);
    }
}
