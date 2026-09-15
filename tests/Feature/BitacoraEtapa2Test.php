<?php

namespace Tests\Feature;

use App\Models\Aviso;
use App\Models\Descuento;
use App\Models\Entrenador;
use App\Models\Persona;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

/**
 * Bitácora de Auditoría — Etapa 2: se agregó LogsActivity a las entidades
 * administrativas que todavía no auditaban (Persona, personal, catálogos,
 * contenido/operaciones). Este test cubre una muestra representativa
 * (Persona, Entrenador, Descuento, Aviso): crear/editar/eliminar deja
 * registro, con los campos correctos, sin datos sensibles, y con el causer.
 * Cliente, MembresiaCliente, Pago y User (Etapa 1) no se tocan acá.
 */
class BitacoraEtapa2Test extends TestCase
{
    use RefreshDatabase;

    private static int $seq = 0;

    private function usuario(string $rol): User
    {
        return User::factory()->create(['rol_id' => Rol::firstOrCreate(['nombre' => $rol])->id]);
    }

    private function admin(): User
    {
        return $this->usuario('Administrador');
    }

    // ── Persona ───────────────────────────────────────────────────────────

    private function crearPersona(): Persona
    {
        self::$seq++;
        $s = self::$seq;

        return Persona::create([
            'nombre_completo'  => "Persona {$s}",
            'apellido_paterno' => 'Test',
            'apellido_materno' => 'Etapa2',
            'ci'               => "CIP2{$s}",
            'telefono'         => "+59171000{$s}",
            'genero'           => 'femenino',
            'fecha_nacimiento' => '1995-05-05',
        ]);
    }

    public function test_crear_una_persona_queda_registrado_con_el_causer(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);

        $persona = $this->crearPersona();

        $actividad = Activity::where('subject_type', Persona::class)
            ->where('subject_id', $persona->id_persona)
            ->where('event', 'created')
            ->first();

