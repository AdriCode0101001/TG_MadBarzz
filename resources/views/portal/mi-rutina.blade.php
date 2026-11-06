@extends('layouts.private')

@section('contenido')
<div class="col-12 mb-portal">

    <x-page-header title="Mi Rutina" subtitle="La rutina del día, según el músculo que toca" icon="fa-solid fa-dumbbell">
        <form method="GET" action="{{ route('portal.rutina') }}" class="d-flex align-items-center gap-2">
            <a href="{{ route('portal.rutina', ['fecha' => $fecha->copy()->subDay()->toDateString()]) }}" class="btn btn-sm btn-outline-secondary" title="Día anterior">
                <i class="fa-solid fa-chevron-left"></i>
            </a>
            <input type="date" name="fecha" value="{{ $fecha->toDateString() }}" max="{{ $hoy->toDateString() }}"
                   class="form-control form-control-sm" style="max-width:170px" onchange="this.form.submit()">
            @if ($fecha->lt($hoy))
                <a href="{{ route('portal.rutina', ['fecha' => $fecha->copy()->addDay()->toDateString()]) }}" class="btn btn-sm btn-outline-secondary" title="Día siguiente">
                    <i class="fa-solid fa-chevron-right"></i>
                </a>
            @else
                <span class="btn btn-sm btn-outline-secondary disabled"><i class="fa-solid fa-chevron-right"></i></span>
            @endif
            @if (!$fecha->isSameDay($hoy))
                <a href="{{ route('portal.rutina') }}" class="btn btn-sm btn-outline-primary">Hoy</a>
            @endif
        </form>
    </x-page-header>

    @php $esHoy = $fecha->isSameDay($hoy); @endphp

    @if (!$membresiaVigente)
        <div class="card">
            <div class="card-body">
                <div class="mb-empty py-4">
                    <div class="mb-empty-icon"><i class="fa-solid fa-dumbbell"></i></div>
                    <h5 class="mb-2">No tienes una membresía activa</h5>
                    <p class="mb-0">Renueva tu membresía para ver la rutina del día. Pasa por recepción para adquirir o renovar tu membresía.</p>
                </div>
            </div>
        </div>
    @elseif ($esDomingo)
        <div class="card">
            <div class="card-body">
                <div class="mb-empty py-4">
                    <div class="mb-empty-icon"><i class="fa-solid fa-mug-hot"></i></div>
                    <h5 class="mb-2">{{ $esHoy ? 'Hoy descansa' : 'Ese día el gimnasio cerró' }}</h5>
                    <p class="mb-0">Los domingos el gimnasio cierra: no hay rutina asignada.</p>
                </div>
            </div>
        </div>
    @elseif (!$calendario)
        <div class="card">
            <div class="card-body">
                <div class="mb-empty py-4">
                    <div class="mb-empty-icon"><i class="fa-solid fa-calendar-xmark"></i></div>
                    <h5 class="mb-2">{{ $esHoy ? 'Hoy el gimnasio no tiene rutina asignada' : 'Ese día no hubo un músculo asignado' }}</h5>
                    <p class="mb-0">Todavía no se definió qué músculo trabajar {{ $esHoy ? 'hoy' : 'ese día' }}.</p>
                </div>
            </div>
        </div>
    @else
        <div class="card mb-4">
            <div class="card-body d-flex align-items-center gap-3">
                <span class="mb-section-ico" style="background:rgba(255, 107, 26,.14);color:#ff6b1a;width:48px;height:48px;font-size:1.3rem;">
                    <i class="fa-solid fa-dumbbell"></i>
                </span>
                <div>
                    <div class="text-muted" style="font-size:.8rem;">{{ $esHoy ? 'Hoy toca' : 'Ese día tocaba' }}</div>
                    <h5 class="mb-0">{{ $calendario->grupoMuscular->nombre ?? '—' }}</h5>
                </div>
            </div>
        </div>

        @if ($rutinasDelDia->isEmpty())
            <div class="card">
                <div class="card-body">
                    <div class="mb-empty py-4">
                        <div class="mb-empty-icon"><i class="fa-solid fa-dumbbell"></i></div>
                        <h5 class="mb-2">Aún no hay rutinas publicadas</h5>
                        <p class="mb-0">
                            {{ $esHoy ? 'Hoy toca' : 'Ese día tocaba' }} {{ $calendario->grupoMuscular->nombre ?? '' }},
                            pero ningún entrenador {{ $esHoy ? 'subió' : 'subió' }} su rutina {{ $esHoy ? 'todavía' : 'ese día' }}.
                        </p>
                    </div>
                </div>
            </div>
        @else
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="card h-100">
                        <div class="card-header"><h5 class="mb-0">Elige un entrenador</h5></div>
                        <div class="card-body p-2">
                            @foreach ($rutinasDelDia as $r)
                                @php $activo = $rutinaSeleccionada && $rutinaSeleccionada->id === $r->id; @endphp
                                <a href="{{ route('portal.rutina', ['fecha' => $fecha->toDateString(), 'entrenador' => $r->id_usuario]) }}"
                                   class="mb-rut-entrenador {{ $activo ? 'mb-rut-entrenador--activo' : '' }}">
                                    <span class="mb-rut-entrenador__avatar"><i class="fa-solid fa-user-tie"></i></span>
                                    <span class="flex-grow-1">
                                        <span class="d-block fw-semibold">{{ $r->usuario?->nombreCompletoConApellido() ?? $r->usuario?->name ?? 'Entrenador' }}</span>
                                        <span class="d-block text-muted" style="font-size:.75rem;">{{ $r->ejercicios_count }} ejercicio(s)</span>
                                    </span>
                                    @if ($activo)
                                        <i class="fa-solid fa-circle-check text-success"></i>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="card h-100">
                        <div class="card-header"><h5 class="mb-0">Ejercicios</h5></div>
                        <div class="card-body p-0">
                            @if (!$rutinaSeleccionada)
                                <div class="mb-empty py-4">
                                    <div class="mb-empty-icon"><i class="fa-solid fa-hand-pointer"></i></div>
                                    <p class="mb-0">Elegí un entrenador de la lista para ver su rutina.</p>
                                </div>
                            @elseif ($rutinaSeleccionada->ejercicios->isEmpty())
                                <div class="mb-empty py-4">
                                    <div class="mb-empty-icon"><i class="fa-solid fa-dumbbell"></i></div>
                                    <p class="mb-0">Esta rutina todavía no tiene ejercicios cargados.</p>
                                </div>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Ejercicio</th>
                                                <th class="text-center">Series</th>
                                                <th class="text-center">Repeticiones</th>
                                                <th>Nota</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($rutinaSeleccionada->ejercicios as $i => $re)
                                                @php
                                                    // El media propio del entrenador (subido para ESTA rutina) tiene
                                                    // prioridad; si no existe, se cae al gif del catálogo. La
                                                    // atribución (© Gym visual) solo corresponde al catálogo.
                                                    $mediaUrl = $re->media_propio
                                                        ? asset('storage/' . $re->media_propio)
                                                        : ($re->ejercicio?->gif ? asset('storage/' . $re->ejercicio->gif) : null);
                                                    $atribucionMedia = $re->media_propio ? '' : ($re->ejercicio->atribucion ?? '');
                                                @endphp
                                                <tr>
                                                    <td>{{ $i + 1 }}</td>
                                                    <td>
                                                        <div class="mb-user-cell">
                                                            @if ($re->ejercicio?->imagen)
                                                                <img src="{{ asset('storage/' . $re->ejercicio->imagen) }}" alt="" style="width:38px;height:38px;border-radius:.4rem;object-fit:cover;">
                                                            @endif
                                                            <div>
                                                                <span class="text-capitalize d-block">{{ $re->ejercicio->nombre ?? '(ejercicio eliminado)' }}</span>
                                                                @if ($mediaUrl)
                                                                    <button type="button" class="btn btn-link p-0 mb-ver-gif" style="font-size:.72rem;"
                                                                            data-gif="{{ $mediaUrl }}"
                                                                            data-nombre="{{ $re->ejercicio->nombre }}"
                                                                            data-atribucion="{{ $atribucionMedia }}"
                                                                            data-instrucciones="{{ $re->ejercicio->instrucciones }}">
                                                                        <i class="fa-solid fa-film me-1"></i>Ver GIF
                                                                    </button>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="text-center">{{ $re->series }}</td>
                                                    <td class="text-center">{{ $re->repeticiones }}</td>
                                                    <td class="text-muted">{{ $re->nota ?? '—' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>

<x-ejercicio-gif-modal />

@push('styles')
<style>
.mb-portal .card { height: auto; }

.mb-rut-entrenador {
    display: flex; align-items: center; gap: .6rem;
    padding: .6rem .7rem; border-radius: .5rem;
    text-decoration: none; color: inherit;
    transition: background .12s;
}
.mb-rut-entrenador:hover { background: rgba(255, 107, 26,.1); color: inherit; }
.mb-rut-entrenador--activo { background: rgba(30,201,139,.12); }
.mb-rut-entrenador__avatar {
    width: 38px; height: 38px; border-radius: 50%; flex: none;
    background: rgba(255, 107, 26,.15); color: #ff6b1a;
    display: flex; align-items: center; justify-content: center;
}
</style>
@endpush
@endsection
