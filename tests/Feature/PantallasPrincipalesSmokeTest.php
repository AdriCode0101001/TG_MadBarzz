<?php

namespace Tests\Feature;

use App\Models\Administrador;
use App\Models\Asistencia;
use App\Models\Aviso;
use App\Models\CalendarioMusculo;
use App\Models\Cliente;
use App\Models\Descuento;
use App\Models\Ejercicio;
use App\Models\Entrenador;
use App\Models\GrupoMuscular;
use App\Models\LoginLockout;
use App\Models\Membresia;
use App\Models\MembresiaCliente;
use App\Models\Notificacion;
use App\Models\Pago;
use App\Models\Persona;
use App\Models\Promocion;
use App\Models\Recepcionista;
use App\Models\Rol;
use App\Models\Rutina;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Revisión general: recorre las pantallas principales del sistema (todos
 * los index/create/edit/show del panel, reportes, dashboards, portal del
 * cliente, sitio público y auth) y confirma que cada una responde 200 para
 * el rol que corresponde, en vez de un 500 silencioso. Nace de encontrar,
 * en una misma sesión, cuatro vistas de edición rotas por sintaxis, la
 * pantalla clientes/show doblemente rota, y ocho rutas *.show registradas
 * sin controller method ni vista (ver routes/web.php). Ninguna de esas
 * fallas tenía cobertura — este test es esa cobertura.
 */
class PantallasPrincipalesSmokeTest extends TestCase
{
    use RefreshDatabase;

