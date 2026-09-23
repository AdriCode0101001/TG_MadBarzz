<?php

namespace Tests\Feature;

use App\Models\Administrador;
use App\Models\Cliente;
use App\Models\Entrenador;
use App\Models\Persona;
use App\Models\Recepcionista;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regresión: una corrección automática de accesibilidad (asociar <label>
 * con su <input> vía for/id) rompió la sintaxis PHP de
 * clientes/entrenadores/recepcionistas/administradores "edit" — en las
 * cuatro vistas, `$modelo->persona->campo` había quedado partido en
 * `$modelo- id="campo">persona->campo`, un error de sintaxis Blade que
 * tira 500 en cualquier intento de editar a alguien desde el panel. Nunca
 * se detectó porque ningún test pegaba GET a esas rutas de edición (solo a
 * las de creación). Estas pruebas cierran ese hueco: no verifican
 * contenido específico, solo que la pantalla de edición carga sin
 * reventar para cada uno de los cuatro roles.
 */
class PanelEditViewsRenderTest extends TestCase
{
    use RefreshDatabase;

    private static int $seq = 0;

    private function admin(): User
    {
        return User::factory()->create(['rol_id' => Rol::firstOrCreate(['nombre' => 'Administrador'])->id]);
    }

    private function persona(): Persona
    {
        self::$seq++;
        $s = self::$seq;

        return Persona::create([
            'nombre_completo' => 'Editable', 'apellido_paterno' => 'Test', 'apellido_materno' => 'Test',
            'ci' => "CIEDIT{$s}", 'telefono' => "+59171122{$s}", 'genero' => 'masculino',
            'fecha_nacimiento' => '1990-01-01',
        ]);
    }

    public function test_clientes_edit_renderiza_sin_error(): void
    {
        $persona = $this->persona();
        $user    = User::factory()->create(['rol_id' => Rol::firstOrCreate(['nombre' => 'Cliente'])->id]);
        $cliente = Cliente::create([
            'id_persona' => $persona->id_persona, 'id_usuario' => $user->id,
            'estado' => 'activo', 'dias_asistidos' => 0,
        ]);

        $this->actingAs($this->admin())
             ->get("/clientes/{$cliente->id_cliente}/edit")
             ->assertStatus(200)
             ->assertSee('Nombres', false);
    }

    public function test_entrenadores_edit_renderiza_sin_error(): void
    {
        Rol::firstOrCreate(['nombre' => 'Entrenador']);

        // Creado vía el flujo real (POST a entrenadores.store), no
        // Entrenador::create() directo: especialidad/disponibilidad se
        // guardan distinto según por dónde entran (ver
        // EntrenadorController::store()), así que solo el flujo real
        // reproduce fielmente cómo queda un entrenador de verdad.
        $this->actingAs($this->admin())->post('/entrenadores', [
            'nombre_completo'       => 'Entrenador Editable',
            'apellido_paterno'      => 'Test',
            'apellido_materno'      => 'Test',
            'ci'                    => 'CIEDITENT1',
            'telefono'              => '+59171122901',
            'genero'                => 'masculino',
            'fecha_nacimiento'      => '1990-01-01',
            'name'                  => 'entrenadoreditable',
            'email'                 => 'entrenador-editable@madbarzz.test',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'especialidad'          => ['Calistenia'],
            'experiencia'           => '5 años',
            'disponibilidad'        => ['Lunes', 'Martes'],
            'estado'                => 'activo',
        ])->assertRedirect(route('entrenadores.index'));

        $entrenador = Entrenador::firstOrFail();

        $this->actingAs($this->admin())
             ->get("/entrenadores/{$entrenador->id_entrenador}/edit")
             ->assertStatus(200)
             ->assertSee('Nombres', false);
    }

    public function test_recepcionistas_edit_renderiza_sin_error(): void
    {
        Rol::firstOrCreate(['nombre' => 'Recepcionista']);
        $persona       = $this->persona();
        $user          = User::factory()->create(['rol_id' => Rol::where('nombre', 'Recepcionista')->first()->id]);
        $recepcionista = Recepcionista::create([
            'id_persona' => $persona->id_persona, 'id_usuario' => $user->id,
            'punto_atencion' => 'Mostrador', 'turno' => 'mañana', 'estado' => 'activo',
        ]);

        $this->actingAs($this->admin())
             ->get("/recepcionistas/{$recepcionista->id_recepcionista}/edit")
             ->assertStatus(200)
             ->assertSee('Nombres', false);
    }

    public function test_administradores_edit_renderiza_sin_error(): void
    {
        $persona      = $this->persona();
        $user         = User::factory()->create(['rol_id' => Rol::firstOrCreate(['nombre' => 'Administrador'])->id]);
        $administrador = Administrador::create([
            'id_persona' => $persona->id_persona, 'id_usuario' => $user->id,
            'area_responsable' => 'General', 'nivel_acceso' => 'gestion', 'estado' => 'activo',
        ]);

        $this->actingAs($this->admin())
             ->get("/administradores/{$administrador->id_administrador}/edit")
             ->assertStatus(200)
             ->assertSee('Nombres', false);
    }
}
