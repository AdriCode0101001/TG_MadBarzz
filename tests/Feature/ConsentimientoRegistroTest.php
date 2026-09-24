<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Grupo 3 legal, parte 1: checkbox obligatorio de aceptación de Términos y
 * Condiciones + Política de Privacidad en el auto-registro público. Nunca
 * premarcada; si no se acepta, el registro se rechaza; si se acepta, queda
 * constancia con fecha en clientes.terminos_aceptados_en.
 */
class ConsentimientoRegistroTest extends TestCase
{
    use RefreshDatabase;

    private static int $seq = 0;

    private function sede(): Sede
    {
        return Sede::create(['nombre' => 'Sede Consentimiento', 'direccion' => 'Calle 1', 'activo' => true]);
    }

    private function payload(array $extra = []): array
    {
        self::$seq++;
        $s = self::$seq;

        return array_merge([
            'nombre_completo'       => 'Luis',
            'apellido_paterno'      => 'Fernandez',
            'apellido_materno'      => 'Paz',
            'ci'                    => "CICONS{$s}",
            'telefono'              => '7' . str_pad((string) $s, 7, '0', STR_PAD_LEFT),
            'genero'                => 'masculino',
            'fecha_nacimiento'      => '1994-08-10',
            'id_sede'               => $this->sede()->id_sede,
            'email'                 => "consentimiento{$s}@test.com",
            'password'              => 'Contrasena123',
            'password_confirmation' => 'Contrasena123',
        ], $extra);
    }

    public function test_el_registro_se_rechaza_si_no_se_marca_la_casilla(): void
    {
        // El checkbox ni siquiera se manda si no está marcado (comportamiento
        // real de un <input type="checkbox">) — simulado con array_diff_key.
        $payload = $this->payload();

        $this->post(route('register'), $payload)
             ->assertSessionHasErrors('acepto_terminos');

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('clientes', 0);
    }

    public function test_el_mensaje_de_error_es_claro(): void
    {
        $this->post(route('register'), $this->payload())
             ->assertSessionHasErrors(['acepto_terminos']);

        $mensaje = session('errors')->first('acepto_terminos');
        $this->assertStringContainsString('Términos', $mensaje);
        $this->assertStringContainsString('Privacidad', $mensaje);
    }

    public function test_se_rechaza_tambien_si_el_valor_no_es_de_aceptacion(): void
    {
        $this->post(route('register'), $this->payload(['acepto_terminos' => '0']))
             ->assertSessionHasErrors('acepto_terminos');

        $this->post(route('register'), $this->payload(['acepto_terminos' => 'no']))
             ->assertSessionHasErrors('acepto_terminos');
    }

    public function test_el_registro_funciona_cuando_se_acepta(): void
    {
        $email = 'consentimiento-ok@test.com';

        $this->post(route('register'), $this->payload([
            'email'           => $email,
            'acepto_terminos' => '1',
        ]))->assertRedirect(route('dashboard'));

        $usuario = User::where('email', $email)->firstOrFail();
        $this->assertAuthenticatedAs($usuario);
    }

    public function test_al_aceptar_queda_registrada_la_fecha_de_aceptacion(): void
    {
        $email = 'consentimiento-fecha@test.com';
        $antes = now();

        $this->post(route('register'), $this->payload([
            'email'           => $email,
            'acepto_terminos' => '1',
        ]))->assertRedirect(route('dashboard'));

        $usuario = User::where('email', $email)->firstOrFail();
        $cliente = Cliente::where('id_usuario', $usuario->id)->firstOrFail();

        $this->assertNotNull($cliente->terminos_aceptados_en);
        $this->assertTrue($cliente->terminos_aceptados_en->greaterThanOrEqualTo($antes->subSecond()));
    }

    // ── La casilla nunca viene premarcada ─────────────────────────────────────

    public function test_la_casilla_no_viene_premarcada_en_una_visita_nueva(): void
    {
        $html = $this->get('/register')->assertStatus(200)->getContent();

        $this->assertMatchesRegularExpression(
            '/<input[^>]*name="acepto_terminos"[^>]*>/',
            $html
        );

        preg_match('/<input[^>]*name="acepto_terminos"[^>]*>/', $html, $matches);
        $this->assertStringNotContainsString('checked', $matches[0]);
    }

    public function test_la_casilla_no_se_premarca_ni_siquiera_tras_un_error_de_otro_campo(): void
    {
        // Falla por CI inválida, sin mandar acepto_terminos: al volver al
        // formulario, la casilla debe seguir sin marcar (el usuario nunca
        // la tildó).
        $response = $this->from(route('register'))->post(route('register'), $this->payload(['ci' => 'x']));
        $response->assertSessionHasErrors(['ci', 'acepto_terminos']);

        $html = $this->get('/register')->getContent();
        preg_match('/<input[^>]*name="acepto_terminos"[^>]*>/', $html, $matches);
        $this->assertNotEmpty($matches);
        $this->assertStringNotContainsString('checked', $matches[0]);
    }

    public function test_enlaza_a_privacidad_y_terminos_desde_el_formulario(): void
    {
        $html = $this->get('/register')->getContent();

        $this->assertStringContainsString('href="' . route('legal.terminos') . '"', $html);
        $this->assertStringContainsString('href="' . route('legal.privacidad') . '"', $html);
    }
}
