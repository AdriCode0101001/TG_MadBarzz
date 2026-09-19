<?php

namespace Tests\Feature\Security;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Revisión de production-readiness — Tanda 1 (3 arreglos chicos):
 *
 * 1) activitylog:clean programado en el scheduler (antes: la tabla
 *    activity_log crecía indefinido pese a tener retención configurada).
 * 2) DatabaseSeeder no debe correr los seeders de DEMO (clientes falsos,
 *    contraseñas 123456/pass1234) si el entorno es 'production'.
 *
 * El arreglo 3 (LOG_STACK=daily documentado en .env.example) es solo un
 * comentario de plantilla — no hay comportamiento de la app que testear.
 */
class ProduccionReadinessTest extends TestCase
{
    use RefreshDatabase;

    // ── Arreglo 1: activitylog:clean programado ─────────────────────────────

    public function test_activitylog_clean_esta_programado_en_el_scheduler(): void
    {
        $schedule = app(Schedule::class);

        $encontrado = collect($schedule->events())
            ->first(fn ($event) => str_contains($event->command ?? '', 'activitylog:clean'));

        $this->assertNotNull($encontrado, 'activitylog:clean no está programado en el scheduler.');
    }

    public function test_activitylog_clean_corre_con_force_para_no_pedir_confirmacion_en_produccion(): void
    {
        $schedule = app(Schedule::class);

        $evento = collect($schedule->events())
            ->first(fn ($event) => str_contains($event->command ?? '', 'activitylog:clean'));

        $this->assertNotNull($evento);
        $this->assertStringContainsString('--force', $evento->command);
    }

    public function test_activitylog_clean_respeta_los_365_dias_de_retencion_configurados(): void
    {
        $this->assertSame(365, config('activitylog.delete_records_older_than_days'));
    }

    public function test_activitylog_clean_no_choca_de_horario_con_los_backups_ni_las_notificaciones(): void
    {
        $schedule = app(Schedule::class);

        $horarios = collect($schedule->events())
            ->filter(fn ($event) => str_contains($event->command ?? '', 'backup:')
                || str_contains($event->command ?? '', 'activitylog:clean')
                || str_contains($event->command ?? '', 'notificaciones:'))
            ->map(fn ($event) => $event->expression)
            ->values();

        // Todas las entradas nocturnas/matutinas deben ser horarios distintos
        // (sin dos comandos compitiendo por I/O al mismo minuto).
        $this->assertSame($horarios->count(), $horarios->unique()->count());
    }

    // ── Arreglo 2: DatabaseSeeder no corre seeders de demo en producción ────

    public function test_database_seeder_aborta_en_produccion(): void
    {
        $this->app['env'] = 'production';

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Los seeders de demo no deben correr en producción');

        (new DatabaseSeeder())->run();
    }

    public function test_ningun_dato_de_demo_se_crea_si_database_seeder_corre_en_produccion(): void
    {
        $this->app['env'] = 'production';

        try {
            (new DatabaseSeeder())->run();
        } catch (\RuntimeException) {
            // esperado
        }

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('clientes', 0);
        $this->assertDatabaseCount('roles', 0);
        // 'sedes' no cuenta acá: la propia migración de esa tabla inserta
        // la fila "Sede Principal" por defecto, independiente de cualquier
        // seeder — no es dato que DatabaseSeeder haya creado.
    }

    public function test_database_seeder_sigue_funcionando_normal_fuera_de_produccion(): void
    {
        $this->app['env'] = 'testing';

        (new DatabaseSeeder())->run();

        // Si llegó hasta acá sin excepción, y con datos creados, es que
        // corrió la cadena normal de seeders de demo (comportamiento igual
        // que antes fuera de producción).
        $this->assertDatabaseCount('roles', 4);
        $this->assertTrue(\App\Models\User::count() > 0);
        $this->assertTrue(\App\Models\Cliente::count() > 0);
    }
}
