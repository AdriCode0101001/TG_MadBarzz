<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Huella;
use Illuminate\Http\RedirectResponse;

/**
 * Borrado de huellas dactilares — Grupo 2 / Etapa 3 de la auditoría de
 * privacidad: derecho al olvido sobre un dato biométrico. Ruta protegida en
 * routes/web.php con middleware role:Administrador (no solo oculta el botón
 * en el menú/vista) — ver el grupo "Solo Administrador".
 *
 * Borrado REAL (forceDelete), no soft-delete: Huella usa SoftDeletes para
 * el resto de su ciclo de vida, pero un soft-delete deja el template
 * (aunque cifrado, ver Etapa 1) recuperable en la fila — para un dato
 * biométrico, "borrar" tiene que sacarlo de la base de una vez.
 *
 * El agente C# no se entera al instante: recarga su buffer completo en
 * cada GET /api/agente/sincronizar (BiometriaController::sincronizar), así
 * que deja de reconocer la huella borrada recién en su próximo ciclo
 * (config SincronizarCadaMinutos del agente, 5 min por defecto) — o antes,
 * si el operador usa "Sincronizar Ahora" en la app del agente.
 */
class HuellaController extends Controller
{
    public function destroy(Cliente $cliente, Huella $huella): RedirectResponse
    {
        abort_unless($huella->id_cliente === $cliente->id_cliente, 404);

        $dedo = $huella->dedo;
        $this->eliminarUnaHuella($cliente, $huella);

        return back()->with('success', "Huella ({$dedo}) borrada correctamente. El agente dejará de reconocerla en su próxima sincronización.");
    }

    public function destroyTodas(Cliente $cliente): RedirectResponse
    {
        $huellas = $cliente->huellas()->get();

        if ($huellas->isEmpty()) {
            return back()->with('error', 'Este cliente no tiene huellas registradas.');
        }

        // Una fila de bitácora por huella (no una sola agregada): igual
        // trazabilidad que borrar una por una, sin duplicar código de log.
        $huellas->each(fn (Huella $huella) => $this->eliminarUnaHuella($cliente, $huella));

        return back()->with('success', 'Todas las huellas del cliente fueron borradas correctamente. El agente dejará de reconocerlas en su próxima sincronización.');
    }

    /**
     * Borra una huella (forceDelete real) y deja constancia en la bitácora.
     * Huella no tiene LogsActivity propio a propósito: forceDelete() no
     * dispara el evento 'deleted' que audita automáticamente (Laravel lo
     * trata como 'forceDeleted', que Spatie no registra por defecto), y de
     * todos modos NO se debe loguear 'template' aunque lo disparara. El log
     * manual se arma con la misma forma que espera BitacoraService::detalle()
     * para un evento 'deleted' (properties.old) — nunca incluye el template.
     */
    private function eliminarUnaHuella(Cliente $cliente, Huella $huella): void
    {
        // Se loguea ANTES de borrar (performedOn() necesita el morph
        // class + key del modelo, no hace falta que siga en la BD después,
        // igual que un 'deleted' normal de LogsActivity).
        activity()
            ->causedBy(auth()->user())
            ->performedOn($huella)
            ->event('deleted')
            ->withProperties([
                'old' => [
                    'id_cliente'      => $cliente->id_cliente,
                    'dedo'            => $huella->dedo,
                    'version_formato' => $huella->version_formato,
                ],
            ])
            ->log('Huella dactilar borrada');

        $huella->forceDelete();
    }
}
