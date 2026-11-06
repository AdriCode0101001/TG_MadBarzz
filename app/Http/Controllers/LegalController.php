<?php

namespace App\Http\Controllers;

use App\Services\ContenidoHomeService;
use Carbon\Carbon;
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

        $markdown = $this->completarDatos($markdown);

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

    /**
     * Rellena con los "Datos legales" del panel (Contenido de la Home) los
     * "[COMPLETAR]" que son datos del negocio. Un dato sin cargar deja su
     * pendiente a la vista. También apunta el enlace a la Política a la página
     * real (/privacidad): los dos documentos ya están publicados en el sitio.
     */
    private function completarDatos(string $markdown): string
    {
        $contenido = new ContenidoHomeService();

        $razon  = $contenido->guardado('legal_razon_social');
        $nit    = $contenido->guardado('legal_nit');
        $correo = $contenido->guardado('legal_correo') ?: $contenido->guardado('contacto_email');
        $fecha  = $contenido->guardado('legal_fecha_publicacion');

        $limpio = fn (?string $v) => ($v !== null && trim($v) !== '') ? trim($v) : null;
        $razon = $limpio($razon); $nit = $limpio($nit); $correo = $limpio($correo); $fecha = $limpio($fecha);
        // Razón social y NIT van dentro de un texto en Markdown: se escapan los caracteres que lo alterarían.
        $escMd = fn (?string $v) => $v === null ? null : preg_replace_callback('/[\\\\*_`\[\]]/u', fn (array $m) => '\\' . $m[0], $v);
        $razon = $escMd($razon); $nit = $escMd($nit);

        // preg_replace_callback: el dato se inserta tal cual, sin interpretar "$" ni "\" del texto cargado.
        $reemplazar = function (string $patron, ?string $valor) use (&$markdown): void {
            if ($valor !== null) {
                $markdown = preg_replace_callback($patron, fn (array $m) => ($m[1] ?? '') . $valor, $markdown);
            }
        };

        $reemplazar('/(\*\*Razón social:\*\*\s*)\[COMPLETAR[^\]]*\]/u', $razon);
        $reemplazar('/(\*\*NIT:\*\*\s*)\[COMPLETAR[^\]]*\]/u', $nit);
        $reemplazar('/(\*\*Correo de contacto:\*\*\s*)\[COMPLETAR[^\]]*\]/u', $correo);
        $reemplazar('/(correo\s+)\[COMPLETAR\]/u', $correo);

        if ($fecha !== null) {
            try {
                $legible = Carbon::parse($fecha)->locale('es')->translatedFormat('j \d\e F \d\e Y');
                $markdown = preg_replace_callback('/\[COMPLETAR:\s*fecha de publicación\]/u', fn () => $legible, $markdown);
            } catch (\Throwable $e) {
                // fecha inválida guardada: se deja el pendiente
            }
        }

        // Los dos documentos ya están publicados: el enlace apunta a la página real y el aviso sobra.
        $markdown = preg_replace('/\[COMPLETAR:\s*actualizar el enlace.*?\]/su', '', $markdown);
        $markdown = str_replace('(/BORRADOR_politica_privacidad.md)', '(/privacidad)', $markdown);

        return $this->ocultarPendientes($markdown);
    }

    /**
     * Mientras los textos se terminan de definir NO se muestran al público los
     * avisos internos "[COMPLETAR: ...]" (son instrucciones para el dueño del
     * negocio, no texto para el visitante). En su lugar la página se presenta
     * como un documento EN REVISIÓN: aviso amable arriba y "(por definir)" en
     * cada punto pendiente. Los archivos en docs/ no se modifican: ahí siguen
     * estando todos los pendientes completos.
     */
    private function ocultarPendientes(string $markdown): string
    {
        // Encabezado interno (habla de "auditoría técnica automatizada") -> aviso para el visitante.
        $markdown = preg_replace(
            '/^> \*\*BORRADOR — REQUIERE REVISIÓN DE UN ABOGADO\.\*\*.*?(?=\R\s*\R)/smu',
            "> **Documento en revisión.**\n> Este texto está en proceso de revisión y todavía no es la versión definitiva. Algunos puntos se están definiendo y pueden cambiar.",
            $markdown
        );

        // Pie interno ("borrador técnico... validación de un abogado") -> pie neutro.
        $markdown = preg_replace(
            '/\*Este es un borrador técnico.*?\]\*/su',
            '*Documento en revisión: su contenido puede cambiar antes de la versión definitiva.*',
            $markdown
        );

        // Cualquier pendiente que quede -> "(por definir)", sin el recuadro naranja ni la instrucción interna.
        return preg_replace('/\[COMPLETAR(?::[^\]]*)?\]/su', '*(por definir)*', $markdown);
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
