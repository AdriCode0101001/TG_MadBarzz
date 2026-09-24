<?php

namespace Tests\Feature\Auth;

use App\Http\Controllers\Auth\RegisteredUserController;
use App\Models\Rol;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Etapa 3 del auto-registro: verificación de email.
 *
 * User implementa MustVerifyEmail, así que Registered dispara el correo de
 * verificación (en modo log, ver AppServiceProvider::boot()). Solo el
 * cliente AUTO-REGISTRADO debe empezar sin verificar: el personal y los
 * clientes que da de alta el personal quedan verificados desde que se crean
 * (ver los User::create() en ClienteController/RecepcionistaController/
 * EntrenadorController/AdministradorController/UserController).
 *
 * El registro público sigue bloqueado (Etapa 0): estos tests registran una
 * ruta propia, solo para el proceso de test, igual que
 * RegistroPublicoClienteTest.
 */
class VerificacionEmailEtapa3Test extends TestCase
{
    use RefreshDatabase;

    private static int $seq = 0;

    protected function setUp(): void
    {
        parent::setUp();

        Route::post('/test-solo/registro-publico', [RegisteredUserController::class, 'store'])
            ->middleware('web');
    }

    private function payload(array $extra = []): array
    {
        self::$seq++;
        $s = self::$seq;

        $sede = Sede::create(['nombre' => "Sede Etapa3 {$s}", 'direccion' => 'Calle 1', 'activo' => true]);

        return array_merge([
            'nombre_completo'       => 'Vero',
            'apellido_paterno'      => 'Sanchez',
            'apellido_materno'      => 'Diaz',
            'ci'                    => "CIVER{$s}",
            'telefono'              => '6' . str_pad((string) $s, 7, '0', STR_PAD_LEFT),
            'genero'                => 'femenino',
            'fecha_nacimiento'      => '1996-02-14',
            'id_sede'               => $sede->id_sede,
            'email'                 => "verif{$s}@test.com",
            'password'              => 'Contrasena123',
            'password_confirmation' => 'Contrasena123',
            'acepto_terminos'       => '1',
        ], $extra);
    }

    private function registrarYObtenerUsuario(array $extra = []): User
    {
        $datos = $this->payload($extra);
        $this->post('/test-solo/registro-publico', $datos);

        return User::where('email', $datos['email'])->firstOrFail();
    }

    // ── El auto-registro dispara el correo y queda sin verificar ────────────

    public function test_cliente_autoregistrado_queda_sin_verificar_y_recibe_el_correo(): void
    {
        Notification::fake();

        $usuario = $this->registrarYObtenerUsuario();

        $this->assertNull($usuario->email_verified_at);
        $this->assertFalse($usuario->fresh()->hasVerifiedEmail());

        Notification::assertSentTo($usuario, VerifyEmail::class);
    }

    // ── Sin verificar: ve el aviso, no el contenido del portal ───────────────

