<?php

namespace Tests\Feature\Auth;

use App\Http\Controllers\Auth\RegisteredUserController;
use App\Models\Cliente;
use App\Models\Persona;
use App\Models\Rol;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Etapas 1 y 2 del auto-registro: RegisteredUserController::create() debe
 * mostrar el formulario completo (persona + cuenta + sede) con diseño GymOn,
 * y ::store() debe crear un cliente COMPLETO (persona + user + cliente), sin
 * membresía, con las mismas reglas de CI/teléfono que el alta del personal
 * (ClienteController) — pero SIN pasar por él (no se toca).
 *
 * El registro público (/register) ya está reactivado (el bloqueo de la
 * Etapa 0 se quitó una vez lista la Etapa 4). Estos tests igual usan una
 * ruta propia registrada en el proceso de test, apuntando directo al
 * controlador: así siguen probando el controlador/servicio en aislado, sin
 * depender de la ruta real ni de su throttle (que tiene su propia cobertura
 * en RegistroSeguridadEtapa4Test).
 */
class RegistroPublicoClienteTest extends TestCase
{
    use RefreshDatabase;

    private static int $seq = 0;

    protected function setUp(): void
    {
        parent::setUp();

        // ->middleware('web'): una ruta definida a mano en un test NO hereda
        // el grupo 'web' automáticamente, y NormalizarTelefono (que arma
        // "telefono" a partir de telefono_codigo/telefono_numero, o le
        // antepone +591 a un número boliviano pelado) vive ahí.
        Route::post('/test-solo/registro-publico', [RegisteredUserController::class, 'store'])
            ->middleware('web');

        Route::get('/test-solo/registro-publico', [RegisteredUserController::class, 'create'])
            ->middleware('web');
    }

    private function sede(): Sede
    {
        return Sede::create(['nombre' => 'Sede Test', 'direccion' => 'Calle 1', 'activo' => true]);
    }

    private function payload(array $extra = []): array
    {
        self::$seq++;
        $s = self::$seq;

        return array_merge([
            'nombre_completo'       => 'Ana',
            'apellido_paterno'      => 'Gutierrez',
            'apellido_materno'      => 'Rojas',
            'ci'                    => "CIREG{$s}",
            'telefono'              => '6' . str_pad((string) $s, 7, '0', STR_PAD_LEFT), // 8 dígitos, sin prefijo
            'genero'                => 'femenino',
            'fecha_nacimiento'      => '1998-05-20',
            'id_sede'               => $this->sede()->id_sede,
            'email'                 => "registro{$s}@test.com",
            'password'              => 'Contrasena123',
            'password_confirmation' => 'Contrasena123',
        ], $extra);
    }

    private function postRegistro(array $payload)
    {
        return $this->post('/test-solo/registro-publico', $payload);
    }

    // ── Crea las 3 entidades completas ──────────────────────────────────────

    public function test_registro_crea_persona_user_y_cliente_completos(): void
    {
        $sede = $this->sede();

        $this->postRegistro($this->payload(['id_sede' => $sede->id_sede, 'ci' => 'CIREGX1', 'email' => 'completo@test.com', 'telefono' => '61111111']))
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('personas', [
            'nombre_completo'  => 'Ana',
            'apellido_paterno' => 'Gutierrez',
            'ci'               => 'CIREGX1',
            'telefono'         => '+59161111111',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'completo@test.com',
            'name'  => 'ana.gutierrez.r',
        ]);

        $usuario = User::where('email', 'completo@test.com')->firstOrFail();
        $this->assertSame(Rol::firstOrCreate(['nombre' => 'Cliente'])->id, $usuario->rol_id);

        $cliente = Cliente::where('id_usuario', $usuario->id)->firstOrFail();
        $this->assertSame(0, (int) $cliente->dias_asistidos);
        $this->assertSame('activo', $cliente->estado);
        $this->assertSame($sede->id_sede, $cliente->id_sede);
        $this->assertNotNull($cliente->qr_token);

        // Queda logueado.
        $this->assertAuthenticatedAs($usuario);
    }

