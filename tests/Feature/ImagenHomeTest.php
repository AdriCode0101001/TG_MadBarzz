<?php

namespace Tests\Feature;

use App\Models\ConfiguracionImagen;
use App\Models\Rol;
use App\Models\User;
use App\Services\ImagenHomeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Imágenes de contenido de la home pública: solo el Administrador las sube/
 * reemplaza, se guardan en el disco 'public', y la home usa el placeholder
 * de siempre para cualquier clave que no tenga imagen subida.
 */
class ImagenHomeTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(string $rol): User
    {
        return User::factory()->create(['rol_id' => Rol::firstOrCreate(['nombre' => $rol])->id]);
    }

    private function subirImagen(User $admin, string $clave, ?UploadedFile $archivo = null)
    {
        return $this->actingAs($admin)->post("/configuracion/imagenes-home/{$clave}", [
            'imagen' => $archivo ?? UploadedFile::fake()->image('foto.jpg'),
        ]);
    }

    // ── 1. Solo el Administrador sube/reemplaza ──────────────────────────────

    public function test_administrador_puede_subir_una_imagen_de_la_home(): void
    {
        Storage::fake('public');
        $admin = $this->usuario('Administrador');

        $this->subirImagen($admin, 'home_cta')
             ->assertRedirect(route('configuracion.imagenes_home.index'));

        $config = ConfiguracionImagen::where('clave', 'home_cta')->firstOrFail();
        Storage::disk('public')->assertExists($config->ruta);
        $this->assertSame($admin->id, $config->id_usuario);
    }

    public function test_administrador_puede_reemplazar_una_imagen_ya_subida(): void
    {
        Storage::fake('public');
        $admin = $this->usuario('Administrador');

        $this->subirImagen($admin, 'home_cta');
        $primero = ConfiguracionImagen::where('clave', 'home_cta')->firstOrFail();
        $rutaAnterior = $primero->ruta;

        $this->subirImagen($admin, 'home_cta');

        $this->assertDatabaseCount('configuracion_imagenes', 1); // se reemplaza, no se acumula
        $actual = ConfiguracionImagen::where('clave', 'home_cta')->firstOrFail();
        $this->assertNotSame($rutaAnterior, $actual->ruta);
        Storage::disk('public')->assertMissing($rutaAnterior); // no deja basura del archivo viejo
        Storage::disk('public')->assertExists($actual->ruta);
    }

    public function test_otros_roles_no_acceden_a_la_gestion_de_imagenes_de_la_home(): void
    {
        Storage::fake('public');

        foreach (['Recepcionista', 'Entrenador', 'Cliente'] as $rol) {
            $usuario = $this->usuario($rol);
            $this->actingAs($usuario)->get('/configuracion/imagenes-home')->assertStatus(403);
            $this->subirImagen($usuario, 'home_cta')->assertStatus(403);
        }
    }

    public function test_invitado_no_accede_a_la_gestion_de_imagenes_de_la_home(): void
    {
        $this->get('/configuracion/imagenes-home')->assertRedirect(route('login'));
    }

    // ── 2. Validación de archivo ──────────────────────────────────────────────

    public function test_rechaza_un_archivo_que_no_es_imagen(): void
    {
        Storage::fake('public');
        $admin = $this->usuario('Administrador');

        $this->subirImagen($admin, 'home_cta', UploadedFile::fake()->create('documento.pdf', 100))
             ->assertSessionHasErrors('imagen');

        $this->assertDatabaseCount('configuracion_imagenes', 0);
    }

    public function test_rechaza_una_imagen_que_supera_el_tamano_maximo(): void
    {
        Storage::fake('public');
        $admin = $this->usuario('Administrador');

        $this->subirImagen($admin, 'home_cta', UploadedFile::fake()->image('grande.jpg')->size(3000))
             ->assertSessionHasErrors('imagen');

        $this->assertDatabaseCount('configuracion_imagenes', 0);
    }

    public function test_rechaza_una_clave_que_no_existe(): void
    {
        Storage::fake('public');
        $admin = $this->usuario('Administrador');

        $this->subirImagen($admin, 'clave_inventada')->assertStatus(404);
    }

    // ── 3. La pantalla de gestión ─────────────────────────────────────────────

    public function test_la_pantalla_de_gestion_muestra_las_claves_agrupadas(): void
    {
        Storage::fake('public');
        $admin = $this->usuario('Administrador');

        $this->actingAs($admin)
             ->get('/configuracion/imagenes-home')
             ->assertStatus(200)
             ->assertSee('Hero / Carrusel')
             ->assertSee('Nosotros / Servicios')
             ->assertSee('Sin foto — usando placeholder');
    }

    /**
     * Regresión: agrupar con collect()->groupBy() sin preserveKeys=true
     * reindexa cada grupo con 0,1,2... y se pierde la clave real — el
     * formulario de cada tarjeta terminaba apuntando a
     * "/configuracion/imagenes-home/0" en vez de "/home_hero_1", así que
     * CUALQUIER subida real por la pantalla fallaba con 404.
     */
    public function test_cada_tarjeta_apunta_a_su_clave_real_no_a_un_indice_numerico(): void
    {
        Storage::fake('public');
        $admin = $this->usuario('Administrador');

        $this->actingAs($admin)
             ->get('/configuracion/imagenes-home')
             ->assertStatus(200)
             ->assertSee(route('configuracion.imagenes_home.update', 'home_hero_1'), false)
             ->assertSee(route('configuracion.imagenes_home.update', 'home_cta'), false)
             ->assertDontSee(route('configuracion.imagenes_home.update', '0'), false);
    }

    // ── 4. ImagenHomeService: la home usa la imagen subida o el placeholder ──

    public function test_el_servicio_devuelve_el_placeholder_cuando_no_hay_imagen_subida(): void
    {
        Storage::fake('public');
        $servicio = new ImagenHomeService();

        $this->assertSame(
            'https://via.placeholder.com/1920x770',
            $servicio->url('home_hero_1', 'https://via.placeholder.com/1920x770')
        );
    }

    public function test_el_servicio_devuelve_la_url_real_cuando_hay_imagen_subida(): void
    {
        Storage::fake('public');
        $admin = $this->usuario('Administrador');
        $this->subirImagen($admin, 'home_hero_1');

        $servicio = new ImagenHomeService();
        $url = $servicio->url('home_hero_1', 'https://via.placeholder.com/1920x770');

        $this->assertNotSame('https://via.placeholder.com/1920x770', $url);
        $this->assertStringContainsString('/storage/home/', $url);
    }

    public function test_el_servicio_devuelve_el_placeholder_si_el_archivo_ya_no_esta_en_disco(): void
    {
        Storage::fake('public');
        $admin = $this->usuario('Administrador');
        $this->subirImagen($admin, 'home_hero_1');

        // Se borra el archivo por fuera (o el disco se limpia) sin borrar la fila
        Storage::disk('public')->deleteDirectory('home');

        $servicio = new ImagenHomeService();
        $this->assertSame(
            'https://via.placeholder.com/1920x770',
            $servicio->url('home_hero_1', 'https://via.placeholder.com/1920x770')
        );
    }

    public function test_la_home_publica_muestra_el_placeholder_cuando_nada_fue_subido(): void
    {
        Storage::fake('public');

        // El placeholder es un SVG local (images/placeholder-home.svg) desde
        // el arreglo de privacidad que sacó la dependencia de
        // via.placeholder.com — ver welcome-gymon.blade.php.
        $this->get('/')
             ->assertStatus(200)
             ->assertSee('images/placeholder-home.svg', false);
    }

    public function test_la_home_publica_muestra_la_imagen_subida_en_vez_del_placeholder(): void
    {
        Storage::fake('public');
        $admin = $this->usuario('Administrador');
        $this->subirImagen($admin, 'home_cta');

        $config = ConfiguracionImagen::where('clave', 'home_cta')->firstOrFail();
        $urlEsperada = Storage::disk('public')->url($config->ruta);

        $this->get('/')
             ->assertStatus(200)
             ->assertSee($urlEsperada, false);
    }

    // ── 5. Reencuadre (posición del recorte) ─────────────────────────────────

    public function test_administrador_puede_elegir_la_posicion_del_recorte_al_subir(): void
    {
        Storage::fake('public');
        $admin = $this->usuario('Administrador');

        $this->actingAs($admin)->post('/configuracion/imagenes-home/home_hero_1', [
            'imagen'   => UploadedFile::fake()->image('foto.jpg'),
            'posicion' => 'arriba',
        ])->assertRedirect(route('configuracion.imagenes_home.index'));

        $config = ConfiguracionImagen::where('clave', 'home_hero_1')->firstOrFail();
        $this->assertSame('arriba', $config->posicion);
    }

    public function test_administrador_puede_cambiar_solo_la_posicion_sin_volver_a_subir_la_imagen(): void
    {
        Storage::fake('public');
        $admin = $this->usuario('Administrador');

        $this->subirImagen($admin, 'home_hero_1'); // sube sin especificar posición (default)
        $original = ConfiguracionImagen::where('clave', 'home_hero_1')->firstOrFail();

        $this->actingAs($admin)->post('/configuracion/imagenes-home/home_hero_1', [
            'posicion' => 'abajo',
        ])->assertRedirect(route('configuracion.imagenes_home.index'));

        $actual = ConfiguracionImagen::where('clave', 'home_hero_1')->firstOrFail();
        $this->assertSame('abajo', $actual->posicion);
        $this->assertSame($original->ruta, $actual->ruta); // la foto no cambió
        Storage::disk('public')->assertExists($actual->ruta);
    }

    public function test_rechaza_una_posicion_invalida(): void
    {
        Storage::fake('public');
        $admin = $this->usuario('Administrador');

        $this->actingAs($admin)->post('/configuracion/imagenes-home/home_hero_1', [
            'imagen'   => UploadedFile::fake()->image('foto.jpg'),
            'posicion' => 'diagonal',
        ])->assertSessionHasErrors('posicion');
    }

    public function test_las_claves_sin_recorte_real_no_aceptan_posicion(): void
    {
        Storage::fake('public');
        $admin = $this->usuario('Administrador');

        foreach (['home_cta', 'home_modal_unete'] as $clave) {
            $this->assertFalse(ImagenHomeService::permiteReencuadre($clave));

            $this->actingAs($admin)->post("/configuracion/imagenes-home/{$clave}", [
                'imagen'   => UploadedFile::fake()->image('foto.jpg'),
                'posicion' => 'arriba',
            ])->assertSessionHasErrors('posicion');
        }
    }

    public function test_debe_venir_imagen_o_posicion(): void
    {
        Storage::fake('public');
        $admin = $this->usuario('Administrador');

        $this->actingAs($admin)->post('/configuracion/imagenes-home/home_hero_1', [])
             ->assertSessionHasErrors('imagen');

        $this->assertDatabaseCount('configuracion_imagenes', 0);
    }

    public function test_posicion_por_defecto_es_centro_cuando_nunca_se_configuro(): void
    {
        $this->assertSame(
            'center center',
            ImagenHomeService::posicionCss(ImagenHomeService::posicionDe(null, 'home_hero_1'))
        );
    }

    public function test_la_home_aplica_la_posicion_elegida_en_el_estilo_inline(): void
    {
        Storage::fake('public');
        $admin = $this->usuario('Administrador');

        $this->actingAs($admin)->post('/configuracion/imagenes-home/home_nosotros_calistenia', [
            'imagen'   => UploadedFile::fake()->image('foto.jpg'),
            'posicion' => 'arriba',
        ]);

        $this->get('/')
             ->assertStatus(200)
             ->assertSee('object-position: center top', false);
    }

    public function test_la_pantalla_solo_muestra_selector_de_posicion_en_claves_con_recorte_real(): void
    {
        Storage::fake('public');
        $admin = $this->usuario('Administrador');

        $response = $this->actingAs($admin)->get('/configuracion/imagenes-home');
        $response->assertStatus(200);

        // Un <select> de posición por cada una de las 9 claves con recorte
        // real (8 de la home + el fondo del login), y ninguno para las que se
        // muestran completas (home_cta, home_modal_unete y los 2 logos).
        // Cuenta solo el TAG <select ...name="posicion"...>, no cualquier
        // aparición del texto (el CSS de arreglo del ícono también lo
        // menciona, en el selector "select[name=\"posicion\"]").
        $cantidadSelects = preg_match_all('/<select[^>]*name="posicion"/', $response->getContent());
        $this->assertSame(9, $cantidadSelects);
    }

    // ── Logos de MadBarzz (emblema y logo de texto) ──────────────────────────

    public function test_la_pantalla_del_admin_tiene_el_apartado_logo_de_madbarzz(): void
    {
        $this->actingAs($this->usuario('Administrador'))
            ->get(route('configuracion.imagenes_home.index'))
            ->assertOk()
            ->assertSee('Logo de MadBarzz')
            ->assertSee(route('configuracion.imagenes_home.update', 'home_logo_emblema'), false)
            ->assertSee(route('configuracion.imagenes_home.update', 'home_logo_texto'), false);
    }

    public function test_sin_subir_nada_la_home_usa_los_logos_de_siempre(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('assets_private/images/logo_mad.png', $html);
        $this->assertStringContainsString('assets/images/log_text_mad.png', $html);
    }

    public function test_los_logos_subidos_reemplazan_a_los_de_siempre_en_la_home(): void
    {
        Storage::fake('public');
        $admin = $this->usuario('Administrador');

        $this->subirImagen($admin, 'home_logo_emblema')->assertRedirect(route('configuracion.imagenes_home.index'));
        $this->subirImagen($admin, 'home_logo_texto')->assertRedirect(route('configuracion.imagenes_home.index'));

        $emblema = ConfiguracionImagen::where('clave', 'home_logo_emblema')->firstOrFail();
        $texto   = ConfiguracionImagen::where('clave', 'home_logo_texto')->firstOrFail();

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString(Storage::disk('public')->url($emblema->ruta), $html);
        $this->assertStringContainsString(Storage::disk('public')->url($texto->ruta), $html);
        // El emblema del header ya no es el archivo fijo.
        $this->assertDoesNotMatchRegularExpression('#assets_private/images/logo_mad\.png"\s+alt="MadBarzz"\s+class="mb-header-logo-img"#', $html);
    }

    public function test_solo_el_administrador_cambia_los_logos(): void
    {
        Storage::fake('public');

        $this->subirImagen($this->usuario('Recepcionista'), 'home_logo_emblema')->assertForbidden();
        $this->assertNull(ConfiguracionImagen::where('clave', 'home_logo_emblema')->first());
    }

    // ── Imagen de Inicio de sesión ───────────────────────────────────────────

    public function test_la_pantalla_del_admin_tiene_el_apartado_inicio_de_sesion(): void
    {
        $this->actingAs($this->usuario('Administrador'))
            ->get(route('configuracion.imagenes_home.index'))
            ->assertOk()
            ->assertSee('Inicio de sesión')
            ->assertSee(route('configuracion.imagenes_home.update', 'login_fondo'), false);
    }

    public function test_sin_subir_nada_el_login_usa_la_imagen_de_siempre(): void
    {
        foreach (['/login', '/forgot-password'] as $ruta) {
            $this->get($ruta)->assertOk()->assertSee('assets/images/login-bg.jpg', false);
        }
    }

    public function test_la_imagen_subida_reemplaza_el_fondo_del_login_con_su_encuadre(): void
    {
        Storage::fake('public');
        $admin = $this->usuario('Administrador');

        $this->actingAs($admin)->post('/configuracion/imagenes-home/login_fondo', [
            'imagen'   => UploadedFile::fake()->image('fondo.jpg'),
            'posicion' => 'arriba',
        ])->assertRedirect(route('configuracion.imagenes_home.index'));

        $config = ConfiguracionImagen::where('clave', 'login_fondo')->firstOrFail();
        $url = Storage::disk('public')->url($config->ruta);

        auth()->logout();

        foreach (['/login', '/forgot-password'] as $ruta) {
            $html = $this->get($ruta)->assertOk()->getContent();
            $this->assertStringContainsString($url, $html);
            $this->assertStringContainsString('background-position: center top', $html);
            $this->assertStringNotContainsString('assets/images/login-bg.jpg', $html);
        }
    }

    public function test_solo_el_administrador_cambia_la_imagen_del_login(): void
    {
        Storage::fake('public');

        $this->subirImagen($this->usuario('Recepcionista'), 'login_fondo')->assertForbidden();
        $this->assertNull(ConfiguracionImagen::where('clave', 'login_fondo')->first());
    }

    // ── Los logos también cambian en el panel y en las pantallas de acceso ───

    public function test_los_logos_subidos_tambien_cambian_en_el_panel_y_en_el_login(): void
    {
        Storage::fake('public');
        $admin = $this->usuario('Administrador');

        // Sin subir nada: los de siempre en el encabezado del panel.
        $html = $this->actingAs($admin)->get('/profile')->assertOk()->getContent();
        $this->assertMatchesRegularExpression('#class="logo-abbr" src="[^"]*assets_private/images/logo_mad\.png"#', $html);
        $this->assertMatchesRegularExpression('#class="brand-title" src="[^"]*log_text_mad\.png"#', $html);

        $this->subirImagen($admin, 'home_logo_emblema');
        $this->subirImagen($admin, 'home_logo_texto');
        $emblema = Storage::disk('public')->url(ConfiguracionImagen::where('clave', 'home_logo_emblema')->firstOrFail()->ruta);
        $texto   = Storage::disk('public')->url(ConfiguracionImagen::where('clave', 'home_logo_texto')->firstOrFail()->ruta);

        $html = $this->actingAs($admin)->get('/profile')->assertOk()->getContent();
        $this->assertStringContainsString('class="logo-abbr" src="' . $emblema . '"', $html);
        $this->assertStringContainsString('class="logo-compact" src="' . $texto . '"', $html);
        $this->assertStringContainsString('class="brand-title" src="' . $texto . '"', $html);

        auth()->logout();
        $this->get('/login')->assertOk()->assertSee($texto, false);
        $this->get('/forgot-password')->assertOk()->assertSee($texto, false);
    }
}