    public function test_cliente_sin_verificar_no_accede_al_dashboard_ni_al_portal(): void
    {
        $usuario = $this->registrarYObtenerUsuario();

        $this->actingAs($usuario)
            ->get(route('dashboard.cliente'))
            ->assertRedirect(route('verification.notice'));

        $this->actingAs($usuario)
            ->get(route('portal.membresia'))
            ->assertRedirect(route('verification.notice'));

        $this->actingAs($usuario)
            ->get(route('portal.rutina'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_cliente_sin_verificar_puede_ver_la_pantalla_de_aviso_y_reenviar(): void
    {
        Notification::fake();
        $usuario = $this->registrarYObtenerUsuario();

        $this->actingAs($usuario)
            ->get(route('verification.notice'))
            ->assertStatus(200)
            ->assertSee('Verificá tu correo');

        $this->actingAs($usuario)
            ->post(route('verification.send'))
            ->assertRedirect();

        Notification::assertSentTo($usuario, VerifyEmail::class);
    }

    // ── Verificando el correo, se desbloquea el portal ───────────────────────

    public function test_al_verificar_el_correo_el_cliente_accede_al_portal(): void
    {
        $usuario = $this->registrarYObtenerUsuario();
        $this->assertFalse($usuario->hasVerifiedEmail());
        $this->assertDatabaseHas('clientes', ['id_usuario' => $usuario->id]);

        $urlFirmada = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $usuario->id, 'hash' => sha1($usuario->email)]
        );

        $this->actingAs($usuario)->get($urlFirmada);

        $this->assertTrue($usuario->fresh()->hasVerifiedEmail());

        $this->actingAs($usuario->fresh())
            ->get(route('dashboard.cliente'))
            ->assertOk();

        $this->actingAs($usuario->fresh())
            ->get(route('portal.membresia'))
            ->assertOk();
    }

    // ── El personal NO se ve afectado ────────────────────────────────────────

    public function test_el_personal_queda_verificado_al_crearse_y_no_lo_frena_nada(): void
    {
        $admin = User::factory()->create(['rol_id' => Rol::firstOrCreate(['nombre' => 'Administrador'])->id]);

        $this->postearAltaDePersonal('administradores', $admin, [
            'name'                  => 'admin.nuevo',
            'nombre_completo'       => 'Admin',
            'apellido_paterno'      => 'Nuevo',
            'apellido_materno'      => 'Test',
            'ci'                    => 'CIADMIN1',
            'telefono'              => '61234567',
            'genero'                => 'masculino',
            'fecha_nacimiento'      => '1985-01-01',
            'email'                 => 'adminnuevo@test.com',
            'password'              => 'secreto123',
            'password_confirmation' => 'secreto123',
            'nivel_acceso'          => 'gestion',
            'area_responsable'      => 'Operaciones',
            'estado'                => 'activo',
        ]);

        $nuevoAdmin = User::where('email', 'adminnuevo@test.com')->firstOrFail();
        $this->assertNotNull($nuevoAdmin->email_verified_at);
        $this->assertTrue($nuevoAdmin->hasVerifiedEmail());

        // Sin 'verified' aplicado a ninguna ruta de personal: accede normal.
        $this->actingAs($nuevoAdmin)->get(route('dashboard.index'))->assertOk();
    }

    public function test_recepcionista_y_entrenador_quedan_verificados_al_crearse(): void
    {
        $admin = User::factory()->create(['rol_id' => Rol::firstOrCreate(['nombre' => 'Administrador'])->id]);
        Rol::firstOrCreate(['nombre' => 'Recepcionista']);
        Rol::firstOrCreate(['nombre' => 'Entrenador']);

        $this->actingAs($admin)->post('/recepcionistas', [
            'name'                  => 'recep.nueva',
            'nombre_completo'       => 'Recep',
            'apellido_paterno'      => 'Nueva',
            'apellido_materno'      => 'Test',
            'ci'                    => 'CIRECEP1',
            'telefono'              => '62234567',
            'genero'                => 'femenino',
            'fecha_nacimiento'      => '1992-01-01',
            'email'                 => 'recepnueva@test.com',
            'password'              => 'secreto123',
            'password_confirmation' => 'secreto123',
            'turno'                 => 'mañana',
            'punto_atencion'        => 'Recepción principal',
            'estado'                => 'activo',
        ]);
        $recep = User::where('email', 'recepnueva@test.com')->firstOrFail();
        $this->assertTrue($recep->hasVerifiedEmail());

        $this->actingAs($admin)->post('/entrenadores', [
            'name'                  => 'entrenador.nuevo',
            'nombre_completo'       => 'Entrenador',
            'apellido_paterno'      => 'Nuevo',
            'apellido_materno'      => 'Test',
            'ci'                    => 'CIENTR1',
            'telefono'              => '63234567',
            'genero'                => 'masculino',
            'fecha_nacimiento'      => '1990-01-01',
            'email'                 => 'entrenadornuevo@test.com',
            'password'              => 'secreto123',
            'password_confirmation' => 'secreto123',
            'especialidad'          => ['Calistenia'],
            'experiencia'           => '5 años',
            'disponibilidad'        => ['lunes', 'miercoles'],
            'estado'                => 'activo',
        ]);
        $entrenador = User::where('email', 'entrenadornuevo@test.com')->firstOrFail();
        $this->assertTrue($entrenador->hasVerifiedEmail());
    }

    // ── El cliente dado de alta por el PERSONAL queda verificado ─────────────

    public function test_cliente_dado_de_alta_por_el_personal_queda_verificado(): void
    {
        $rolOperador = Rol::firstOrCreate(['nombre' => 'Recepcionista']);
        $operador = User::factory()->create(['rol_id' => $rolOperador->id]);

        $this->actingAs($operador)->post('/clientes', [
            'nombre_completo'       => 'Cliente',
            'apellido_paterno'      => 'DePersonal',
            'apellido_materno'      => 'Test',
            'ci'                    => 'CIPERSONALV1',
            'telefono'              => '68000001',
            'genero'                => 'masculino',
            'fecha_nacimiento'      => '1995-03-10',
            'email'                 => 'clientepersonal@test.com',
            'password'              => 'secreto123',
            'password_confirmation' => 'secreto123',
            'estado'                => 'activo',
        ]);

        $cliente = User::where('email', 'clientepersonal@test.com')->firstOrFail();
        $this->assertNotNull($cliente->email_verified_at);
        $this->assertTrue($cliente->hasVerifiedEmail());

        // Portal accesible de una: no necesita verificar nada.
        $this->actingAs($cliente)->get(route('portal.membresia'))->assertOk();
    }

    private function postearAltaDePersonal(string $ruta, User $operador, array $datos): void
    {
        $this->actingAs($operador)->post('/' . $ruta, $datos);
    }
}
