<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Descuento;
use App\Models\Membresia;
use App\Models\MembresiaCliente;
use App\Models\Pago;
use App\Models\Rol;
use App\Models\Sede;
use App\Models\User;
use App\Services\AsistenciaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * "Sin pago no hay membresía activa": la asignación de membresía nace
 * pendiente_pago y solo pasa a activa cuando tiene su pago vinculado
 * (registrado en el mismo paso desde "Registrar Pago"). Si el pago se
 * elimina, la membresía vuelve a pendiente_pago y deja de dar acceso.
 */
class PagoMembresiaActivacionTest extends TestCase
{
    use RefreshDatabase;

    private static int $seq = 0;

    private function operador(string $rol = 'Recepcionista'): User
    {
        return User::factory()->create(['rol_id' => Rol::firstOrCreate(['nombre' => $rol])->id]);
    }

    private function crearCliente(?Sede $sede = null): Cliente
    {
        self::$seq++;
        $s = self::$seq;

        $sede ??= Sede::create(['nombre' => "Sede Auto {$s}", 'direccion' => 'Calle 1', 'activo' => true]);

        $user = User::factory()->create([
            'rol_id'  => Rol::firstOrCreate(['nombre' => 'Cliente'])->id,
            'id_sede' => $sede->id_sede,
        ]);

        $personaId = DB::table('personas')->insertGetId([
            'nombre_completo'  => "Activacion {$s}",
            'apellido_paterno' => 'Test',
            'apellido_materno' => 'Cliente',
            'ci'               => "CIACT{$s}",
            'telefono'         => "+5917600000{$s}",
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
            'id_sede'        => $sede->id_sede,
        ]);
    }

    private function sinDescuento(): Descuento
    {
        return Descuento::where('nombre', 'Sin descuento')->firstOrFail();
    }

    private function registrarPagoUnPaso(Cliente $cliente, Membresia $membresia, array $extra = [])
    {
        return $this->actingAs($this->operador())->post('/pagos', array_merge([
            'id_cliente'   => $cliente->id_cliente,
            'id_membresia' => $membresia->id_membresia,
            'id_descuento' => $this->sinDescuento()->id,
            'metodo_pago'  => 'efectivo',
        ], $extra));
    }

    // ── 1. Registro en un paso → membresía activa + pago vinculado ──────────

    public function test_registro_en_un_paso_crea_membresia_activa_y_pago_vinculado(): void
    {
        $cliente   = $this->crearCliente();
        $membresia = Membresia::create(['tipo_membresia' => 'Mensual', 'precio' => 150]);

        $this->registrarPagoUnPaso($cliente, $membresia)
             ->assertRedirect(route('pagos.index'));

        $this->assertDatabaseCount('membresia_cliente', 1);
        $this->assertDatabaseCount('pagos', 1);

        $mc   = MembresiaCliente::firstOrFail();
        $pago = Pago::firstOrFail();

        $this->assertSame('activa', $mc->estado);
        $this->assertSame($mc->id, $pago->id_membresia_cliente);
        $this->assertEquals(150.00, (float) $pago->monto);
        $this->assertEquals(150.00, (float) $mc->precio_final);
        $this->assertSame($cliente->id_cliente, $pago->id_cliente);
    }

    public function test_el_precio_final_del_pago_refleja_el_descuento_elegido(): void
    {
        $cliente   = $this->crearCliente();
        $membresia = Membresia::create(['tipo_membresia' => 'Mensual', 'precio' => 150]);
        $descuento = Descuento::create(['nombre' => 'UAGRM', 'porcentaje' => 20, 'activo' => true]);

        $this->registrarPagoUnPaso($cliente, $membresia, ['id_descuento' => $descuento->id]);

        $pago = Pago::firstOrFail();
        $mc   = MembresiaCliente::firstOrFail();

        $this->assertEquals(120.00, (float) $pago->monto);
        $this->assertEquals(120.00, (float) $mc->precio_final);
        $this->assertSame('activa', $mc->estado);
    }

