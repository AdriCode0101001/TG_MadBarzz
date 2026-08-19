<?php

namespace App\Http\Controllers;

use App\Models\Entrenador;
use App\Models\HorarioEntrenador;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Horario BASE semanal de entrenadores (Parte 1: solo gestión del
 * Administrador — la vista pública es la Parte 2, todavía no construida).
 * El gimnasio abre lunes a sábado, franjas de 1 hora de 07:00 a 21:00; el
 * domingo nunca recibe asignación.
 */
class HorarioEntrenadorController extends Controller
{
    private const DIAS = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado'];

    private const HORA_APERTURA = 7;
    private const HORA_CIERRE   = 21;

    /** Franjas de 1 hora dentro del rango de apertura, en formato "H:i" (ej. "07:00"). */
    private function franjas(): array
    {
        $franjas = [];
        for ($h = self::HORA_APERTURA; $h < self::HORA_CIERRE; $h++) {
            $franjas[] = sprintf('%02d:00', $h);
        }

        return $franjas;
    }

    private function sedeActual(): int
    {
        return Auth::user()->id_sede ?? 1;
    }

    /** Regla de validación para el id de entrenador asignable a una franja. */
    private function reglaEntrenador(): array
    {
        return [
            'nullable',
            Rule::exists('entrenadores', 'id_entrenador')
                ->where(fn ($q) => $q->where('estado', 'activo')->whereNull('deleted_at')),
        ];
    }

    public function index()
    {
        $sedeId = $this->sedeActual();

        $asignaciones = HorarioEntrenador::with('entrenador.persona')
            ->where('id_sede', $sedeId)
            ->get()
            ->keyBy(fn (HorarioEntrenador $h) => $h->dia_semana . '_' . substr($h->hora_inicio, 0, 5));

        $entrenadores = Entrenador::with('persona')
            ->where('estado', 'activo')
            ->get()
            ->sortBy(fn (Entrenador $e) => optional($e->persona)->nombre_completo);

        return view('horarios_entrenadores.index', [
            'dias'         => self::DIAS,
            'franjas'      => $this->franjas(),
            'asignaciones' => $asignaciones,
            'entrenadores' => $entrenadores,
            // Recepcionista y Entrenador ven esta grilla en solo lectura
            // (les sirve saber quién cubre cada franja); solo Admin edita.
            'puedeEditar'  => Auth::user()->hasRole('Administrador'),
        ]);
    }

    /**
     * Guarda TODA la grilla de una vez: el admin arma/cambia las casillas
     * que quiera en la vista (sin que nada se guarde solo) y recién acá se
     * persiste todo junto, en una transacción. Reemplaza el guardado al
     * vuelo por casilla que tenía antes esta pantalla.
     *
     * Payload esperado: asignaciones[dia][hora] = id_entrenador|'' , para
     * cada una de las 84 casillas visibles en la grilla (la vista siempre
     * manda las 84, aunque estén "— Sin asignar —").
     */
    public function guardar(Request $request)
    {
        $data = $request->validate([
            'asignaciones'     => ['required', 'array'],
            'asignaciones.*'   => ['array'],
            'asignaciones.*.*' => $this->reglaEntrenador(),
        ]);

        $diasValidos    = array_keys(self::DIAS);
        $franjasValidas = $this->franjas();

        // Domingo o cualquier día/franja fuera de rango: se rechaza TODO el
        // guardado (no se persiste nada), igual que antes rechazaba esa
        // casilla puntual — acá no hay "una casilla", es todo o nada.
        foreach ($data['asignaciones'] as $dia => $porHora) {
            if (! in_array((int) $dia, $diasValidos, true)) {
                throw ValidationException::withMessages([
                    'asignaciones' => 'El gimnasio cierra los domingos: no se puede asignar un entrenador.',
                ]);
            }

            foreach (array_keys($porHora) as $hora) {
                if (! in_array($hora, $franjasValidas, true)) {
                    throw ValidationException::withMessages([
                        'asignaciones' => 'Hay una franja horaria inválida en el horario enviado.',
                    ]);
                }
            }
        }

        $sedeId = $this->sedeActual();

        DB::transaction(function () use ($data, $sedeId) {
            foreach ($data['asignaciones'] as $dia => $porHora) {
                foreach ($porHora as $hora => $idEntrenador) {
                    $horaFin = sprintf('%02d:00', ((int) substr($hora, 0, 2)) + 1);

                    $registro = HorarioEntrenador::firstOrNew([
                        'dia_semana'  => (int) $dia,
                        'hora_inicio' => $hora,
                        'id_sede'     => $sedeId,
                    ]);
                    $registro->hora_fin      = $horaFin;
                    $registro->id_entrenador = ($idEntrenador === '' || $idEntrenador === null) ? null : $idEntrenador;
                    $registro->save();
                }
            }
        });

        return back()->with('success', 'Horario guardado correctamente.');
    }
}
