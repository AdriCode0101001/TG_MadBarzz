<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->uuid('qr_token')->nullable()->unique()->after('estado');
        });

        // Backfill: generar token para todos los clientes existentes (incluidos soft-deleted)
        DB::table('clientes')->whereNull('qr_token')->pluck('id_cliente')->each(function ($id) {
            DB::table('clientes')->where('id_cliente', $id)->update(['qr_token' => (string) Str::uuid()]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropUnique(['qr_token']);
            $table->dropColumn('qr_token');
        });
    }
};
