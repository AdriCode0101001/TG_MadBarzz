<?php

namespace App\Http\Controllers;

use App\Models\CalendarioMusculo;
use App\Models\GrupoMuscular;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Calendario mensual de grupos musculares (Etapa 2 del módulo de rutinas).
 * Solo el Administrador lo gestiona; el gimnasio abre lunes a sábado, así
 * que los domingos nunca reciben asignación.
 */
class CalendarioMusculoController extends Controller
{
    /** Reglas de validación para el id de grupo muscular asignable a un día. */
    private function reglaGrupoMuscular(): array
    {
        return [
            'nullable',
            Rule::exists('grupos_musculares', 'id')
                ->where(fn ($q) => $q->where('activo', true)->whereNull('deleted_at')),
        ];
    }

    private function sedeActual(): int
    {
        return Auth::user()->id_sede ?? 1;
    }

    public function index(Request $request)
    {
        $hoy  = Carbon::now('America/La_Paz');
        $anio = (int) $request->query('anio', $hoy->year);
        $mes  = (int) $request->query('mes', $hoy->month);

        if ($mes < 1 || $mes > 12) {
            $mes = $hoy->month;
        }

        $inicioMes = Carbon::createFromDate($anio, $mes, 1)->startOfMonth();
        $finMes    = $inicioMes->copy()->endOfMonth();

        // Cuadrícula: desde el lunes de la semana del día 1 hasta el domingo
        // de la semana del último día del mes.
        $inicioGrilla = $inicioMes->copy()->startOfWeek(Carbon::MONDAY);
        $finGrilla    = $finMes->copy()->endOfWeek(Carbon::SUNDAY);

        $asignaciones = CalendarioMusculo::with('grupoMuscular')
            ->whereBetween('fecha', [$inicioGrilla->toDateString(), $finGrilla->toDateString()])
            ->get()
            ->keyBy(fn ($c) => $c->fecha->toDateString());

        $semanas = [];
        $semana  = [];
        for ($f = $inicioGrilla->copy(); $f->lte($finGrilla); $f->addDay()) {
            $semana[] = [
                'fecha'        => $f->copy(),
                'enElMes'      => $f->month === $mes,
                'esDomingo'    => $f->dayOfWeekIso === 7,
                'asignacion'   => $asignaciones->get($f->toDateString()),
            ];
            if ($f->dayOfWeekIso === 7) {
                $semanas[] = $semana;
                $semana = [];
            }
        }

        $gruposMusculares = GrupoMuscular::activos()->orderBy('nombre')->get();

        // Para el aviso de sobrescritura del patrón semanal: cuántos días
        // lun-sáb de ESTE mes ya tienen un grupo muscular asignado.
        $diasYaAsignados = $asignaciones
            ->filter(fn ($c) => $c->fecha->month === $mes && $c->fecha->dayOfWeekIso !== 7)
            ->count();

        return view('calendario_musculos.index', [
            'anio'             => $anio,
            'mes'              => $mes,
            'inicioMes'        => $inicioMes,
            'semanas'          => $semanas,
            'gruposMusculares' => $gruposMusculares,
            'diasYaAsignados'  => $diasYaAsignados,
        ]);
    }

    /** Asigna, cambia o quita (id_grupo_muscular = null) el músculo de un día. */
    public function asignarDia(Request $request, string $fecha)
    {
        $fechaCarbon = Carbon::createFromFormat('Y-m-d', $fecha);

        if ($fechaCarbon->dayOfWeekIso === 7) {
            return back()->withErrors(['id_grupo_muscular' => 'El gimnasio cierra los domingos: no se puede asignar un músculo.']);
        }

        $data = $request->validate([
            'id_grupo_muscular' => $this->reglaGrupoMuscular(),
        ]);

        $sedeId = $this->sedeActual();

        if (empty($data['id_grupo_muscular'])) {
            CalendarioMusculo::where('fecha', $fechaCarbon->toDateString())
                ->where('id_sede', $sedeId)
                ->delete();

            return back()->with('success', "Se liberó el {$fechaCarbon->translatedFormat('d \d\e F')}.");
        }

        $registro = CalendarioMusculo::withTrashed()->firstOrNew([
            'fecha'   => $fechaCarbon->toDateString(),
            'id_sede' => $sedeId,
        ]);
        $registro->id_grupo_muscular = $data['id_grupo_muscular'];

        $registro->trashed() ? $registro->restore() : $registro->save();

        return back()->with('success', "Se asignó el músculo del {$fechaCarbon->translatedFormat('d \d\e F')}.");
    }

    /** Aplica un patrón semanal (lunes..sábado) a todo un mes. */
    public function aplicarPatron(Request $request)
    {
        $data = $request->validate([
            'anio'      => 'required|integer|min:2020|max:2100',
            'mes'       => 'required|integer|min:1|max:12',
            'patron'    => 'required|array',
            'patron.*'  => $this->reglaGrupoMuscular(),
        ]);

        $sedeId = $this->sedeActual();

        $inicioMes = Carbon::createFromDate($data['anio'], $data['mes'], 1)->startOfMonth();
        $finMes    = $inicioMes->copy()->endOfMonth();

        $aplicados = 0;

        for ($f = $inicioMes->copy(); $f->lte($finMes); $f->addDay()) {
            $diaIso = $f->dayOfWeekIso; // 1 = lunes ... 7 = domingo

            if ($diaIso === 7) {
                continue; // cerrado
            }

            $idGrupo = $data['patron'][$diaIso] ?? null;
            if (empty($idGrupo)) {
                continue; // ese día de la semana no se definió en el patrón: no se toca
            }

            $registro = CalendarioMusculo::withTrashed()->firstOrNew([
                'fecha'   => $f->toDateString(),
                'id_sede' => $sedeId,
            ]);
            $registro->id_grupo_muscular = $idGrupo;
            $registro->trashed() ? $registro->restore() : $registro->save();
            $aplicados++;
        }

        return redirect()
            ->route('calendario_musculos.index', ['anio' => $data['anio'], 'mes' => $data['mes']])
            ->with('success', "Patrón aplicado: {$aplicados} día(s) actualizado(s).");
    }
}
