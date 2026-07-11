@extends('layouts.private')
@section('contenido')

<x-page-header title="Recepcionistas" subtitle="Personal de recepción del gimnasio" icon="fa-solid fa-id-badge">
    <form method="GET" action="{{ route('recepcionistas.index') }}" class="mb-searchbar" style="max-width:300px">
        <i class="fa-solid fa-magnifying-glass mb-search-icon"></i>
        <input type="text" name="buscar" class="form-control" placeholder="Buscar recepcionista…" value="{{ request('buscar') }}">
    </form>
    <a href="{{ route('recepcionistas.create') }}" class="btn btn-primary">
        <i class="fa-solid fa-circle-plus me-1"></i> Agregar recepcionista
    </a>
</x-page-header>

@if(session('success'))
    <div class="alert alert-success border-0 rounded-3 mb-4">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger border-0 rounded-3 mb-4">{{ session('error') }}</div>
@endif

@if($recepcionistas->count())
    <div class="row g-4">
        @foreach($recepcionistas as $r)
            @php
                $nombre  = trim(($r->persona->nombre_completo ?? '') . ' ' . ($r->persona->apellido_paterno ?? '') . ' ' . ($r->persona->apellido_materno ?? ''));
                $inicial = strtoupper(mb_substr($nombre ?: 'R', 0, 1));
                $activo  = $r->estado === 'activo';
            @endphp
            <div class="col-xl-3 col-lg-4 col-md-6">
                <div class="card custom-card h-100" style="border-radius:1rem;overflow:hidden;">

                    <div style="height:70px;background:linear-gradient(135deg,#1a1a2e,#16213e,#0f3460);position:relative;">
                        <span class="badge position-absolute top-2 end-2 mt-2 me-2 {{ $activo ? 'bg-success' : 'bg-danger' }}">
                            {{ $activo ? 'Activo' : 'Inactivo' }}
                        </span>
                    </div>

                    <div style="display:flex;justify-content:center;margin-top:-32px;position:relative;z-index:1;">
                        <div style="width:64px;height:64px;border-radius:50%;background:linear-gradient(135deg,#6366f1,#8b5cf6);
                                    display:flex;align-items:center;justify-content:center;
                                    border:3px solid var(--card-bg,#1e2139);font-size:1.4rem;font-weight:700;color:#fff;">
                            {{ $inicial }}
                        </div>
                    </div>

                    <div class="card-body text-center pt-2">
                        <h6 class="fw-bold mb-0" style="color:#e2e8f0;">{{ $nombre ?: '—' }}</h6>
                        <small class="text-muted">{{ optional($r->user)->email ?? '' }}</small>

                        <hr style="border-color:rgba(255,255,255,.07);margin:.75rem 0;">

                        <div class="d-flex flex-column gap-2 text-start">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa-solid fa-id-card" style="color:#6366f1;font-size:.8rem;width:16px;"></i>
                                <span style="font-size:.82rem;color:#e2e8f0;">{{ $r->persona->ci ?? '—' }}</span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa-solid fa-phone" style="color:#10b981;font-size:.8rem;width:16px;"></i>
                                <span style="font-size:.82rem;color:#e2e8f0;">{{ $r->persona->telefono ?? '—' }}</span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa-solid fa-clock" style="color:#f59e0b;font-size:.8rem;width:16px;"></i>
                                <span style="font-size:.82rem;color:#e2e8f0;">Turno: {{ ucfirst($r->turno ?? '—') }}</span>
                            </div>
                            @if($r->punto_atencion)
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa-solid fa-location-dot" style="color:#f5365c;font-size:.8rem;width:16px;"></i>
                                <span style="font-size:.82rem;color:#e2e8f0;">{{ $r->punto_atencion }}</span>
                            </div>
                            @endif
                        </div>
                    </div>

                    <div class="card-footer d-flex gap-2 justify-content-center" style="background:transparent;">
                        <a href="{{ route('recepcionistas.edit', $r->id_recepcionista) }}" class="btn btn-sm btn-warning flex-grow-1">
                            <i class="fa-solid fa-pen-to-square me-1"></i> Editar
                        </a>
                        @if (Auth::user()->hasRole('Administrador'))
                            <form action="{{ route('recepcionistas.destroy', $r->id_recepcionista) }}" method="POST"
                                  data-confirm="¿Eliminar a {{ $nombre }}?">
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
                <div class="mb-empty-icon"><i class="fa-solid fa-id-badge"></i></div>
                <p class="mb-2">No hay recepcionistas registrados.</p>
                <a href="{{ route('recepcionistas.create') }}" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-circle-plus me-1"></i> Registrar el primero
                </a>
            </div>
        </div>
    </div>
@endif

@endsection