    // ── Sin membresía ────────────────────────────────────────────────────────

    public function test_cliente_registrado_queda_sin_membresia(): void
    {
        $this->postRegistro($this->payload(['email' => 'sinmembresia@test.com']));

        $cliente = Cliente::whereHas('user', fn ($q) => $q->where('email', 'sinmembresia@test.com'))->firstOrFail();

        $this->assertFalse($cliente->tieneMembresiaVigente());
        $this->assertDatabaseCount('membresia_cliente', 0);
    }

    // ── Rechaza duplicados ───────────────────────────────────────────────────

    public function test_rechaza_ci_duplicada(): void
    {
        Persona::create([
            'nombre_completo' => 'Otro', 'apellido_paterno' => 'Cliente', 'apellido_materno' => 'Ya',
            'ci' => 'CIDUP1', 'telefono' => '+59169999999', 'genero' => 'masculino', 'fecha_nacimiento' => '1990-01-01',
        ]);

        $this->postRegistro($this->payload(['ci' => 'CIDUP1']))
            ->assertSessionHasErrors('ci');

        $this->assertDatabaseCount('clientes', 0);
    }

    public function test_rechaza_email_duplicado(): void
    {
        $rol = Rol::firstOrCreate(['nombre' => 'Cliente']);
        User::factory()->create(['rol_id' => $rol->id, 'email' => 'yaexiste@test.com']);

        $this->postRegistro($this->payload(['email' => 'yaexiste@test.com']))
            ->assertSessionHasErrors('email');

        $this->assertDatabaseCount('clientes', 0);
    }

    public function test_rechaza_telefono_duplicado(): void
    {
        Persona::create([
            'nombre_completo' => 'Otro', 'apellido_paterno' => 'Cliente', 'apellido_materno' => 'Ya',
            'ci' => 'CIOTRO1', 'telefono' => '+59162222222', 'genero' => 'masculino', 'fecha_nacimiento' => '1990-01-01',
        ]);

        $this->postRegistro($this->payload(['telefono' => '62222222']))
            ->assertSessionHasErrors('telefono');

        $this->assertDatabaseCount('clientes', 0);
    }

    public function test_rechaza_password_debil(): void
    {
        $this->postRegistro($this->payload(['password' => '123', 'password_confirmation' => '123']))
            ->assertSessionHasErrors('password');

        $this->assertDatabaseCount('clientes', 0);
    }

    // ── El rol siempre es Cliente ────────────────────────────────────────────

    public function test_el_rol_siempre_es_cliente_no_se_puede_forzar_otro(): void
    {
        $rolAdmin = Rol::firstOrCreate(['nombre' => 'Administrador']);

        $this->postRegistro($this->payload([
            'email'   => 'noadmin@test.com',
            'rol_id'  => $rolAdmin->id, // intento de forzar otro rol
            'rol'     => 'Administrador',
        ]));

        $usuario = User::where('email', 'noadmin@test.com')->firstOrFail();
        $this->assertSame(Rol::firstOrCreate(['nombre' => 'Cliente'])->id, $usuario->rol_id);
        $this->assertNotSame($rolAdmin->id, $usuario->rol_id);
    }

    // ── id_sede se asigna correctamente ──────────────────────────────────────

    public function test_id_sede_se_asigna_a_la_sede_elegida(): void
    {
        $sedeA = Sede::create(['nombre' => 'Sede A', 'direccion' => 'Calle 1', 'activo' => true]);
        $sedeB = Sede::create(['nombre' => 'Sede B', 'direccion' => 'Calle 2', 'activo' => true]);

        $this->postRegistro($this->payload(['id_sede' => $sedeB->id_sede, 'email' => 'sedeb@test.com']));

        $cliente = Cliente::whereHas('user', fn ($q) => $q->where('email', 'sedeb@test.com'))->firstOrFail();
        $this->assertSame($sedeB->id_sede, $cliente->id_sede);
        $this->assertNotSame($sedeA->id_sede, $cliente->id_sede);
    }

