@extends('layouts.private')

@section('contenido')
<div class="col-12 mb-portal">

    <x-page-header title="Mis Rutinas" subtitle="Rutinas de entrenamiento del gimnasio" icon="fa-solid fa-dumbbell" />

    @if(!$membresiaVigente)
        {{-- Sin membresía vigente: no se muestran las rutinas --}}
        <div class="card">
            <div class="card-body">
                <div class="mb-empty py-5">
                    <div class="mb-empty-icon"><i class="fa-solid fa-dumbbell"></i></div>
                    <h5 class="mb-2">Las rutinas están disponibles para socios con membresía activa</h5>
                    <p class="mb-3">
                        Renueva tu membresía para acceder.<br>
                        Puedes adquirirla o renovarla en recepción.
                    </p>
                    <a href="{{ route('portal.membresia') }}" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-id-badge me-1"></i> Ver mi membresía
                    </a>
                </div>
            </div>
        </div>
    @elseif($rutinas->isEmpty())
        <div class="card">
            <div class="card-body">
                <div class="mb-empty py-5">
                    <div class="mb-empty-icon"><i class="fa-solid fa-dumbbell"></i></div>
                    <h5 class="mb-2">No hay rutinas publicadas todavía</h5>
                    <p class="mb-0">Cuando el equipo de entrenadores publique rutinas, las verás aquí.</p>
                </div>
            </div>
        </div>
    @else
        <div class="row">
            @foreach($rutinas as $rutina)
                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card h-100">
                        @if($rutina->archivo)
                            <img src="{{ asset('storage/'.$rutina->archivo) }}" alt="{{ $rutina->titulo }}"
                                 style="width:100%;height:160px;object-fit:cover;border-radius:14px 14px 0 0;">
                        @else
                            <div class="d-flex align-items-center justify-content-center"
                                 style="height:160px;background:rgba(99,102,241,.12);color:#6366f1;font-size:2.2rem;border-radius:14px 14px 0 0;">
                                <i class="fa-solid fa-dumbbell"></i>
                            </div>
                        @endif
                        <div class="card-body">
                            <h5 class="mb-1">{{ $rutina->titulo }}</h5>
                            <div class="mb-2" style="font-size:.8rem;color:#6366f1;">
                                <i class="fa-solid fa-user-tie me-1"></i>
                                {{ $rutina->entrenador->persona->nombre_completo ?? 'Sin asignar' }}
                            </div>
                            <p class="text-muted mb-0" style="font-size:.85rem;">
                                {{ \Illuminate\Support\Str::limit($rutina->descripcion, 120) }}
                            </p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

@push('styles')
<style>
/* Altura natural para las tarjetas apiladas del portal (ver mi-membresia) */
.mb-portal > .card { height: auto; }
</style>
@endpush
@endsection
