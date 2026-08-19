<?php

namespace Tests\Feature;

use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Etapa 2 de Web Push: el botón "Activar/Desactivar notificaciones" en el
 * portal del cliente. RIESGO PRINCIPAL: layouts/private.blade.php es
 * compartido por TODOS los roles — estas pruebas confirman que el botón (y
 * el JS que lo respalda) solo aparece para Cliente, y que el panel de los
 * demás roles sigue renderizando igual.
 */
class WebPushBotonTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(string $rol, array $overrides = []): User
    {
        return User::factory()->create(array_merge(
            ['rol_id' => Rol::firstOrCreate(['nombre' => $rol])->id],
            $overrides
        ));
    }

    public function test_el_cliente_ve_el_boton_de_notificaciones_push(): void
    {
        $user = $this->usuario('Cliente');

        \App\Models\Cliente::create([
            'id_persona'     => \App\Models\Persona::create([
                'nombre_completo'  => 'Cliente Push Boton',
                'apellido_paterno' => 'Test',
                'apellido_materno' => 'Test',
                'ci'               => 'CIBOTON1',
                'telefono'         => '+59175000001',
                'genero'           => 'masculino',
                'fecha_nacimiento' => '1990-01-01',
            ])->id_persona,
            'id_usuario'     => $user->id,
            'estado'         => 'activo',
            'dias_asistidos' => 0,
        ]);

        $response = $this->actingAs($user)->get('/dashboard/cliente');

        $response->assertStatus(200)
                 ->assertSee('mbPushBtn', false)
                 ->assertSee('Activar notificaciones')
                 ->assertSee('subscribeUser', false)
                 ->assertSee(route('portal.push.suscribir'), false)
                 ->assertSee(route('portal.push.desuscribir'), false);
    }

    public function test_el_administrador_no_ve_el_boton_ni_el_js_de_push_en_su_panel(): void
    {
        $user = $this->usuario('Administrador');

        $response = $this->actingAs($user)->get('/dashboard/index');

        $response->assertStatus(200)
                 ->assertDontSee('mbPushBtn', false)
                 ->assertDontSee('Notificaciones push')
                 ->assertDontSee('subscribeUser', false);
    }

    public function test_el_recepcionista_no_ve_el_boton_ni_el_js_de_push_en_su_panel(): void
    {
        $user = $this->usuario('Recepcionista');

        $response = $this->actingAs($user)->get('/dashboard/index');

        $response->assertStatus(200)
                 ->assertDontSee('mbPushBtn', false)
                 ->assertDontSee('Notificaciones push')
                 ->assertDontSee('subscribeUser', false);
    }

    public function test_el_entrenador_no_ve_el_boton_ni_el_js_de_push_en_su_panel(): void
    {
        $user = $this->usuario('Entrenador');

        $response = $this->actingAs($user)->get('/dashboard/entrenador');

        $response->assertStatus(200)
                 ->assertDontSee('mbPushBtn', false)
                 ->assertDontSee('Notificaciones push')
                 ->assertDontSee('subscribeUser', false);
    }

    /**
     * Aunque alguien del personal navegue A MANO a la URL del dashboard del
     * cliente, no llega a ver el botón: DashboardController::cliente() no
     * encuentra un Cliente ligado a su usuario y lo redirige antes de
     * renderizar la vista.
     */
    public function test_un_administrador_que_entra_a_mano_a_dashboard_cliente_no_ve_la_vista(): void
    {
        $user = $this->usuario('Administrador');

        $response = $this->actingAs($user)->get('/dashboard/cliente');

        $response->assertRedirect();
    }

    public function test_el_panel_de_administrador_sigue_renderizando_bien(): void
    {
        $user = $this->usuario('Administrador', ['name' => 'admin.panel.test']);

        $this->actingAs($user)->get('/dashboard/index')
             ->assertStatus(200)
             ->assertSee('admin.panel.test');
    }

    public function test_el_panel_de_recepcionista_sigue_renderizando_bien(): void
    {
        $user = $this->usuario('Recepcionista', ['name' => 'recep.panel.test']);

        $this->actingAs($user)->get('/dashboard/index')
             ->assertStatus(200)
             ->assertSee('recep.panel.test');
    }

    public function test_el_panel_de_entrenador_sigue_renderizando_bien(): void
    {
        $user = $this->usuario('Entrenador', ['name' => 'entre.panel.test']);

        $this->actingAs($user)->get('/dashboard/entrenador')
             ->assertStatus(200)
             ->assertSee('entre.panel.test');
    }
}
