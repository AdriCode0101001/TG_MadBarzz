<?php

namespace Tests\Feature;

use App\Models\ConfiguracionHome;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Contenido editable de la home (publicación de Instagram, redes, contacto,
 * WhatsApp): solo el Administrador; sin configurar nada la home se ve igual
 * que siempre; la publicación de Instagram no se puede dejar vacía.
 */
class ContenidoHomeTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(string $rol): User
    {
        return User::factory()->create(['rol_id' => Rol::firstOrCreate(['nombre' => $rol])->id]);
    }

    private function datos(array $extra = []): array
    {
        return array_merge([
            'instagram_post_url' => 'https://www.instagram.com/p/ABC123xyz/?utm_source=ig_web',
            'info_texto'         => 'Texto de información de prueba.',
            'facebook_url'       => 'https://www.facebook.com/otra',
            'instagram_url'      => 'https://www.instagram.com/otro_perfil',
            'contacto_direccion' => 'Calle Falsa 123',
            'contacto_maps_url'  => 'https://maps.app.goo.gl/abc',
            'contacto_telefono'  => '+59170000001',
            'contacto_email'     => 'contacto@madbarzz.test',
            'whatsapp_numero'    => '70000002',
            'whatsapp_mensaje'   => 'Hola, soy Ana y quiero info.',
        ], $extra);
    }

    private function guardar(array $extra = [])
    {
        return $this->actingAs($this->usuario('Administrador'))
            ->post(route('configuracion.contenido_home.update'), $this->datos($extra));
    }

    public function test_solo_el_administrador_accede(): void
    {
        foreach (['Recepcionista', 'Entrenador', 'Cliente'] as $rol) {
            $u = $this->usuario($rol);
            $this->actingAs($u)->get(route('configuracion.contenido_home.edit'))->assertForbidden();
            $this->actingAs($u)->post(route('configuracion.contenido_home.update'), $this->datos())->assertForbidden();
        }
        auth()->logout();
        $this->get(route('configuracion.contenido_home.edit'))->assertRedirect(route('login'));
    }

    public function test_el_administrador_ve_el_formulario_con_los_valores_por_defecto(): void
    {
        $this->actingAs($this->usuario('Administrador'))
            ->get(route('configuracion.contenido_home.edit'))
            ->assertOk()
            ->assertSee('https://www.instagram.com/p/DfH5P7dMT-s/', false)
            ->assertSee('59176372337', false);
    }

    public function test_sin_configurar_la_home_se_ve_como_siempre(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('data-instgrm-permalink="https://www.instagram.com/p/DfH5P7dMT-s/"', $html);
        $this->assertStringContainsString('https://www.facebook.com/MadBarzz', $html);
        $this->assertStringContainsString('wa.me/59176372337', $html);
        $this->assertStringContainsString('Av. Santos Dumont', $html);
    }

    public function test_guardar_cambia_la_home(): void
    {
        $this->guardar()->assertRedirect(route('configuracion.contenido_home.edit'));

        $html = $this->get('/')->assertOk()->getContent();

        // Permalink normalizado (sin parámetros) y número con prefijo 591.
        $this->assertStringContainsString('data-instgrm-permalink="https://www.instagram.com/p/ABC123xyz/"', $html);
        $this->assertStringNotContainsString('DfH5P7dMT-s', $html);
        $this->assertStringContainsString('https://www.facebook.com/otra', $html);
        $this->assertStringContainsString('https://www.instagram.com/otro_perfil', $html);
        $this->assertStringContainsString('Calle Falsa 123', $html);
        $this->assertStringContainsString('https://maps.app.goo.gl/abc', $html);
        $this->assertStringContainsString('+59170000001', $html);
        $this->assertStringContainsString('contacto@madbarzz.test', $html);
        $this->assertStringContainsString('Texto de información de prueba.', $html);
        $this->assertStringContainsString('wa.me/59170000002?text=' . rawurlencode('Hola, soy Ana y quiero info.'), $html);
        $this->assertStringNotContainsString('wa.me/59176372337', $html);
    }

    public function test_la_publicacion_de_instagram_no_se_puede_dejar_vacia_ni_invalida(): void
    {
        $this->guardar(['instagram_post_url' => ''])->assertSessionHasErrors('instagram_post_url');
        $this->guardar(['instagram_post_url' => 'https://www.facebook.com/p/ABC/'])->assertSessionHasErrors('instagram_post_url');
        $this->guardar(['instagram_post_url' => 'javascript:alert(1)'])->assertSessionHasErrors('instagram_post_url');

        $this->assertNull(ConfiguracionHome::where('clave', 'instagram_post_url')->first());
        $this->get('/')->assertSee('data-instgrm-permalink="https://www.instagram.com/p/DfH5P7dMT-s/"', false);
    }

    public function test_no_existe_ruta_para_borrar_la_publicacion(): void
    {
        $admin = $this->usuario('Administrador');

        $this->actingAs($admin)->delete(route('configuracion.contenido_home.update'))->assertStatus(405);
    }

    public function test_redes_vacias_ocultan_los_iconos_y_los_enlaces_peligrosos_se_rechazan(): void
    {
        $this->guardar(['facebook_url' => '', 'instagram_url' => '', 'contacto_maps_url' => ''])
            ->assertSessionHasNoErrors();

        $html = $this->get('/')->assertOk()->getContent();
        $this->assertStringNotContainsString('aria-label="Facebook de MadBarzz"', $html);
        $this->assertStringNotContainsString('aria-label="Instagram de MadBarzz"', $html);

        $this->guardar(['facebook_url' => 'javascript:alert(1)'])->assertSessionHasErrors('facebook_url');
        $this->guardar(['contacto_maps_url' => 'javascript:alert(1)'])->assertSessionHasErrors('contacto_maps_url');
    }

    public function test_whatsapp_numero_invalido_se_rechaza(): void
    {
        $this->guardar(['whatsapp_numero' => '123'])->assertSessionHasErrors('whatsapp_numero');
        $this->guardar(['whatsapp_numero' => 'abc'])->assertSessionHasErrors('whatsapp_numero');
    }

    public function test_telefono_y_correo_vacios_vuelven_a_los_del_superadmin(): void
    {
        $this->guardar(['contacto_telefono' => '', 'contacto_email' => ''])->assertSessionHasNoErrors();

        $html = $this->get('/')->assertOk()->getContent();
        $this->assertStringNotContainsString('contacto@madbarzz.test', $html);
        $this->assertStringNotContainsString('+59170000001', $html);
    }

    public function test_el_formulario_ofrece_rellenar_con_datos_del_personal(): void
    {
        $this->actingAs($this->usuario('Administrador'))
            ->get(route('configuracion.contenido_home.edit'))
            ->assertOk()
            ->assertSee('Rellenar teléfono y correo', false)
            ->assertSee('id="btnRellenar"', false);
    }
}
