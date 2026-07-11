<?php

namespace Tests\Feature;

use App\Models\QrPago;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * QR de pago único del gimnasio: solo el Administrador lo sube/reemplaza,
 * se muestra al cobrar por QR (roles que cobran) por ruta protegida, y se
 * avisa cuando está por vencer o ya venció.
 */
class QrPagoTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(string $rol): User
    {
        return User::factory()->create(['rol_id' => Rol::firstOrCreate(['nombre' => $rol])->id]);
    }

    private function subirQr(User $admin, string $fechaVencimiento)
    {
        return $this->actingAs($admin)->post('/configuracion/qr-pago', [
            'imagen'            => UploadedFile::fake()->image('qr.jpg'),
            'fecha_vencimiento' => $fechaVencimiento,
        ]);
    }

    // ── 1 y 2. Solo el Administrador configura el QR ─────────────────────────

    public function test_administrador_puede_subir_el_qr_de_pago(): void
    {
        Storage::fake('privado');

        $admin = $this->usuario('Administrador');

        $this->subirQr($admin, now()->addDays(30)->toDateString())
             ->assertRedirect(route('qr_pago.edit'));

        $qr = QrPago::firstOrFail();
        Storage::disk('privado')->assertExists($qr->imagen);
        $this->assertSame($admin->id, $qr->id_usuario);
    }

    public function test_administrador_puede_reemplazar_el_qr_por_uno_nuevo(): void
    {
        Storage::fake('privado');
        $admin = $this->usuario('Administrador');

        $this->subirQr($admin, now()->addDays(30)->toDateString());
        $primero = QrPago::firstOrFail();

        $this->subirQr($admin, now()->addDays(60)->toDateString());

        $this->assertDatabaseCount('qr_pagos', 2); // se conserva el historial
        $actual = QrPago::actual();
        $this->assertNotSame($primero->id, $actual->id);
        $this->assertSame(now()->addDays(60)->toDateString(), $actual->fecha_vencimiento->toDateString());
    }

    public function test_recepcionista_no_puede_configurar_el_qr(): void
    {
        Storage::fake('privado');
        $recepcionista = $this->usuario('Recepcionista');

        $this->actingAs($recepcionista)->get('/configuracion/qr-pago')->assertStatus(403);
        $this->subirQr($recepcionista, now()->addDays(30)->toDateString())->assertStatus(403);
    }

    public function test_entrenador_no_puede_configurar_el_qr(): void
    {
        Storage::fake('privado');
        $entrenador = $this->usuario('Entrenador');

        $this->actingAs($entrenador)->get('/configuracion/qr-pago')->assertStatus(403);
        $this->subirQr($entrenador, now()->addDays(30)->toDateString())->assertStatus(403);
    }

    public function test_cliente_no_puede_configurar_el_qr(): void
    {
        Storage::fake('privado');
        $cliente = $this->usuario('Cliente');

        $this->actingAs($cliente)->get('/configuracion/qr-pago')->assertStatus(403);
        $this->subirQr($cliente, now()->addDays(30)->toDateString())->assertStatus(403);
    }

    public function test_la_fecha_de_vencimiento_debe_ser_futura(): void
    {
        Storage::fake('privado');
        $admin = $this->usuario('Administrador');

        $this->subirQr($admin, now()->toDateString())
             ->assertSessionHasErrors('fecha_vencimiento');

        $this->assertDatabaseCount('qr_pagos', 0);
    }

    // ── 3. Se muestra por ruta protegida al cobrar por QR ────────────────────

    public function test_los_tres_roles_que_cobran_pueden_ver_la_imagen_del_qr(): void
    {
        Storage::fake('privado');
        $admin = $this->usuario('Administrador');
        $this->subirQr($admin, now()->addDays(30)->toDateString());

        foreach (['Administrador', 'Recepcionista', 'Entrenador'] as $rol) {
            $this->actingAs($this->usuario($rol))
                 ->get('/qr-pago/imagen')
                 ->assertStatus(200);
        }
    }

    public function test_un_cliente_no_accede_a_la_imagen_del_qr(): void
    {
        Storage::fake('privado');
        $admin = $this->usuario('Administrador');
        $this->subirQr($admin, now()->addDays(30)->toDateString());

        $this->actingAs($this->usuario('Cliente'))
             ->get('/qr-pago/imagen')
             ->assertStatus(403);
    }

    public function test_invitado_no_accede_a_la_imagen_del_qr(): void
    {
        // Se crea directo por el modelo (sin actingAs) para probar realmente
        // sin sesión: subirQr() dejaría la petición autenticada como admin.
        Storage::fake('privado');
        QrPago::create([
            'imagen'            => 'qr-pago/fake.jpg',
            'fecha_vencimiento' => now()->addDays(30),
        ]);

        $this->get('/qr-pago/imagen')->assertRedirect(route('login'));
    }

    public function test_la_pantalla_de_registrar_pago_muestra_el_qr_vigente(): void
    {
        Storage::fake('privado');
        $admin = $this->usuario('Administrador');
        $this->subirQr($admin, now()->addDays(30)->toDateString());

        $this->actingAs($this->usuario('Recepcionista'))
             ->get('/pagos/create')
             ->assertStatus(200)
             ->assertSee(route('qr_pago.imagen'), false)
             ->assertDontSee('No hay un QR de pago vigente');
    }

    // ── 4. Sin QR o vencido: aviso claro, sin mostrar imagen caduca ──────────

    public function test_sin_qr_configurado_la_pantalla_de_pago_avisa_y_no_intenta_mostrar_imagen(): void
    {
        $this->actingAs($this->usuario('Recepcionista'))
             ->get('/pagos/create')
             ->assertStatus(200)
             ->assertSee('No hay un QR de pago vigente. Pídele al administrador que lo actualice.')
             ->assertDontSee(route('qr_pago.imagen'), false);
    }

    public function test_qr_vencido_la_pantalla_de_pago_avisa_y_no_muestra_la_imagen_caduca(): void
    {
        Storage::fake('privado');
        $admin = $this->usuario('Administrador');

        // Se sube vigente por 1 día y luego viajamos en el tiempo para que venza
        $this->subirQr($admin, now()->addDay()->toDateString());
        $this->travelTo(Carbon::now()->addDays(3));

        $this->actingAs($this->usuario('Entrenador'))
             ->get('/pagos/create')
             ->assertStatus(200)
             ->assertSee('No hay un QR de pago vigente. Pídele al administrador que lo actualice.')
             ->assertDontSee(route('qr_pago.imagen'), false);
    }

    public function test_la_imagen_del_qr_vencido_sigue_disponible_para_la_pantalla_de_configuracion(): void
    {
        // La ruta de imagen NO gatea por vigencia: la config del Admin debe
        // poder MOSTRAR el QR vencido (con su badge) para decidir reemplazarlo.
        Storage::fake('privado');
        $admin = $this->usuario('Administrador');

        $this->subirQr($admin, now()->addDay()->toDateString());
        $this->travelTo(Carbon::now()->addDays(3));

        $this->actingAs($admin)
             ->get('/configuracion/qr-pago')
             ->assertStatus(200)
             ->assertSee(route('qr_pago.imagen'), false)
             ->assertSee('Vencido');

        $this->actingAs($admin)->get('/qr-pago/imagen')->assertStatus(200);
    }

    // ── 5. Aviso de vencimiento al Administrador ──────────────────────────────

    public function test_el_admin_ve_aviso_en_su_dashboard_cuando_el_qr_esta_por_vencer(): void
    {
        Storage::fake('privado');
        $admin = $this->usuario('Administrador');

        // Por defecto el aviso dispara a 7 días o menos
        $this->subirQr($admin, now()->addDays(5)->toDateString());

        $this->actingAs($admin)
             ->get('/dashboard/index')
             ->assertStatus(200)
             ->assertSee('El QR de pago vence en');
    }

    public function test_el_admin_ve_aviso_en_su_dashboard_cuando_el_qr_ya_vencio(): void
    {
        Storage::fake('privado');
        $admin = $this->usuario('Administrador');

        $this->subirQr($admin, now()->addDay()->toDateString());
        $this->travelTo(Carbon::now()->addDays(3));

        $this->actingAs($admin)
             ->get('/dashboard/index')
             ->assertStatus(200)
             ->assertSee('El QR de pago está');
        $this->actingAs($admin)->get('/dashboard/index')->assertSee('VENCIDO', false);
    }

    public function test_el_admin_ve_aviso_cuando_no_ha_configurado_ningun_qr(): void
    {
        $admin = $this->usuario('Administrador');

        $this->actingAs($admin)
             ->get('/dashboard/index')
             ->assertStatus(200)
             ->assertSee('No has configurado ningún QR de pago');
    }

    public function test_no_hay_aviso_cuando_el_qr_esta_lejos_de_vencer(): void
    {
        Storage::fake('privado');
        $admin = $this->usuario('Administrador');
        $this->subirQr($admin, now()->addDays(60)->toDateString());

        $this->actingAs($admin)
             ->get('/dashboard/index')
             ->assertStatus(200)
             ->assertDontSee('El QR de pago vence en')
             ->assertDontSee('No has configurado ningún QR de pago');
    }

    public function test_el_aviso_de_vencimiento_no_se_muestra_a_recepcionista_ni_entrenador(): void
    {
        Storage::fake('privado');
        $admin = $this->usuario('Administrador');
        $this->subirQr($admin, now()->addDay()->toDateString());
        $this->travelTo(Carbon::now()->addDays(3)); // vencido

        foreach (['Recepcionista', 'Entrenador'] as $rol) {
            $this->actingAs($this->usuario($rol))
                 ->get('/dashboard/index')
                 ->assertStatus(200)
                 ->assertDontSee('El QR de pago está')
                 ->assertDontSee('QR de Pago'); // el enlace de configuración tampoco es suyo
        }
    }
}
