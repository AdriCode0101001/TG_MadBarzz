<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ---------------------------------------------------------------------------
// Respaldos automáticos — Fase 4
//
// Para activar el scheduler en producción (VPS), agregar al crontab del servidor:
//   * * * * * cd /var/www/madbarzz && php artisan schedule:run >> /dev/null 2>&1
// ---------------------------------------------------------------------------

// 02:00 — Respaldo completo (BD + archivos privados) a local y S3
Schedule::command('backup:run')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::error('Backup diario falló.');
    });

// 02:30 — Limpiar respaldos antiguos según política de retención
Schedule::command('backup:clean')
    ->dailyAt('02:30')
    ->withoutOverlapping();

// 03:00 — Monitor de salud: alerta si el backup más reciente es viejo o pesa demasiado
Schedule::command('backup:monitor')
    ->dailyAt('03:00');
