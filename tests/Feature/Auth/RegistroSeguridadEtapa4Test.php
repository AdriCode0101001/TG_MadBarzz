<?php

namespace Tests\Feature\Auth;

use App\Http\Controllers\Auth\RegisteredUserController;
use App\Models\Persona;
use App\Models\Rol;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Etapa 4 del auto-registro: endurecimiento de seguridad, ya con el
 * registro público REACTIVADO (el bloqueo de la Etapa 0 se quitó).
 *
 * Como en RegistroPublicoClienteTest y VerificacionEmailEtapa3Test, se usa
 * una ruta propia registrada en el proceso de test, con la misma cadena de
 * middleware que la ruta real ('web' + 'throttle:register'), para probar el
 * controlador/servicio en aislado sin pisarse con otros tests que también
 * pegan contra /register.
 */
class RegistroSeguridadEtapa4Test extends TestCase
{
    use RefreshDatabase;

    private static int $seq = 0;

    protected function setUp(): void
    {
        parent::setUp();

        Route::post('/test-solo/registro-publico', [RegisteredUserController::class, 'store'])
            ->middleware(['web', 'throttle:register']);

        // El limitador de 'register' cuenta contra la IP del cliente de
        // test, que es la misma en todos los métodos de esta clase — sin
        // limpiar el cache entre tests, los intentos se acumulan de un test
        // al siguiente y un test que no tiene nada que ver con el límite
        // podría recibir 429 solo por los tests que corrieron antes.
        Cache::flush();
    }

    private function payload(array $extra = []): array
    {
        self::$seq++;
        $s = self::$seq;

        $sede = Sede::create(['nombre' => "Sede Etapa4 {$s}", 'direccion' => 'Calle 1', 'activo' => true]);

        return array_merge([
            'nombre_completo'       => 'Marco',
            'apellido_paterno'      => 'Villca',
            'apellido_materno'      => 'Quispe',
            'ci'                    => "CISEG{$s}",
            'telefono'              => '7' . str_pad((string) $s, 7, '0', STR_PAD_LEFT),
            'genero'                => 'masculino',
            'fecha_nacimiento'      => '1994-08-10',
            'id_sede'               => $sede->id_sede,
            'email'                 => "seguridad{$s}@test.com",
            'password'              => 'Contrasena123',
            'password_confirmation' => 'Contrasena123',
        ], $extra);
    }

    private function postearRegistro(array $payload)
    {
        return $this->post('/test-solo/registro-publico', $payload);
    }

    // ── 1. RATE LIMITING ─────────────────────────────────────────────────────

    public function test_bloquea_mas_de_5_intentos_de_registro_por_minuto_desde_la_misma_ip(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            // El límite corre ANTES de la validación: un payload vacío alcanza
            // para gastar los 5 intentos permitidos.
            $this->postearRegistro([])->assertStatus(302);
        }

