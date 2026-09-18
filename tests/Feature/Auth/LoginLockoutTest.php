<?php

namespace Tests\Feature\Auth;

use App\Models\LoginLockout;
use App\Models\Rol;
use App\Models\User;
use App\Services\LoginLockoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Bloqueo progresivo de login por intentos fallidos (por login+IP, nunca
 * global). El bloqueo real lo sigue haciendo RateLimiter (ver
 * LoginLockoutService); login_lockouts es solo la memoria de escalón que
 * RateLimiter no puede guardar por sí solo.
 */
class LoginLockoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // El limiter 'login' de bootstrap/app.php es 5/min POR IP, GLOBAL a
        // todos los emails desde esa IP — interferiría con estos tests, que
        // a propósito mandan más de 5 POST /login seguidos para probar la
        // escalada del bloqueo NUEVO (por login+IP). Se desactiva SOLO en
        // estos tests; la app real lo sigue teniendo activo sin cambios.
        $this->withoutMiddleware(ThrottleRequests::class);
    }

    private function usuario(string $email, string $rol = 'Administrador'): User
    {
        return User::factory()->create([
            'email'  => $email,
            'rol_id' => Rol::firstOrCreate(['nombre' => $rol])->id,
        ]);
    }

    private function fallar(string $email, ?string $ip = null): TestResponse
    {
        $server = $ip ? ['REMOTE_ADDR' => $ip] : [];

        return $this->call('POST', '/login', [
            'email'    => $email,
            'password' => 'clave-incorrecta',
        ], [], [], $server);
    }

    private function intentar(string $email, string $password, ?string $ip = null): TestResponse
    {
        $server = $ip ? ['REMOTE_ADDR' => $ip] : [];

        return $this->call('POST', '/login', [
            'email'    => $email,
            'password' => $password,
        ], [], [], $server);
    }

    // ── 1. Umbral inicial: 5 fallos -> bloqueo de 3 minutos ─────────────────

    public function test_cinco_fallos_seguidos_bloquean_por_tres_minutos(): void
    {
        $user   = $this->usuario('cinco@test.com');
        $lockout = app(LoginLockoutService::class);

        for ($i = 1; $i <= 4; $i++) {
            $this->fallar($user->email);
            $this->assertGuest();
        }

        $this->assertFalse($lockout->estaBloqueado($user->email, '127.0.0.1'), 'No debería bloquear antes del 5to intento.');

        $this->fallar($user->email); // 5to fallo

        $this->assertTrue($lockout->estaBloqueado($user->email, '127.0.0.1'));
        $segundos = $lockout->segundosRestantes($user->email, '127.0.0.1');
        $this->assertGreaterThan(170, $segundos);
        $this->assertLessThanOrEqual(180, $segundos);

        // Aunque ponga la contraseña CORRECTA, sigue bloqueado.
        $response = $this->intentar($user->email, 'password');
        $this->assertGuest();
        $response->assertSessionHasErrors('email');
        $mensaje = session('errors')->first('email');
        $this->assertStringContainsString('Demasiados intentos', $mensaje);
        $this->assertStringContainsString('3 minuto', $mensaje);
    }

    // ── 2. Escalada: cada 3 fallos adicionales duplica, tope 15 min ─────────

    public function test_la_duracion_del_bloqueo_escala_al_doble_con_tope_de_15_minutos(): void
    {
        $lockout = app(LoginLockoutService::class);

        $this->assertSame(180, $lockout->segundosParaEscalon(0));  // 3 min
        $this->assertSame(360, $lockout->segundosParaEscalon(1));  // 6 min
        $this->assertSame(720, $lockout->segundosParaEscalon(2));  // 12 min
        $this->assertSame(900, $lockout->segundosParaEscalon(3));  // tope 15 min (24 -> capado)
        $this->assertSame(900, $lockout->segundosParaEscalon(4));  // se mantiene en el tope
    }

    public function test_tras_cumplir_la_espera_el_siguiente_bloqueo_es_el_doble(): void
    {
        $user    = $this->usuario('escalada@test.com');
        $lockout = app(LoginLockoutService::class);

        // 5 fallos -> primer bloqueo (3 min, escalón 0 -> 1)
        for ($i = 1; $i <= 5; $i++) {
            $this->fallar($user->email);
        }
        $this->assertTrue($lockout->estaBloqueado($user->email, '127.0.0.1'));

        $registro = LoginLockout::where('login', $user->email)->first();
        $this->assertSame(1, $registro->lockout_level);

        // Viajar en el tiempo más allá de los 3 minutos: ya no debería estar bloqueado.
        Carbon::setTestNow(now()->addMinutes(4));
        $this->assertFalse($lockout->estaBloqueado($user->email, '127.0.0.1'));

        // 3 fallos MÁS (umbral del escalón 1: 5 + 1*3 = 8) -> segundo bloqueo, el DOBLE (6 min)
        for ($i = 1; $i <= 3; $i++) {
            $this->fallar($user->email);
        }

        $this->assertTrue($lockout->estaBloqueado($user->email, '127.0.0.1'));
        $segundos = $lockout->segundosRestantes($user->email, '127.0.0.1');
        $this->assertGreaterThan(350, $segundos);
        $this->assertLessThanOrEqual(360, $segundos);

        $registro->refresh();
        $this->assertSame(2, $registro->lockout_level);

        Carbon::setTestNow();
    }

    // ── 3. Login exitoso resetea el contador ────────────────────────────────

    public function test_un_login_exitoso_resetea_el_contador(): void
    {
        $user    = $this->usuario('reseteo@test.com');
        $lockout = app(LoginLockoutService::class);

        $this->fallar($user->email);
        $this->fallar($user->email);
        $this->fallar($user->email); // 3 fallos, todavía lejos del umbral de 5

        $this->intentar($user->email, 'password'); // contraseña correcta del factory
        $this->assertAuthenticated();

        $this->assertNull(LoginLockout::where('login', $user->email)->first());
        $this->assertFalse($lockout->estaBloqueado($user->email, '127.0.0.1'));

        auth()->logout();

        // Si el contador NO se hubiera reseteado, estos 4 fallos sumados a
        // los 3 anteriores ya serían 7 (bloqueado). Como se resetea, con 4
        // todavía no llega al umbral de 5.
        for ($i = 1; $i <= 4; $i++) {
            $this->fallar($user->email);
        }
        $this->assertFalse($lockout->estaBloqueado($user->email, '127.0.0.1'));
    }

    // ── 4. Por login+IP: un usuario bloqueado no afecta a otro ──────────────

    public function test_el_bloqueo_es_por_login_e_ip_no_afecta_a_otros_usuarios(): void
    {
        $victima = $this->usuario('victima@test.com');
        $otro    = $this->usuario('otro@test.com');
        $lockout = app(LoginLockoutService::class);

        for ($i = 1; $i <= 5; $i++) {
            $this->fallar($victima->email, '10.0.0.1');
        }

        $this->assertTrue($lockout->estaBloqueado($victima->email, '10.0.0.1'));

        // Mismo IP, OTRO usuario: no debería estar bloqueado.
        $this->assertFalse($lockout->estaBloqueado($otro->email, '10.0.0.1'));
        $response = $this->intentar($otro->email, 'password', '10.0.0.1');
        $this->assertAuthenticated();

        auth()->logout();

        // MISMO usuario víctima, pero desde OTRA IP: tampoco debería estar bloqueado.
        $this->assertFalse($lockout->estaBloqueado($victima->email, '10.0.0.2'));
    }

    // ── 5. Desbloqueo manual del Admin ──────────────────────────────────────

    public function test_el_administrador_puede_desbloquear_manualmente(): void
    {
        $admin   = $this->usuario('admin.desbloquea@test.com', 'Administrador');
        $victima = $this->usuario('bloqueado@test.com');
        $lockout = app(LoginLockoutService::class);

        for ($i = 1; $i <= 5; $i++) {
            $this->fallar($victima->email);
        }
        $this->assertTrue($lockout->estaBloqueado($victima->email, '127.0.0.1'));

        $registro = LoginLockout::where('login', $victima->email)->firstOrFail();

        $this->actingAs($admin)
            ->delete(route('login_lockouts.destroy', $registro))
            ->assertRedirect(route('login_lockouts.index'));

        auth()->logout();

        $this->assertFalse($lockout->estaBloqueado($victima->email, '127.0.0.1'));
        $this->assertNull(LoginLockout::find($registro->id));

        // Ahora puede loguearse de inmediato, sin esperar el tiempo.
        $this->intentar($victima->email, 'password');
        $this->assertAuthenticated();
    }

    public function test_otros_roles_no_pueden_desbloquear(): void
    {
        $victima = $this->usuario('protegido@test.com');
        $lockout = app(LoginLockoutService::class);

        for ($i = 1; $i <= 5; $i++) {
            $this->fallar($victima->email);
        }
        $registro = LoginLockout::where('login', $victima->email)->firstOrFail();

        foreach (['Recepcionista', 'Entrenador', 'Cliente'] as $rol) {
            $otro = $this->usuario("intento.{$rol}@test.com", $rol);

            $this->actingAs($otro)
                ->delete(route('login_lockouts.destroy', $registro))
                ->assertStatus(403);

            auth()->logout();
        }

        // Sigue bloqueado: ningún intento de otro rol lo desbloqueó.
        $this->assertTrue($lockout->estaBloqueado($victima->email, '127.0.0.1'));
    }

    public function test_solo_el_admin_ve_la_pantalla_de_bloqueos(): void
    {
        $admin = $this->usuario('admin.ve@test.com', 'Administrador');
        $this->actingAs($admin)->get(route('login_lockouts.index'))->assertStatus(200);

        auth()->logout();

        $recepcionista = $this->usuario('recep.no.ve@test.com', 'Recepcionista');
        $this->actingAs($recepcionista)->get(route('login_lockouts.index'))->assertStatus(403);
    }

    // ── 6. El bloqueo aplica a cualquier rol ────────────────────────────────

    public function test_el_bloqueo_aplica_a_cualquier_rol(): void
    {
        $cliente = $this->usuario('cliente.bloqueado@test.com', 'Cliente');
        $lockout = app(LoginLockoutService::class);

        for ($i = 1; $i <= 5; $i++) {
            $this->fallar($cliente->email);
        }

        $this->assertTrue($lockout->estaBloqueado($cliente->email, '127.0.0.1'));
    }

    // ── 7. El login normal no se ve afectado ────────────────────────────────

    public function test_el_login_normal_con_credenciales_correctas_sigue_funcionando(): void
    {
        $user = $this->usuario('normal@test.com');

        $response = $this->intentar($user->email, 'password');

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }
}
