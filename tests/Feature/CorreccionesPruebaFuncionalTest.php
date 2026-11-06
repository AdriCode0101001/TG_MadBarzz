<?php

namespace Tests\Feature;

use App\Models\Administrador;
use App\Models\Entrenador;
use App\Models\Persona;
use App\Models\Promocion;
use App\Models\Recepcionista;
use App\Models\Rol;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

/**
 * Correcciones de la prueba funcional previa al despliegue:
 *  1) admin SIN fila en `administradores` puede crear promociones.
 *  2) mensajes de duplicado amables (centralizados en lang/es/validation.php).
 *  3) textos del diálogo de desbloqueo de login.
 *  4) gestión de PERSONAL solo para Administrador (protección en rutas + menú).
 */
class CorreccionesPruebaFuncionalTest extends TestCase
{
    use RefreshDatabase;

    private static int $seq = 0;

    protected function setUp(): void
    {
        parent::setUp();
        App::setLocale('es');
        foreach (['Administrador', 'Recepcionista', 'Entrenador', 'Cliente'] as $r) {
            Rol::firstOrCreate(['nombre' => $r]);
        }
    }

    private function usuario(string $rol): User
    {
        return User::factory()->create(['rol_id' => Rol::where('nombre', $rol)->value('id')]);
    }

    private function persona(string $prefijo = 'P'): Persona
    {
        self::$seq++;

        return Persona::create([
            'nombre_completo' => "{$prefijo} " . self::$seq, 'apellido_paterno' => 'A', 'apellido_materno' => 'B',
            'ci' => "{$prefijo}CI" . self::$seq, 'telefono' => '+5917' . str_pad((string) self::$seq, 7, '0', STR_PAD_LEFT),
            'genero' => 'otro', 'fecha_nacimiento' => '1990-01-01',
        ]);
    }

    private function payloadPromo(): array
    {
        return [
            'titulo' => 'Promo prueba', 'descripcion' => 'desc',
            'fecha_inicio' => now()->toDateString(), 'fecha_fin' => now()->addDays(5)->toDateString(),
            'estado' => 'activa',
        ];
    }

    private function payloadPersonal(string $tag, array $extra = []): array
    {
        self::$seq++;
        $s = self::$seq;

        return array_merge([
            'nombre_completo' => "Staff {$tag}{$s}", 'apellido_paterno' => 'Ap', 'apellido_materno' => 'Am',
            'ci' => "STF{$tag}{$s}", 'telefono' => '+5917' . str_pad((string) (5000 + $s), 7, '0', STR_PAD_LEFT),
            'genero' => 'femenino', 'fecha_nacimiento' => '1991-02-02',
            'name' => "staff{$tag}{$s}", 'email' => "staff{$tag}{$s}@madbarzz.test",
            'password' => 'password123', 'password_confirmation' => 'password123',
            'estado' => 'activo',
        ], $extra);
    }

    // ── ARREGLO 1 ────────────────────────────────────────────────────────────

    public function test_admin_sin_perfil_en_administradores_puede_crear_promocion(): void
    {
        $admin = $this->usuario('Administrador'); // sin fila en `administradores`
        $this->assertNull(Administrador::where('id_usuario', $admin->id)->first());

        $this->actingAs($admin)->post('/promociones', $this->payloadPromo())
            ->assertRedirect(route('promociones.index'))
            ->assertSessionHas('success')
            ->assertSessionMissing('error');

        $promo = Promocion::firstOrFail();
        $this->assertSame('Promo prueba', $promo->titulo);
        $this->assertNull($promo->id_administrador);

        // El listado (con promoción sin administrador asociado) sigue renderizando.
        $this->actingAs($admin)->get('/promociones')->assertOk()->assertSee('Promo prueba');
    }

    public function test_admin_con_perfil_sigue_guardando_su_id_administrador(): void
    {
        $admin = $this->usuario('Administrador');
        $perfil = Administrador::create([
            'id_persona' => $this->persona('ADM')->id_persona, 'id_usuario' => $admin->id,
            'nivel_acceso' => 'superadmin', 'area_responsable' => 'General', 'estado' => 'activo',
        ]);

        $this->actingAs($admin)->post('/promociones', $this->payloadPromo())
            ->assertRedirect(route('promociones.index'));

        $this->assertSame($perfil->id_administrador, Promocion::firstOrFail()->id_administrador);
    }

