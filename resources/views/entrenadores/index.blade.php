@extends('layouts.private')
@section('contenido')

<x-page-header title="Entrenadores" subtitle="Personal de entrenamiento del gimnasio" icon="fa-solid fa-dumbbell">
    <form method="GET" action="{{ route('entrenadores.index') }}" class="mb-searchbar" style="max-width:300px">
        <i class="fa-solid fa-magnifying-glass mb-search-icon"></i>
        <input type="text" name="buscar" class="form-control" placeholder="Buscar entrenador…" value="{{ request('buscar') }}">
    </form>
    <a href="{{ route('entrenadores.create') }}" class="btn btn-primary">
        <i class="fa-solid fa-circle-plus me-1"></i> Nuevo entrenador
    </a>
</x-page-header>

@if(session('success'))
    <div class="alert alert-success border-0 rounded-3 mb-4">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger border-0 rounded-3 mb-4">{{ session('error') }}</div>
@endif

@if($entrenadores->count())
    <div class="row g-4">
        @foreach($entrenadores as $e)
            @php
                $nombre = trim(($e->persona->nombre_completo ?? '') . ' ' . ($e->persona->apellido_paterno ?? '') . ' ' . ($e->persona->apellido_materno ?? ''));
                $inicial = strtoupper(mb_substr($nombre ?: 'E', 0, 1));
                $activo  = $e->estado === 'activo';
                $disps   = is_array($e->disponibilidad) ? $e->disponibilidad : (json_decode($e->disponibilidad, true) ?? []);
                $esps    = is_array($e->especialidad)   ? $e->especialidad   : explode(', ', $e->especialidad ?? '');
            @endphp
            <div class="col-xl-3 col-lg-4 col-md-6">
                <div class="card custom-card h-100" style="border-radius:1rem;overflow:hidden;">

                    {{-- Header de color --}}
                    <div style="height:70px;background:linear-gradient(135deg,#0f2027,#203a43,#2c5364);position:relative;">
                        <span class="badge position-absolute top-2 end-2 mt-2 me-2 {{ $activo ? 'bg-success' : 'bg-danger' }}">
                            {{ $activo ? 'Activo' : 'Inactivo' }}
                        </span>
                    </div>

                    {{-- Avatar --}}
                    <div style="display:flex;justify-content:center;margin-top:-32px;position:relative;z-index:1;">
                        <div style="width:64px;height:64px;border-radius:50%;background:linear-gradient(135deg,#1ec98b,#0ea374);
                                    display:flex;align-items:center;justify-content:center;
                                    border:3px solid var(--card-bg,#1e2139);font-size:1.4rem;font-weight:700;color:#fff;">
                            {{ $inicial }}
                        </div>
                    </div>

                    <div class="card-body text-center pt-2">
                        <h6 class="fw-bold mb-0" style="color:#e2e8f0;">{{ $nombre ?: '—' }}</h6>
                        <small class="text-muted">{{ optional($e->user)->email ?? '' }}</small>

                        {{-- Especialidades --}}
                        @if(count($esps))
                            <div class="d-flex flex-wrap justify-content-center gap-1 mt-2">
                                @foreach($esps as $esp)
                                    <span class="badge" style="background:rgba(30,201,139,.15);color:#1ec98b;font-size:.7rem;">{{ trim($esp) }}</span>
                                @endforeach
                            </div>
                        @endif

                        <hr style="border-color:rgba(255,255,255,.07);margin:.75rem 0;">

                        <div class="d-flex justify-content-around text-center">
                            <div>
                                <div style="font-size:.7rem;color:#94a3b8;">CI</div>
                                <div style="font-size:.82rem;color:#e2e8f0;font-weight:600;">{{ $e->persona->ci ?? '—' }}</div>
                            </div>
                            <div>
                                <div style="font-size:.7rem;color:#94a3b8;">Experiencia</div>
                                <div style="font-size:.82rem;color:#e2e8f0;font-weight:600;">{{ $e->experiencia ?? '—' }}</div>
                            </div>
                        </div>

                        @if(count($disps))
                            <div class="mt-2">
                                <div style="font-size:.7rem;color:#94a3b8;margin-bottom:.3rem;">Disponibilidad</div>
                                <div class="d-flex flex-wrap justify-content-center gap-1">
                                    @foreach($disps as $d)
                                        <span class="badge" style="background:rgba(99,102,241,.15);color:#6366f1;font-size:.68rem;">{{ $d }}</span>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="card-footer d-flex gap-2 justify-content-center" style="background:transparent;">
                        <a href="{{ route('entrenadores.edit', $e->id_entrenador) }}" class="btn btn-sm btn-warning flex-grow-1" title="Editar">
                            <i class="fa-solid fa-pen-to-square me-1"></i> Editar
                        </a>
                        @if (Auth::user()->hasRole('Administrador'))
                            <form action="{{ route('entrenadores.destroy', $e->id_entrenador) }}" method="POST"
                                  data-confirm="¿Eliminar al entrenador {{ $nombre }}?">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-danger" title="Eliminar">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        @endif
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
                <p class="mb-2">No hay entrenadores registrados.</p>
                <a href="{{ route('entrenadores.create') }}" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-circle-plus me-1"></i> Registrar el primero
                </a>
            </div>
        </div>
    </div>
@endif

@endsection
