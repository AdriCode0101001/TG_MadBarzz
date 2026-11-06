<?php

namespace Tests\Feature;

use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * "Datos legales" del panel (Contenido de la Home): rellenan los [COMPLETAR]
 * que son datos del negocio en Términos y Privacidad. Las decisiones legales
 * (mora, reglamento, jurisdicción…) y el aviso de borrador no se tocan.
 */
class DatosLegalesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['rol_id' => Rol::firstOrCreate(['nombre' => 'Administrador'])->id]);
    }

    private function datosBase(array $extra = []): array
    {
        return array_merge([
            'instagram_post_url'      => 'https://www.instagram.com/p/ABC123/',
            'info_texto'              => 'Texto',
            'contacto_direccion'      => 'Calle 1',
            'whatsapp_numero'         => '70000002',
            'whatsapp_mensaje'        => 'Hola',
            'legal_razon_social'      => 'MadBarzz Calistenia S.R.L.',
            'legal_nit'               => '1023456019',
            'legal_correo'            => 'legal@madbarzz.test',
            'legal_fecha_publicacion' => '2026-11-01',
        ], $extra);
    }

    private function guardar(array $extra = [])
    {
        $respuesta = $this->actingAs($this->admin())
            ->post(route('configuracion.contenido_home.update'), $this->datosBase($extra));
        auth()->logout();

        return $respuesta;
    }

    public function test_los_datos_legales_del_panel_reemplazan_los_completar_de_datos(): void
    {
        $this->guardar()->assertSessionHasNoErrors();

        foreach (['/terminos', '/privacidad'] as $ruta) {
            $html = $this->get($ruta)->assertOk()->getContent();

            $this->assertStringContainsString('MadBarzz Calistenia S.R.L.', $html, $ruta);
            $this->assertStringContainsString('1023456019', $html, $ruta);
            $this->assertStringContainsString('legal@madbarzz.test', $html, $ruta);
            $this->assertStringContainsString('1 de noviembre de 2026', $html, $ruta);
            $this->assertStringNotContainsString('COMPLETAR', $html, $ruta);
        }
    }

    public function test_sin_datos_legales_los_pendientes_siguen_a_la_vista(): void
    {
        $html = $this->get('/privacidad')->assertOk()->getContent();

        // Sin datos cargados: no se ve el aviso interno, se ve "(por definir)".
        $this->assertStringNotContainsString('COMPLETAR', $html);
        $this->assertStringContainsString('(por definir)', $html);
    }

    public function test_el_correo_legal_vacio_usa_el_correo_de_contacto_del_footer(): void
    {
        $this->guardar(['legal_correo' => '', 'contacto_email' => 'contacto@madbarzz.test'])->assertSessionHasNoErrors();

        $html = $this->get('/privacidad')->assertOk()->getContent();

        $this->assertStringContainsString('contacto@madbarzz.test', $html);
        $this->assertStringNotContainsString('COMPLETAR', $html);
    }

    public function test_las_demas_clausulas_siguen_por_definir_y_el_aviso_de_revision_sigue(): void
    {
        $this->guardar();

        $html = $this->get('/terminos')->assertOk()->getContent();

        // Las decisiones legales pendientes se muestran como "(por definir)" y el documento sigue "en revisión".
        $this->assertStringContainsString('(por definir)', $html);
        $this->assertStringContainsString('Documento en revisión', $html);
    }

    public function test_el_enlace_a_la_politica_apunta_a_la_pagina_real(): void
    {
        $html = $this->get('/terminos')->assertOk()->getContent();

        $this->assertStringContainsString('href="/privacidad"', $html);
        $this->assertStringNotContainsString('BORRADOR_politica_privacidad.md', $html);
        $this->assertStringNotContainsString('actualizar el enlace', $html);
    }

    public function test_un_dato_con_signos_especiales_se_muestra_sin_romper_la_pagina(): void
    {
        $this->guardar(['legal_razon_social' => 'Mad $1 \\0 *S.R.L.*'])->assertSessionHasNoErrors();

        $html = $this->get('/privacidad')->assertOk()->getContent();

        $this->assertStringContainsString('Mad $1', $html);
        $this->assertStringContainsString('S.R.L.', $html);
    }

    public function test_los_datos_legales_exigen_valores_validos(): void
    {
        $this->actingAs($this->admin())
            ->post(route('configuracion.contenido_home.update'), $this->datosBase([
                'legal_nit'               => 'NIT <script>',
                'legal_correo'            => 'no-es-correo',
                'legal_fecha_publicacion' => '2999-01-01',
            ]))
            ->assertSessionHasErrors(['legal_nit', 'legal_correo', 'legal_fecha_publicacion']);
    }

    public function test_el_formulario_del_panel_muestra_la_seccion_de_datos_legales(): void
    {
        $this->actingAs($this->admin())
            ->get(route('configuracion.contenido_home.edit'))
            ->assertOk()
            ->assertSee('Datos legales')
            ->assertSee('name="legal_nit"', false);
    }
}
