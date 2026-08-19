@extends('layouts.private')
@section('contenido')

<x-page-header title="Promociones" subtitle="Ofertas y descuentos activos" icon="fa-solid fa-tags">
    <a href="{{ route('promociones.create') }}" class="btn btn-primary">
        <i class="fa-solid fa-circle-plus me-1"></i> Nueva promoción
    </a>
</x-page-header>

@if(session('success'))
    <div class="alert alert-success border-0 rounded-3 mb-4">{{ session('success') }}</div>
@endif

@if($promociones->count())
    <div class="row g-4">
        @foreach($promociones as $p)
            @php
                $hoy     = now();
                $inicio  = \Carbon\Carbon::parse($p->fecha_inicio);
                $fin     = \Carbon\Carbon::parse($p->fecha_fin);
                $activa  = strtolower($p->estado) === 'activo' && $hoy->between($inicio, $fin);
                $vencida = $fin->isPast();
            @endphp
            <div class="col-xl-3 col-lg-4 col-md-6">
                <div class="card custom-card h-100" style="border-radius:1rem;overflow:hidden;">

                    {{-- Imagen --}}
                    @if($p->imagen)
                        <div style="height:150px;overflow:hidden;">
                            <img src="{{ asset('storage/'.$p->imagen) }}" alt="{{ $p->titulo }}"
                                 style="width:100%;height:100%;object-fit:cover;">
                        </div>
                    @else
                        <div style="height:150px;background:linear-gradient(135deg,rgba(245,158,11,.2),rgba(234,88,12,.2));
                                    display:flex;align-items:center;justify-content:center;">
                            <i class="fa-solid fa-tags fa-3x" style="color:#f59e0b;opacity:.5;"></i>
                        </div>
                    @endif

                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h6 class="fw-bold mb-0" style="color:var(--mbf-text,#e2e8f0);">{{ $p->titulo }}</h6>
                            <span class="badge {{ $activa ? 'bg-success' : ($vencida ? 'bg-danger' : 'bg-warning text-dark') }} ms-2" style="flex-shrink:0;">
                                {{ $activa ? 'Activa' : ($vencida ? 'Vencida' : ucfirst($p->estado)) }}
                            </span>
                        </div>

                        <div class="d-flex flex-column gap-1 mt-3">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa-solid fa-calendar-plus" style="color:#10b981;font-size:.8rem;width:14px;"></i>
                                <span style="font-size:.8rem;color:var(--mbf-text-muted,#94a3b8);">Inicio: <strong style="color:var(--mbf-text,#e2e8f0);">{{ $inicio->format('d/m/Y') }}</strong></span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa-solid fa-calendar-xmark" style="color:#f5365c;font-size:.8rem;width:14px;"></i>
                                <span style="font-size:.8rem;color:var(--mbf-text-muted,#94a3b8);">Fin: <strong style="color:var(--mbf-text,#e2e8f0);">{{ $fin->format('d/m/Y') }}</strong></span>
                            </div>
                            @if($p->administrador)
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa-solid fa-user-shield" style="color:#6366f1;font-size:.8rem;width:14px;"></i>
                                <span style="font-size:.8rem;color:var(--mbf-text-muted,#94a3b8);">{{ $p->administrador->persona->nombre_completo ?? '—' }}</span>
                            </div>
                            @endif
                        </div>
                    </div>

                    <div class="card-footer d-flex gap-2 justify-content-end" style="background:transparent;">
                        <a href="{{ route('promociones.edit', $p->id_promocion) }}" class="btn btn-sm btn-warning flex-grow-1">
                            <i class="fa-solid fa-pen-to-square me-1"></i> Editar
                        </a>
                        <form action="{{ route('promociones.destroy', $p->id_promocion) }}" method="POST"
                              data-confirm="¿Eliminar la promoción «{{ $p->titulo }}»?">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-danger"><i class="fa-solid fa-trash"></i></button>
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
                <div class="mb-empty-icon"><i class="fa-solid fa-tags"></i></div>
                <p class="mb-2">No hay promociones registradas.</p>
                <a href="{{ route('promociones.create') }}" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-circle-plus me-1"></i> Crear la primera
                </a>
            </div>
        </div>
    </div>
@endif

@endsection
