<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notificaciones', function (Blueprint $table) {
            $table->string('canal')->nullable()->after('estado');
        });

        // Agrega 'pendiente' al enum de estado (MySQL únicamente; SQLite ignora tipos enum)
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE notificaciones MODIFY COLUMN estado ENUM('pendiente','enviado','fallido') NULL DEFAULT NULL");
        }
    }

    public function down(): void
    {
        Schema::table('notificaciones', function (Blueprint $table) {
            $table->dropColumn('canal');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE notificaciones MODIFY COLUMN estado ENUM('enviado','fallido') NULL DEFAULT NULL");
        }
    }
};
