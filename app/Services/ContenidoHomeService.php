<?php

namespace App\Services;

use App\Models\ConfiguracionHome;

/**
 * Textos y enlaces editables de la home pública (los edita el Administrador
 * en "Contenido de la Home"). Cada campo tiene un valor por defecto (lo que
 * la home mostraba antes de existir esta pantalla), así que sin configurar
 * nada la home se ve igual que siempre.
 */
class ContenidoHomeService
{
    /** Clave => valor por defecto. null = sin valor (se oculta o cae a otro dato). */
    const DEFAULTS = [
        // Publicación de Instagram del bloque junto a la guía (SIEMPRE tiene que haber una).
        'instagram_post_url'  => 'https://www.instagram.com/p/DfH5P7dMT-s/',

        // Footer · Información
        'info_texto'          => 'En Calistenia MadBarzz creemos que la constancia es la clave. Entrenar regularmente mejora tu fuerza, resistencia y bienestar.',
        'facebook_url'        => 'https://www.facebook.com/MadBarzz',
        'instagram_url'       => 'https://www.instagram.com/calistenia_madbarzz?igsh=aW91eXl6ZnVtMGJ2',

        // Footer · Contacto
        'contacto_direccion'  => 'Av. Santos Dumont, 4to anillo, al lado de la HOT, Santa Cruz de la Sierra',
        'contacto_maps_url'   => null,
        'contacto_telefono'   => null, // null = usa el del superadmin
        'contacto_email'      => null, // null = usa el del superadmin

        // Datos legales (Términos y Privacidad): rellenan los "[COMPLETAR]" de datos del negocio.
        // Sin valor, el texto sigue mostrando el pendiente resaltado.
        'legal_razon_social'      => null,
        'legal_nit'               => null,
        'legal_correo'            => null, // null = usa el correo de contacto del footer, si está configurado
        'legal_fecha_publicacion' => null, // Y-m-d

        // Footer · ¿Tenés dudas? y TODOS los botones de WhatsApp de la home
        'whatsapp_numero'     => '59176372337',
        'whatsapp_mensaje'    => 'Hola, soy [tu nombre] y quisiera más información de la Calistenia MadBarzz.',
    ];

    /**
     * Campos que se pueden dejar VACÍOS a propósito: una vez que el admin
     * guardó el formulario, vacío = "no mostrar" (no vuelve al valor por defecto).
     */
    private const OCULTABLES = ['facebook_url', 'instagram_url'];

    private ?array $valores = null;

    private function cargar(): array
    {
        if ($this->valores === null) {
            $this->valores = ConfiguracionHome::whereIn('clave', array_keys(self::DEFAULTS))
                ->pluck('valor', 'clave')
                ->all();
        }

        return $this->valores;
    }

    /** Valor guardado, o el por defecto si no hay fila o está vacío. */
    public function get(string $clave): ?string
    {
        $valor = $this->cargar()[$clave] ?? null;

        if (in_array($clave, self::OCULTABLES, true) && array_key_exists($clave, $this->cargar())) {
            return ($valor === null || trim($valor) === '') ? null : $valor;
        }

        if ($valor === null || trim($valor) === '') {
            return self::DEFAULTS[$clave] ?? null;
        }

        return $valor;
    }

    /** Valor tal cual está guardado (sin caer al por defecto): para el formulario del admin. */
    public function guardado(string $clave): ?string
    {
        return $this->cargar()[$clave] ?? null;
    }

    /** Valor para precargar el formulario del admin: lo vigente (respeta los campos vaciados a propósito). */
    public function paraFormulario(string $clave): ?string
    {
        if (in_array($clave, self::OCULTABLES, true)) {
            return $this->get($clave);
        }

        // Campos sin valor por defecto (opcionales): se muestra solo lo que el admin guardó.
        return self::DEFAULTS[$clave] === null
            ? $this->guardado($clave)
            : $this->get($clave);
    }

    /** Link wa.me al número configurado, con el mensaje predeterminado. */
    public function whatsappLink(): string
    {
        return 'https://wa.me/' . $this->get('whatsapp_numero')
            . '?text=' . rawurlencode($this->get('whatsapp_mensaje'));
    }

    /**
     * Normaliza un permalink de Instagram a su forma canónica
     * (https://www.instagram.com/{p|reel|tv}/{id}/). null si no es válido.
     */
    public static function normalizarInstagramPost(string $url): ?string
    {
        if (preg_match('#^https?://(?:www\.)?instagram\.com/(p|reel|tv)/([A-Za-z0-9_-]+)/?(?:[?\#].*)?$#i', trim($url), $m)) {
            return 'https://www.instagram.com/' . strtolower($m[1]) . '/' . $m[2] . '/';
        }

        return null;
    }

    /** Deja solo dígitos; un número boliviano de 8 dígitos recibe el prefijo 591. */
    public static function normalizarWhatsapp(string $numero): string
    {
        $digitos = preg_replace('/\D+/', '', $numero);

        return strlen($digitos) === 8 ? '591' . $digitos : $digitos;
    }
}
