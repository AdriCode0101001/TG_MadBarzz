<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Descuento;
use App\Models\Membresia;
use App\Models\MembresiaCliente;
use App\Models\Pago;
use App\Models\Persona;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

/**
 * Bitácora de Auditoría — Etapa 1: higiene sobre lo que ya existe.
 * (1) huella_digital y qr_token de Cliente ya no se guardan en el
 *     historial de auditoría; los campos normales (estado, etc.) siguen
 *     igual que antes.
 * (2) El comando bitacora:limpiar-campos-sensibles redacta esos dos campos
 *     en filas YA guardadas, sin borrar ninguna fila.
 * (3) activity_log tiene índice en created_at.
 * No se tocó MembresiaCliente, Pago ni User — se re-verifica que su
 * auditoría sigue funcionando exactamente igual.
 */
class BitacoraEtapa1Test extends TestCase
{
    use RefreshDatabase;

    private static int $seq = 0;

    private function crearCliente(array $overrides = []): Cliente
    {
        self::$seq++;
        $s = self::$seq;

        $persona = Persona::create([
            'nombre_completo'  => "Bitacora {$s}",
            'apellido_paterno' => 'Test',
            'apellido_materno' => 'Etapa1',
            'ci'               => "CIBIT{$s}",
            'telefono'         => "+59178100{$s}",
            'genero'           => 'masculino',
            'fecha_nacimiento' => '1990-01-01',
        ]);

        $user = User::factory()->create(['rol_id' => Rol::firstOrCreate(['nombre' => 'Cliente'])->id]);

        return Cliente::create(array_merge([
            'id_persona'     => $persona->id_persona,
            'id_usuario'     => $user->id,
            'estado'         => 'activo',
            'dias_asistidos' => 0,
        ], $overrides));
    }

    private function admin(): User
    {
        return User::factory()->create(['rol_id' => Rol::firstOrCreate(['nombre' => 'Administrador'])->id]);
    }

    // ── 1. huella_digital y qr_token no se guardan al auditar un Cliente ────

    public function test_actualizar_huella_digital_junto_a_otro_campo_no_la_guarda_en_la_bitacora(): void
    {
        $this->actingAs($this->admin());
        $cliente = $this->crearCliente();
        Activity::query()->delete(); // limpiar el "created" y quedarnos solo con el "updated" que sigue

        $cliente->update([
            'estado'         => 'inactivo',
            'huella_digital' => 'PLANTILLA-BIOMETRICA-SECRETA-XYZ',
        ]);

        $actividad = Activity::where('subject_type', Cliente::class)->where('subject_id', $cliente->id_cliente)->first();

        $this->assertNotNull($actividad);
        $this->assertArrayNotHasKey('huella_digital', $actividad->properties['attributes'] ?? []);
        $this->assertArrayNotHasKey('huella_digital', $actividad->properties['old'] ?? []);
        // Prueba directa de que el valor sensible no quedó en ningún lado del JSON
        $this->assertStringNotContainsString('PLANTILLA-BIOMETRICA-SECRETA-XYZ', json_encode($actividad->properties));
    }

    public function test_actualizar_qr_token_junto_a_otro_campo_no_lo_guarda_en_la_bitacora(): void
    {
        $this->actingAs($this->admin());
        $cliente = $this->crearCliente();
        $tokenOriginal = $cliente->qr_token;
        Activity::query()->delete();

        $cliente->update([
            'estado'   => 'inactivo',
            'qr_token' => 'uuid-nuevo-de-prueba-1234',
        ]);

        $actividad = Activity::where('subject_type', Cliente::class)->where('subject_id', $cliente->id_cliente)->first();

        $this->assertNotNull($actividad);
        $this->assertArrayNotHasKey('qr_token', $actividad->properties['attributes'] ?? []);
        $this->assertArrayNotHasKey('qr_token', $actividad->properties['old'] ?? []);
        $this->assertStringNotContainsString('uuid-nuevo-de-prueba-1234', json_encode($actividad->properties));
        $this->assertStringNotContainsString($tokenOriginal, json_encode($actividad->properties));
    }

