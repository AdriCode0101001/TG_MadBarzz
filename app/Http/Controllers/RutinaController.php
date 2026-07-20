<?php

namespace App\Http\Controllers;

use App\Models\CalendarioMusculo;
use App\Models\Ejercicio;
use App\Models\GrupoMuscular;
use App\Models\Rutina;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Rutina estructurada (Etapa 3 del módulo de rutinas): el Entrenador (o
 * Administrador) elige un día del calendario, ve qué grupo muscular toca,
 * y arma su rutina eligiendo SOLO ejercicios de ese grupo, con series y
 * repeticiones. Una rutina por persona por día (ver migración).
 */
class RutinaController extends Controller
{
    private function sedeActual(): int
    {
        return Auth::user()->id_sede ?? 1;
    }

    /**
     * Regla de validación: el ejercicio debe existir. En un día normal,
     * además debe ser del grupo muscular del día. En un día "Full body" el
     * catálogo no tiene ejercicios propios de esa categoría (el dataset no
     * la trae), así que se permite cualquier grupo (o sin grupo).
     */
    private function reglaEjercicioDelGrupo(?int $idGrupo, bool $esFullBody): array
    {
        return [
            'required',
            Rule::exists('ejercicios', 'id')->where(function ($q) use ($idGrupo, $esFullBody) {
                $q->whereNull('deleted_at');
                if (!$esFullBody) {
                    $q->where('id_grupo_muscular', $idGrupo);
                }
            }),
        ];
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
        $inicioGrilla = $inicioMes->copy()->startOfWeek(Carbon::MONDAY);
        $finGrilla    = $finMes->copy()->endOfWeek(Carbon::SUNDAY);

        $sedeId = $this->sedeActual();

        $calendario = CalendarioMusculo::with('grupoMuscular')
            ->where('id_sede', $sedeId)
            ->whereBetween('fecha', [$inicioGrilla->toDateString(), $finGrilla->toDateString()])
            ->get()
            ->keyBy(fn ($c) => $c->fecha->toDateString());

        $misRutinas = Rutina::where('id_usuario', Auth::id())
            ->whereBetween('fecha', [$inicioGrilla->toDateString(), $finGrilla->toDateString()])
            ->withCount('ejercicios')
            ->get()
            ->keyBy(fn ($r) => $r->fecha->toDateString());

        $semanas = [];
        $semana  = [];
        for ($f = $inicioGrilla->copy(); $f->lte($finGrilla); $f->addDay()) {
            $fStr = $f->toDateString();
            $semana[] = [
                'fecha'      => $f->copy(),
                'enElMes'    => $f->month === $mes,
                'esDomingo'  => $f->dayOfWeekIso === 7,
                'calendario' => $calendario->get($fStr),
                'miRutina'   => $misRutinas->get($fStr),
            ];
            if ($f->dayOfWeekIso === 7) {
                $semanas[] = $semana;
                $semana = [];
            }
        }

        $mesAnterior = $mes === 1 ? 12 : $mes - 1;
        $anioMesAnterior = $mes === 1 ? $anio - 1 : $anio;
        $mesSiguiente = $mes === 12 ? 1 : $mes + 1;
        $anioMesSiguiente = $mes === 12 ? $anio + 1 : $anio;

        // El Administrador además ve (y puede borrar) las rutinas de TODO el personal.
        $todasLasRutinas = null;
        if (Auth::user()->hasRole('Administrador')) {
            $todasLasRutinas = Rutina::with(['grupoMuscular', 'usuario'])
                ->withCount('ejercicios')
                ->orderByDesc('fecha')
                ->get();
        }

        return view('rutinas.index', [
            'anio' => $anio, 'mes' => $mes,
            'mesAnterior' => $mesAnterior, 'anioMesAnterior' => $anioMesAnterior,
            'mesSiguiente' => $mesSiguiente, 'anioMesSiguiente' => $anioMesSiguiente,
            'semanas' => $semanas,
            'todasLasRutinas' => $todasLasRutinas,
        ]);
    }

