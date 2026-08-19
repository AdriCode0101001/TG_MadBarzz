<?php

namespace Tests\Feature;

use App\Models\Aviso;
use App\Models\AvisoCliente;
use App\Models\Cliente;
use App\Models\Persona;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Campanita de Avisos (Etapa 2): SOLO el rol Cliente la ve en el header
 * compartido (layouts/private.blade.php). El riesgo principal de esta etapa
 * es que ese layout es compartido por TODOS los roles — estos tests
 * verifican explícitamente que Admin/Recepcionista/Entrenador NO la ven y
 * que sus pantallas principales siguen renderizando bien.
 */
class AvisoCampanitaTest extends TestCase
{
    use RefreshDatabase;

    private static int $seq = 0;

    private function usuario(string $rol): User
    {
        return User::factory()->create(['rol_id' => Rol::firstOrCreate(['nombre' => $rol])->id]);
    }

    private function crearCliente(): Cliente
    {
        self::$seq++;
        $s = self::$seq;

        $persona = Persona::create([
            'nombre_completo'  => "Cliente Campanita {$s}",
            'apellido_paterno' => 'Test',
            'apellido_materno' => 'Test',
            'ci'               => "CICAMP{$s}",
            'telefono'         => "+59173000{$s}",
            'genero'           => 'masculino',
            'fecha_nacimiento' => '1990-01-01',
        ]);

        $user = User::factory()->create(['rol_id' => Rol::firstOrCreate(['nombre' => 'Cliente'])->id]);

        return Cliente::create([
            'id_persona'     => $persona->id_persona,
            'id_usuario'     => $user->id,
            'estado'         => 'activo',
            'dias_asistidos' => 0,
        ]);
    }

    private function crearAviso(string $titulo = 'Aviso de prueba'): Aviso
    {
        $admin = $this->usuario('Administrador');

        return Aviso::create([
            'titulo'     => $titulo,
            'texto'      => 'Texto de prueba para el aviso.',
            'id_usuario' => $admin->id,
        ]);
    }

    // ── 1. El cliente ve la campanita con el contador correcto ───────────────

    public function test_cliente_ve_la_campanita_con_el_contador_correcto_de_no_leidos(): void
    {
        $cliente = $this->crearCliente();

        $aviso1 = $this->crearAviso('Aviso uno');
        $aviso2 = $this->crearAviso('Aviso dos');
        $aviso3 = $this->crearAviso('Aviso tres');

        // El cliente ya leyó uno de los tres -> deberían quedar 2 no leídos
        AvisoCliente::create(['id_aviso' => $aviso1->id, 'id_cliente' => $cliente->id_cliente]);

        $response = $this->actingAs($cliente->user)->get('/dashboard/cliente');
        $response->assertStatus(200);
        $response->assertSee('id="mbAvisosMenu"', false);

        // El badge existe y su texto (recortado) es "2" — sin asumir que
        // Blade lo imprime pegado a los tags (queda con espacios/saltos de
        // línea alrededor por cómo está formateado el <span> en varias líneas).
        preg_match('/bg-danger"[^>]*>(.*?)<\/span>/s', $response->getContent(), $match);
        $this->assertNotEmpty($match, 'No se encontró el badge de no leídos.');
        $this->assertSame('2', trim($match[1]));
    }

    public function test_cliente_sin_avisos_no_leidos_no_muestra_el_badge(): void
    {
        $cliente = $this->crearCliente();
        $aviso = $this->crearAviso();
        AvisoCliente::create(['id_aviso' => $aviso->id, 'id_cliente' => $cliente->id_cliente]);

        $response = $this->actingAs($cliente->user)->get('/dashboard/cliente');

        $response->assertStatus(200)->assertSee('mbAvisosMenu', false);
        $response->assertDontSee('badge rounded-pill bg-danger', false);
    }

    // ── 2. El dropdown lista los avisos existentes ───────────────────────────

    public function test_el_dropdown_lista_los_avisos_existentes_para_el_cliente(): void
    {
        $cliente = $this->crearCliente();
        $this->crearAviso('Feriado nacional');
        $this->crearAviso('Nueva clase de spinning');

        $this->actingAs($cliente->user)
             ->get('/dashboard/cliente')
             ->assertStatus(200)
             ->assertSee('Feriado nacional')
             ->assertSee('Nueva clase de spinning');
    }

