<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Huella;
use App\Models\Persona;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Grupo 3 legal, parte 2: comando de una sola corrida que marca como
 * "consentimiento registrado" a los clientes que ya tenían huella enrolada
 * ANTES de exigir este dato — para no invalidar huellas ya capturadas.
 */
class MarcarConsentimientoHuellasExistentesCommandTest extends TestCase
{
    use RefreshDatabase;

    private static int $seq = 0;

    private function crearCliente(string $fechaNacimiento): Cliente
    {
        self::$seq++;
        $s = self::$seq;

        $persona = Persona::create([
            'nombre_completo' => "Cliente Previo {$s}", 'apellido_paterno' => 'Test', 'apellido_materno' => 'Test',
            'ci' => "CIPREV{$s}", 'telefono' => "+59171500{$s}", 'genero' => 'masculino',
            'fecha_nacimiento' => $fechaNacimiento,
        ]);
        $user = User::factory()->create(['rol_id' => Rol::firstOrCreate(['nombre' => 'Cliente'])->id]);

        return Cliente::create([
            'id_persona' => $persona->id_persona, 'id_usuario' => $user->id,
            'estado' => 'activo', 'dias_asistidos' => 0,
        ]);
    }

    public function test_marca_a_un_adulto_con_huella_previa_y_sin_consentimiento(): void
    {
        $cliente = $this->crearCliente('1990-01-01');
        Huella::create(['id_cliente' => $cliente->id_cliente, 'dedo' => 'indice_derecho', 'template' => base64_encode('previa')]);

        $this->artisan('clientes:marcar-consentimiento-huellas-existentes')
             ->expectsOutputToContain('Marcados 1 clientes (0 menores')
             ->assertExitCode(0);

        $cliente->refresh();
        $this->assertNotNull($cliente->consentimiento_huella_en);
        $this->assertNull($cliente->consentimiento_tutor_en);
    }

    public function test_marca_a_un_menor_con_huella_previa_sin_datos_de_tutor(): void
    {
        $cliente = $this->crearCliente(now()->subYears(15)->toDateString());
        Huella::create(['id_cliente' => $cliente->id_cliente, 'dedo' => 'indice_derecho', 'template' => base64_encode('previa-menor')]);

        $this->artisan('clientes:marcar-consentimiento-huellas-existentes')
             ->expectsOutputToContain('Marcados 1 clientes (1 menores')
             ->assertExitCode(0);

        $cliente->refresh();
        $this->assertNotNull($cliente->consentimiento_huella_en);
        $this->assertNotNull($cliente->consentimiento_tutor_en);
        // No hay forma de inventar los datos del tutor: quedan vacíos, a completar.
        $this->assertNull($cliente->tutor_nombre);
        $this->assertNull($cliente->tutor_ci);
    }

    public function test_no_toca_un_cliente_sin_huella(): void
    {
        $this->crearCliente('1990-01-01');

        $this->artisan('clientes:marcar-consentimiento-huellas-existentes')
             ->expectsOutputToContain('No hay clientes con huella enrolada y sin consentimiento')
             ->assertExitCode(0);

        $this->assertDatabaseCount('clientes', 1);
        $this->assertDatabaseHas('clientes', ['consentimiento_huella_en' => null]);
    }

    public function test_no_toca_un_cliente_que_ya_tiene_consentimiento(): void
    {
        $cliente = $this->crearCliente('1990-01-01');
        $cliente->update(['consentimiento_huella_en' => now()->subDays(10)]);
        $fechaOriginal = $cliente->consentimiento_huella_en;
        Huella::create(['id_cliente' => $cliente->id_cliente, 'dedo' => 'indice_derecho', 'template' => base64_encode('ya-tenia')]);

        $this->artisan('clientes:marcar-consentimiento-huellas-existentes')
             ->expectsOutputToContain('No hay clientes con huella enrolada y sin consentimiento')
             ->assertExitCode(0);

        $this->assertTrue($fechaOriginal->equalTo($cliente->fresh()->consentimiento_huella_en));
    }

    public function test_correr_el_comando_dos_veces_es_seguro(): void
    {
        $cliente = $this->crearCliente('1990-01-01');
        Huella::create(['id_cliente' => $cliente->id_cliente, 'dedo' => 'indice_derecho', 'template' => base64_encode('idempotencia')]);

        $this->artisan('clientes:marcar-consentimiento-huellas-existentes')->assertExitCode(0);
        $fecha1 = $cliente->fresh()->consentimiento_huella_en;

        $this->artisan('clientes:marcar-consentimiento-huellas-existentes')
             ->expectsOutputToContain('No hay clientes con huella enrolada y sin consentimiento')
             ->assertExitCode(0);

        $this->assertTrue($fecha1->equalTo($cliente->fresh()->consentimiento_huella_en));
    }

    public function test_dry_run_no_escribe_nada(): void
    {
        $cliente = $this->crearCliente('1990-01-01');
        Huella::create(['id_cliente' => $cliente->id_cliente, 'dedo' => 'indice_derecho', 'template' => base64_encode('dry')]);

        $this->artisan('clientes:marcar-consentimiento-huellas-existentes', ['--dry-run' => true])
             ->expectsOutputToContain('[DRY-RUN] Se marcarían 1 clientes')
             ->assertExitCode(0);

        $this->assertNull($cliente->fresh()->consentimiento_huella_en);
    }

    public function test_un_cliente_con_dos_huellas_solo_se_cuenta_una_vez(): void
    {
        $cliente = $this->crearCliente('1990-01-01');
        Huella::create(['id_cliente' => $cliente->id_cliente, 'dedo' => 'indice_derecho', 'template' => base64_encode('dedo1')]);
        Huella::create(['id_cliente' => $cliente->id_cliente, 'dedo' => 'pulgar_derecho', 'template' => base64_encode('dedo2')]);

        $this->artisan('clientes:marcar-consentimiento-huellas-existentes')
             ->expectsOutputToContain('Marcados 1 clientes')
             ->assertExitCode(0);
    }
}
