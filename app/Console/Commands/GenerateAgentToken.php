<?php

namespace App\Console\Commands;

use App\Models\AgentToken;
use App\Models\Sede;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateAgentToken extends Command
{
    protected $signature = 'agente:token
                            {sede_id : ID de la sede para la que se genera el token}
                            {--nombre= : Nombre descriptivo del agente (ej. "Agente Sede Central")}';

    protected $description = 'Genera un token de agente para una sede y lo almacena hasheado.';

    public function handle(): int
    {
        $idSede = (int) $this->argument('sede_id');

        $sede = Sede::find($idSede);
        if (!$sede) {
            $this->error("No existe ninguna sede con id_sede = {$idSede}.");
            return Command::FAILURE;
        }

        $nombre = $this->option('nombre') ?? "Agente {$sede->nombre}";

        // Generar token plano (40 bytes = 80 chars hex, suficientemente seguro)
        $plain = Str::random(60);
        $hash  = hash('sha256', $plain);

        $record = AgentToken::create([
            'id_sede' => $idSede,
            'nombre'  => $nombre,
            'token'   => $hash,
            'activo'  => true,
        ]);

        $this->newLine();
        $this->line('  <fg=green;options=bold>Token generado para sede:</> ' . $sede->nombre . " (id={$idSede})");
        $this->newLine();
        $this->line('  <fg=yellow;options=bold>TOKEN PLANO (cópialo ahora, no se mostrará de nuevo):</>');
        $this->line('  ' . $plain);
        $this->newLine();
        $this->line("  Nombre del agente : {$nombre}");
        $this->line("  Registro creado   : id={$record->id}");
        $this->line('  Almacenado como   : SHA-256 en agent_tokens.token');
        $this->newLine();
        $this->line('  Usa el token como cabecera HTTP:');
        $this->line("  <fg=cyan>Authorization: Bearer {$plain}</>");
        $this->newLine();

        return Command::SUCCESS;
    }
}
