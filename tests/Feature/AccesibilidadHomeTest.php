<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Grupo 1 de la auditoría de privacidad/accesibilidad, arreglo 3: la home
 * pública tenía enlaces-ícono sin nombre accesible (WCAG 4.1.2) y tres
 * <h1> en el carrusel (uno por slide). Estas pruebas fijan la corrección.
 */
class AccesibilidadHomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_los_enlaces_icono_del_header_y_footer_tienen_aria_label(): void
    {
        $html = $this->get('/')->assertStatus(200)->getContent();

        $this->assertStringContainsString('aria-label="Abrir menú de navegación"', $html);
        $this->assertStringContainsString('aria-label="Cerrar menú de navegación"', $html);
        $this->assertStringContainsString('aria-label="Facebook de MadBarzz"', $html);
        $this->assertStringContainsString('aria-label="Escribinos por WhatsApp"', $html);
        $this->assertStringContainsString('aria-label="Instagram de MadBarzz"', $html);
    }

    public function test_el_carrusel_hero_tiene_un_solo_h1_real_y_los_demas_van_como_aria_level_2(): void
    {
        $html = $this->get('/')->assertStatus(200)->getContent();

        // Las 3 slides siguen siendo <h1> a propósito (el CSS de la plantilla
        // apunta a ".featured-text-two h1" para la animación del carrusel),
        // pero solo la primera es el <h1> real de la página — las otras dos
        // llevan aria-level="2" para que el árbol de accesibilidad las
        // exponga como h2, no como un segundo/tercer <h1>.
        $this->assertSame(3, substr_count($html, '<h1'));
        $this->assertSame(2, substr_count($html, '<h1 aria-level="2">'));
        $this->assertStringContainsString('<h1>Clases de Calistenia</h1>', $html);
    }

    public function test_el_formulario_del_modal_unete_hoy_ya_no_es_decorativo(): void
    {
        $html = $this->get('/')->assertStatus(200)->getContent();

        $this->assertStringContainsString('id="formUneteModal"', $html);
        $this->assertStringNotContainsString('onsubmit="return false;"', $html);
    }

    public function test_la_home_no_pide_imagenes_a_via_placeholder_com(): void
    {
        $html = $this->get('/')->assertStatus(200)->getContent();

        $this->assertStringNotContainsString('via.placeholder.com', $html);
        $this->assertStringContainsString('images/placeholder-home.svg', $html);
    }

    public function test_la_home_ya_no_muestra_el_countdown_de_urgencia_falsa(): void
    {
        $html = $this->get('/')->assertStatus(200)->getContent();

        $this->assertStringNotContainsString('id="countdown"', $html);
        $this->assertStringNotContainsString('promoción por tiempo limitado', $html);
        // El bloque de la promo ahora muestra la publicación de Instagram.
        $this->assertStringContainsString('instagram-media', $html);
    }
}