    // ── 2. Membresía pendiente (sin pago) NO da acceso ──────────────────────

    public function test_membresia_pendiente_de_pago_no_permite_marcar_asistencia(): void
    {
        $sede    = Sede::create(['nombre' => 'Sede Activacion', 'direccion' => 'Calle 1', 'activo' => true]);
        $cliente = $this->crearCliente($sede);

        // Asignación SUELTA (sin pago): nace pendiente_pago
        $membresia = Membresia::create(['tipo_membresia' => 'Mensual', 'precio' => 150]);
        MembresiaCliente::create([
            'id_cliente'   => $cliente->id_cliente,
            'id_membresia' => $membresia->id_membresia,
            'fecha_inicio' => now()->toDateString(),
            'fecha_fin'    => now()->addDays(30)->toDateString(),
            'precio_final' => 150,
            'descuento'    => 0,
            'estado'       => MembresiaCliente::ESTADO_PENDIENTE,
        ]);

        $servicio  = app(AsistenciaService::class);
        $resultado = $servicio->registrarEntrada($cliente, $sede->id_sede);

        $this->assertFalse($resultado['permitido']);
        $this->assertSame(AsistenciaService::MEMBRESIA_PENDIENTE_PAGO, $resultado['motivo']);
        $this->assertDatabaseCount('asistencias', 0);
    }

    public function test_membresia_pendiente_de_pago_no_cuenta_como_activa_en_mi_membresia(): void
    {
        $cliente = $this->crearCliente();

        $membresia = Membresia::create(['tipo_membresia' => 'Mensual', 'precio' => 150]);
        MembresiaCliente::create([
            'id_cliente'   => $cliente->id_cliente,
            'id_membresia' => $membresia->id_membresia,
            'fecha_inicio' => now()->toDateString(),
            'fecha_fin'    => now()->addDays(30)->toDateString(),
            'precio_final' => 150,
            'descuento'    => 0,
            'estado'       => MembresiaCliente::ESTADO_PENDIENTE,
        ]);

        $this->actingAs($cliente->user)
             ->get('/mi-membresia')
             ->assertStatus(200)
             ->assertSee('No tienes una membresía activa')
             ->assertSee('Pendiente de pago'); // en el historial
    }

    public function test_el_escaner_qr_tambien_bloquea_la_membresia_pendiente_de_pago(): void
    {
        $sede    = Sede::create(['nombre' => 'Sede QR Pendiente', 'direccion' => 'Calle 1', 'activo' => true]);
        $cliente = $this->crearCliente($sede);
        $membresia = Membresia::create(['tipo_membresia' => 'Mensual', 'precio' => 150]);

        MembresiaCliente::create([
            'id_cliente'   => $cliente->id_cliente,
            'id_membresia' => $membresia->id_membresia,
            'fecha_inicio' => now()->toDateString(),
            'fecha_fin'    => now()->addDays(30)->toDateString(),
            'precio_final' => 150,
            'estado'       => MembresiaCliente::ESTADO_PENDIENTE,
        ]);

        $operador = $this->operador();
        $operador->update(['id_sede' => $sede->id_sede]);

        $this->actingAs($operador)
             ->postJson('/asistencias-qr', ['qr_token' => $cliente->qr_token])
             ->assertStatus(200)
             ->assertJsonFragment([
                 'estado'  => 'pendiente_pago',
                 'mensaje' => 'Tu membresía está pendiente de pago. Pasa por recepción para completarlo y activarla.',
             ]);

        $this->assertDatabaseCount('asistencias', 0);
    }

    // ── 3. Eliminar el pago → la membresía vuelve a pendiente ───────────────

