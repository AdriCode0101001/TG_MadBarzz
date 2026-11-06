@extends('layouts.private')

@section('contenido')
@php
    $meses = [1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'];

    $mesAnt = $mesAnterior; $anioAnt = $anioMesAnterior;
    $mesSig = $mesSiguiente; $anioSig = $anioMesSiguiente;
@endphp
<div class="col-12 mb-rutinas-page">

    <x-page-header title="Rutinas" subtitle="Elige un día del calendario para armar tu rutina" icon="fa-solid fa-dumbbell">
        <a href="{{ route('rutinas.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-calendar-day me-1"></i> Hoy
        </a>
    </x-page-header>

    @if (session('error'))
        <div class="alert alert-danger border-0 rounded-3 mb-4">{{ session('error') }}</div>
    @endif
    @if (session('success'))
        <div class="alert alert-success border-0 rounded-3 mb-4">{{ session('success') }}</div>
    @endif

    <div class="card mb-4">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('rutinas.index', ['anio' => $anioAnt, 'mes' => $mesAnt]) }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fa-solid fa-chevron-left"></i>
                </a>
                <h5 class="mb-0">{{ $meses[$mes] }} {{ $anio }}</h5>
                <a href="{{ route('rutinas.index', ['anio' => $anioSig, 'mes' => $mesSig]) }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fa-solid fa-chevron-right"></i>
                </a>
            </div>
            <span class="text-muted" style="font-size:.82rem;">Mi calendario de rutinas</span>
        </div>
        <div class="card-body">
            <div class="mb-cal-grid">
                <div class="mb-cal-head">Lun</div>
                <div class="mb-cal-head">Mar</div>
                <div class="mb-cal-head">Mié</div>
                <div class="mb-cal-head">Jue</div>
                <div class="mb-cal-head">Vie</div>
                <div class="mb-cal-head">Sáb</div>
                <div class="mb-cal-head mb-cal-head--dom">Dom</div>

                @foreach ($semanas as $semana)
                    @foreach ($semana as $dia)
                        <div class="mb-cal-day {{ !$dia['enElMes'] ? 'mb-cal-day--fuera' : '' }} {{ $dia['esDomingo'] ? 'mb-cal-day--cerrado' : '' }}">
                            <div class="mb-cal-day__num">{{ $dia['fecha']->day }}</div>

                            @if ($dia['esDomingo'])
                                <div class="mb-cal-day__cerrado">Cerrado</div>
                            @elseif ($dia['enElMes'])
                                @if ($dia['calendario'])
                                    <a href="{{ route('rutinas.edit', $dia['fecha']->toDateString()) }}" class="mb-rut-dia {{ $dia['miRutina'] ? 'mb-rut-dia--tengo' : '' }}">
                                        <span class="mb-rut-dia__grupo">{{ $dia['calendario']->grupoMuscular->nombre ?? '—' }}</span>
                                        @if ($dia['miRutina'])
                                            <span class="mb-rut-dia__badge"><i class="fa-solid fa-circle-check me-1"></i>{{ $dia['miRutina']->ejercicios_count }} ejerc.</span>
                                        @else
                                            <span class="mb-rut-dia__badge mb-rut-dia__badge--vacio"><i class="fa-solid fa-plus me-1"></i>Armar</span>
                                        @endif
                                    </a>
                                @else
                                    <div class="mb-rut-dia mb-rut-dia--sinmusculo" title="Sin grupo muscular asignado en el calendario">
                                        <span class="mb-rut-dia__grupo text-muted">Sin músculo</span>
                                    </div>
                                @endif
                            @endif
                        </div>
                    @endforeach
                @endforeach
            </div>
        </div>
    </div>

    @if ($todasLasRutinas !== null)
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fa-solid fa-users-gear me-2"></i>Rutinas de todo el personal</h5>
                <p class="mb-0 text-muted" style="font-size:.82rem;">Como Administrador podés eliminar la rutina de cualquier entrenador.</p>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Entrenador</th>
                                <th>Grupo muscular</th>
                                <th class="text-center">Ejercicios</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($todasLasRutinas as $r)
                                <tr>
                                    <td>{{ $r->fecha->translatedFormat('d M Y') }}</td>
                                    <td>{{ $r->usuario?->nombreCompleto() ?? $r->usuario?->name ?? '—' }}</td>
                                    <td><span class="badge bg-primary">{{ $r->grupoMuscular->nombre ?? '—' }}</span></td>
                                    <td class="text-center">{{ $r->ejercicios_count }}</td>
                                    <td class="text-end">
                                        <div class="mb-actions justify-content-end">
                                            <a href="{{ route('rutinas.show', $r) }}" class="btn btn-sm btn-outline-primary" title="Ver">
                                                <i class="fa-solid fa-eye"></i>
                                            </a>
                                            <form action="{{ route('rutinas.destroy', $r) }}" method="POST"
                                                  data-confirm="¿Eliminar la rutina del {{ $r->fecha->format('d/m/Y') }}?">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-danger" title="Eliminar">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5">
                                        <div class="mb-empty">
                                            <div class="mb-empty-icon"><i class="fa-solid fa-dumbbell"></i></div>
                                            <p class="mb-2">Todavía no hay rutinas armadas.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>

@push('styles')
<style>
.mb-rutinas-page .card { height: auto; }

.mb-cal-grid {
    display: grid;
    grid-template-columns: repeat(7, minmax(0, 1fr));
    gap: .5rem;
}
.mb-cal-head {
    text-align: center;
    font-weight: 700;
    font-size: .75rem;
    text-transform: uppercase;
    letter-spacing: .06em;
    opacity: .65;
    padding-bottom: .4rem;
}
.mb-cal-head--dom { color: #f5365c; }
.mb-cal-day {
    min-height: 90px;
    min-width: 0;
    border-radius: .6rem;
    border: 1px solid rgba(128,128,128,.16);
    padding: .5rem;
    display: flex;
    flex-direction: column;
    gap: .4rem;
}
.mb-cal-day--fuera { opacity: .35; }
.mb-cal-day--cerrado { background: rgba(128,128,128,.08); }
.mb-cal-day__num { font-weight: 700; font-size: .85rem; }
.mb-cal-day__cerrado { font-size: .72rem; opacity: .6; text-transform: uppercase; letter-spacing: .04em; margin-top: auto; }

.mb-rut-dia {
    display: flex; flex-direction: column; gap: .35rem;
    text-decoration: none; color: inherit;
    border-radius: .5rem; padding: .35rem .4rem;
    background: rgba(255, 107, 26,.08);
    transition: background .15s;
    flex: 1;
}
a.mb-rut-dia:hover { background: rgba(255, 107, 26,.18); color: inherit; }
.mb-rut-dia--tengo { background: rgba(30,201,139,.12); }
a.mb-rut-dia--tengo:hover { background: rgba(30,201,139,.22); }
.mb-rut-dia--sinmusculo { background: transparent; cursor: not-allowed; }
.mb-rut-dia__grupo { font-size: .72rem; font-weight: 600; line-height: 1.15; }
.mb-rut-dia__badge { font-size: .66rem; opacity: .8; }
.mb-rut-dia__badge--vacio { opacity: .55; }

@media (max-width: 767.98px) {
    .mb-cal-grid { grid-template-columns: repeat(7, minmax(70px, 1fr)); overflow-x: auto; }
    .mb-cal-day { min-height: 70px; }
}
</style>
@endpush
@endsection
