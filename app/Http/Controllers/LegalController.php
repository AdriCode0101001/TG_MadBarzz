<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;
use Illuminate\View\View;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\Autolink\AutolinkExtension;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\MarkdownConverter;

/**
 * Grupo 3 legal, parte 1: páginas públicas de Política de Privacidad y
 * Términos y Condiciones. El contenido se lee y convierte de Markdown a
 * HTML EN CADA REQUEST directo desde los mismos archivos que ya usa el
 * resto del proyecto para estos borradores (docs/BORRADOR_*.md) — cuando
 * el abogado devuelva la versión final, alcanza con reemplazar el texto de
 * esos dos archivos (o editar $rutaPrivacidad/$rutaTerminos si cambia el
 * nombre del archivo); no hace falta tocar ni desplegar código.
 *
 * El aviso de "borrador" que se ve en la página se prende solo mientras el
 * archivo siga teniendo el encabezado "BORRADOR — REQUIERE REVISIÓN DE UN
 * ABOGADO" — al reemplazar el contenido con la versión final (sin ese
 * encabezado), el aviso desaparece solo, sin tocar código.
 */
class LegalController extends Controller
{
    private const MARCA_BORRADOR = 'BORRADOR — REQUIERE REVISIÓN DE UN ABOGADO';

    public function privacidad(): View
    {
        return $this->documento('Política de Privacidad', base_path('docs/BORRADOR_politica_privacidad.md'));
    }

    public function terminos(): View
    {
        return $this->documento('Términos y Condiciones', base_path('docs/BORRADOR_terminos.md'));
    }

    private function documento(string $titulo, string $ruta): View
    {
        $markdown = File::exists($ruta)
            ? File::get($ruta)
            : "# {$titulo}\n\n[COMPLETAR: este documento todavía no fue cargado.]";

        $esBorrador = str_contains($markdown, self::MARCA_BORRADOR);

        $html = $this->convertirAHtml($markdown);

        // Resalta cada "[COMPLETAR: ...]" que haya quedado en el texto, para
        // que salte a la vista tanto en la vista previa como una vez
        // convertido a HTML (no se interpreta como link: CommonMark solo
        // arma un link si sigue "(url)" u otro "[ref]").
        $html = preg_replace('/\[COMPLETAR:([^\]]*)\]/u', '<span class="mb-legal-pendiente">[COMPLETAR:$1]</span>', $html);

        return view('legal.documento', [
            'titulo'     => $titulo,
            'html'       => $html,
            'esBorrador' => $esBorrador,
        ]);
    }

    private function convertirAHtml(string $markdown): string
    {
        $entorno = new Environment(['html_input' => 'escape']);
        $entorno->addExtension(new CommonMarkCoreExtension());
        $entorno->addExtension(new TableExtension());
        $entorno->addExtension(new AutolinkExtension());

        return (new MarkdownConverter($entorno))->convert($markdown)->getContent();
    }
}
