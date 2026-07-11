<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Alta de cliente: username automático, dias_asistidos en 0, sin campo de
 * huella, teléfono boliviano normalizado a formato internacional, y
 * username editable (único) después de creado.
 */
class ClienteAltaTest extends TestCase
{
    use RefreshDatabase;

    private static int $seq = 0;

    private function operador(): User
    {
        $rol = Rol::firstOrCreate(['nombre' => 'Recepcionista']);
        return User::factory()->create(['rol_id' => $rol->id]);
    }

    private function payload(array $extra = []): array
    {
        self::$seq++;
        $s = self::$seq;

        return array_merge([
            'nombre_completo'       => 'Jhon',
            'apellido_paterno'      => 'Crespo',
            'apellido_materno'      => 'Coria',
            'ci'                    => "CIALTA{$s}",
            'telefono'              => '6' . str_pad((string) $s, 7, '0', STR_PAD_LEFT), // 8 dígitos, sin prefijo
            'genero'                => 'masculino',
            'fecha_nacimiento'      => '1995-03-10',
            'email'                 => "cliente{$s}@test.com",
            'password'              => 'secreto123',
            'password_confirmation' => 'secreto123',
            'estado'                => 'activo',
        ], $extra);
    }

    // ── 1. Username automático ────────────────────────────────────────────────

    public function test_username_se_genera_automaticamente(): void
    {
        $this->actingAs($this->operador())
             ->post('/clientes', $this->payload())
             ->assertRedirect(route('clientes.index'));

        $this->assertDatabaseHas('users', ['name' => 'jhon.crespo.c']);
    }

    public function test_username_duplicado_recibe_numero_incremental(): void
    {
        $this->actingAs($this->operador());

        $this->post('/clientes', $this->payload());
        $this->post('/clientes', $this->payload()); // mismo nombre, otra CI/email/teléfono

        $this->assertDatabaseHas('users', ['name' => 'jhon.crespo.c']);
        $this->assertDatabaseHas('users', ['name' => 'jhon.crespo.c2']);
    }

    public function test_username_sin_tildes_ni_espacios(): void
    {
        $this->actingAs($this->operador())
             ->post('/clientes', $this->payload([
                 'nombre_completo'  => 'José Ángel',
                 'apellido_paterno' => 'Pérez',
                 'apellido_materno' => 'Ñuñez',
             ]));

        $this->assertDatabaseHas('users', ['name' => 'jose.perez.n']);
    }

    public function test_username_enviado_a_mano_se_ignora(): void
    {
        $this->actingAs($this->operador())
             ->post('/clientes', $this->payload(['name' => 'hacker.manual']));

        $this->assertDatabaseMissing('users', ['name' => 'hacker.manual']);
        $this->assertDatabaseHas('users', ['name' => 'jhon.crespo.c']);
    }

    // ── 3 y 4. Días asistidos en 0 y sin huella ──────────────────────────────

    public function test_dias_asistidos_siempre_inicia_en_cero_aunque_manden_otro_valor(): void
    {
        $this->actingAs($this->operador())
             ->post('/clientes', $this->payload(['dias_asistidos' => 99]));

        $cliente = Cliente::firstOrFail();
        $this->assertSame(0, (int) $cliente->dias_asistidos);
    }

    public function test_huella_enviada_en_el_formulario_se_ignora(): void
    {
        $this->actingAs($this->operador())
             ->post('/clientes', $this->payload(['huella_digital' => 'hash-manual']));

        $cliente = Cliente::firstOrFail();
        $this->assertNull($cliente->huella_digital);
    }

    public function test_el_formulario_de_crear_no_muestra_campos_de_huella_ni_dias(): void
    {
        $this->actingAs($this->operador())
             ->get('/clientes/create')
             ->assertStatus(200)
             ->assertDontSee('name="huella_digital"', false)
             ->assertDontSee('name="dias_asistidos"', false)
             ->assertDontSee('name="name"', false);
    }

    // ── 5. Teléfono boliviano de 8 dígitos ────────────────────────────────────