    public function test_eliminar_el_pago_devuelve_la_membresia_a_pendiente_de_pago(): void
    {
        $sede    = Sede::create(['nombre' => 'Sede Elimina', 'direccion' => 'Calle 1', 'activo' => true]);
        $cliente = $this->crearCliente($sede);
        $membresia = Membresia::create(['tipo_membresia' => 'Mensual', 'precio' => 150]);

        $admin = $this->operador('Administrador');

        $this->registrarPagoUnPaso($cliente, $membresia);

        $mc   = MembresiaCliente::firstOrFail();
        $pago = Pago::firstOrFail();
        $this->assertSame('activa', $mc->fresh()->estado);

        // Antes de eliminar: el cliente marca asistencia con normalidad
        $servicio = app(AsistenciaService::class);
        $this->assertTrue($servicio->registrarEntrada($cliente, $sede->id_sede)['permitido']);

        // Elimina el pago (Admin)
        $this->actingAs($admin)
             ->delete("/pagos/{$pago->id_pago}")
             ->assertRedirect(route('pagos.index'));

        $this->assertSame('pendiente_pago', $mc->fresh()->estado);
        $this->assertSoftDeleted('pagos', ['id_pago' => $pago->id_pago]);

        // Tras eliminar: ya no marca asistencia (mismo día, cliente distinto sin duplicado interfiriendo)
        $cliente2 = $this->crearCliente($sede);
        MembresiaCliente::create([
            'id_cliente'   => $cliente2->id_cliente,
            'id_membresia' => $membresia->id_membresia,
            'fecha_inicio' => now()->toDateString(),
            'fecha_fin'    => now()->addDays(30)->toDateString(),
            'precio_final' => 150,
            'estado'       => 'activa',
        ]);

        $resultadoOriginal = $servicio->registrarEntrada($cliente, $sede->id_sede);
        $this->assertFalse($resultadoOriginal['permitido']);
        $this->assertSame(AsistenciaService::MEMBRESIA_PENDIENTE_PAGO, $resultadoOriginal['motivo']);
    }

    // ── 4. Membresías ya existentes (activas antes de esta funcionalidad) siguen activas ──

    public function test_membresia_ya_activa_preexistente_sigue_dando_acceso_normalmente(): void
    {
        // Simula una membresía "vieja" tal como quedó tras el backfill de la
        // migración (estado = activa), sin pasar por el flujo de un paso.
        $sede    = Sede::create(['nombre' => 'Sede Vieja', 'direccion' => 'Calle 1', 'activo' => true]);
        $cliente = $this->crearCliente($sede);
        $membresia = Membresia::create(['tipo_membresia' => 'Mensual', 'precio' => 150]);

        MembresiaCliente::create([
            'id_cliente'   => $cliente->id_cliente,
            'id_membresia' => $membresia->id_membresia,
            'fecha_inicio' => now()->subDays(5)->toDateString(),
            'fecha_fin'    => now()->addDays(25)->toDateString(),
            'precio_final' => 150,
            'descuento'    => 0,
            'estado'       => MembresiaCliente::ESTADO_ACTIVA,
        ]);

        $this->assertTrue($cliente->fresh()->tieneMembresiaVigente());

        $servicio  = app(AsistenciaService::class);
        $resultado = $servicio->registrarEntrada($cliente, $sede->id_sede);
        $this->assertTrue($resultado['permitido']);
        $this->assertDatabaseCount('asistencias', 1);
    }