    public function test_el_dropdown_muestra_mensaje_cuando_no_hay_avisos(): void
    {
        $cliente = $this->crearCliente();

        $this->actingAs($cliente->user)
             ->get('/dashboard/cliente')
             ->assertStatus(200)
             ->assertSee('No tenés avisos.');
    }

    // ── 3. CRÍTICO: los demás roles NO ven la campanita ──────────────────────

    public function test_administrador_no_ve_la_campanita(): void
    {
        $this->crearAviso(); // que existan avisos no cambia nada para el admin

        $admin = $this->usuario('Administrador');

        $response = $this->actingAs($admin)->get('/dashboard/index');

        $response->assertStatus(200);
        // "id=\"mbAvisosMenu\"" y aria-label="Avisos" solo existen dentro del
        // bloque @if de la campanita — a diferencia de "mbToggleAvisos" (el
        // NOMBRE de la función JS), que está definida una sola vez al final
        // del body y se carga en TODAS las páginas sin importar el rol, así
        // que buscar ese string solo no sirve para distinguir si la
        // campanita se renderizó o no.
        $response->assertDontSee('id="mbAvisosMenu"', false);
        $response->assertDontSee('aria-label="Avisos"', false);
    }

    public function test_recepcionista_no_ve_la_campanita(): void
    {
        $this->crearAviso();

        $recepcionista = $this->usuario('Recepcionista');

        $response = $this->actingAs($recepcionista)->get('/dashboard/index');

        $response->assertStatus(200);
        $response->assertDontSee('id="mbAvisosMenu"', false);
        $response->assertDontSee('aria-label="Avisos"', false);
    }

    public function test_entrenador_no_ve_la_campanita(): void
    {
        $this->crearAviso();

        $entrenador = $this->usuario('Entrenador');

        $response = $this->actingAs($entrenador)->get('/dashboard/entrenador');

        $response->assertStatus(200);
        $response->assertDontSee('id="mbAvisosMenu"', false);
        $response->assertDontSee('aria-label="Avisos"', false);
    }

    /**
     * El requisito explícito era "cero consultas extra para los otros
     * roles": se verifica que, al cargar su dashboard, no corre NINGUNA
     * consulta contra las tablas avisos/aviso_cliente.
     */
    public function test_no_corre_ninguna_consulta_de_avisos_para_el_administrador(): void
    {
        $this->crearAviso();
        $admin = $this->usuario('Administrador');

        $queries = [];
        DB::listen(function ($query) use (&$queries) {
            $queries[] = $query->sql;
        });

        $this->actingAs($admin)->get('/dashboard/index')->assertStatus(200);

        $tocoAvisos = collect($queries)->contains(function ($sql) {
            return str_contains($sql, 'avisos') || str_contains($sql, 'aviso_cliente');
        });

        $this->assertFalse($tocoAvisos, 'No debería haber ninguna consulta a avisos/aviso_cliente para el Administrador.');
    }

    // ── 4. Ningún panel queda roto ────────────────────────────────────────────

    public function test_el_panel_de_administrador_sigue_funcionando_bien(): void
    {
        $admin = $this->usuario('Administrador');

        $this->actingAs($admin)->get('/dashboard/index')->assertStatus(200);
        $this->actingAs($admin)->get('/clientes')->assertStatus(200);
        $this->actingAs($admin)->get('/avisos')->assertStatus(200);
    }

    public function test_el_panel_de_recepcionista_sigue_funcionando_bien(): void
    {
        $recepcionista = $this->usuario('Recepcionista');

        $this->actingAs($recepcionista)->get('/dashboard/index')->assertStatus(200);
        $this->actingAs($recepcionista)->get('/clientes')->assertStatus(200);
    }

    public function test_el_panel_de_entrenador_sigue_funcionando_bien(): void
    {
        $entrenador = $this->usuario('Entrenador');

        $this->actingAs($entrenador)->get('/dashboard/entrenador')->assertStatus(200);
    }

    public function test_el_portal_del_cliente_sigue_funcionando_bien(): void
    {
        $cliente = $this->crearCliente();

        $this->actingAs($cliente->user)->get('/dashboard/cliente')->assertStatus(200);
        $this->actingAs($cliente->user)->get('/mi-membresia')->assertStatus(200);
        $this->actingAs($cliente->user)->get('/mi-rutina')->assertStatus(200);
    }
}
