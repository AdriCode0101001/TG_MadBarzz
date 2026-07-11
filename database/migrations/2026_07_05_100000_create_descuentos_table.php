<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('descuentos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->decimal('porcentaje', 5, 2)->default(0); // % sobre el precio de la membresía
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Registro por defecto: sin descuento (0%)
        DB::table('descuentos')->insert([
            'nombre'     => 'Sin descuento',
            'porcentaje' => 0,
            'activo'     => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('descuentos');
    }
};
