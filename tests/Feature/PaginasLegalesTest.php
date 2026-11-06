<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Grupo 3 legal, parte 1: páginas públicas de Política de Privacidad y
 * Términos y Condiciones, con el contenido leído en vivo desde
 * docs/BORRADOR_*.md — y el aviso de borrador visible mientras esos
 * archivos conserven el encabezado "BORRADOR — REQUIERE REVISIÓN DE UN
 * ABOGADO".
 */
class PaginasLegalesTest extends TestCase
{
    use RefreshDatabase;

    public function test_privacidad_responde_200_y_muestra_el_aviso_de_borrador(): void
    {
        $html = $this->get('/privacidad')->assertStatus(200)->getContent();

        $this->assertStringContainsString('Documento preliminar en revisión', $html);
        $this->assertStringContainsString('Política de Privacidad', $html);
        // Contenido real del borrador, no solo el título de la página.
        $this->assertStringContainsString('Datos biométricos', $html);
    }

    public function test_terminos_responde_200_y_muestra_el_aviso_de_borrador(): void
    {
        $html = $this->get('/terminos')->assertStatus(200)->getContent();

        $this->assertStringContainsString('Documento preliminar en revisión', $html);
        $this->assertStringContainsString('Términos y Condiciones', $html);
        $this->assertStringContainsString('menores de edad', $html);
    }

    public function test_los_completar_internos_no_se_muestran_al_publico_y_queda_por_definir(): void
    {
        foreach (['/privacidad', '/terminos'] as $ruta) {
            $html = $this->get($ruta)->getContent();

            $this->assertStringNotContainsString('COMPLETAR', $html, $ruta);
            $this->assertStringNotContainsString('mb-legal-pendiente">', $html, $ruta);
            $this->assertStringContainsString('(por definir)', $html, $ruta);
            // Se presenta como documento en revisión, no como una auditoría interna.
            $this->assertStringContainsString('Documento en revisión', $html, $ruta);
            $this->assertStringNotContainsString('auditoría técnica', $html, $ruta);
        }
    }

    public function test_las_tablas_markdown_se_renderizan_como_tabla_html(): void
    {
        $html = $this->get('/privacidad')->getContent();

        $this->assertStringContainsString('<table>', $html);
        $this->assertStringContainsString('Backblaze B2', $html);
    }

    public function test_el_footer_del_sitio_publico_enlaza_ambas_paginas_legales(): void
    {
        $html = $this->get('/')->assertStatus(200)->getContent();

        $this->assertStringContainsString(route('legal.privacidad'), $html);
        $this->assertStringContainsString(route('legal.terminos'), $html);
    }

    public function test_si_se_reemplaza_el_contenido_sin_la_marca_de_borrador_el_aviso_desaparece(): void
    {
        $ruta = base_path('docs/BORRADOR_politica_privacidad.md');
        $original = file_get_contents($ruta);

        try {
            file_put_contents($ruta, "# Política de Privacidad — Calistenia MadBarzz\n\nVersión final aprobada.");

            $html = $this->get('/privacidad')->assertStatus(200)->getContent();

            $this->assertStringNotContainsString('Documento preliminar en revisión', $html);
            $this->assertStringContainsString('Versión final aprobada', $html);
        } finally {
            file_put_contents($ruta, $original);
        }
    }
}