    public function test_actualizar_solo_huella_digital_no_genera_ninguna_fila_nueva_en_la_bitacora(): void
    {
        $this->actingAs($this->admin());
        $cliente = $this->crearCliente();
        Activity::query()->delete();

        $cliente->update(['huella_digital' => 'OTRA-PLANTILLA-SECRETA']);

        // Sin ningún campo auditable de por medio, no hay nada que loggear.
        $this->assertSame(0, Activity::where('subject_type', Cliente::class)->where('subject_id', $cliente->id_cliente)->count());
    }

    // ── 2. Los campos normales de Cliente se siguen auditando igual ─────────

    public function test_actualizar_el_estado_de_un_cliente_sigue_registrando_el_cambio(): void
    {
        $this->actingAs($this->admin());
        $cliente = $this->crearCliente(['estado' => 'activo']);
        Activity::query()->delete();

        $cliente->update(['estado' => 'inactivo']);

        $actividad = Activity::where('subject_type', Cliente::class)->where('subject_id', $cliente->id_cliente)->first();

        $this->assertNotNull($actividad);
        $this->assertSame('inactivo', $actividad->properties['attributes']['estado'] ?? null);
        $this->assertSame('activo', $actividad->properties['old']['estado'] ?? null);
    }

    public function test_crear_un_cliente_sigue_quedando_registrado(): void
    {
        $this->actingAs($this->admin());
        $cliente = $this->crearCliente();

        $this->assertSame(1, Activity::where('subject_type', Cliente::class)
            ->where('subject_id', $cliente->id_cliente)
            ->where('event', 'created')
            ->count());
    }

    // ── 3. MembresiaCliente, Pago y User: no se tocaron, siguen igual ───────

    public function test_membresia_cliente_sigue_auditando_normalmente(): void
    {
        $this->actingAs($this->admin());
        $cliente = $this->crearCliente();
        $membresia = Membresia::create(['tipo_membresia' => 'Mensual', 'precio' => 150]);

        $mc = MembresiaCliente::create([
            'id_cliente' => $cliente->id_cliente, 'id_membresia' => $membresia->id_membresia,
            'fecha_inicio' => now()->toDateString(), 'fecha_fin' => now()->addDays(30)->toDateString(),
            'precio_final' => 150, 'descuento' => 0, 'estado' => MembresiaCliente::ESTADO_PENDIENTE,
        ]);
        $mc->update(['estado' => MembresiaCliente::ESTADO_ACTIVA]);

        $actividad = Activity::where('subject_type', MembresiaCliente::class)
            ->where('subject_id', $mc->id)->where('event', 'updated')->first();

        $this->assertNotNull($actividad);
        $this->assertSame('activa', $actividad->properties['attributes']['estado'] ?? null);
    }

    public function test_pago_sigue_auditando_normalmente(): void
    {
        $this->actingAs($this->admin());
        $cliente = $this->crearCliente();
        $membresia = Membresia::create(['tipo_membresia' => 'Mensual', 'precio' => 150]);

        $pago = Pago::create([
            'id_cliente' => $cliente->id_cliente, 'id_membresia' => $membresia->id_membresia,
            'monto' => 150, 'fecha_pago' => now()->toDateString(), 'metodo_pago' => 'efectivo',
        ]);

        $this->assertSame(1, Activity::where('subject_type', Pago::class)
            ->where('subject_id', $pago->id_pago)->where('event', 'created')->count());
    }

    public function test_user_sigue_auditando_solo_sus_campos_seguros(): void
    {
        $this->actingAs($this->admin());
        $user = User::factory()->create(['rol_id' => Rol::firstOrCreate(['nombre' => 'Recepcionista'])->id, 'name' => 'original']);
        Activity::query()->delete();

        $user->update(['name' => 'nuevo.nombre', 'password' => bcrypt('otra-clave-cualquiera')]);

        $actividad = Activity::where('subject_type', User::class)->where('subject_id', $user->id)->first();

        $this->assertNotNull($actividad);
        $this->assertSame('nuevo.nombre', $actividad->properties['attributes']['name'] ?? null);
        $this->assertArrayNotHasKey('password', $actividad->properties['attributes'] ?? []);
    }

