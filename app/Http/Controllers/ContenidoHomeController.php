<?php

namespace App\Http\Controllers;

use App\Models\Administrador;
use App\Models\ConfiguracionHome;
use App\Models\Recepcionista;
use App\Services\ContenidoHomeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Textos y enlaces editables de la home pública (solo Administrador). La
 * publicación de Instagram se puede cambiar pero NO borrar: siempre tiene
 * que haber una.
 */
class ContenidoHomeController extends Controller
{
    public function edit()
    {
        $contenido = new ContenidoHomeService();

        $valores = [];
        foreach (array_keys(ContenidoHomeService::DEFAULTS) as $clave) {
            $valores[$clave] = $contenido->paraFormulario($clave);
        }

        return view('configuracion.contenido_home', [
            'valores'  => $valores,
            'personal' => $this->personalParaRellenar(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'instagram_post_url' => ['required', 'string', 'max:300'],
            'info_texto'         => ['required', 'string', 'max:500'],
            'facebook_url'       => ['nullable', 'string', 'max:300', 'regex:#^https?://#i'],
            'instagram_url'      => ['nullable', 'string', 'max:300', 'regex:#^https?://#i'],
            'contacto_direccion' => ['required', 'string', 'max:255'],
            'contacto_maps_url'  => ['nullable', 'string', 'max:500', 'regex:#^https?://#i'],
            'contacto_telefono'  => ['nullable', 'string', 'max:30'],
            'contacto_email'     => ['nullable', 'email', 'max:255'],
            'legal_razon_social'      => ['nullable', 'string', 'max:255'],
            'legal_nit'               => ['nullable', 'string', 'max:30', 'regex:/^[0-9A-Za-z\-\.\s]+$/'],
            'legal_correo'            => ['nullable', 'email', 'max:255'],
            'legal_fecha_publicacion' => ['nullable', 'date', 'before_or_equal:today', 'after:2000-01-01'],
            'whatsapp_numero'    => ['required', 'string', 'max:30'],
            'whatsapp_mensaje'   => ['required', 'string', 'max:300'],
        ], [
            'instagram_post_url.required' => 'Tiene que haber una publicación de Instagram: no se puede dejar vacía.',
            'facebook_url.regex'          => 'El enlace de Facebook debe empezar con http:// o https://.',
            'instagram_url.regex'         => 'El enlace de Instagram debe empezar con http:// o https://.',
            'contacto_maps_url.regex'     => 'El enlace de Google Maps debe empezar con http:// o https://.',
            'legal_nit.regex'             => 'El NIT solo puede tener números, letras, guiones y puntos.',
            'legal_fecha_publicacion.before_or_equal' => 'La fecha de publicación no puede ser futura.',
        ]);

        $post = ContenidoHomeService::normalizarInstagramPost($data['instagram_post_url']);
        if ($post === null) {
            throw ValidationException::withMessages([
                'instagram_post_url' => 'Pegá el enlace de una publicación de Instagram (ej. https://www.instagram.com/p/XXXX/).',
            ]);
        }
        $data['instagram_post_url'] = $post;

        $numero = ContenidoHomeService::normalizarWhatsapp($data['whatsapp_numero']);
        if (strlen($numero) < 8 || strlen($numero) > 15) {
            throw ValidationException::withMessages([
                'whatsapp_numero' => 'El número de WhatsApp no es válido (8 a 15 dígitos, con código de país).',
            ]);
        }
        $data['whatsapp_numero'] = $numero;

        foreach ($data as $clave => $valor) {
            $valor = is_string($valor) ? trim($valor) : $valor;
            ConfiguracionHome::updateOrCreate(
                ['clave' => $clave],
                ['valor' => ($valor === '' ? null : $valor), 'id_usuario' => Auth::id()]
            );
        }

        return redirect()->route('configuracion.contenido_home.edit')
            ->with('success', 'Contenido de la home actualizado correctamente.');
    }

    /** Administradores y recepcionistas activos con teléfono/correo, para el botón "Rellenar con…". */
    private function personalParaRellenar(): array
    {
        $lista = [];

        foreach (Administrador::withoutGlobalScopes()->with(['persona', 'user'])->get() as $a) {
            if ($a->persona && $a->user) {
                $lista[] = [
                    'rol'      => 'Administrador',
                    'nombre'   => trim($a->persona->nombre_completo . ' ' . $a->persona->apellido_paterno),
                    'telefono' => $a->persona->telefono,
                    'email'    => $a->user->email,
                ];
            }
        }
        foreach (Recepcionista::withoutGlobalScopes()->with(['persona', 'user'])->where('estado', 'activo')->get() as $r) {
            if ($r->persona && $r->user) {
                $lista[] = [
                    'rol'      => 'Recepcionista',
                    'nombre'   => trim($r->persona->nombre_completo . ' ' . $r->persona->apellido_paterno),
                    'telefono' => $r->persona->telefono,
                    'email'    => $r->user->email,
                ];
            }
        }

        return $lista;
    }
}