        $this->postearRegistro([])->assertStatus(429);
    }

    public function test_un_sexto_intento_valido_tambien_es_bloqueado_por_el_limite(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->postearRegistro([]);
        }

        // Ni siquiera un registro con datos perfectamente válidos pasa: el
        // límite es por IP, no depende de si los datos están bien.
        $this->postearRegistro($this->payload())->assertStatus(429);
        $this->assertDatabaseCount('clientes', 0);
    }

    // ── 2. Controles ya existentes (confirmación) ────────────────────────────

    public function test_rechaza_ci_email_y_telefono_duplicados(): void
    {
        Persona::create([
            'nombre_completo' => 'Otra', 'apellido_paterno' => 'Persona', 'apellido_materno' => 'Ya',
            'ci' => 'CIDUPSEG1', 'telefono' => '+59179999999', 'genero' => 'masculino', 'fecha_nacimiento' => '1990-01-01',
        ]);
        $rol = Rol::firstOrCreate(['nombre' => 'Cliente']);
        User::factory()->create(['rol_id' => $rol->id, 'email' => 'yaexisteseg@test.com']);

        $this->postearRegistro($this->payload(['ci' => 'CIDUPSEG1']))->assertSessionHasErrors('ci');
        $this->postearRegistro($this->payload(['email' => 'yaexisteseg@test.com']))->assertSessionHasErrors('email');
        $this->postearRegistro($this->payload(['telefono' => '79999999']))->assertSessionHasErrors('telefono');

        $this->assertDatabaseCount('clientes', 0);
    }

    public function test_es_imposible_forzar_un_rol_distinto_de_cliente(): void
    {
        $rolAdmin = Rol::firstOrCreate(['nombre' => 'Administrador']);

        $this->postearRegistro($this->payload([
            'email'  => 'noadminseg@test.com',
            'rol_id' => $rolAdmin->id,
            'rol'    => 'Administrador',
        ]));

        $usuario = User::where('email', 'noadminseg@test.com')->first();
        $this->assertNotNull($usuario);
        $this->assertSame(Rol::firstOrCreate(['nombre' => 'Cliente'])->id, $usuario->rol_id);
    }

    public function test_rechaza_password_debil(): void
    {
        $this->postearRegistro($this->payload(['password' => '123', 'password_confirmation' => '123']))
            ->assertSessionHasErrors('password');
    }

    public function test_verificacion_de_email_sigue_exigida_tras_registrarse(): void
    {
        $usuario = User::where('email', 'noexiste-todavia@test.com')->first();
        $this->assertNull($usuario);

        $this->postearRegistro($this->payload(['email' => 'verificarseg@test.com']));

        $usuario = User::where('email', 'verificarseg@test.com')->firstOrFail();
        $this->assertNull($usuario->email_verified_at);
        $this->assertFalse($usuario->hasVerifiedEmail());
    }

    // ── 3. Validación robusta de datos ───────────────────────────────────────

    public function test_rechaza_email_mal_formado(): void
    {
        $this->postearRegistro($this->payload(['email' => 'no-es-un-correo']))
            ->assertSessionHasErrors('email');
    }

    public function test_rechaza_fecha_de_nacimiento_futura(): void
    {
        $this->postearRegistro($this->payload(['fecha_nacimiento' => now()->addYear()->toDateString()]))
            ->assertSessionHasErrors('fecha_nacimiento');
    }

    public function test_rechaza_menor_de_la_edad_minima(): void
    {
        $this->postearRegistro($this->payload(['fecha_nacimiento' => now()->subYears(10)->toDateString()]))
            ->assertSessionHasErrors('fecha_nacimiento');
    }

    public function test_acepta_justo_en_la_edad_minima(): void
    {
        $this->postearRegistro($this->payload([
            'email'            => 'edadminimaseg@test.com',
            'fecha_nacimiento' => now()->subYears(14)->subDay()->toDateString(),
        ]))->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('users', ['email' => 'edadminimaseg@test.com']);
    }

    public function test_rechaza_sede_inexistente(): void
    {
        $this->postearRegistro($this->payload(['id_sede' => 999999]))
            ->assertSessionHasErrors('id_sede');
    }

    public function test_rechaza_sede_inactiva(): void
    {
        $sedeInactiva = Sede::create(['nombre' => 'Sede Baja', 'direccion' => 'Calle X', 'activo' => false]);

        $this->postearRegistro($this->payload(['id_sede' => $sedeInactiva->id_sede]))
            ->assertSessionHasErrors('id_sede');

        $this->assertDatabaseCount('clientes', 0);
    }

    public function test_rechaza_genero_fuera_de_la_lista_valida(): void
    {
        $this->postearRegistro($this->payload(['genero' => 'robot']))
            ->assertSessionHasErrors('genero');
    }

    public function test_rechaza_nombre_con_numeros_o_simbolos(): void
    {
        $this->postearRegistro($this->payload(['nombre_completo' => '<script>alert(1)</script>']))
            ->assertSessionHasErrors('nombre_completo');

        $this->postearRegistro($this->payload(['apellido_paterno' => 'Perez123']))
            ->assertSessionHasErrors('apellido_paterno');
    }

    public function test_rechaza_ci_con_formato_invalido(): void
    {
        $this->postearRegistro($this->payload(['ci' => 'a']))->assertSessionHasErrors('ci'); // muy corto
        $this->postearRegistro($this->payload(['ci' => "1234; DROP TABLE users;--"]))->assertSessionHasErrors('ci');
    }

    // ── 5. El registro público real ya funciona (y con su propio límite) ────

    public function test_el_registro_publico_real_funciona_con_su_propio_rate_limit(): void
    {
        Cache::flush();

        $this->get(route('register'))
            ->assertStatus(200)
            ->assertSee('name="nombre_completo"', false);

        $this->post(route('register'), $this->payload(['email' => 'realseg@test.com']))
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('users', ['email' => 'realseg@test.com']);

        // El límite de la ruta real es el mismo 'throttle:register': agotar
        // los 5 restantes también lo bloquea.
        for ($i = 1; $i <= 4; $i++) {
            $this->post(route('register'), []);
        }
        $this->post(route('register'), [])->assertStatus(429);
    }
}