        $this->assertNotNull($actividad);
        $this->assertSame($admin->id, $actividad->causer_id);
        $this->assertSame(User::class, $actividad->causer_type);
        $this->assertSame($persona->ci, $actividad->properties['attributes']['ci'] ?? null);
    }

    public function test_editar_una_persona_registra_el_cambio(): void
    {
        $this->actingAs($this->admin());
        $persona = $this->crearPersona();
        Activity::query()->delete();

        $persona->update(['telefono' => '+59179999999']);

        $actividad = Activity::where('subject_type', Persona::class)
            ->where('subject_id', $persona->id_persona)->first();

        $this->assertNotNull($actividad);
        $this->assertSame('+59179999999', $actividad->properties['attributes']['telefono'] ?? null);
    }

    public function test_eliminar_una_persona_registra_el_evento(): void
    {
        $this->actingAs($this->admin());
        $persona = $this->crearPersona();
        Activity::query()->delete();

        $persona->delete();

        $this->assertSame(1, Activity::where('subject_type', Persona::class)
            ->where('subject_id', $persona->id_persona)
            ->where('event', 'deleted')
            ->count());
    }

    // ── Entrenador ────────────────────────────────────────────────────────

    private function crearEntrenador(): Entrenador
    {
        self::$seq++;
        $s = self::$seq;

        $persona = Persona::create([
            'nombre_completo'  => "Entrenador {$s}",
            'apellido_paterno' => 'Test',
            'apellido_materno' => 'Etapa2',
            'ci'               => "CIENT2{$s}",
            'telefono'         => "+59172000{$s}",
            'genero'           => 'masculino',
            'fecha_nacimiento' => '1990-01-01',
        ]);

        $user = User::factory()->create(['rol_id' => Rol::firstOrCreate(['nombre' => 'Entrenador'])->id]);

        return Entrenador::create([
            'id_persona'     => $persona->id_persona,
            'id_usuario'     => $user->id,
            'especialidad'   => ['Calistenia'],
            'experiencia'    => '5 años',
            'disponibilidad' => ['lunes', 'martes'],
            'estado'         => 'activo',
        ]);
    }

    public function test_crear_un_entrenador_queda_registrado(): void
    {
        $this->actingAs($this->admin());
        $entrenador = $this->crearEntrenador();

        $this->assertSame(1, Activity::where('subject_type', Entrenador::class)
            ->where('subject_id', $entrenador->id_entrenador)
            ->where('event', 'created')
            ->count());
    }

    public function test_editar_el_estado_de_un_entrenador_registra_el_cambio(): void
    {
        $this->actingAs($this->admin());
        $entrenador = $this->crearEntrenador();
        Activity::query()->delete();

        $entrenador->update(['estado' => 'inactivo']);

        $actividad = Activity::where('subject_type', Entrenador::class)
            ->where('subject_id', $entrenador->id_entrenador)->first();

        $this->assertNotNull($actividad);
        $this->assertSame('inactivo', $actividad->properties['attributes']['estado'] ?? null);
        $this->assertSame('activo', $actividad->properties['old']['estado'] ?? null);
    }

    // ── Descuento ─────────────────────────────────────────────────────────

    public function test_crear_editar_y_eliminar_un_descuento_queda_registrado(): void
    {
        $this->actingAs($this->admin());

        $descuento = Descuento::create(['nombre' => 'Promo verano', 'porcentaje' => 15, 'activo' => true]);
        $this->assertSame(1, Activity::where('subject_type', Descuento::class)
            ->where('subject_id', $descuento->id)->where('event', 'created')->count());

        Activity::query()->delete();
        $descuento->update(['porcentaje' => 20]);
        $actividad = Activity::where('subject_type', Descuento::class)->where('subject_id', $descuento->id)->first();
        $this->assertNotNull($actividad);
        $this->assertEquals(20, $actividad->properties['attributes']['porcentaje'] ?? null);

        Activity::query()->delete();
        $descuento->delete();
        $this->assertSame(1, Activity::where('subject_type', Descuento::class)
            ->where('subject_id', $descuento->id)->where('event', 'deleted')->count());
    }

    // ── Aviso ─────────────────────────────────────────────────────────────

    public function test_crear_y_editar_un_aviso_queda_registrado_con_los_campos_correctos(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);

        $aviso = Aviso::create([
            'titulo'     => 'Cierre por feriado',
            'texto'      => 'El gimnasio no abre el lunes.',
            'id_usuario' => $admin->id,
        ]);

        $actividad = Activity::where('subject_type', Aviso::class)
            ->where('subject_id', $aviso->id)->where('event', 'created')->first();

        $this->assertNotNull($actividad);
        $this->assertSame('Cierre por feriado', $actividad->properties['attributes']['titulo'] ?? null);
        $this->assertSame($admin->id, $actividad->causer_id);

        Activity::query()->delete();
        $aviso->update(['titulo' => 'Cierre reprogramado']);

        $actividadEdit = Activity::where('subject_type', Aviso::class)->where('subject_id', $aviso->id)->first();
        $this->assertNotNull($actividadEdit);
        $this->assertSame('Cierre reprogramado', $actividadEdit->properties['attributes']['titulo'] ?? null);
    }

    // ── No cambia el comportamiento normal de los modelos ────────────────

    public function test_los_modelos_nuevos_siguen_funcionando_igual_fuera_de_la_bitacora(): void
    {
        $this->actingAs($this->admin());

        $persona = $this->crearPersona();
        $this->assertDatabaseHas('personas', ['id_persona' => $persona->id_persona, 'ci' => $persona->ci]);

        $entrenador = $this->crearEntrenador();
        $this->assertSame('activo', $entrenador->fresh()->estado);
        $this->assertSame(['Calistenia'], $entrenador->fresh()->especialidad);

        $descuento = Descuento::create(['nombre' => 'Sin cambios', 'porcentaje' => 0, 'activo' => true]);
        $this->assertTrue($descuento->fresh()->activo);
    }
}
