<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Huella;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Grupo 2 / Etapa 1 de la auditoría de privacidad: comando de una sola
 * corrida que cifra las plantillas biométricas que quedaron en texto plano
 * de antes de activar el cast 'encrypted' en Huella::template.
 */
class CifrarHuellasExistentesCommandTest extends TestCase
{
    use RefreshDatabase;

    private static int $seq = 0;

    /**
     * Inserta una Huella "vieja" directamente con DB::table() (sin pasar por
     * el modelo, que ya tiene el cast 'encrypted') para simular una fila que
     * quedó en texto plano de antes de este cambio.
     */
    private function crearHuellaSinCifrar(string $templateEnClaro): int
    {
        self::$seq++;
        $s = self::$seq;

        $rol       = Rol::firstOrCreate(['nombre' => 'Cliente']);
        $user      = User::factory()->create(['rol_id' => $rol->id]);
        $personaId = DB::table('personas')->insertGetId([
            'nombre_completo' => "Cliente {$s}", 'apellido_paterno' => 'Test', 'apellido_materno' => 'Test',
            'ci' => "CICMD{$s}", 'telefono' => "+5917100{$s}00", 'genero' => 'masculino',
            'fecha_nacimiento' => '1990-01-01', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $cliente = Cliente::create([
            'id_persona' => $personaId, 'id_usuario' => $user->id,
            'estado' => 'activo', 'dias_asistidos' => 0,
        ]);

        return DB::table('huellas')->insertGetId([
            'id_cliente' => $cliente->id_cliente,
            'dedo'       => 'indice_derecho',
            'template'   => $templateEnClaro, // texto plano, a propósito
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_cifra_una_huella_en_texto_plano(): void
    {
        $templateEnClaro = base64_encode('plantilla-vieja-sin-cifrar');
        $id = $this->crearHuellaSinCifrar($templateEnClaro);

        $this->artisan('huellas:cifrar-existentes')
             ->expectsOutputToContain('Cifradas ahora: 1. Ya estaban cifradas: 0. Total: 1.')
             ->assertExitCode(0);

        $crudo = DB::table('huellas')->where('id', $id)->value('template');
        $this->assertNotSame($templateEnClaro, $crudo);

        // Legible en claro vía el modelo tras la migración.
        $this->assertSame($templateEnClaro, Huella::find($id)->template);

        // template_hash quedó poblado y es correcto (lo usa el anti-duplicado).
        $this->assertSame(hash('sha256', $templateEnClaro), Huella::find($id)->template_hash);
    }

    public function test_dry_run_no_escribe_nada(): void
    {
        $templateEnClaro = base64_encode('plantilla-dry-run');
        $id = $this->crearHuellaSinCifrar($templateEnClaro);

        $this->artisan('huellas:cifrar-existentes', ['--dry-run' => true])
             ->expectsOutputToContain('[DRY-RUN] Se cifrarían 1 huellas.')
             ->assertExitCode(0);

        // Sigue en texto plano: el dry-run no tocó la fila.
        $this->assertSame($templateEnClaro, DB::table('huellas')->where('id', $id)->value('template'));
    }

    public function test_correr_el_comando_dos_veces_no_cifra_de_nuevo_ni_pierde_el_dato(): void
    {
        $templateEnClaro = base64_encode('plantilla-idempotencia');
        $id = $this->crearHuellaSinCifrar($templateEnClaro);

        $this->artisan('huellas:cifrar-existentes')->assertExitCode(0);
        $cifradoTrasPrimeraCorrida = DB::table('huellas')->where('id', $id)->value('template');

        $this->artisan('huellas:cifrar-existentes')
             ->expectsOutputToContain('Cifradas ahora: 0. Ya estaban cifradas: 1. Total: 1.')
             ->assertExitCode(0);

        $cifradoTrasSegundaCorrida = DB::table('huellas')->where('id', $id)->value('template');

        // Ni se volvió a cifrar (mismo ciphertext) ni se perdió el dato original.
        $this->assertSame($cifradoTrasPrimeraCorrida, $cifradoTrasSegundaCorrida);
        $this->assertSame($templateEnClaro, Huella::find($id)->template);
    }

    public function test_no_toca_una_huella_que_ya_esta_cifrada(): void
    {
        // Enrolada por el flujo normal (vía el modelo): ya queda cifrada de entrada.
        $rol       = Rol::firstOrCreate(['nombre' => 'Cliente']);
        $user      = User::factory()->create(['rol_id' => $rol->id]);
        $personaId = DB::table('personas')->insertGetId([
            'nombre_completo' => 'Cliente Ya Cifrado', 'apellido_paterno' => 'Test', 'apellido_materno' => 'Test',
            'ci' => 'CICMDYA1', 'telefono' => '+59171000099', 'genero' => 'masculino',
            'fecha_nacimiento' => '1990-01-01', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $cliente = Cliente::create(['id_persona' => $personaId, 'id_usuario' => $user->id, 'estado' => 'activo', 'dias_asistidos' => 0]);

        $huella = Huella::create([
            'id_cliente' => $cliente->id_cliente,
            'dedo'       => 'indice_derecho',
            'template'   => base64_encode('ya-cifrada-de-entrada'),
        ]);

        $ciphertextAntes = DB::table('huellas')->where('id', $huella->id)->value('template');

        $this->artisan('huellas:cifrar-existentes')
             ->expectsOutputToContain('Cifradas ahora: 0. Ya estaban cifradas: 1. Total: 1.')
             ->assertExitCode(0);

        $this->assertSame($ciphertextAntes, DB::table('huellas')->where('id', $huella->id)->value('template'));
    }

    public function test_maneja_varias_huellas_mezclando_cifradas_y_sin_cifrar(): void
    {
        $sinCifrar1 = $this->crearHuellaSinCifrar(base64_encode('sin-cifrar-1'));
        $sinCifrar2 = $this->crearHuellaSinCifrar(base64_encode('sin-cifrar-2'));

        $this->artisan('huellas:cifrar-existentes')
             ->expectsOutputToContain('Cifradas ahora: 2. Ya estaban cifradas: 0. Total: 2.')
             ->assertExitCode(0);

        $this->assertSame(base64_encode('sin-cifrar-1'), Huella::find($sinCifrar1)->template);
        $this->assertSame(base64_encode('sin-cifrar-2'), Huella::find($sinCifrar2)->template);
    }
}