    public function test_rechaza_una_sede_que_no_existe(): void
    {
        $this->postRegistro($this->payload(['id_sede' => 999999]))
            ->assertSessionHasErrors('id_sede');

        $this->assertDatabaseCount('clientes', 0);
    }

    // ── El alta del personal no se ve afectada ───────────────────────────────

    public function test_el_alta_del_personal_sigue_creando_clientes_normalmente(): void
    {
        $rol = Rol::firstOrCreate(['nombre' => 'Recepcionista']);
        $operador = User::factory()->create(['rol_id' => $rol->id]);

        $this->actingAs($operador)->post('/clientes', [
            'nombre_completo'       => 'Jhon',
            'apellido_paterno'      => 'Crespo',
            'apellido_materno'      => 'Coria',
            'ci'                    => 'CIPERSONAL1',
            'telefono'              => '67000001',
            'genero'                => 'masculino',
            'fecha_nacimiento'      => '1995-03-10',
            'email'                 => 'personal1@test.com',
            'password'              => 'secreto123',
            'password_confirmation' => 'secreto123',
            'estado'                => 'activo',
        ])->assertRedirect(route('clientes.index'));

        $this->assertDatabaseHas('users', ['name' => 'jhon.crespo.c']);
        $this->assertDatabaseHas('clientes', ['estado' => 'activo']);
    }

    // ── Etapa 2: la vista de registro ─────────────────────────────────────────

    public function test_la_vista_de_registro_incluye_todos_los_campos_del_formulario(): void
    {
        $this->sede();

        $respuesta = $this->get('/test-solo/registro-publico');

        $respuesta->assertStatus(200);
        $respuesta->assertSee('name="nombre_completo"', false);
        $respuesta->assertSee('name="apellido_paterno"', false);
        $respuesta->assertSee('name="apellido_materno"', false);
        $respuesta->assertSee('name="ci"', false);
        $respuesta->assertSee('name="telefono_codigo"', false);
        $respuesta->assertSee('name="telefono_numero"', false);
        $respuesta->assertSee('name="genero"', false);
        $respuesta->assertSee('name="fecha_nacimiento"', false);
        $respuesta->assertSee('name="id_sede"', false);
        $respuesta->assertSee('name="email"', false);
        $respuesta->assertSee('name="password"', false);
        $respuesta->assertSee('name="password_confirmation"', false);
    }

    public function test_la_vista_de_registro_solo_lista_sedes_activas(): void
    {
        Sede::create(['nombre' => 'Sede Visible Test', 'direccion' => 'Calle 1', 'activo' => true]);
        Sede::create(['nombre' => 'Sede Inactiva Test', 'direccion' => 'Calle 2', 'activo' => false]);

        $respuesta = $this->get('/test-solo/registro-publico');

        $respuesta->assertSee('Sede Visible Test');
        $respuesta->assertDontSee('Sede Inactiva Test');
    }

    public function test_la_vista_de_registro_preselecciona_la_unica_sede_activa(): void
    {
        // La migración de "sedes" ya inserta una "Sede Principal" de base en
        // cualquier entorno (incluido este de test) — para probar el caso
        // "una sola sede activa" de verdad, hay que desactivarla primero.
        Sede::where('activo', true)->update(['activo' => false]);

        $sede = Sede::create(['nombre' => 'Única Sede', 'direccion' => 'Calle 1', 'activo' => true]);

        $respuesta = $this->get('/test-solo/registro-publico');

        $respuesta->assertSee('value="' . $sede->id_sede . '" selected', false);
    }

    public function test_el_registro_publico_real_ya_esta_activo(): void
    {
        $this->get(route('register'))
            ->assertStatus(200)
            ->assertSee('name="nombre_completo"', false)
            ->assertSee('name="id_sede"', false);
    }
}
