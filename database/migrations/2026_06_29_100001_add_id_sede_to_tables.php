<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Tablas a las que se agrega id_sede con sus posiciones preferidas. */
    private array $tables = [
        'clientes'          => 'id_usuario',
        'asistencias'       => 'fecha_hora_entrada',
        'pagos'             => 'imagen_qr',
        'membresia_cliente' => 'precio_final',
        'recepcionistas'    => 'estado',
        'entrenadores'      => 'estado',
        'users'             => 'foto',
    ];

    public function up(): void
    {
        foreach ($this->tables as $tableName => $afterColumn) {
            Schema::table($tableName, function (Blueprint $blueprint) use ($afterColumn) {
                $blueprint->unsignedBigInteger('id_sede')
                          ->default(1)
                          ->after($afterColumn);

                $blueprint->foreign('id_sede')
                          ->references('id_sede')
                          ->on('sedes')
                          ->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (array_keys($this->tables) as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->dropForeign("{$tableName}_id_sede_foreign");
                $table->dropColumn('id_sede');
            });
        }
    }
};
