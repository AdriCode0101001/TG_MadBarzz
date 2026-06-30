<?php

namespace Tests\Feature;

use App\Jobs\EnviarNotificacionJob;
use App\Models\MembresiaCliente;
use App\Models\Notificacion;
use App\Models\Rol;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NotificacionesVencimientosTest extends TestCase
{
    use RefreshDatabase;

    private function crearClienteConMembresia(int $diasParaVencer): MembresiaCliente
    {
        $sede = Sede::create(['nombre' => 'Sede Test', 'direccion' => 'Calle 1']);
        $rol  = Rol::create(['nombre' => 'Cliente']);
        $user = User::factory()->create(['rol_id' => $rol->id, 'id_sede' => $sede->id_sede]);

        $personaId = DB::table('personas')->insertGetId([
            'nombre_completo'  => 'Ana',
            'apellido_paterno' => 'López',
            'apellido_materno' => 'García',
            'ci'               => '12345678',
            'telefono'         => '71234567',
            'genero'           => 'F',
            'fecha_nacimiento' => '1990-01-01',
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        $clienteId = DB::table('clientes')->insertGetId([
            'id_persona'     => $personaId,
            'id_usuario'     => $user->id,
            'estado'         => 'activo',
            'dias_asistidos' => 0,
            'id_sede'        => $sede->id_sede,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        $membresiaId = DB::table('membresias')->insertGetId([
            'tipo_membresia' => 'Mensual',
            'precio'         => 100,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        return MembresiaCliente::create([
            'id_cliente'   => $clienteId,
            'id_membresia' => $membresiaId,
            'fecha_inicio' => now()->subMonth(),
            'fecha_fin'    => now()->addDays($diasParaVencer)->toDateString(),
            'precio_final' => 100,
            'id_sede'      => $sede->id_sede,
        ]);
    }

    public function test_encola_notificacion_para_membresia_proxima_a_vencer(): void
    {
        Bus::fake();

        $this->crearClienteConMembresia(diasParaVencer: 3);

        $this->artisan('notificaciones:vencimientos', ['--dias' => 3])
             ->assertSuccessful();

        $this->assertDatabaseCount('notificaciones', 1);
        $this->assertDatabaseHas('notificaciones', [
            'tipo'   => 'recordatorio_vencimiento',
            'estado' => 'pendiente',
        ]);
        Bus::assertDispatched(EnviarNotificacionJob::class);
    }

    public function test_no_duplica_notificacion_si_cliente_ya_fue_notificado_hoy(): void
    {
        Bus::fake();

        $mc = $this->crearClienteConMembresia(diasParaVencer: 3);

        Notificacion::create([
            'id_cliente' => $mc->id_cliente,
            'tipo'       => 'recordatorio_vencimiento',
            'mensaje'    => 'aviso previo',
            'estado'     => 'enviado',
        ]);

        $this->artisan('notificaciones:vencimientos', ['--dias' => 3])
             ->assertSuccessful();

        $this->assertDatabaseCount('notificaciones', 1);
        Bus::assertNotDispatched(EnviarNotificacionJob::class);
    }

    public function test_ignora_membresias_fuera_del_rango_de_dias(): void
    {
        Bus::fake();

        $this->crearClienteConMembresia(diasParaVencer: 10);

        $this->artisan('notificaciones:vencimientos', ['--dias' => 3])
             ->assertSuccessful();

        $this->assertDatabaseCount('notificaciones', 0);
        Bus::assertNotDispatched(EnviarNotificacionJob::class);
    }

    public function test_omite_clientes_inactivos(): void
    {
        Bus::fake();

        $mc = $this->crearClienteConMembresia(diasParaVencer: 3);
        DB::table('clientes')->where('id_cliente', $mc->id_cliente)->update(['estado' => 'inactivo']);

        $this->artisan('notificaciones:vencimientos', ['--dias' => 3])
             ->assertSuccessful();

        $this->assertDatabaseCount('notificaciones', 0);
        Bus::assertNotDispatched(EnviarNotificacionJob::class);
    }
}
