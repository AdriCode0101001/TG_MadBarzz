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

// ---------------------------------------------------------------------------
// Notificaciones automáticas de vencimiento — Fase 5
// ---------------------------------------------------------------------------

// 08:00 — Avisa a los clientes cuya membresía vence en N días (NOTIF_DIAS_AVISO)
Schedule::command('notificaciones:vencimientos')
    ->dailyAt('08:00')
    ->withoutOverlapping()
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::error('notificaciones:vencimientos falló en la ejecución programada.');
    });

// ---------------------------------------------------------------------------
// Pago en 2 Cuotas — Etapa 5: aviso de saldo pendiente próximo a vencer
// ---------------------------------------------------------------------------

// 08:05 — Avisa a los clientes cuyo saldo pendiente vence en 3 días, antes
// de que la desactivación automática (08:15) llegue a afectarlos.
Schedule::command('notificaciones:saldo-pendiente')
    ->dailyAt('08:05')
    ->withoutOverlapping()
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::error('notificaciones:saldo-pendiente falló en la ejecución programada.');
    });

// ---------------------------------------------------------------------------
// Pago en 2 Cuotas — Etapa 4: desactivación automática de saldo vencido
// ---------------------------------------------------------------------------

// 08:15 — Después de las notificaciones de vencimiento: suspende las
// membresías activas cuyo saldo pendiente venció sin pagarse.
Schedule::command('membresias:desactivar-saldo-vencido')
    ->dailyAt('08:15')
    ->withoutOverlapping()
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::error('membresias:desactivar-saldo-vencido falló en la ejecución programada.');
    });
