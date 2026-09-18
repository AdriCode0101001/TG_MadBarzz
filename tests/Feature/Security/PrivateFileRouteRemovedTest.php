<?php

namespace Tests\Feature\Security;

use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Hallazgo 4.1 de la auditoría de seguridad (Alta): existía una ruta
 * genérica GET /archivos/privados/{path} que servía cualquier archivo del
 * disco 'privado' verificando solo que hubiera sesión (auth), sin chequear
 * que el archivo perteneciera al usuario — un Cliente podía potencialmente
 * descargar el comprobante de pago o la foto de perfil de OTRO si conocía
 * la ruta. Era código muerto (ninguna vista la enlazaba) y se eliminó junto
 * con PrivateFileController. Este test es un guardarraíl: si alguna vez se
 * vuelve a agregar sin la verificación de propiedad correspondiente, falla.
 */
class PrivateFileRouteRemovedTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_ruta_generica_de_archivos_privados_ya_no_existe(): void
    {
        $this->assertFalse(
            \Illuminate\Support\Facades\Route::has('archivos.privado'),
            'La ruta archivos.privado no debería existir: exponía archivos privados sin verificar propiedad.'
        );
    }

    public function test_un_cliente_autenticado_no_puede_acceder_a_la_ruta_generica_eliminada(): void
    {
        $cliente = User::factory()->create([
            'rol_id' => Rol::firstOrCreate(['nombre' => 'Cliente'])->id,
        ]);

        $this->actingAs($cliente)
            ->get('/archivos/privados/qrs/cualquier-archivo.jpg')
            ->assertStatus(404);
    }

    public function test_un_invitado_no_autenticado_tampoco_puede_acceder(): void
    {
        $this->get('/archivos/privados/fotos_perfil/cualquier-archivo.jpg')
            ->assertStatus(404);
    }
}
