<?php

namespace Tests\Feature\Auth;

use App\Models\LoginLockout;
use App\Models\Rol;
use App\Models\User;
use App\Services\LoginLockoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Hallazgo 6.1 de la auditoría: POST /api/login no tenía NINGÚN freno, así
 * que se podía hacer fuerza bruta ilimitada contra cualquier cuenta por la
 * API móvil, saltándose el bloqueo progresivo que solo vivía en el login
 * web (LoginRequest). Ahora AuthApiController::login comparte el MISMO
 * LoginLockoutService (misma llave login+IP) — estos tests confirman que
 * ambos canales quedan protegidos y que se retroalimentan entre sí.
 */
class ApiLoginLockoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Igual que en LoginLockoutTest: el limiter 'login' de bootstrap/app.php
        // (30/min por IP) interferiría con los tests que mandan muchos
        // requests seguidos para probar la escalada del bloqueo progresivo.
        $this->withoutMiddleware(ThrottleRequests::class);
    }

    private function usuario(string $email, string $rol = 'Cliente'): User
    {
        return User::factory()->create([
            'email'  => $email,
            'rol_id' => Rol::firstOrCreate(['nombre' => $rol])->id,
        ]);
    }

    private function admin(): User
    {
        return $this->usuario('admin.api@test.com', 'Administrador');
    }

    private function fallarApi(string $email, string $ip = '127.0.0.1'): TestResponse
    {
        return $this->call('POST', '/api/login', [
            'email'    => $email,
            'password' => 'clave-incorrecta',
        ], [], [], ['REMOTE_ADDR' => $ip]);
    }

    private function intentarApi(string $email, string $password, string $ip = '127.0.0.1'): TestResponse
    {
        return $this->call('POST', '/api/login', [
            'email'    => $email,
            'password' => $password,
        ], [], [], ['REMOTE_ADDR' => $ip]);
    }

    private function fallarWeb(string $email, string $ip = '127.0.0.1'): TestResponse
    {
        return $this->call('POST', '/login', [
            'email'    => $email,
            'password' => 'clave-incorrecta',
        ], [], [], ['REMOTE_ADDR' => $ip]);
    }

    // ── El login legítimo por API sigue funcionando ─────────────────────────

    public function test_el_login_legitimo_por_api_sigue_devolviendo_token(): void
    {
        $user = $this->usuario('movil@test.com');

        $response = $this->intentarApi($user->email, 'password');

        $response->assertStatus(200);
        $response->assertJsonStructure(['token', 'user' => ['id', 'name', 'email', 'rol']]);
        $this->assertNotEmpty($response->json('token'));
    }

    // ── Fuerza bruta contra la API se bloquea igual que el web ──────────────

    public function test_fuerza_bruta_contra_la_api_se_bloquea_igual_que_el_web(): void
    {
        $victima = $this->usuario('ataque.api@test.com');
        $lockout = app(LoginLockoutService::class);

        for ($i = 1; $i <= 4; $i++) {
            $resp = $this->fallarApi($victima->email);
            $resp->assertStatus(401);
        }

        $this->assertFalse($lockout->estaBloqueado($victima->email, '127.0.0.1'));

        $this->fallarApi($victima->email); // 5to fallo -> activa el bloqueo

        $this->assertTrue($lockout->estaBloqueado($victima->email, '127.0.0.1'));

        // El 6to intento, aunque ponga la contraseña CORRECTA: 429, no token.
        $response = $this->intentarApi($victima->email, 'password');

        $response->assertStatus(429);
        $response->assertJsonStructure(['message', 'retry_after_seconds']);
        $this->assertStringContainsString('Demasiados intentos', $response->json('message'));
        $this->assertGreaterThan(0, $response->json('retry_after_seconds'));
        $response->assertHeader('Retry-After');
    }

    // ── El conteo se comparte entre web y API (mismo login+IP) ──────────────

    public function test_los_fallos_se_comparten_entre_web_y_api_por_login_e_ip(): void
    {
        $victima = $this->usuario('compartido@test.com');
        $ip      = '10.9.9.9';
        $lockout = app(LoginLockoutService::class);

        // 3 fallos por la API + 2 fallos por el web = 5 en total -> bloqueo.
        $this->fallarApi($victima->email, $ip);
        $this->fallarApi($victima->email, $ip);
        $this->fallarApi($victima->email, $ip);
        $this->fallarWeb($victima->email, $ip);
        $this->fallarWeb($victima->email, $ip);

        $this->assertTrue($lockout->estaBloqueado($victima->email, $ip));

        // Bloqueado en AMBOS canales, no solo en el que lo disparó.
        $this->intentarApi($victima->email, 'password', $ip)->assertStatus(429);

        $webResponse = $this->call('POST', '/login', [
            'email'    => $victima->email,
            'password' => 'password',
        ], [], [], ['REMOTE_ADDR' => $ip]);
        $webResponse->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    // ── El desbloqueo del Admin libera también la API ───────────────────────

    public function test_el_desbloqueo_del_admin_libera_tambien_la_api(): void
    {
        $victima = $this->usuario('bloqueado.api@test.com');
        $admin   = $this->admin();
        $lockout = app(LoginLockoutService::class);

        for ($i = 1; $i <= 5; $i++) {
            $this->fallarApi($victima->email);
        }
        $this->assertTrue($lockout->estaBloqueado($victima->email, '127.0.0.1'));

        $registro = LoginLockout::where('login', $victima->email)->firstOrFail();

        $this->actingAs($admin)
            ->delete(route('login_lockouts.destroy', $registro))
            ->assertRedirect(route('login_lockouts.index'));

        auth()->logout();

        $this->assertFalse($lockout->estaBloqueado($victima->email, '127.0.0.1'));

        $response = $this->intentarApi($victima->email, 'password');
        $response->assertStatus(200);
        $this->assertNotEmpty($response->json('token'));
    }

    // ── Un login exitoso por API también resetea el contador ────────────────

    public function test_un_login_exitoso_por_api_resetea_el_contador(): void
    {
        $user    = $this->usuario('reseteo.api@test.com');
        $lockout = app(LoginLockoutService::class);

        $this->fallarApi($user->email);
        $this->fallarApi($user->email);
        $this->fallarApi($user->email);

        $this->intentarApi($user->email, 'password')->assertStatus(200);

        $this->assertNull(LoginLockout::where('login', $user->email)->first());
        $this->assertFalse($lockout->estaBloqueado($user->email, '127.0.0.1'));
    }
}