    // ── 4. Índice en created_at ────────────────────────────────────────────

    public function test_activity_log_tiene_indice_en_created_at(): void
    {
        $indices = collect(Schema::getIndexes('activity_log'));

        $tieneIndiceCreatedAt = $indices->contains(fn ($idx) => in_array('created_at', $idx['columns'] ?? []));

        $this->assertTrue($tieneIndiceCreatedAt, 'No se encontró un índice que incluya created_at en activity_log.');
    }

    // ── 5. Comando de limpieza de registros viejos ───────────────────────────

    private function crearActividadHistoricaConDatosSensibles(Cliente $cliente): Activity
    {
        return Activity::create([
            'log_name'     => 'default',
            'description'  => 'Cliente updated',
            'subject_type' => Cliente::class,
            'subject_id'   => $cliente->id_cliente,
            'event'        => 'updated',
            'properties'   => [
                'attributes' => [
                    'estado'         => 'activo',
                    'huella_digital' => 'PLANTILLA-VIEJA-GUARDADA-ANTES-DEL-FIX',
                    'qr_token'       => 'uuid-viejo-real-guardado-antes-del-fix',
                ],
                'old' => [
                    'estado'         => 'inactivo',
                    'huella_digital' => 'PLANTILLA-AUN-MAS-VIEJA',
                    'qr_token'       => 'uuid-mas-viejo-todavia',
                ],
            ],
        ]);
    }

    public function test_el_comando_redacta_los_campos_sensibles_sin_borrar_la_fila(): void
    {
        $cliente   = $this->crearCliente();
        $actividad = $this->crearActividadHistoricaConDatosSensibles($cliente);

        Artisan::call('bitacora:limpiar-campos-sensibles');

        $actividad->refresh();

        // La fila SIGUE existiendo (no se borró el historial)
        $this->assertDatabaseHas('activity_log', ['id' => $actividad->id]);

        // Los campos sensibles quedaron redactados, no en null ni con el valor real
        $this->assertStringNotContainsString('PLANTILLA-VIEJA-GUARDADA-ANTES-DEL-FIX', json_encode($actividad->properties));
        $this->assertStringNotContainsString('uuid-viejo-real-guardado-antes-del-fix', json_encode($actividad->properties));
        $this->assertStringContainsString('redactado', $actividad->properties['attributes']['huella_digital']);
        $this->assertStringContainsString('redactado', $actividad->properties['old']['qr_token']);

        // Lo demás queda intacto
        $this->assertSame('activo', $actividad->properties['attributes']['estado']);
        $this->assertSame('inactivo', $actividad->properties['old']['estado']);
    }

    public function test_el_dry_run_del_comando_no_modifica_nada(): void
    {
        $cliente   = $this->crearCliente();
        $actividad = $this->crearActividadHistoricaConDatosSensibles($cliente);
        $propiedadesOriginales = $actividad->properties->toArray();

        Artisan::call('bitacora:limpiar-campos-sensibles', ['--dry-run' => true]);

        $actividad->refresh();

        $this->assertSame($propiedadesOriginales, $actividad->properties->toArray());
    }

    public function test_el_comando_no_toca_filas_sin_campos_sensibles(): void
    {
        $this->actingAs($this->admin());
        $cliente = $this->crearCliente();
        Activity::query()->delete();
        $cliente->update(['estado' => 'inactivo']); // sin campos sensibles, gracias al fix de esta etapa

        $actividadNormal = Activity::where('subject_type', Cliente::class)->where('subject_id', $cliente->id_cliente)->first();
        $propiedadesAntes = $actividadNormal->properties->toArray();

        Artisan::call('bitacora:limpiar-campos-sensibles');

        $actividadNormal->refresh();
        $this->assertSame($propiedadesAntes, $actividadNormal->properties->toArray());
    }
}