    public function test_seeder_de_produccion_crea_persona_user_y_administrador_completos(): void
    {
        $fuente = file_get_contents(base_path('database/seeders/ProduccionInicialSeeder.php'));

        $this->assertStringContainsString('Persona::create(', $fuente);
        $this->assertStringContainsString('User::create(', $fuente);
        $this->assertStringContainsString('Administrador::create(', $fuente);
        $this->assertStringContainsString("'id_usuario'       => \$usuario->id", $fuente);
        $this->assertStringContainsString("'id_persona'       => \$persona->id_persona", $fuente);
    }

    // ── ARREGLO 2 ────────────────────────────────────────────────────────────

    public function test_duplicados_muestran_mensajes_amables_en_alta_de_personal(): void
    {
        $admin = $this->usuario('Administrador');
        $existente = $this->persona('DUP');
        $otro = $this->usuario('Cliente');

        $payload = $this->payloadPersonal('R', [
            'ci' => $existente->ci, 'telefono' => $existente->telefono, 'email' => $otro->email,
            'turno' => 'mañana', 'punto_atencion' => 'Mostrador',
        ]);

        $resp = $this->actingAs($admin)->from('/recepcionistas/create')->post('/recepcionistas', $payload);
        $errores = session('errors')->getBag('default');

        $resp->assertSessionHasErrors(['ci', 'telefono', 'email']);
        $this->assertSame('La cédula de identidad ya está registrada.', $errores->first('ci'));
        $this->assertSame('El teléfono ya está registrado.', $errores->first('telefono'));
        $this->assertSame('El correo electrónico ya está registrado.', $errores->first('email'));
    }

    public function test_duplicados_muestran_mensajes_amables_en_alta_de_cliente_y_entrenador(): void
    {
        $admin = $this->usuario('Administrador');
        $existente = $this->persona('DUP2');

        foreach (['/entrenadores', '/clientes'] as $url) {
            $this->actingAs($admin)->from($url . '/create')->post($url, $this->payloadPersonal('X', [
                'ci' => $existente->ci, 'telefono' => $existente->telefono,
            ]));
            $errores = session('errors')->getBag('default');

            $this->assertSame('La cédula de identidad ya está registrada.', $errores->first('ci'), $url);
            $this->assertSame('El teléfono ya está registrado.', $errores->first('telefono'), $url);
            $this->assertStringNotContainsString('El campo ci', $errores->first('ci'));
            $this->assertStringNotContainsString('El campo telefono', $errores->first('telefono'));
        }
    }

    public function test_nombres_de_campo_tecnicos_se_traducen_en_otros_mensajes(): void
    {
        $this->assertSame('cédula de identidad', __('validation.attributes.ci'));
        $this->assertSame('teléfono', __('validation.attributes.telefono'));
        $this->assertSame('correo electrónico', __('validation.attributes.email'));

        $v = \Illuminate\Support\Facades\Validator::make(['ci' => '', 'email' => 'x'], ['ci' => 'required', 'email' => 'email']);
        $this->assertSame('El campo cédula de identidad es obligatorio.', $v->errors()->first('ci'));
        $this->assertStringContainsString('correo electrónico', $v->errors()->first('email'));
    }

    // ── ARREGLO 3 ────────────────────────────────────────────────────────────

    public function test_pantalla_de_bloqueos_habla_de_desbloquear_no_de_eliminar(): void
    {
        \App\Models\LoginLockout::create(['login' => 'alguien@test.com', 'ip' => '127.0.0.1', 'attempts' => 5, 'lockout_level' => 1]);

        $html = $this->actingAs($this->usuario('Administrador'))->get('/login_lockouts')->assertOk()->getContent();

        $this->assertStringContainsString('data-confirm-title="¿Desbloquear a este usuario?"', $html);
        $this->assertStringContainsString('data-confirm-button="Sí, desbloquear"', $html);
        $this->assertStringContainsString('No se elimina ninguna cuenta', $html);
        $this->assertStringNotContainsString('Eliminar registro', $html);

        // El script que dibuja el diálogo respeta los textos del formulario.
        $js = file_get_contents(public_path('assets_private/js/app-ui.js'));
        $this->assertStringContainsString("form.getAttribute('data-confirm-title')", $js);
        $this->assertStringContainsString("form.getAttribute('data-confirm-button')", $js);
    }

