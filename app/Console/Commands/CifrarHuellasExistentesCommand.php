<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * Grupo 2 / Etapa 1 de la auditoría de privacidad: cifra con APP_KEY las
 * plantillas biométricas (huellas.template) que todavía estén en texto
 * plano, de una sola corrida.
 *
 * Usa DB::table() (consultas crudas) en vez del modelo Huella a propósito:
 * el modelo ya tiene el cast 'encrypted' en `template`, así que leerlo vía
 * Eloquent lanzaría DecryptException sobre las filas viejas todavía sin
 * cifrar. Trabajar crudo evita ese problema de raíz y de paso deja la
 * detección de "ya migrada" simple (ver yaEstaCifrada()).
 *
 * Segura de correr más de una vez: cada fila se prueba a descifrar primero;
 * si ya es un ciphertext válido de Laravel, se salta. No hay forma de que
 * una corrida repetida cifre dos veces la misma fila ni pierda datos.
 */
class CifrarHuellasExistentesCommand extends Command
{
    protected $signature = 'huellas:cifrar-existentes
                            {--dry-run : Solo mostrar cuántas filas se cifrarían, sin escribir nada}';

    protected $description = 'Cifra con APP_KEY las plantillas biométricas (huellas.template) que todavía estén en texto plano.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        // Incluye soft-deleted a propósito: siguen siendo un dato biométrico
        // en texto plano en la BD hasta que se cifren, borradas o no.
        $huellas = DB::table('huellas')->select('id', 'id_cliente', 'dedo', 'template')->get();

        $migradas   = 0;
        $yaCifradas = 0;

        foreach ($huellas as $huella) {
            if ($this->yaEstaCifrada($huella->template)) {
                $yaCifradas++;
                continue;
            }

            $migradas++;

            if ($dryRun) {
                $this->line("  [DRY-RUN] Se cifraría huella #{$huella->id} (cliente #{$huella->id_cliente}, {$huella->dedo})");
                continue;
            }

            DB::table('huellas')->where('id', $huella->id)->update([
                'template'      => Crypt::encryptString($huella->template),
                'template_hash' => hash('sha256', $huella->template),
            ]);

            $this->line("  → Cifrada huella #{$huella->id} (cliente #{$huella->id_cliente}, {$huella->dedo})");
        }

        $resumen = $dryRun
            ? "[DRY-RUN] Se cifrarían {$migradas} huellas. Ya estaban cifradas: {$yaCifradas}. Total: " . ($migradas + $yaCifradas) . '.'
            : "Cifradas ahora: {$migradas}. Ya estaban cifradas: {$yaCifradas}. Total: " . ($migradas + $yaCifradas) . '.';

        $this->info($resumen);

        return self::SUCCESS;
    }

    /**
     * true si $valor ya es un ciphertext válido de Laravel (Crypt::decryptString
     * lo acepta sin lanzar excepción) — o sea, esta fila ya pasó por este
     * comando antes y no hay que volver a tocarla.
     */
    private function yaEstaCifrada(string $valor): bool
    {
        try {
            Crypt::decryptString($valor);
            return true;
        } catch (DecryptException) {
            return false;
        }
    }
}
