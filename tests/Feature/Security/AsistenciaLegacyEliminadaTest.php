<?php

namespace Tests\Feature\Security;

use App\Models\Cliente;
use App\Models\Rol;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Hallazgo 8.4 de la auditoría de seguridad (Media): AsistenciaController
 * tenía un flujo de registro de asistencia por huella digital en TEXTO
 * PLANO (Cliente::where('huella_digital', $request->huella_digital)), sin
 * límite de intentos ni validación de formato — resabio de ANTES del agente
 * biométrico C# actual. Estaba desconectado (enlace del menú comentado,
 * ningún cliente con huella_digital poblado, ningún test lo ejercitaba) y
 * se eliminó junto con sus vistas (index/create/show) y la entrada del
 * recurso en routes/web.php. "Mis Asistencias" (misAsistencias) y los dos
 * flujos reales (QR y agente biométrico) no dependían de nada de esto y
 * siguen intactos — se confirma acá.
 */
class AsistenciaLegacyEliminadaTest extends TestCase
{
    use RefreshDatabase;

    private function staff(string $rol = 'Recepcionista'): User
    {
        $sede = Sede::firstOrCreate(['nombre' => 'Sede Principal'], ['activo' => true]);

        return User::factory()->create([
            'rol_id'  => Rol::firstOrCreate(['nombre' => $rol])->id,
            'id_sede' => $sede->id_sede,
        ]);
    }

    private function clienteConMembresiaVigente(int $idSede): Cliente
    {
        $rol  = Rol::firstOrCreate(['nombre' => 'Cliente']);
        $user = User::factory()->create(['rol_id' => $rol->id, 'id_sede' => $idSede]);

        $personaId = DB::table('personas')->insertGetId([
            'nombre_completo'  => 'Cliente QR',
            'apellido_paterno' => 'Test',
            'apellido_materno' => 'Legacy',
            'ci'               => 'CILEG' . uniqid(),
            'telefono'         => '+59171112222',
            'genero'           => 'masculino',
            'fecha_nacimiento' => '1990-01-01',
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        $cliente = Cliente::create([
            'id_persona'     => $personaId,
            'id_usuario'     => $user->id,
            'estado'         => 'activo',
            'dias_asistidos' => 0,
            'id_sede'        => $idSede,
        ]);

        $idMembresia = DB::table('membresias')->insertGetId([
            'tipo_membresia' => 'Mensual', 'precio' => 100,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::table('membresia_cliente')->insert([
            'id_cliente'   => $cliente->id_cliente,
            'id_membresia' => $idMembresia,
            'fecha_inicio' => now()->subDays(5)->toDateString(),
            'fecha_fin'    => now()->addDays(25)->toDateString(),
            'estado'       => 'activa',
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        return $cliente->fresh();
    }

    // ── El flujo legacy ya no existe ─────────────────────────────────────────

    public function test_get_asistencias_ya_no_existe(): void
    {
        $this->actingAs($this->staff())->get('/asistencias')->assertStatus(404);
    }

    public function test_get_asistencias_create_ya_no_existe(): void
    {
        $this->actingAs($this->staff())->get('/asistencias/create')->assertStatus(404);
    }

    public function test_post_asistencias_ya_no_existe(): void
    {
        $this->actingAs($this->staff())
            ->post('/asistencias', ['huella_digital' => 'cualquier-cosa'])
            ->assertStatus(404);
    }

    public function test_la_ruta_del_recurso_asistencias_no_esta_registrada(): void
    {
        foreach (['asistencias.index', 'asistencias.create', 'asistencias.store', 'asistencias.show'] as $ruta) {
            $this->assertFalse(
                \Illuminate\Support\Facades\Route::has($ruta),
                "La ruta {$ruta} no debería existir: era parte del flujo legacy de huella en texto plano."
            );
        }
    }

    // ── Lo que SÍ debe seguir funcionando ────────────────────────────────────

    public function test_mis_asistencias_del_cliente_sigue_funcionando(): void
    {
        $sede    = Sede::firstOrCreate(['nombre' => 'Sede Principal'], ['activo' => true]);
        $cliente = $this->clienteConMembresiaVigente($sede->id_sede);

        $this->actingAs($cliente->user)
            ->get(route('asistencias.mis'))
            ->assertStatus(200);
    }

    public function test_el_flujo_de_asistencia_por_qr_sigue_funcionando(): void
    {
        $staff   = $this->staff('Recepcionista');
        $cliente = $this->clienteConMembresiaVigente($staff->id_sede);

        $response = $this->actingAs($staff)
            ->postJson(route('asistencias.qr.registrar'), ['qr_token' => $cliente->qr_token]);

        $response->assertStatus(200);
        $response->assertJson(['estado' => 'ok']);

        $this->assertDatabaseHas('asistencias', ['id_cliente' => $cliente->id_cliente]);
    }

    public function test_la_pantalla_de_registrar_asistencia_por_qr_sigue_accesible(): void
    {
        $this->actingAs($this->staff())
            ->get(route('asistencias.qr'))
            ->assertStatus(200);
    }
}
