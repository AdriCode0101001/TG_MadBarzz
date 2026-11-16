<?php

namespace Tests\Feature\Auth;

use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * La casilla "Recordarme" del login: con ella marcada el navegador recibe una cookie de
 * "recordar sesión" que sigue valiendo aunque la sesión normal haya caducado; sin marcarla, no.
 */
class RecordarmeTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(): User
    {
        return User::factory()->create([
            'email'    => 'recordar@madbarzz.test',
            'password' => Hash::make('Clave-segura-1'),
            'rol_id'   => Rol::firstOrCreate(['nombre' => 'Administrador'])->id,
        ]);
    }

    /** Nombre de la cookie "recordar" que genera Laravel para el guard web. */
    private function nombreCookieRecordar(): string
    {
        return Auth::guard('web')->getRecallerName();
    }

    private function cookieRecordar($respuesta): ?\Symfony\Component\HttpFoundation\Cookie
    {
        return collect($respuesta->headers->getCookies())
            ->first(fn ($c) => $c->getName() === $this->nombreCookieRecordar());
    }

    public function test_la_casilla_esta_en_el_formulario_con_el_nombre_que_espera_el_servidor(): void
    {
        $this->get('/login')->assertOk()->assertSee('name="remember"', false)->assertSee('Recordarme');
    }

    public function test_con_la_casilla_marcada_se_entrega_la_cookie_de_recordar_y_se_guarda_el_token(): void
    {
        $usuario = $this->usuario();

        $respuesta = $this->post('/login', ['email' => $usuario->email, 'password' => 'Clave-segura-1', 'remember' => 'on']);

        $respuesta->assertRedirect();
        $cookie = $this->cookieRecordar($respuesta);
        $this->assertNotNull($cookie, 'Con "Recordarme" marcado debe llegar la cookie de recordar.');
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertGreaterThan(now()->addYear()->timestamp, $cookie->getExpiresTime(), 'La cookie debe durar mucho más que la sesión normal.');
        $this->assertNotEmpty($usuario->fresh()->remember_token);
    }

    public function test_sin_la_casilla_no_se_entrega_cookie_de_recordar(): void
    {
        $usuario = $this->usuario();

        $respuesta = $this->post('/login', ['email' => $usuario->email, 'password' => 'Clave-segura-1']);

        $respuesta->assertRedirect();
        $this->assertNull($this->cookieRecordar($respuesta));
    }

    public function test_con_recordarme_sigues_dentro_aunque_la_sesion_haya_caducado(): void
    {
        $usuario = $this->usuario();
        $login = $this->post('/login', ['email' => $usuario->email, 'password' => 'Clave-segura-1', 'remember' => 'on']);
        $cookie = $this->cookieRecordar($login);

        // Navegador "nuevo": sin cookie de sesión, solo con la cookie de recordar.
        $this->app['auth']->forgetGuards();
        $respuesta = $this->call('GET', '/dashboard', [], [$cookie->getName() => $cookie->getValue()]);

        $respuesta->assertRedirect(); // a su panel, no a /login
        $this->assertStringNotContainsString('/login', (string) $respuesta->headers->get('Location'));
    }

    public function test_sin_recordarme_la_sesion_caducada_te_manda_al_login(): void
    {
        $usuario = $this->usuario();
        $this->post('/login', ['email' => $usuario->email, 'password' => 'Clave-segura-1']);

        // Navegador "nuevo": sin ninguna cookie.
        $this->app['auth']->forgetGuards();
        $this->flushSession();
        $respuesta = $this->call('GET', '/dashboard');

        $respuesta->assertRedirect(route('login'));
    }

    public function test_al_cerrar_sesion_la_cookie_de_recordar_deja_de_servir(): void
    {
        $usuario = $this->usuario();
        $login = $this->post('/login', ['email' => $usuario->email, 'password' => 'Clave-segura-1', 'remember' => 'on']);
        $cookie = $this->cookieRecordar($login);

        $this->post('/logout')->assertRedirect('/');

        $this->app['auth']->forgetGuards();
        $respuesta = $this->call('GET', '/dashboard', [], [$cookie->getName() => $cookie->getValue()]);

        $respuesta->assertRedirect(route('login'));
    }
}