    /** Pantalla para armar/editar MI rutina de un día (crea si no existe todavía). */
    public function edit(string $fecha)
    {
        $fechaCarbon = Carbon::createFromFormat('Y-m-d', $fecha)->startOfDay();

        if ($fechaCarbon->dayOfWeekIso === 7) {
            return redirect()->route('rutinas.index')
                ->with('error', 'El gimnasio cierra los domingos: no se puede armar rutina.');
        }

        $sedeId = $this->sedeActual();
        $calendario = CalendarioMusculo::with('grupoMuscular')
            ->where('id_sede', $sedeId)
            ->where('fecha', $fechaCarbon->toDateString())
            ->first();

        if (!$calendario) {
            return redirect()->route('rutinas.index')
                ->with('error', 'Este día todavía no tiene un grupo muscular asignado en el calendario.');
        }

        $rutina = Rutina::with('ejercicios.ejercicio')
            ->where('id_usuario', Auth::id())
            ->where('fecha', $fechaCarbon->toDateString())
            ->first();

        $esFullBody = (bool) ($calendario->grupoMuscular->es_full_body ?? false);

        // Día normal: solo el grupo del día (como siempre). Día "Full body":
        // el dataset no clasificó nada como "Full body", así que se ofrece
        // el catálogo completo (con su grupo, para las pestañas) y el
        // entrenador elige libremente de cualquier grupo.
        $ejerciciosDelGrupo = $esFullBody
            ? Ejercicio::orderBy('nombre')->get(['id', 'nombre', 'imagen', 'category', 'target', 'equipment', 'id_grupo_muscular'])
            : Ejercicio::where('id_grupo_muscular', $calendario->id_grupo_muscular)
                ->orderBy('nombre')
                ->get(['id', 'nombre', 'imagen', 'category', 'target', 'equipment', 'id_grupo_muscular']);

        $gruposParaTabs = $esFullBody
            ? GrupoMuscular::activos()->where('es_full_body', false)->orderBy('nombre')->get(['id', 'nombre'])
            : collect();

        $seleccionados = $rutina
            ? $rutina->ejercicios->map(fn ($re) => [
                'id_ejercicio' => $re->id_ejercicio,
                'nombre'       => $re->ejercicio->nombre ?? '(ejercicio eliminado)',
                'imagen'       => $re->ejercicio->imagen ?? null,
                'series'       => $re->series,
                'repeticiones' => $re->repeticiones,
                'nota'         => $re->nota,
                'media_propio' => $re->media_propio,
                'quitar_media' => false,
            ])->values()
            : collect();

        return view('rutinas.edit', [
            'fechaCarbon'        => $fechaCarbon,
            'calendario'         => $calendario,
            'rutina'             => $rutina,
            'ejerciciosDelGrupo' => $ejerciciosDelGrupo,
            'seleccionados'      => $seleccionados,
            'esFullBody'         => $esFullBody,
            'gruposParaTabs'     => $gruposParaTabs,
        ]);
    }

