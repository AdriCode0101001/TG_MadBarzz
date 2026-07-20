<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Rol;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Portal del cliente: Mi Membresía.
 *
 * (El feature viejo de "Mis Rutinas" —subir un PDF/imagen suelta— se quitó:
 * lo reemplaza la rutina estructurada del entrenador, ver RutinaTest. La
 * vista del CLIENTE para consultar esa rutina es la Etapa 4, todavía no
 * construida.)
 */
class PortalClienteTest extends TestCase
{
    use RefreshDatabase;

    private static int $seq = 0;

    private function crearClienteConUsuario(): array
    {
        self::$seq++;
        $s = self::$seq;

        $sede = Sede::create(['nombre' => "Sede {$s}", 'direccion' => 'Calle 1', 'activo' => true]);
        $rol  = Rol::firstOrCreate(['nombre' => 'Cliente']);
        $user = User::factory()->create(['rol_id' => $rol->id, 'id_sede' => $sede->id_sede]);

        $personaId = DB::table('personas')->insertGetId([
            'nombre_completo'  => "Cliente {$s}",
            'apellido_paterno' => 'Portal',
            'apellido_materno' => 'Test',
            'ci'               => "CIPOR{$s}",
            'telefono'         => "+5917200000{$s}",
            'genero'           => 'M',
            'fecha_nacimiento' => '1990-01-01',
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        $cliente = Cliente::create([
            'id_persona'     => $personaId,
            'id_usuario'     => $user->id,
            'estado'         => 'activo',
            'dias_asistidos' => 0,
            'id_sede'        => $sede->id_sede,
        ]);

        return [$user, $cliente];
    }

    private function crearMembresia(Cliente $cliente, string $fechaFin): void
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
            'fecha_fin'    => $fechaFin,
            'estado'       => 'activa', // pagada: da acceso
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);
    }

    // ── Acceso (antes daban 403 por apuntar a rutas de admin) ────────────────

    public function test_cliente_puede_ver_mi_membresia_con_membresia_activa(): void
    {
        [$user, $cliente] = $this->crearClienteConUsuario();
        $this->crearMembresia($cliente, now()->addDays(20)->toDateString());

        $this->actingAs($user)
             ->get('/mi-membresia')
             ->assertStatus(200)
             ->assertSee('Vigente')
             ->assertSee('Mensual');
    }

    public function test_cliente_sin_membresia_ve_aviso_en_mi_membresia(): void
    {
        [$user] = $this->crearClienteConUsuario();

        $this->actingAs($user)
             ->get('/mi-membresia')
             ->assertStatus(200)
             ->assertSee('No tienes una membresía activa');
    }

    // ── Las rutas del portal son solo para el rol Cliente ────────────────────

    public function test_un_no_cliente_no_accede_al_portal(): void
    {
        $rol  = Rol::firstOrCreate(['nombre' => 'Recepcionista']);
        $user = User::factory()->create(['rol_id' => $rol->id]);

        $this->actingAs($user)->get('/mi-membresia')->assertStatus(403);
    }
}
