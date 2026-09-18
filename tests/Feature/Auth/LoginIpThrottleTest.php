<?php

namespace Tests\Feature\Auth;

use App\Models\Rol;
use App\Models\User;
use App\Services\LoginLockoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Límite POR IP de la ruta POST /login (RateLimiter::for('login', ...) en
 * bootstrap/app.php, aplicado vía throttle:login). A diferencia de
 * LoginLockoutTest (que prueba el bloqueo PROGRESIVO por login+IP y por eso
 * desactiva este middleware), estos tests corren CON él activo a propósito:
 * es justo lo que se está probando acá.
 */
class LoginIpThrottleTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(string $email): User
    {
        return User::factory()->create([
            'email'  => $email,
            'rol_id' => Rol::firstOrCreate(['nombre' => 'Recepcionista'])->id,
        ]);
    }

    private function intentar(string $email, string $password, string $ip): TestResponse
    {
        return $this->call('POST', '/login', [
            'email'    => $email,
            'password' => $password,
        ], [], [], ['REMOTE_ADDR' => $ip]);
    }

    private function fallar(string $email, string $ip): TestResponse
    {
        return $this->intentar($email, 'clave-incorrecta', $ip);
    }

    // ── Varios usuarios de la misma IP no se traban entre sí ────────────────

    public function test_varios_usuarios_legitimos_de_la_misma_ip_no_se_traban_entre_si(): void
    {
        $ip       = '10.5.5.5';
        $usuarios = collect(range(1, 6))->map(fn ($i) => $this->usuario("staff{$i}@test.com"));

        // Cada uno falla 2 veces (12 requests en total desde la MISMA IP,
        // bien por debajo del límite de respaldo de 30/min) — a ninguno
        // individualmente le alcanza para llegar al bloqueo progresivo (5).
        foreach ($usuarios as $u) {
            $respuesta = null;
            for ($i = 1; $i <= 2; $i++) {
                $respuesta = $this->fallar($u->email, $ip);
            }

            // Sigue siendo un fallo de login NORMAL (redirect + error de
            // validación), no un 429 del límite viejo por IP.
            $respuesta->assertStatus(302);
            $respuesta->assertSessionHasErrors('email');
        }

        // El último puede loguearse con su clave correcta sin problema,
        // aunque los otros 5 ya generaron tráfico fallido desde su misma IP.
        $ultimo    = $usuarios->last();
        $respuesta = $this->intentar($ultimo->email, 'password', $ip);

        $this->assertAuthenticated();
        $respuesta->assertRedirect(route('dashboard', absolute: false));
    }

    // ── El límite de respaldo por IP sigue existiendo, para un diluvio real ─

    public function test_el_limite_de_respaldo_por_ip_sigue_frenando_un_diluvio_de_requests(): void
    {
        $ip = '10.6.6.6';

        // 30 intentos con emails DISTINTOS (así ninguno activa el bloqueo
        // progresivo individual) siguen pasando como fallos normales.
        for ($i = 1; $i <= 30; $i++) {
            $respuesta = $this->fallar("random{$i}@test.com", $ip);
            $respuesta->assertStatus(302);
        }

        // El request número 31 desde la MISMA IP en el mismo minuto: lo
        // frena el límite de respaldo (429), antes de llegar al controlador.
        $this->fallar('random31@test.com', $ip)->assertStatus(429);
    }

    // ── El login normal sigue intacto ───────────────────────────────────────

    public function test_el_login_normal_sigue_funcionando_con_el_limite_ajustado(): void
    {
        $user      = $this->usuario('normal@test.com');
        $respuesta = $this->intentar($user->email, 'password', '10.7.7.7');

        $this->assertAuthenticated();
        $respuesta->assertRedirect(route('dashboard', absolute: false));
    }

    // ── Un ataque a UNA cuenta puntual lo sigue frenando el bloqueo progresivo ─

    public function test_un_ataque_a_una_cuenta_puntual_lo_frena_el_bloqueo_progresivo(): void
    {
        $victima = $this->usuario('victima.progresivo@test.com');
        $ip      = '10.8.8.8';

        for ($i = 1; $i <= 5; $i++) {
            $this->fallar($victima->email, $ip);
        }

        $this->assertTrue(app(LoginLockoutService::class)->estaBloqueado($victima->email, $ip));

        // El 6to intento (bien por debajo del límite de respaldo de 30/min):
        // NO es un 429 del límite por IP, es el mensaje del bloqueo progresivo.
        $respuesta = $this->intentar($victima->email, 'password', $ip);

        $this->assertGuest();
        $respuesta->assertStatus(302);
        $respuesta->assertSessionHasErrors('email');
        $this->assertStringContainsString(
            'Demasiados intentos',
            session('errors')->first('email')
        );
    }
}
