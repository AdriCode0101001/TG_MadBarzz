<?php

namespace App\Services;

use App\Models\ConfiguracionImagen;
use Illuminate\Support\Facades\Storage;

/**
 * Resuelve qué imagen mostrar en cada punto de contenido de la home pública:
 * la que subió el Administrador (disco 'public') si existe, o el placeholder
 * actual si no subió nada todavía. La home nunca se rompe por falta de carga.
 */
class ImagenHomeService
{
    /**
     * Claves válidas => [etiqueta legible, tamaño recomendado, grupo para el formulario del admin].
     */
    const CLAVES = [
        'home_hero_1'               => ['Hero · Slide 1',            '1920x770', 'Hero / Carrusel'],
        'home_hero_2'               => ['Hero · Slide 2',            '1920x770', 'Hero / Carrusel'],
        'home_hero_3'               => ['Hero · Slide 3',            '1920x770', 'Hero / Carrusel'],
        'home_nosotros_calistenia'  => ['Clases de Calistenia',      '416x584',  'Nosotros / Servicios'],
        'home_nosotros_cardio'      => ['Programa de Cardio',        '416x584',  'Nosotros / Servicios'],
        'home_nosotros_fitness'     => ['Estudio de Fitness',        '416x584',  'Nosotros / Servicios'],
        'home_cta'                  => ['¡Comienza tu Entrenamiento!', '858x597', 'CTA'],
        'home_membresias_fondo'     => ['Fondo de la sección Membresías', '1920x846', 'Membresías'],
        'home_footer_fondo'         => ['Fondo del footer',          '1920x732', 'Footer'],
        'home_modal_unete'          => ['Imagen del modal "Únete hoy"', '420x591', 'Modal'],
    ];
    // El marquee de "Entrenadores" ya NO usa imágenes genéricas subidas acá:
    // muestra automáticamente la foto real (o inicial) de cada entrenador
    // activo — ver EntrenadorPublicoService y welcome-gymon.blade.php.

    /**
     * Claves cuya imagen tiene un recorte REAL y determinístico en la home
     * (object-fit:cover o background-size:cover sobre una caja de tamaño
     * fijo) => posición por defecto si el Admin nunca la cambió. "home_cta"
     * y "home_modal_unete" quedan afuera a propósito: en la home esas dos NO
     * se recortan (se muestran completas, sin object-fit real ni caja de
     * tamaño fijo), así que no tiene sentido ofrecer ni previsualizar un
     * reencuadre que no existe.
     */
    const POSICION_POR_DEFECTO = [
        'home_hero_1'              => 'centro',
        'home_hero_2'              => 'centro',
        'home_hero_3'              => 'centro',
        'home_nosotros_calistenia' => 'centro',
        'home_nosotros_cardio'     => 'centro',
        'home_nosotros_fitness'    => 'centro',
        'home_membresias_fondo'    => 'centro',
        'home_footer_fondo'        => 'centro',
    ];

    const POSICIONES_VALIDAS = ['arriba', 'centro', 'abajo'];

    /** Cache por instancia: evita repetir la misma consulta para url()+posicionCssPara() de una clave. */
    private array $cache = [];

    private function config(string $clave): ?ConfiguracionImagen
    {
        if (!array_key_exists($clave, $this->cache)) {
            $this->cache[$clave] = ConfiguracionImagen::where('clave', $clave)->first();
        }

        return $this->cache[$clave];
    }

    /**
     * URL a usar para la clave dada: la imagen subida si existe en disco, o
     * el placeholder recibido si no hay fila, no tiene "ruta", o el archivo
     * ya no está en disco.
     */
    public function url(string $clave, string $fallbackPlaceholder): string
    {
        $config = $this->config($clave);

        if (!$config || !$config->ruta || !Storage::disk('public')->exists($config->ruta)) {
            return $fallbackPlaceholder;
        }

        return Storage::disk('public')->url($config->ruta);
    }

    /** true si esta clave tiene un recorte real en la home (ver POSICION_POR_DEFECTO). */
    public static function permiteReencuadre(string $clave): bool
    {
        return array_key_exists($clave, self::POSICION_POR_DEFECTO);
    }

    /**
     * Posición guardada para $clave ("arriba"/"centro"/"abajo"), o su
     * default si nunca se guardó una o la clave no admite reencuadre.
     */
    public static function posicionDe(?ConfiguracionImagen $config, string $clave): string
    {
        if ($config && $config->posicion && in_array($config->posicion, self::POSICIONES_VALIDAS, true)) {
            return $config->posicion;
        }

        return self::POSICION_POR_DEFECTO[$clave] ?? 'centro';
    }

    /** Traduce "arriba"/"centro"/"abajo" al valor CSS de object-position/background-position. */
    public static function posicionCss(string $posicion): string
    {
        return match ($posicion) {
            'arriba' => 'center top',
            'abajo'  => 'center bottom',
            default  => 'center center',
        };
    }

    /** Atajo para la home: valor CSS de posición ya resuelto para $clave. */
    public function posicionCssPara(string $clave): string
    {
        return self::posicionCss(self::posicionDe($this->config($clave), $clave));
    }
}