    public function test_telefono_boliviano_de_8_digitos_se_guarda_en_formato_internacional(): void
    {
        $this->actingAs($this->operador())
             ->post('/clientes', $this->payload(['telefono' => '67893733']))
             ->assertRedirect(route('clientes.index'))
             ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('personas', ['telefono' => '+59167893733']);
    }

    public function test_telefono_con_prefijo_internacional_se_guarda_igual(): void
    {
        $this->actingAs($this->operador())
             ->post('/clientes', $this->payload(['telefono' => '+59171234567']))
             ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('personas', ['telefono' => '+59171234567']);
    }

    public function test_el_formulario_real_envia_codigo_y_numero_por_separado_sin_depender_de_js(): void
    {
        // Simula EXACTAMENTE lo que envía el navegador (incluso sin JavaScript):
        // los campos telefono_codigo y telefono_numero; NO existe campo telefono.
        $payload = $this->payload();
        unset($payload['telefono']);
        $payload['telefono_codigo'] = '+591';
        $payload['telefono_numero'] = '67893733';

        $this->actingAs($this->operador())
             ->post('/clientes', $payload)
             ->assertRedirect(route('clientes.index'))
             ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('personas', ['telefono' => '+59167893733']);
    }

    public function test_numero_pegado_con_prefijo_no_duplica_el_codigo(): void
    {
        // El operador pega "+591 78451236" en el campo del número
        $payload = $this->payload();
        unset($payload['telefono']);
        $payload['telefono_codigo'] = '+591';
        $payload['telefono_numero'] = '+591 78451236';

        $this->actingAs($this->operador())
             ->post('/clientes', $payload)
             ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('personas', ['telefono' => '+59178451236']);
    }

    // ── 2. Username editable después (propio y por el admin) ─────────────────

    public function test_un_usuario_edita_su_propio_username_desde_su_perfil(): void
    {
        $rol  = Rol::firstOrCreate(['nombre' => 'Entrenador']);
        $user = User::factory()->create(['rol_id' => $rol->id, 'name' => 'nombre.viejo']);

        $this->actingAs($user)
             ->patch('/profile', ['name' => 'nombre.nuevo', 'email' => $user->email])
             ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'nombre.nuevo']);
    }

    public function test_no_se_permite_un_username_duplicado_al_editar_perfil(): void
    {
        $rol = Rol::firstOrCreate(['nombre' => 'Entrenador']);
        User::factory()->create(['rol_id' => $rol->id, 'name' => 'ya.existe']);
        $user = User::factory()->create(['rol_id' => $rol->id, 'name' => 'otro.nombre']);

        $this->actingAs($user)
             ->patch('/profile', ['name' => 'ya.existe', 'email' => $user->email])
             ->assertSessionHasErrors('name');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'otro.nombre']);
    }

    public function test_al_editar_cliente_el_username_debe_ser_unico(): void
    {
        $this->actingAs($this->operador());

        $this->post('/clientes', $this->payload());                      // jhon.crespo.c
        $this->post('/clientes', $this->payload(['nombre_completo' => 'Mario'])); // mario.crespo.c

        $cliente = Cliente::with('persona', 'user')
            ->whereHas('user', fn ($q) => $q->where('name', 'mario.crespo.c'))
            ->firstOrFail();

        $respuesta = $this->put('/clientes/' . $cliente->id_cliente, [
            'nombre_completo'  => $cliente->persona->nombre_completo,
            'apellido_paterno' => $cliente->persona->apellido_paterno,
            'apellido_materno' => $cliente->persona->apellido_materno,
            'ci'               => $cliente->persona->ci,
            'telefono'         => $cliente->persona->telefono,
            'genero'           => 'masculino',
            'fecha_nacimiento' => '1995-03-10',
            'name'             => 'jhon.crespo.c', // ya lo usa otro usuario
            'email'            => $cliente->user->email,
            'dias_asistidos'   => 0,
            'estado'           => 'activo',
        ]);

        $respuesta->assertSessionHasErrors('name');
    }
}
