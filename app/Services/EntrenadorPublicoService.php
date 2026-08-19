<?php

namespace App\Services;

use App\Models\Entrenador;
use Illuminate\Support\Facades\Storage;

/**
 * Entrenadores activos para el marquee de la home pública (nombre + foto).
 * Mismo patrón que HorarioEntrenadorPublicoService: sin caché, se consulta
 * de nuevo en cada visita, así que agregar/editar/desactivar un entrenador
 * o cambiarle la foto se refleja solo, sin tocar código.
 */
class EntrenadorPublicoService
{
    /**
     * @return array<int, array{nombre: string, foto: ?string, etiqueta: string}>
     */
    public function activos(int $idSede = 1): array
    {
        return Entrenador::with(['persona', 'user'])
            ->where('estado', 'activo')
            ->where('id_sede', $idSede)
            ->get()
            ->map(function (Entrenador $entrenador) {
                $foto = $entrenador->user?->foto;
                $tieneFotoEnDisco = $foto && Storage::disk('privado')->exists($foto);

                return [
                    'nombre'   => $entrenador->persona?->nombre_completo ?? '',
                    'foto'     => $tieneFotoEnDisco
                        ? route('entrenadores.foto_publica', $entrenador->id_entrenador)
                        : null,
                    'etiqueta' => $entrenador->persona?->genero === 'femenino'
                        ? 'Entrenadora certificada'
                        : 'Entrenador certificado',
                ];
            })
            ->values()
            ->all();
    }
}
