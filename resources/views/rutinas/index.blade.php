@extends('layouts.private')
@section('contenido')

<x-page-header title="Rutinas" subtitle="Rutinas disponibles para los clientes" icon="fa-solid fa-dumbbell">
    <form method="GET" action="{{ route('rutinas.index') }}" class="mb-searchbar" style="max-width:320px">
        <i class="fa-solid fa-magnifying-glass mb-search-icon"></i>
        <input type="text" name="buscar" class="form-control"
               placeholder="Buscar rutina…"
               value="{{ $buscar ?? '' }}">
    </form>
    <a href="{{ route('rutinas.create') }}" class="btn btn-primary">
        <i class="fa-solid fa-circle-plus me-1"></i> Subir rutina
    </a>
</x-page-header>

@if(session('success'))
    <div class="alert alert-success border-0 rounded-3 mb-4">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger border-0 rounded-3 mb-4">{{ session('error') }}</div>
@endif

@if($rutinas->count())
    <div class="row g-4">
        @foreach($rutinas as $rutina)
        <div class="col-xl-3 col-lg-4 col-md-6">
            <div class="card custom-card h-100 overflow-hidden" style="border-radius:1rem;">

                {{-- Imagen --}}
                @if($rutina->archivo)
                    <div style="height:160px;overflow:hidden;">
                        <img src="{{ asset('storage/'.$rutina->archivo) }}" alt="Rutina"
                             style="width:100%;height:100%;object-fit:cover;">
                    </div>
                @else
                    <div style="height:160px;background:linear-gradient(135deg,rgba(99,102,241,.2),rgba(139,92,246,.2));
                                display:flex;align-items:center;justify-content:center;">
                        <i class="fa-solid fa-dumbbell fa-3x" style="color:#6366f1;opacity:.5;"></i>
                    </div>
                @endif

                <div class="card-body d-flex flex-column gap-2">
                    <h6 class="fw-bold mb-0">{{ $rutina->titulo }}</h6>
                    <p class="text-muted small mb-0" style="line-height:1.4;">
                        {{ \Illuminate\Support\Str::limit($rutina->descripcion, 90) }}
                    </p>
                    <div class="d-flex align-items-center gap-1 mt-auto pt-2">
                        <i class="fa-solid fa-user-tie small text-primary"></i>
                        <span class="small text-muted">
                            {{ $rutina->entrenador->persona->nombre_completo ?? 'Sin asignar' }}
                            {{ $rutina->entrenador->persona->apellido_paterno ?? '' }}
                        </span>
                    </div>
                    <div class="text-muted" style="font-size:.75rem;">
                        <i class="fa-regular fa-calendar me-1"></i>
                        {{ $rutina->fecha_subida
                            ? \Carbon\Carbon::parse($rutina->fecha_subida)->format('d/m/Y')
                            : ($rutina->created_at ? $rutina->created_at->format('d/m/Y') : '—') }}
                    </div>
                </div>

                <div class="card-footer d-flex gap-2 justify-content-end" style="background:transparent;">
                    <a href="{{ route('rutinas.edit', $rutina->id_rutina) }}"
                       class="btn btn-sm btn-warning" title="Editar">
                        <i class="fa-solid fa-pen-to-square"></i>
                    </a>
                    <form action="{{ route('rutinas.destroy', $rutina->id_rutina) }}" method="POST"
                          data-confirm="¿Eliminar la rutina «{{ $rutina->titulo }}»?">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-danger" title="Eliminar">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </form>
                </div>

            </div>
        </div>
        @endforeach
    </div>
@else
    <div class="card custom-card">
        <div class="card-body">
            <div class="mb-empty">
                <div class="mb-empty-icon"><i class="fa-solid fa-dumbbell"></i></div>
                <p class="mb-2">No hay rutinas subidas.</p>
                <a href="{{ route('rutinas.create') }}" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-circle-plus me-1"></i> Subir la primera
                </a>
            </div>
        </div>
    </div>
@endif

@endsection