    private Sede $sede;
    private Cliente $cliente;
    private Entrenador $entrenador;
    private Recepcionista $recepcionista;
    private Administrador $administrador;
    private User $admin;
    private User $recepcionistaUser;
    private User $entrenadorUser;
    private Membresia $membresia;
    private GrupoMuscular $grupoMuscular;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['Administrador', 'Recepcionista', 'Entrenador', 'Cliente'] as $rol) {
            Rol::firstOrCreate(['nombre' => $rol]);
        }

        $this->sede = Sede::create(['nombre' => 'Sede Smoke', 'direccion' => 'Calle 1', 'activo' => true]);

        // ── Personal ──────────────────────────────────────────────────────
        $this->admin = User::factory()->create([
            'rol_id' => Rol::where('nombre', 'Administrador')->first()->id, 'id_sede' => $this->sede->id_sede,
        ]);
        $this->administrador = Administrador::create([
            'id_persona' => $this->persona('ADM'), 'id_usuario' => $this->admin->id,
            'area_responsable' => 'General', 'nivel_acceso' => 'gestion', 'estado' => 'activo',
        ]);

        $this->recepcionistaUser = User::factory()->create([
            'rol_id' => Rol::where('nombre', 'Recepcionista')->first()->id, 'id_sede' => $this->sede->id_sede,
        ]);
        $this->recepcionista = Recepcionista::create([
            'id_persona' => $this->persona('REC'), 'id_usuario' => $this->recepcionistaUser->id,
            'punto_atencion' => 'Mostrador', 'turno' => 'mañana', 'estado' => 'activo',
            'id_sede' => $this->sede->id_sede,
        ]);

        $this->entrenadorUser = User::factory()->create([
            'rol_id' => Rol::where('nombre', 'Entrenador')->first()->id, 'id_sede' => $this->sede->id_sede,
        ]);
        $this->entrenador = Entrenador::create([
            'id_persona' => $this->persona('ENT'), 'id_usuario' => $this->entrenadorUser->id,
            'especialidad' => ['Calistenia'], 'experiencia' => '5 años',
            'disponibilidad' => ['Lunes'], 'estado' => 'activo',
            'id_sede' => $this->sede->id_sede,
        ]);

        // ── Cliente con membresía, pago y asistencia ────────────────────────
        $clienteUser = User::factory()->create([
            'rol_id' => Rol::where('nombre', 'Cliente')->first()->id, 'id_sede' => $this->sede->id_sede,
        ]);
        $this->cliente = Cliente::create([
            'id_persona' => $this->persona('CLI'), 'id_usuario' => $clienteUser->id,
            'estado' => 'activo', 'dias_asistidos' => 2, 'id_sede' => $this->sede->id_sede,
        ]);

        $this->membresia = Membresia::create(['tipo_membresia' => 'Mensual', 'precio' => 150]);
        MembresiaCliente::create([
            'id_cliente' => $this->cliente->id_cliente, 'id_membresia' => $this->membresia->id_membresia,
            'fecha_inicio' => now()->subDays(5)->toDateString(), 'fecha_fin' => now()->addDays(25)->toDateString(),
            'estado' => 'activa', 'precio_final' => 150, 'id_sede' => $this->sede->id_sede,
        ]);
        Pago::create([
            'id_cliente' => $this->cliente->id_cliente, 'id_membresia' => $this->membresia->id_membresia,
            'monto' => 150, 'fecha_pago' => now()->toDateString(), 'metodo_pago' => 'efectivo', 'id_sede' => $this->sede->id_sede,
        ]);
        Asistencia::create(['id_cliente' => $this->cliente->id_cliente, 'fecha_hora_entrada' => now(), 'id_sede' => $this->sede->id_sede]);

        // ── Catálogos ─────────────────────────────────────────────────────
        $this->grupoMuscular = GrupoMuscular::create(['nombre' => 'Pecho', 'activo' => true, 'es_full_body' => false]);
        Ejercicio::create(['nombre' => 'Flexiones', 'id_grupo_muscular' => $this->grupoMuscular->id, 'origen' => 'manual']);
        Descuento::create(['nombre' => 'UAGRM', 'porcentaje' => 10, 'activo' => true]);
        Promocion::create([
            'id_administrador' => $this->administrador->id_administrador, 'titulo' => 'Promo Smoke',
            'descripcion' => 'desc', 'fecha_inicio' => now()->toDateString(), 'fecha_fin' => now()->addDays(10)->toDateString(),
            'estado' => 'activa',
        ]);
        Aviso::create(['titulo' => 'Aviso Smoke', 'texto' => 'texto', 'id_usuario' => $this->admin->id]);
        Notificacion::create([
            'id_cliente' => $this->cliente->id_cliente, 'tipo' => 'recordatorio_vencimiento',
            'mensaje' => 'msj', 'estado' => 'pendiente',
        ]);
        CalendarioMusculo::create([
            'fecha' => now()->toDateString(), 'id_grupo_muscular' => $this->grupoMuscular->id, 'id_sede' => $this->sede->id_sede,
        ]);
        Rutina::create([
            'fecha' => now()->toDateString(), 'id_grupo_muscular' => $this->grupoMuscular->id,
            'id_usuario' => $this->entrenadorUser->id, 'id_sede' => $this->sede->id_sede,
        ]);
        LoginLockout::create(['login' => 'alguien@test.com', 'ip' => '127.0.0.1', 'attempts' => 1, 'lockout_level' => 0]);
    }

    private static int $seq = 0;

    private function persona(string $prefijo): int
    {
        self::$seq++;
        $s = self::$seq;

        return Persona::create([
            'nombre_completo' => "{$prefijo} Smoke {$s}", 'apellido_paterno' => 'Apellido', 'apellido_materno' => 'Materno',
            'ci' => "CISMK{$prefijo}{$s}", 'telefono' => "+59171200{$s}", 'genero' => 'masculino',
            'fecha_nacimiento' => '1990-01-01',
        ])->id_persona;
    }

    private function assertPantalla(string $ruta, User $como, string $etiqueta): void
    {
        $this->actingAs($como)->get($ruta)->assertStatus(200, "{$etiqueta} ({$ruta}) no devolvió 200.");
    }

    // ── Sitio público / auth ────────────────────────────────────────────────

    public function test_sitio_publico_y_auth(): void
    {
        $this->get('/')->assertStatus(200);
        $this->get('/login')->assertStatus(200);
        $this->get('/register')->assertStatus(200);
        $this->get('/forgot-password')->assertStatus(200);
    }

    // ── Dashboards por rol ─────────────────────────────────────────────────

    public function test_dashboards(): void
    {
        $this->assertPantalla('/dashboard/index', $this->admin, 'Dashboard índice (Admin)');
        $this->assertPantalla('/dashboard/index', $this->recepcionistaUser, 'Dashboard índice (Recepcionista)');
        $this->assertPantalla('/dashboard/entrenador', $this->entrenadorUser, 'Dashboard Entrenador');
        $this->assertPantalla('/dashboard/cliente', $this->cliente->user, 'Dashboard Cliente');
        $this->assertPantalla('/profile', $this->admin, 'Mi perfil');
    }

    // ── Solo Administrador ─────────────────────────────────────────────────

    public function test_pantallas_solo_administrador(): void
    {
        $admin = $this->admin;

        $this->assertPantalla('/roles', $admin, 'Roles index');
        $this->assertPantalla('/roles/create', $admin, 'Roles create');
        $this->assertPantalla(route('roles.edit', Rol::first()->id), $admin, 'Roles edit');

        $this->assertPantalla('/users', $admin, 'Users index');
        $this->assertPantalla('/users/create', $admin, 'Users create');
        $this->assertPantalla(route('users.edit', $admin->id), $admin, 'Users edit');

        $this->assertPantalla('/personas', $admin, 'Personas index');
        $this->assertPantalla('/personas/create', $admin, 'Personas create');
        $this->assertPantalla(route('personas.edit', $this->cliente->persona->id_persona), $admin, 'Personas edit');

        // Gestión de personal: solo Administrador (antes también Recepcionista).
        $this->assertPantalla('/entrenadores', $admin, 'Entrenadores index');
        $this->assertPantalla('/entrenadores/create', $admin, 'Entrenadores create');
        $this->assertPantalla(route('entrenadores.edit', $this->entrenador->id_entrenador), $admin, 'Entrenadores edit');

        $this->assertPantalla('/recepcionistas', $admin, 'Recepcionistas index');
        $this->assertPantalla('/recepcionistas/create', $admin, 'Recepcionistas create');
        $this->assertPantalla(route('recepcionistas.edit', $this->recepcionista->id_recepcionista), $admin, 'Recepcionistas edit');

        $this->assertPantalla('/administradores', $admin, 'Administradores index');
        $this->assertPantalla('/administradores/create', $admin, 'Administradores create');
        $this->assertPantalla(route('administradores.edit', $this->administrador->id_administrador), $admin, 'Administradores edit');

        $this->assertPantalla('/promociones', $admin, 'Promociones index');
        $this->assertPantalla('/promociones/create', $admin, 'Promociones create');
        $this->assertPantalla(route('promociones.edit', Promocion::first()->id_promocion), $admin, 'Promociones edit');

        $this->assertPantalla('/membresias/create', $admin, 'Membresias create');
        $this->assertPantalla(route('membresias.edit', $this->membresia->id_membresia), $admin, 'Membresias edit');

        $this->assertPantalla('/descuentos/create', $admin, 'Descuentos create');
        $this->assertPantalla(route('descuentos.edit', Descuento::first()->id), $admin, 'Descuentos edit');

        $this->assertPantalla('/grupos_musculares/create', $admin, 'Grupos musculares create');
        $this->assertPantalla(route('grupos_musculares.edit', $this->grupoMuscular->id), $admin, 'Grupos musculares edit');

        $this->assertPantalla('/calendario-musculos', $admin, 'Calendario de musculos');
        $this->assertPantalla('/ejercicios', $admin, 'Ejercicios index');

        $this->assertPantalla('/configuracion/qr-pago', $admin, 'QR de pago (config)');
        $this->assertPantalla('/configuracion/imagenes-home', $admin, 'Imagenes de la home');

        $this->assertPantalla('/bitacora', $admin, 'Bitacora');
        $this->assertPantalla('/login_lockouts', $admin, 'Bloqueos de login');
    }

    // ── Administrador + Recepcionista ──────────────────────────────────────

    public function test_pantallas_administrador_y_recepcionista(): void
    {
        foreach ([$this->admin, $this->recepcionistaUser] as $usuario) {
            $etiqueta = $usuario->is($this->admin) ? 'Admin' : 'Recepcionista';

            $this->assertPantalla('/notificaciones', $usuario, "Notificaciones index ({$etiqueta})");
            $this->assertPantalla('/notificaciones/create', $usuario, "Notificaciones create ({$etiqueta})");
            $this->assertPantalla('/notificaciones/enviar', $usuario, "Notificaciones enviar ({$etiqueta})");

            $this->assertPantalla('/avisos', $usuario, "Avisos index ({$etiqueta})");
            $this->assertPantalla('/avisos/create', $usuario, "Avisos create ({$etiqueta})");

            $this->assertPantalla(route('pagos.edit', Pago::first()->id_pago), $usuario, "Pagos edit ({$etiqueta})");
        }
    }

    // ── Administrador + Recepcionista + Entrenador ─────────────────────────

    public function test_pantallas_de_los_tres_roles_de_staff(): void
    {
        foreach ([$this->admin, $this->recepcionistaUser, $this->entrenadorUser] as $usuario) {
            $this->assertPantalla('/asistencias-qr', $usuario, 'Asistencias QR');
            $this->assertPantalla('/membresia_cliente', $usuario, 'Membresias de clientes index');
            $this->assertPantalla(route('membresia_cliente.edit', MembresiaCliente::first()->id), $usuario, 'Membresias de clientes edit');

            $this->assertPantalla('/clientes', $usuario, 'Clientes index');
            $this->assertPantalla('/clientes/create', $usuario, 'Clientes create');
            $this->assertPantalla(route('clientes.edit', $this->cliente->id_cliente), $usuario, 'Clientes edit');
            $this->assertPantalla(route('clientes.show', $this->cliente->id_cliente), $usuario, 'Clientes show');

            $this->assertPantalla('/pagos', $usuario, 'Pagos index');
            $this->assertPantalla('/pagos/create', $usuario, 'Pagos create');
            $this->assertPantalla(route('pagos.show', Pago::first()->id_pago), $usuario, 'Pagos show');

            $this->assertPantalla('/membresias', $usuario, 'Membresias index (solo lectura)');
            $this->assertPantalla('/descuentos', $usuario, 'Descuentos index (solo lectura)');
            $this->assertPantalla('/grupos_musculares', $usuario, 'Grupos musculares index (solo lectura)');
            $this->assertPantalla('/horarios-entrenadores', $usuario, 'Horarios de entrenadores');
        }
    }

    // ── Rutinas (Administrador + Entrenador) ───────────────────────────────

    public function test_pantallas_de_rutinas(): void
    {
        $fecha = now()->toDateString();

        foreach ([$this->admin, $this->entrenadorUser] as $usuario) {
            $this->assertPantalla('/rutinas', $usuario, 'Rutinas index');
            $this->assertPantalla("/rutinas/dia/{$fecha}", $usuario, 'Rutinas editar dia');
        }

        $this->assertPantalla(route('rutinas.show', Rutina::first()->id), $this->admin, 'Rutinas show');
    }

    // ── Portal del cliente ──────────────────────────────────────────────────

    public function test_portal_del_cliente(): void
    {
        $cliente = $this->cliente->user;
        $cliente->markEmailAsVerified();

        $this->assertPantalla('/mi-membresia', $cliente, 'Mi membresia');
        $this->assertPantalla('/mi-rutina', $cliente, 'Mi rutina');
        $this->assertPantalla('/mis-asistencias', $cliente, 'Mis asistencias');
        $this->assertPantalla('/mis-pagos', $cliente, 'Mis pagos');
    }

    // ── Rutas muertas: confirmadas quitadas, no solo "no probadas" ─────────

    /**
     * Estas 8 rutas *.show (y notificaciones.edit/update/destroy) estaban
     * registradas por Route::resource() sin que el controller method
     * existiera — 500 garantizado para quien las visitara a mano. Ninguna
     * vista ni test las enlazaba (confirmado por grep en todo el repo antes
     * de tocar routes/web.php). Se excluyeron del resource() — lo único que
     * importa es que YA NO EXPLOTEN (nunca más 500):
     *   - users/personas/administradores/promociones/roles/entrenadores/
     *     recepcionistas conservan su ruta PUT/DELETE en la misma URI (el
     *     'edit'/'show' se excluyó, 'update'/'destroy' no), así que un GET
     *     ahí da 405 Method Not Allowed — Laravel reconoce la URL pero no
     *     ese verbo, nunca llega al controller roto.
     *   - notificaciones no tiene NINGÚN verbo registrado ya en esa URI
     *     (solo quedaron index/create/store, con otras rutas), así que ahí
     *     sí da 404 — la URL entera dejó de existir.
     */
    public function test_las_rutas_show_muertas_ya_no_existen(): void
    {
        $admin = $this->admin;

        $this->actingAs($admin)->get(route('users.index') . '/' . $admin->id)->assertStatus(405);
        $this->actingAs($admin)->get('/personas/' . $this->cliente->persona->id_persona)->assertStatus(405);
        $this->actingAs($admin)->get('/administradores/' . $this->administrador->id_administrador)->assertStatus(405);
        $this->actingAs($admin)->get('/promociones/' . Promocion::first()->id_promocion)->assertStatus(405);
        $this->actingAs($admin)->get('/roles/' . Rol::first()->id)->assertStatus(405);
        $this->actingAs($admin)->get('/notificaciones/' . Notificacion::first()->id . '/edit')->assertStatus(404);

        $this->actingAs($this->recepcionistaUser)->get('/entrenadores/' . $this->entrenador->id_entrenador)->assertStatus(405);
        $this->actingAs($this->recepcionistaUser)->get('/recepcionistas/' . $this->recepcionista->id_recepcionista)->assertStatus(405);
    }

    // ── Reportes (solo Administrador) ──────────────────────────────────────

    public function test_reportes(): void
    {
        $admin = $this->admin;

        $this->assertPantalla('/reportes/clientes-estado', $admin, 'Reporte clientes por estado');
        $this->assertPantalla('/reportes/clientes-nuevos', $admin, 'Reporte clientes nuevos');
        $this->assertPantalla('/reportes/clientes-membresia-vencida', $admin, 'Reporte membresia vencida');
        $this->assertPantalla('/reportes/pagos-por-mes', $admin, 'Reporte pagos por mes');
    }
}
