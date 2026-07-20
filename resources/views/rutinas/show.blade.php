@extends('layouts.private')

@section('contenido')
<div class="col-12 mb-rutinas-page">

    <x-page-header title="Rutina del {{ $rutina->fecha->translatedFormat('d \d\e F Y') }}"
                   subtitle="{{ $rutina->usuario?->nombreCompleto() ?? $rutina->usuario?->name ?? '—' }}"
                   icon="fa-solid fa-dumbbell">
        <span class="badge bg-primary" style="font-size:.85rem;">{{ $rutina->grupoMuscular->nombre ?? '—' }}</span>
        <a href="{{ route('rutinas.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-arrow-left me-1"></i> Volver
        </a>
    </x-page-header>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Ejercicios <span class="text-muted fw-normal" style="font-size:.8rem;">({{ $rutina->ejercicios->count() }})</span></h5>
        </div>
        <div class="card-body p-0">
            @if ($rutina->ejercicios->isEmpty())
                <div class="mb-empty">
                    <div class="mb-empty-icon"><i class="fa-solid fa-dumbbell"></i></div>
                    <p class="mb-2">Esta rutina todavía no tiene ejercicios.</p>
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
                            @foreach ($rutina->ejercicios as $i => $re)
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td>
                                        <div class="mb-user-cell">
                                            @if ($re->ejercicio?->imagen)
                                                <img src="{{ asset('storage/' . $re->ejercicio->imagen) }}" alt="" style="width:38px;height:38px;border-radius:.4rem;object-fit:cover;">
                                            @endif
                                            <span class="text-capitalize">{{ $re->ejercicio->nombre ?? '(ejercicio eliminado)' }}</span>
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
@endsection
