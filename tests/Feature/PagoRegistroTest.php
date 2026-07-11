<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Descuento;
use App\Models\Membresia;
use App\Models\MembresiaCliente;
use App\Models\Pago;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Registro de pagos: fecha siempre HOY (la impone el servidor), métodos solo
 * Efectivo/QR, y comprobante visible por rutas protegidas (disco privado).
 */
class PagoRegistroTest extends TestCase
{
    use RefreshDatabase;

    private static int $seq = 0;

    private function operador(string $rol = 'Recepcionista'): User
    {
        return User::factory()->create([
            'rol_id' => Rol::firstOrCreate(['nombre' => $rol])->id,
        ]);
    }

    private function crearCliente(): Cliente
    {
        self::$seq++;
        $s = self::$seq;

        $user = User::factory()->create([
            'rol_id' => Rol::firstOrCreate(['nombre' => 'Cliente'])->id,
        ]);

        $personaId = DB::table('personas')->insertGetId([
            'nombre_completo'  => "Pagador {$s}",
            'apellido_paterno' => 'Pago',
            'apellido_materno' => 'Test',
            'ci'               => "CIPAGO{$s}",
            'telefono'         => "+5917500000{$s}",
            'genero'           => 'M',
            'fecha_nacimiento' => '1990-01-01',
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        return Cliente::create([
            'id_persona'     => $personaId,
            'id_usuario'     => $user->id,
            'estado'         => 'activo',
            'dias_asistidos' => 0,
        ]);
    }

    private function sinDescuento(): Descuento
    {
        // Creado por la migración de la tabla descuentos
        return Descuento::where('nombre', 'Sin descuento')->firstOrFail();
    }

    private function payload(Cliente $cliente, Membresia $membresia, array $extra = []): array
    {
        return array_merge([
            'id_cliente'   => $cliente->id_cliente,
            'id_membresia' => $membresia->id_membresia,
            'id_descuento' => $this->sinDescuento()->id,
            'metodo_pago'  => 'efectivo',
        ], $extra);
    }

    // ── 2. Fecha de pago = hoy, impuesta por el servidor ─────────────────────

    public function test_fecha_de_pago_es_siempre_hoy_aunque_el_envio_traiga_otra(): void
    {
        $this->travelTo(Carbon::parse('2030-06-10 15:00:00', config('app.timezone')));

        $cliente   = $this->crearCliente();
        $membresia = Membresia::create(['tipo_membresia' => 'Mensual', 'precio' => 150]);

        $this->actingAs($this->operador())
             ->post('/pagos', $this->payload($cliente, $membresia, ['fecha_pago' => '2020-01-01']))
             ->assertRedirect(route('pagos.index'));

        $pago = Pago::firstOrFail();
        $this->assertSame('2030-06-10', Carbon::parse($pago->fecha_pago)->toDateString());
    }

    // ── 3. Métodos: solo efectivo y QR ────────────────────────────────────────

    public function test_metodo_transferencia_es_rechazado(): void
    {
        $cliente   = $this->crearCliente();
        $membresia = Membresia::create(['tipo_membresia' => 'Mensual', 'precio' => 150]);

        $this->actingAs($this->operador())
             ->post('/pagos', $this->payload($cliente, $membresia, ['metodo_pago' => 'transferencia']))
             ->assertSessionHasErrors('metodo_pago');

        $this->assertDatabaseCount('pagos', 0);
        $this->assertDatabaseCount('membresia_cliente', 0);
    }

    public function test_metodos_efectivo_y_qr_son_aceptados(): void
    {
        Storage::fake('privado');

        $cliente   = $this->crearCliente();
        $membresia = Membresia::create(['tipo_membresia' => 'Mensual', 'precio' => 150]);

        $this->actingAs($this->operador());
        // Efectivo no exige comprobante; QR sí, así que va con su imagen.
        $this->post('/pagos', $this->payload($cliente, $membresia, ['metodo_pago' => 'efectivo']))->assertSessionHasNoErrors();
        $this->post('/pagos', $this->payload($cliente, $membresia, [
            'metodo_pago' => 'QR',
            'imagen_qr'   => UploadedFile::fake()->image('comprobante.jpg'),
        ]))->assertSessionHasNoErrors();

        $this->assertDatabaseCount('pagos', 2);
    }

    // ── Comprobante obligatorio cuando el método es QR ───────────────────────

    public function test_pago_qr_sin_comprobante_es_rechazado(): void
    {
        Storage::fake('privado');

        $cliente   = $this->crearCliente();
        $membresia = Membresia::create(['tipo_membresia' => 'Mensual', 'precio' => 150]);

        // QR sin imagen: el servidor lo rechaza aunque el frontend se manipule
        $this->actingAs($this->operador())
             ->post('/pagos', $this->payload($cliente, $membresia, ['metodo_pago' => 'QR']))
             ->assertSessionHasErrors(['imagen_qr' => 'Debes subir el comprobante para un pago por QR.']);

        $this->assertDatabaseCount('pagos', 0);
        $this->assertDatabaseCount('membresia_cliente', 0);
    }

    public function test_pago_qr_con_comprobante_es_aceptado(): void
    {
        Storage::fake('privado');

        $cliente   = $this->crearCliente();
        $membresia = Membresia::create(['tipo_membresia' => 'Mensual', 'precio' => 150]);

        $this->actingAs($this->operador())
             ->post('/pagos', $this->payload($cliente, $membresia, [
                 'metodo_pago' => 'QR',
                 'imagen_qr'   => UploadedFile::fake()->image('comprobante.png'),
             ]))
             ->assertSessionHasNoErrors()
             ->assertRedirect(route('pagos.index'));

        $pago = Pago::firstOrFail();
        $this->assertNotNull($pago->imagen_qr);
        Storage::disk('privado')->assertExists($pago->imagen_qr);
    }

    public function test_pago_efectivo_sin_comprobante_es_aceptado(): void
    {
        Storage::fake('privado');

        $cliente   = $this->crearCliente();
        $membresia = Membresia::create(['tipo_membresia' => 'Mensual', 'precio' => 150]);

        $this->actingAs($this->operador())
             ->post('/pagos', $this->payload($cliente, $membresia, ['metodo_pago' => 'efectivo']))
             ->assertSessionHasNoErrors()
             ->assertRedirect(route('pagos.index'));

        $pago = Pago::firstOrFail();
        $this->assertNull($pago->imagen_qr);
    }

    public function test_pago_qr_rechaza_archivo_que_no_es_imagen(): void
    {
        Storage::fake('privado');

        $cliente   = $this->crearCliente();
        $membresia = Membresia::create(['tipo_membresia' => 'Mensual', 'precio' => 150]);

        // Un PDF disfrazado / archivo no-imagen debe rechazarse
        $this->actingAs($this->operador())
             ->post('/pagos', $this->payload($cliente, $membresia, [
                 'metodo_pago' => 'QR',
                 'imagen_qr'   => UploadedFile::fake()->create('comprobante.pdf', 40, 'application/pdf'),
             ]))
             ->assertSessionHasErrors('imagen_qr');

        $this->assertDatabaseCount('pagos', 0);
    }

    public function test_el_formulario_de_crear_no_ofrece_transferencia(): void
    {
        $this->actingAs($this->operador())
             ->get('/pagos/create')
             ->assertStatus(200)
             ->assertDontSee('value="transferencia"', false);
    }

    // ── 4. Comprobante visible por ruta protegida (disco privado) ────────────

    public function test_el_personal_puede_ver_el_comprobante_del_pago(): void
    {
        Storage::fake('privado');

        $cliente   = $this->crearCliente();
        $membresia = Membresia::create(['tipo_membresia' => 'Mensual', 'precio' => 150]);

        $this->actingAs($this->operador())
             ->post('/pagos', $this->payload($cliente, $membresia, [
                 'metodo_pago' => 'QR',
                 'imagen_qr'   => UploadedFile::fake()->image('comprobante.jpg'),
             ]))
             ->assertSessionHasNoErrors();

        $pago = Pago::firstOrFail();
        $this->assertNotNull($pago->imagen_qr);
        Storage::disk('privado')->assertExists($pago->imagen_qr);

        // Los tres roles de personal pueden verlo por la ruta protegida
        foreach (['Administrador', 'Recepcionista', 'Entrenador'] as $rol) {
            $this->actingAs($this->operador($rol))
                 ->get("/pagos/{$pago->id_pago}/comprobante")
                 ->assertStatus(200);
        }
    }

    public function test_un_cliente_no_accede_al_comprobante_por_la_ruta_del_personal(): void
    {
        Storage::fake('privado');

        $cliente   = $this->crearCliente();
        $membresia = Membresia::create(['tipo_membresia' => 'Mensual', 'precio' => 150]);

        $this->actingAs($this->operador())
             ->post('/pagos', $this->payload($cliente, $membresia, [
                 'metodo_pago' => 'QR',
                 'imagen_qr'   => UploadedFile::fake()->image('comprobante.jpg'),
             ]));

        $pago = Pago::firstOrFail();

        $this->actingAs($cliente->user)
             ->get("/pagos/{$pago->id_pago}/comprobante")
             ->assertStatus(403);
    }

    public function test_el_cliente_ve_su_propio_comprobante_pero_no_el_de_otro(): void
    {
        Storage::fake('privado');

        $clienteA  = $this->crearCliente();
        $clienteB  = $this->crearCliente();
        $membresia = Membresia::create(['tipo_membresia' => 'Mensual', 'precio' => 150]);

        $this->actingAs($this->operador())
             ->post('/pagos', $this->payload($clienteA, $membresia, [
                 'metodo_pago' => 'QR',
                 'imagen_qr'   => UploadedFile::fake()->image('comprobante.jpg'),
             ]));

        $pago = Pago::firstOrFail();

        // Dueño: sí lo ve
        $this->actingAs($clienteA->user)
             ->get("/mis-pagos/{$pago->id_pago}/comprobante")
             ->assertStatus(200);

        // Otro cliente: 403
        $this->actingAs($clienteB->user)
             ->get("/mis-pagos/{$pago->id_pago}/comprobante")
             ->assertStatus(403);
    }

    // ── 1. Nombre completo en el listado y el detalle ─────────────────────────

    public function test_el_listado_y_el_detalle_muestran_el_nombre_completo(): void
    {
        $cliente   = $this->crearCliente();
        $membresia = Membresia::create(['tipo_membresia' => 'Mensual', 'precio' => 150]);

        $this->actingAs($this->operador());
        $this->post('/pagos', $this->payload($cliente, $membresia));

        $pago           = Pago::firstOrFail();
        $nombreCompleto = $cliente->persona->nombre_completo . ' ' .
                          $cliente->persona->apellido_paterno . ' ' .
                          $cliente->persona->apellido_materno;

        $this->get('/pagos')->assertSee($nombreCompleto);
        $this->get("/pagos/{$pago->id_pago}")->assertSee($nombreCompleto);
    }
}
