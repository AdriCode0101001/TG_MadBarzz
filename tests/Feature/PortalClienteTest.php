<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Rol;
use App\Models\Rutina;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Portal del cliente: Mi Membresía y Mis Rutinas.
 * Las rutinas solo se muestran a clientes con membresía vigente.
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

    private function crearRutina(string $titulo = 'Full Body Calistenia'): Rutina
    {
        self::$seq++;
        $s = self::$seq;

        $personaId = DB::table('personas')->insertGetId([
            'nombre_completo'  => "Entrenador {$s}",
            'apellido_paterno' => 'Portal',
            'apellido_materno' => 'Test',
            'ci'               => "CIENT{$s}",
            'telefono'         => "+5917300000{$s}",
            'genero'           => 'M',
            'fecha_nacimiento' => '1985-01-01',
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        $rolEnt  = Rol::firstOrCreate(['nombre' => 'Entrenador']);
        $userEnt = User::factory()->create(['rol_id' => $rolEnt->id]);

        $entrenadorId = DB::table('entrenadores')->insertGetId([
            'id_persona'     => $personaId,
            'id_usuario'     => $userEnt->id,
            'especialidad'   => json_encode(['Calistenia']),
            'experiencia'    => '5 años',
            'disponibilidad' => json_encode(['lunes']),
            'estado'         => 'activo',
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        return Rutina::create([
            'id_entrenador' => $entrenadorId,
            'titulo'        => $titulo,
            'descripcion'   => 'Rutina de prueba',
            'fecha_subida'  => now(),
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

    // ── Rutinas condicionadas a membresía vigente ────────────────────────────

    public function test_cliente_con_membresia_ve_las_rutinas(): void
    {
        [$user, $cliente] = $this->crearClienteConUsuario();
        $this->crearMembresia($cliente, now()->addDays(20)->toDateString());
        $this->crearRutina('Full Body Calistenia');

        $this->actingAs($user)
             ->get('/mis-rutinas')
             ->assertStatus(200)
             ->assertSee('Full Body Calistenia');
    }

    public function test_cliente_sin_membresia_no_ve_rutinas_y_se_le_invita_a_pagar(): void
    {
        [$user] = $this->crearClienteConUsuario();
        $this->crearRutina('Rutina Secreta');

        $this->actingAs($user)
             ->get('/mis-rutinas')
             ->assertStatus(200)
             ->assertDontSee('Rutina Secreta')
             ->assertSee('Las rutinas están disponibles para socios con membresía activa')
             ->assertSee('Renueva tu membresía');
    }

    public function test_cliente_con_membresia_vencida_no_ve_rutinas(): void
    {
        [$user, $cliente] = $this->crearClienteConUsuario();
        $this->crearMembresia($cliente, now()->subDays(3)->toDateString()); // vencida
        $this->crearRutina('Rutina Secreta');

        $this->actingAs($user)
             ->get('/mis-rutinas')
             ->assertStatus(200)
             ->assertDontSee('Rutina Secreta')
             ->assertSee('Las rutinas están disponibles para socios con membresía activa');
    }

    public function test_membresia_que_vence_hoy_cuenta_como_vigente_para_rutinas(): void
    {
        // Mismo criterio que el control de asistencia: vence hoy = al día
        [$user, $cliente] = $this->crearClienteConUsuario();
        $this->crearMembresia($cliente, now()->toDateString());
        $this->crearRutina('Rutina De Hoy');

        $this->actingAs($user)
             ->get('/mis-rutinas')
             ->assertStatus(200)
             ->assertSee('Rutina De Hoy');
    }

    public function test_dashboard_del_cliente_sin_membresia_tampoco_muestra_rutinas(): void
    {
        [$user] = $this->crearClienteConUsuario();
        $this->crearRutina('Rutina Secreta');

        $this->actingAs($user)
             ->get('/dashboard/cliente')
             ->assertStatus(200)
             ->assertDontSee('Rutina Secreta')
             ->assertSee('Las rutinas están disponibles para socios con membresía activa');
    }

    public function test_cliente_no_accede_a_la_gestion_de_rutinas_por_url_directa(): void
    {
        [$user] = $this->crearClienteConUsuario();
        $rutina = $this->crearRutina('Rutina Secreta');

        // El módulo de gestión de rutinas es solo del personal (403 por rol)
        $this->actingAs($user)->get('/rutinas')->assertStatus(403);
        $this->actingAs($user)->get('/rutinas/ver/' . $rutina->id_rutina)->assertStatus(403);
    }

    public function test_el_personal_siempre_ve_las_rutinas(): void
    {
        $this->crearRutina('Rutina Staff');

        foreach (['Administrador', 'Entrenador'] as $rolNombre) {
            $rol  = Rol::firstOrCreate(['nombre' => $rolNombre]);
            $user = User::factory()->create(['rol_id' => $rol->id]);

            $this->actingAs($user)
                 ->get('/rutinas')
                 ->assertStatus(200);
        }
    }

    // ── Las rutas del portal son solo para el rol Cliente ────────────────────

    public function test_un_no_cliente_no_accede_al_portal(): void
    {
        $rol  = Rol::firstOrCreate(['nombre' => 'Recepcionista']);
        $user = User::factory()->create(['rol_id' => $rol->id]);

        $this->actingAs($user)->get('/mi-membresia')->assertStatus(403);
        $this->actingAs($user)->get('/mis-rutinas')->assertStatus(403);
    }
}