    /** Guarda (crea o actualiza) MI rutina del día: reemplaza todos sus ejercicios. */
    public function update(Request $request, string $fecha)
    {
        $fechaCarbon = Carbon::createFromFormat('Y-m-d', $fecha)->startOfDay();

        if ($fechaCarbon->dayOfWeekIso === 7) {
            return back()->withErrors(['fecha' => 'El gimnasio cierra los domingos: no se puede armar rutina.']);
        }

        $sedeId = $this->sedeActual();
        $calendario = CalendarioMusculo::where('id_sede', $sedeId)
            ->where('fecha', $fechaCarbon->toDateString())
            ->first();

        if (!$calendario) {
            return back()->withErrors(['fecha' => 'Este día todavía no tiene un grupo muscular asignado en el calendario.']);
        }

        $idGrupo = $calendario->id_grupo_muscular;
        $esFullBody = (bool) (GrupoMuscular::find($idGrupo)?->es_full_body ?? false);

        $data = $request->validate([
            'ejercicios'                 => 'array',
            'ejercicios.*.id_ejercicio'  => $this->reglaEjercicioDelGrupo($idGrupo, $esFullBody),
            'ejercicios.*.series'        => 'required|integer|min:1|max:50',
            'ejercicios.*.repeticiones'  => 'required|string|max:20',
            'ejercicios.*.nota'          => 'nullable|string|max:255',
            'ejercicios.*.media'         => 'nullable|file|mimes:jpg,jpeg,png,gif,mp4,webm|max:20480',
            'ejercicios.*.quitar_media'  => 'nullable|boolean',
        ]);

        DB::transaction(function () use ($fechaCarbon, $idGrupo, $sedeId, $data, $request) {
            $rutina = Rutina::withTrashed()->firstOrNew([
                'id_usuario' => Auth::id(),
                'fecha'      => $fechaCarbon->toDateString(),
            ]);
            $rutina->id_grupo_muscular = $idGrupo;
            $rutina->id_sede = $sedeId;
            $rutina->trashed() ? $rutina->restore() : $rutina->save();

            // Media propio (por rutina-ejercicio) de ANTES de reemplazar la
            // lista, para poder conservarlo si el entrenador no lo tocó, y
            // para saber qué archivos físicos quedan huérfanos y borrar.
            $mediaAnteriorPorEjercicio = $rutina->ejercicios()->pluck('media_propio', 'id_ejercicio')->filter()->all();
            $mediaEnUso = [];

            // Reemplaza la lista completa: más simple y robusto que diffear
            // altas/bajas/reordenamientos fila por fila.
            $rutina->ejercicios()->delete();
            foreach (($data['ejercicios'] ?? []) as $i => $item) {
                $mediaPropio = $mediaAnteriorPorEjercicio[$item['id_ejercicio']] ?? null;
                $archivo = $request->file("ejercicios.{$i}.media");

                if ($archivo instanceof UploadedFile) {
                    $mediaPropio = $this->guardarMediaPropio($archivo, $rutina->id);
                } elseif (!empty($item['quitar_media'])) {
                    $mediaPropio = null;
                }

                if ($mediaPropio) {
                    $mediaEnUso[] = $mediaPropio;
                }

                $rutina->ejercicios()->create([
                    'id_ejercicio' => $item['id_ejercicio'],
                    'series'       => $item['series'],
                    'repeticiones' => $item['repeticiones'],
                    'nota'         => $item['nota'] ?? null,
                    'orden'        => $i,
                    'media_propio' => $mediaPropio,
                ]);
            }

            // Ejercicios reemplazados/quitados/con "quitar media": su archivo
            // anterior ya no está en uso en ninguna fila nueva, se borra.
            foreach ($mediaAnteriorPorEjercicio as $pathAnterior) {
                if (!in_array($pathAnterior, $mediaEnUso, true)) {
                    Storage::disk('public')->delete($pathAnterior);
                }
            }
        });

        return redirect()->route('rutinas.edit', $fechaCarbon->toDateString())
            ->with('success', 'Rutina guardada correctamente.');
    }

    /** Guarda el media propio del entrenador para un ejercicio de SU rutina. */
    private function guardarMediaPropio(UploadedFile $archivo, int $idRutina): string
    {
        return $archivo->store("rutinas/{$idRutina}", 'public');
    }

    /** Detalle de solo lectura: el dueño o el Administrador. */
    public function show(Rutina $rutina)
    {
        $this->autorizarDueñoOAdmin($rutina);

        $rutina->load(['grupoMuscular', 'usuario', 'ejercicios.ejercicio']);

        return view('rutinas.show', compact('rutina'));
    }

    /** Borrar: el dueño, o el Administrador (de cualquier entrenador). */
    public function destroy(Rutina $rutina)
    {
        $this->autorizarDueñoOAdmin($rutina);

        foreach ($rutina->ejercicios()->pluck('media_propio')->filter() as $path) {
            Storage::disk('public')->delete($path);
        }

        $rutina->delete();

        return redirect()->route('rutinas.index')->with('success', 'Rutina eliminada correctamente.');
    }

    private function autorizarDueñoOAdmin(Rutina $rutina): void
    {
        $esDueño = $rutina->id_usuario === Auth::id();
        $esAdmin = Auth::user()->hasRole('Administrador');

        abort_unless($esDueño || $esAdmin, 403, 'No tienes permiso para acceder a la rutina de otro entrenador.');
    }
}