    // ── ARREGLO 4 ────────────────────────────────────────────────────────────

    private function personalExistente(): array
    {
        $entrenador = Entrenador::create([
            'id_persona' => $this->persona('ENT')->id_persona, 'id_usuario' => $this->usuario('Entrenador')->id,
            'especialidad' => ['Calistenia'], 'experiencia' => '1', 'disponibilidad' => ['Lunes'], 'estado' => 'activo',
        ]);
        $recepcionista = Recepcionista::create([
            'id_persona' => $this->persona('REC')->id_persona, 'id_usuario' => $this->usuario('Recepcionista')->id,
            'punto_atencion' => 'M', 'turno' => 'mañana', 'estado' => 'activo',
        ]);
        $administrador = Administrador::create([
            'id_persona' => $this->persona('ADM')->id_persona, 'id_usuario' => $this->usuario('Administrador')->id,
            'nivel_acceso' => 'gestion', 'area_responsable' => 'X', 'estado' => 'activo',
        ]);

        return [$entrenador, $recepcionista, $administrador];
    }

    public static function rolesSinPermisoDePersonal(): array
    {
        return ['recepcionista' => ['Recepcionista'], 'entrenador' => ['Entrenador']];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('rolesSinPermisoDePersonal')]
    public function test_recepcionista_y_entrenador_no_gestionan_personal(string $rol): void
    {
        [$ent, $rec, $adm] = $this->personalExistente();
        $this->actingAs($this->usuario($rol));

        foreach ([
            ['GET', '/entrenadores'], ['GET', '/entrenadores/create'], ['GET', "/entrenadores/{$ent->id_entrenador}/edit"],
            ['POST', '/entrenadores'], ['PUT', "/entrenadores/{$ent->id_entrenador}"], ['DELETE', "/entrenadores/{$ent->id_entrenador}"],
            ['GET', '/recepcionistas'], ['GET', '/recepcionistas/create'], ['GET', "/recepcionistas/{$rec->id_recepcionista}/edit"],
            ['POST', '/recepcionistas'], ['PUT', "/recepcionistas/{$rec->id_recepcionista}"], ['DELETE', "/recepcionistas/{$rec->id_recepcionista}"],
            ['GET', '/administradores'], ['GET', '/administradores/create'], ['POST', '/administradores'],
            ['DELETE', "/administradores/{$adm->id_administrador}"],
        ] as [$metodo, $ruta]) {
            $this->call($metodo, $ruta, $this->payloadPersonal('Z'))->assertStatus(403, "{$rol} no debería poder {$metodo} {$ruta}");
        }

        $this->assertDatabaseCount('entrenadores', 1);
        $this->assertDatabaseCount('recepcionistas', 1);
        $this->assertDatabaseCount('administradores', 1);
        $this->assertNull(Recepcionista::first()->deleted_at);
        $this->assertNull(Entrenador::first()->deleted_at);
    }

    public function test_recepcionista_sigue_gestionando_clientes_y_pagos(): void
    {
        $this->actingAs($this->usuario('Recepcionista'));

        foreach (['/clientes', '/clientes/create', '/pagos', '/pagos/create', '/membresia_cliente', '/asistencias-qr', '/avisos'] as $ruta) {
            $this->get($ruta)->assertOk();
        }
    }

