<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Huella;
use App\Models\Persona;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Bug cosmético: la columna "Huella" de clientes/index.blade.php usaba
 * $cliente->huella_digital (columna LEGACY, nunca poblada desde que existe
 * el agente biométrico C# y la tabla `huellas` real) — siempre mostraba
 * "No", tuviera o no huella enrolada de verdad. Corregido para usar
 * huellas_count (withCount('huellas') en ClienteController::index, sin
 * N+1) y nunca mostrar el template en sí.
 */
class ClientesHuellaColumnaTest extends TestCase
{
    use RefreshDatabase;

    private static int $seq = 0;

    private function admin(): User
    {
        return User::factory()->create(['rol_id' => Rol::firstOrCreate(['nombre' => 'Administrador'])->id]);
    }

    private function crearCliente(string $nombre): Cliente
    {
        self::$seq++;
        $s = self::$seq;

        $persona = Persona::create([
            'nombre_completo' => $nombre, 'apellido_paterno' => 'Test', 'apellido_materno' => 'Huella',
            'ci' => "CIHCOL{$s}", 'telefono' => "+59171600{$s}", 'genero' => 'masculino',
            'fecha_nacimiento' => '1990-01-01',
        ]);
        $user = User::factory()->create(['rol_id' => Rol::firstOrCreate(['nombre' => 'Cliente'])->id]);

        return Cliente::create([
            'id_persona' => $persona->id_persona, 'id_usuario' => $user->id,
            'estado' => 'activo', 'dias_asistidos' => 0,
        ]);
    }

    // ── Refleja la realidad ────────────────────────────────────────────────

    public function test_un_cliente_con_huella_enrolada_muestra_si_en_el_listado(): void
    {
        $cliente = $this->crearCliente('Con Huella');
        Huella::create(['id_cliente' => $cliente->id_cliente, 'dedo' => 'indice_derecho', 'template' => base64_encode('t1')]);

        $html = $this->actingAs($this->admin())->get('/clientes')->assertStatus(200)->getContent();

        // La fila de ESTE cliente debe mostrar el indicador positivo con el conteo.
        $this->assertMatchesRegularExpression(
            '/Con Huella.*?badge bg-success"[^>]*>\s*<i class="fa-solid fa-fingerprint me-1"><\/i>Sí \(1\)/s',
            $html
        );
    }

    public function test_un_cliente_con_dos_huellas_muestra_el_conteo_correcto(): void
    {
        $cliente = $this->crearCliente('Dos Huellas');
        Huella::create(['id_cliente' => $cliente->id_cliente, 'dedo' => 'indice_derecho', 'template' => base64_encode('t1')]);
        Huella::create(['id_cliente' => $cliente->id_cliente, 'dedo' => 'pulgar_derecho', 'template' => base64_encode('t2')]);

        $html = $this->actingAs($this->admin())->get('/clientes')->getContent();

        $this->assertMatchesRegularExpression('/Dos Huellas.*?Sí \(2\)/s', $html);
    }

    public function test_un_cliente_sin_huella_muestra_no(): void
    {
        $this->crearCliente('Sin Huella');

        $html = $this->actingAs($this->admin())->get('/clientes')->assertStatus(200)->getContent();

        $this->assertMatchesRegularExpression('/Sin Huella.*?badge bg-secondary">No</s', $html);
    }

    public function test_la_columna_ya_no_depende_de_huella_digital_legacy(): void
    {
        // Cliente con huella_digital (legacy) poblada a mano pero SIN huella
        // real en la tabla `huellas`: antes del arreglo mostraba "Sí" por
        // error; ahora debe mostrar "No", porque no tiene huella de verdad.
        $cliente = $this->crearCliente('Legacy Poblado');
        $cliente->forceFill(['huella_digital' => 'algo-viejo-irrelevante'])->save();

        $html = $this->actingAs($this->admin())->get('/clientes')->getContent();

        $this->assertMatchesRegularExpression('/Legacy Poblado.*?badge bg-secondary">No</s', $html);
    }

    public function test_el_template_de_la_huella_nunca_aparece_en_el_listado(): void
    {
        $cliente = $this->crearCliente('Con Template');
        $templateSecreto = base64_encode('plantilla-biometrica-no-debe-salir-aqui');
        Huella::create(['id_cliente' => $cliente->id_cliente, 'dedo' => 'indice_derecho', 'template' => $templateSecreto]);

        $html = $this->actingAs($this->admin())->get('/clientes')->getContent();

        $this->assertStringNotContainsString($templateSecreto, $html);
    }

    // ── Rendimiento: sin N+1 ────────────────────────────────────────────────

    public function test_el_listado_no_dispara_una_consulta_de_huellas_por_cliente(): void
    {
        $admin = $this->admin();

        // Fixtures creadas ANTES de prender el query log: solo nos importan
        // las consultas del RENDER de la página, no las de armar los datos
        // de prueba (inserts, bitácora, etc. — si no, cualquier diferencia
        // de cantidad de fixtures ensucia la comparación).
        for ($i = 0; $i < 5; $i++) {
            $cliente = $this->crearCliente("Cliente Query {$i}");
            Huella::create(['id_cliente' => $cliente->id_cliente, 'dedo' => 'indice_derecho', 'template' => base64_encode("t{$i}")]);
        }

        DB::enableQueryLog();
        $this->actingAs($admin)->get('/clientes')->assertStatus(200);
        $consultasCon5 = count(DB::getQueryLog());
        DB::flushQueryLog();
        DB::disableQueryLog();

        for ($i = 5; $i < 15; $i++) {
            $cliente = $this->crearCliente("Cliente Query {$i}");
            Huella::create(['id_cliente' => $cliente->id_cliente, 'dedo' => 'indice_derecho', 'template' => base64_encode("t{$i}")]);
        }

        DB::enableQueryLog();
        $this->actingAs($admin)->get('/clientes')->assertStatus(200);
        $consultasCon15 = count(DB::getQueryLog());
        DB::disableQueryLog();

        // Si hubiera N+1, 10 clientes más en la página serían ~10 consultas
        // de más (una por fila). Con withCount() el número de consultas del
        // render es prácticamente el mismo sin importar cuántos clientes
        // traiga la página — se tolera una diferencia chica (1-2) por
        // consultas incidentales de infraestructura (ej. caché de rol) que
        // no tienen que ver con las filas en sí.
        $diferencia = abs($consultasCon15 - $consultasCon5);
        $this->assertLessThanOrEqual(2, $diferencia,
            "El número de consultas del render creció {$diferencia} al agregar 10 clientes más ({$consultasCon5} vs {$consultasCon15}) — probable N+1.");
    }

    public function test_huellas_count_llega_precargado_sin_consulta_extra_por_fila(): void
    {
        $admin = $this->admin();
        for ($i = 0; $i < 3; $i++) {
            $this->crearCliente("Cliente Sin Huella {$i}");
        }

        DB::enableQueryLog();
        $this->actingAs($admin)->get('/clientes')->assertStatus(200);
        $consultas = DB::getQueryLog();
        DB::disableQueryLog();

        // withCount agrega un subselect DENTRO de la consulta principal de
        // clientes (por eso "from huellas" SÍ aparece ahí, como substring) —
        // lo que no debe existir es una consulta APARTE cuyo FROM principal
        // sea huellas (eso sí sería una consulta de más por fila).
        $consultasATablaHuellas = collect($consultas)->filter(function ($q) {
            $consulta = trim($q['query']);
            return (str_starts_with($consulta, 'select * from "huellas"')
                    || str_starts_with($consulta, 'select * from `huellas`'));
        });

        $this->assertCount(0, $consultasATablaHuellas, 'No debería haber una consulta separada a la tabla huellas (debe ir como subselect de withCount).');
    }
}