    public function test_migracion_deja_activas_las_membresias_que_ya_existian_antes_del_cambio(): void
    {
        // Prueba directamente la migración de backfill: simula datos "viejos"
        // insertados antes de que existiera la columna estado, y confirma
        // que al ejecutarse (up()) las deja en 'activa'.
        $cliente   = $this->crearCliente();
        $membresia = Membresia::create(['tipo_membresia' => 'Mensual', 'precio' => 150]);

        DB::table('membresia_cliente')->where('id_cliente', $cliente->id_cliente)->delete();

        DB::statement('ALTER TABLE membresia_cliente DROP COLUMN estado');

        $idPreexistente = DB::table('membresia_cliente')->insertGetId([
            'id_cliente'   => $cliente->id_cliente,
            'id_membresia' => $membresia->id_membresia,
            'fecha_inicio' => now()->subDays(5)->toDateString(),
            'fecha_fin'    => now()->addDays(25)->toDateString(),
            'precio_final' => 150,
            'descuento'    => 0,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        $migracion = require database_path('migrations/2026_07_07_100000_add_estado_to_membresia_cliente_table.php');
        $migracion->up();

        $this->assertDatabaseHas('membresia_cliente', [
            'id'     => $idPreexistente,
            'estado' => 'activa',
        ]);
    }

    // ── La pantalla de "asignar membresía" suelta ya NO existe ───────────────
    // "Registrar Pago" es el ÚNICO camino para darle una membresía a un
    // cliente. Ni el formulario ni la acción de guardar siguen accesibles.

    public function test_el_formulario_de_asignar_membresia_suelta_ya_no_existe(): void
    {
        // Sin la ruta 'create', "/membresia_cliente/create" coincide con el
        // patrón {membresia_cliente} de update/destroy (que sí siguen
        // registradas) pero no admite GET, así que Laravel responde 405
        // Método No Permitido: nunca llega a ningún formulario ni controlador.
        $this->actingAs($this->operador())
             ->get('/membresia_cliente/create')
             ->assertStatus(405);
    }

    public function test_la_accion_de_crear_membresia_suelta_ya_no_existe(): void
    {
        $cliente   = $this->crearCliente();
        $membresia = Membresia::create(['tipo_membresia' => 'Mensual', 'precio' => 150]);

        // La ruta /membresia_cliente sigue existiendo para GET (index), así
        // que un POST ahí es 405 Método No Permitido (no hay acción "store"
        // registrada); no llega a ningún método del controlador.
        $this->actingAs($this->operador())
             ->post('/membresia_cliente', [
                 'id_cliente'   => $cliente->id_cliente,
                 'id_membresia' => $membresia->id_membresia,
                 'id_descuento' => $this->sinDescuento()->id,
             ])
             ->assertStatus(405);

        $this->assertDatabaseCount('membresia_cliente', 0);
        $this->assertDatabaseCount('pagos', 0);
    }

    public function test_el_listado_de_membresias_de_clientes_se_mantiene_y_ya_no_ofrece_asignar(): void
    {
        $this->actingAs($this->operador())
             ->get('/membresia_cliente')
             ->assertStatus(200)
             ->assertDontSee('Asignar membresía')
             ->assertSee('Registrar pago');
    }

    public function test_editar_y_eliminar_una_asignacion_existente_siguen_funcionando(): void
    {
        // No se borran datos: lo que ya existe se puede seguir viendo,
        // editando y eliminando desde el listado. Solo se quitó CREAR.
        $cliente   = $this->crearCliente();
        $membresia = Membresia::create(['tipo_membresia' => 'Mensual', 'precio' => 150]);

        $this->registrarPagoUnPaso($cliente, $membresia);
        $mc = MembresiaCliente::firstOrFail();

        $this->actingAs($this->operador())
             ->get("/membresia_cliente/{$mc->id}/edit")
             ->assertStatus(200);

        $this->actingAs($this->operador())
             ->delete("/membresia_cliente/{$mc->id}")
             ->assertRedirect(route('membresia_cliente.index'));

        $this->assertSoftDeleted('membresia_cliente', ['id' => $mc->id]);
    }

    // ── Cobertura trasladada desde el flujo suelto: fechas y precio ─────────
    // (antes se probaban vía /membresia_cliente; ahora Registrar Pago es el
    // único camino que ejercita MembresiaService, así que se prueba aquí)

    public function test_mensual_inicia_hoy_y_vence_un_mes_mas_un_dia_despues(): void
    {
        $this->travelTo(Carbon::parse('2030-07-05 10:00:00', config('app.timezone')));

        $cliente   = $this->crearCliente();
        $membresia = Membresia::create(['tipo_membresia' => 'Mensual', 'precio' => 150]);

        $this->registrarPagoUnPaso($cliente, $membresia);

        $this->assertDatabaseHas('membresia_cliente', [
            'id_cliente'   => $cliente->id_cliente,
            'fecha_inicio' => '2030-07-05',
            'fecha_fin'    => '2030-08-06', // +1 mes +1 día de regalo
        ]);
    }

    public function test_semestral_vence_seis_meses_mas_un_dia_despues(): void
    {
        $this->travelTo(Carbon::parse('2030-07-05 10:00:00', config('app.timezone')));

        $cliente   = $this->crearCliente();
        $membresia = Membresia::create(['tipo_membresia' => 'Semestral', 'precio' => 770]);

        $this->registrarPagoUnPaso($cliente, $membresia);

        $this->assertDatabaseHas('membresia_cliente', [
            'id_cliente' => $cliente->id_cliente,
            'fecha_fin'  => '2031-01-06', // +6 meses +1 día
        ]);
    }

    public function test_anual_vence_un_ano_mas_un_dia_despues(): void
    {
        $this->travelTo(Carbon::parse('2030-07-05 10:00:00', config('app.timezone')));

        $cliente   = $this->crearCliente();
        $membresia = Membresia::create(['tipo_membresia' => 'Anual', 'precio' => 1300]);

        $this->registrarPagoUnPaso($cliente, $membresia);

        $this->assertDatabaseHas('membresia_cliente', [
            'id_cliente' => $cliente->id_cliente,
            'fecha_fin'  => '2031-07-06',
        ]);
    }

    public function test_fin_de_mes_no_desborda_al_mes_siguiente(): void
    {
        // 31 de enero + 1 mes = 28 de febrero (sin desbordar) + 1 día = 1 de marzo
        $this->travelTo(Carbon::parse('2030-01-31 10:00:00', config('app.timezone')));

        $cliente   = $this->crearCliente();
        $membresia = Membresia::create(['tipo_membresia' => 'Mensual', 'precio' => 150]);

        $this->registrarPagoUnPaso($cliente, $membresia);

        $this->assertDatabaseHas('membresia_cliente', [
            'id_cliente'   => $cliente->id_cliente,
            'fecha_inicio' => '2030-01-31',
            'fecha_fin'    => '2030-03-01',
        ]);
    }

    public function test_fechas_y_precio_manipulados_en_el_envio_se_ignoran(): void
    {
        $this->travelTo(Carbon::parse('2030-07-05 10:00:00', config('app.timezone')));

        $cliente   = $this->crearCliente();
        $membresia = Membresia::create(['tipo_membresia' => 'Mensual', 'precio' => 150]);

        // Un envío malicioso intenta forzar fechas, descuento y precio propios
        $this->registrarPagoUnPaso($cliente, $membresia, [
            'fecha_inicio' => '2029-01-01',
            'fecha_fin'    => '2099-12-31',
            'precio_final' => 1,
            'descuento'    => 99,
            'monto'        => 1,
        ]);

        $mc = MembresiaCliente::where('id_cliente', $cliente->id_cliente)->firstOrFail();

        $this->assertSame('2030-07-05', Carbon::parse($mc->fecha_inicio)->toDateString());
        $this->assertSame('2030-08-06', Carbon::parse($mc->fecha_fin)->toDateString());
        $this->assertEquals(150.00, (float) $mc->precio_final);
        $this->assertEquals(0, (float) $mc->descuento);
    }

    public function test_descuento_inactivo_es_rechazado(): void
    {
        $cliente   = $this->crearCliente();
        $membresia = Membresia::create(['tipo_membresia' => 'Mensual', 'precio' => 150]);
        $inactivo  = Descuento::create(['nombre' => 'Viejo', 'porcentaje' => 50, 'activo' => false]);

        $this->registrarPagoUnPaso($cliente, $membresia, ['id_descuento' => $inactivo->id])
             ->assertSessionHasErrors('id_descuento');

        $this->assertDatabaseCount('membresia_cliente', 0);
        $this->assertDatabaseCount('pagos', 0);
    }
}