    public function test_admin_gestiona_personal_sin_problema(): void
    {
        $admin = $this->usuario('Administrador');
        $this->actingAs($admin);

        $this->get('/entrenadores')->assertOk();
        $this->get('/recepcionistas')->assertOk();
        $this->get('/administradores')->assertOk();

        // Alta
        $this->post('/recepcionistas', $this->payloadPersonal('R', ['turno' => 'tarde', 'punto_atencion' => 'Caja']))
            ->assertRedirect(route('recepcionistas.index'));
        $this->post('/entrenadores', $this->payloadPersonal('E', [
            'especialidad' => ['Calistenia'], 'experiencia' => '2 años', 'disponibilidad' => ['Lunes'],
        ]))->assertRedirect(route('entrenadores.index'));
        $this->assertDatabaseCount('recepcionistas', 1);
        $this->assertDatabaseCount('entrenadores', 1);

        // Edición y borrado
        $rec = Recepcionista::firstOrFail();
        $ent = Entrenador::firstOrFail();
        $this->get("/recepcionistas/{$rec->id_recepcionista}/edit")->assertOk();
        $this->get("/entrenadores/{$ent->id_entrenador}/edit")->assertOk();

        $this->delete("/recepcionistas/{$rec->id_recepcionista}")->assertRedirect(route('recepcionistas.index'));
        $this->delete("/entrenadores/{$ent->id_entrenador}")->assertRedirect(route('entrenadores.index'));
        $this->assertNotNull(Recepcionista::withTrashed()->find($rec->id_recepcionista)->deleted_at);
        $this->assertNull(Entrenador::find($ent->id_entrenador));
    }

    public function test_menu_oculta_personal_a_quien_ya_no_lo_tiene(): void
    {
        $urlEntrenadores = route('entrenadores.index');
        $urlRecepcionistas = route('recepcionistas.index');

        $htmlRecep = $this->actingAs($this->usuario('Recepcionista'))->get('/clientes')->assertOk()->getContent();
        $this->assertStringNotContainsString($urlEntrenadores, $htmlRecep);
        $this->assertStringNotContainsString($urlRecepcionistas, $htmlRecep);

        $htmlEntr = $this->actingAs($this->usuario('Entrenador'))->get('/clientes')->assertOk()->getContent();
        $this->assertStringNotContainsString($urlEntrenadores, $htmlEntr);

        $htmlAdmin = $this->actingAs($this->usuario('Administrador'))->get('/clientes')->assertOk()->getContent();
        $this->assertStringContainsString($urlEntrenadores, $htmlAdmin);
        $this->assertStringContainsString($urlRecepcionistas, $htmlAdmin);
    }
    // ── AJUSTES COSMÉTICOS ───────────────────────────────────────────────────

    public function test_desbloqueo_usa_color_positivo_y_el_borrado_sigue_en_rojo(): void
    {
        \App\Models\LoginLockout::create(['login' => 'a@test.com', 'ip' => '127.0.0.1', 'attempts' => 5, 'lockout_level' => 1]);
        $html = $this->actingAs($this->usuario('Administrador'))->get('/login_lockouts')->assertOk()->getContent();
        $this->assertStringContainsString('data-confirm-color="#198754"', $html);

        $js = file_get_contents(public_path('assets_private/js/app-ui.js'));
        $this->assertStringContainsString("form.getAttribute('data-confirm-color')", $js);
        // Sin data-confirm-color el diálogo de borrado conserva el rojo.
        $this->assertStringContainsString("positivo || '#f5365c'", $js);
        $this->assertStringContainsString("'btn btn-danger'", $js);
    }

    public function test_etiqueta_telefono_del_alta_de_cliente_usa_el_mismo_estilo_que_las_demas(): void
    {
        $html = $this->actingAs($this->usuario('Administrador'))->get('/clientes/create')->assertOk()->getContent();

        $this->assertStringContainsString('<label class="form-label">Teléfono</label>', $html);
        $this->assertStringNotContainsString('<label class="">Teléfono</label>', $html);
    }

    public function test_grupos_musculares_no_se_lista_en_el_menu_de_la_recepcionista_pero_si_en_el_resto(): void
    {
        $url = route('grupos_musculares.index');
        $horario = route('horarios_entrenadores.index');

        $recep = $this->actingAs($this->usuario('Recepcionista'))->get('/clientes')->assertOk()->getContent();
        $this->assertStringNotContainsString($url, $recep);
        $this->assertStringContainsString($horario, $recep); // el horario sí le sirve

        foreach (['Administrador', 'Entrenador'] as $rol) {
            $html = $this->actingAs($this->usuario($rol))->get('/clientes')->assertOk()->getContent();
            $this->assertStringContainsString($url, $html, $rol);
            $this->assertStringContainsString($horario, $html, $rol);
        }

        // El permiso de la ruta no cambió (solo se ocultó del menú).
        $this->actingAs($this->usuario('Recepcionista'))->get('/grupos_musculares')->assertOk();
    }
}
