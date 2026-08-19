<?php

namespace Tests\Feature;

use App\Models\Entrenador;
use App\Models\HorarioEntrenador;
use App\Models\Persona;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Horario de entrenadores, Parte 2: la home pública ("/") muestra el
 * horario REAL armado por el Admin en el panel privado (tabla
 * horarios_entrenadores), no el ejemplo hardcodeado que tenía antes.
 */
class HorarioPublicoHomeTest extends TestCase
{
    use RefreshDatabase;

    private static int $seq = 0;

    private function crearEntrenador(string $nombre = 'Carlos Eduardo'): Entrenador
    {
        self::$seq++;
        $s = self::$seq;

        $persona = Persona::create([
            'nombre_completo'  => $nombre,
            'apellido_paterno' => "Publico{$s}",
            'apellido_materno' => 'Test',
            'ci'               => "CIPUBH{$s}",
            'telefono'         => "+59175000{$s}",
            'genero'           => 'masculino',
            'fecha_nacimiento' => '1990-01-01',
        ]);

        $user = User::factory()->create(['rol_id' => Rol::firstOrCreate(['nombre' => 'Entrenador'])->id]);

        return Entrenador::create([
            'id_persona'     => $persona->id_persona,
            'id_usuario'     => $user->id,
            'especialidad'   => ['Calistenia'],
            'experiencia'    => '5 años',
            'disponibilidad' => ['lunes'],
            'estado'         => 'activo',
        ]);
    }

    private function asignar(int $dia, string $hora, ?int $idEntrenador, int $idSede = 1): HorarioEntrenador
    {
        return HorarioEntrenador::create([
            'dia_semana'    => $dia,
            'hora_inicio'   => $hora,
            'hora_fin'      => sprintf('%02d:00', ((int) substr($hora, 0, 2)) + 1),
            'id_entrenador' => $idEntrenador,
            'id_sede'       => $idSede,
        ]);
    }

    public function test_la_home_muestra_el_horario_real_desde_la_bd(): void
    {
        $entrenador = $this->crearEntrenador('Carlos Eduardo');
        $this->asignar(1, '07:00', $entrenador->id_entrenador);

        $this->get('/')
            ->assertStatus(200)
            ->assertSee('Horario Semanal', false)
            ->assertSee('Carlos Eduardo', false);
    }

    public function test_una_franja_con_entrenador_muestra_su_nombre_real(): void
    {
        $entrenador = $this->crearEntrenador('Gabriela Sofía');
        $this->asignar(3, '15:00', $entrenador->id_entrenador);

        $response = $this->get('/');
        $response->assertSee('Calistenia');
        $response->assertSee('Gabriela Sofía', false);
    }

    public function test_una_franja_sin_entrenador_muestra_cerrado(): void
    {
        // Ninguna asignación para el mediodía: debe verse "Cerrado" ahí.
        $this->get('/')->assertSee('Cerrado', false);
    }

    public function test_las_franjas_son_de_una_hora_de_07_a_21(): void
    {
        $response = $this->get('/');

        $response->assertSee('07:00 - 08:00', false);
        $response->assertSee('13:00 - 14:00', false);
        $response->assertSee('20:00 - 21:00', false);

        // El horario de ejemplo viejo usaba franjas de 2 horas: no deben quedar restos.
        $response->assertDontSee('07:00 - 09:00', false);
        $response->assertDontSee('18:30 - 20:30', false);
        $response->assertDontSee('Gimnasio libre', false);
        $response->assertDontSee('Horario de ejemplo', false);
    }

    /**
     * Ambos entrenadores están ACTIVOS, así que los dos aparecen siempre en
     * el marquee de Entrenadores (EntrenadorPublicoService) sin importar el
     * horario — esta prueba mira solo la TABLA de horario, no la página
     * entera, para no confundir esa sección con la del marquee.
     */
    private function tablaHorario(string $html): string
    {
        $inicio = strpos($html, 'mb-horario-tabla');
        $fin    = strpos($html, '</table>', $inicio);

        return substr($html, $inicio, $fin - $inicio);
    }

    public function test_el_horario_de_la_home_cambia_si_el_admin_cambia_la_grilla(): void
    {
        $juan  = $this->crearEntrenador('Juan Perez');
        $marco = $this->crearEntrenador('Marco Rojas');

        $this->asignar(2, '09:00', $juan->id_entrenador);
        $tabla = $this->tablaHorario($this->get('/')->getContent());
        $this->assertStringContainsString('Juan Perez', $tabla);
        $this->assertStringNotContainsString('Marco Rojas', $tabla);

        // El admin reasigna esa misma franja (mismo día+hora+sede, no duplica fila).
        HorarioEntrenador::where('dia_semana', 2)->where('hora_inicio', '09:00')->where('id_sede', 1)
            ->update(['id_entrenador' => $marco->id_entrenador]);

        $tabla = $this->tablaHorario($this->get('/')->getContent());
        $this->assertStringContainsString('Marco Rojas', $tabla);
        $this->assertStringNotContainsString('Juan Perez', $tabla);
    }
}
