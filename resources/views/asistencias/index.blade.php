@extends('layouts.private')
@section('contenido')

<x-page-header title="Asistencias" subtitle="Registro de entradas al gimnasio" icon="fa-solid fa-clipboard-user">
    <a href="{{ route('asistencias.create') }}" class="btn btn-primary">
        <i class="fa-solid fa-circle-plus me-1"></i> Registrar asistencia
    </a>
</x-page-header>

@if(session('success'))
    <div class="alert alert-success border-0 rounded-3 mb-4">{{ session('success') }}</div>
@endif

<div class="card custom-card">
    <div class="card-header">
        <form method="GET" action="{{ route('asistencias.index') }}" class="mb-searchbar w-100" style="max-width:460px">
            <i class="fa-solid fa-magnifying-glass mb-search-icon"></i>
            <input type="text" name="buscar" class="form-control"
                   placeholder="Buscar por cliente o cédula…"
                   value="{{ request('buscar') }}">
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive" style="overflow-x:auto; -webkit-overflow-scrolling:touch;">
            <table class="table table-hover align-middle mb-0" style="min-width:540px;">
                <thead>
                    <tr>
                        <th style="min-width:50px;">#</th>
                        <th style="min-width:200px;">Cliente</th>
                        <th style="min-width:100px;">Cédula</th>
                        <th style="min-width:180px; white-space:nowrap;">Fecha y hora de entrada</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($asistencias as $a)
                        <tr>
                            <td class="text-muted">{{ $a->id_asistencia }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="mb-avatar-sm">
                                        <i class="fa-solid fa-user"></i>
                                    </div>
                                    <span>{{ $a->cliente->persona->nombre_completo ?? '—' }}
                                          {{ $a->cliente->persona->apellido_paterno ?? '' }}</span>
                                </div>
                            </td>
                            <td><span class="badge bg-secondary">{{ $a->cliente->persona->ci ?? '—' }}</span></td>
                            <td>
                                <i class="fa-regular fa-clock text-muted me-1"></i>
                                {{ \Carbon\Carbon::parse($a->fecha_hora_entrada)->format('d/m/Y H:i') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
                                <div class="mb-empty">
                                    <div class="mb-empty-icon"><i class="fa-solid fa-clipboard-user"></i></div>
                                    <p class="mb-2">No hay asistencias registradas.</p>
                                    <a href="{{ route('asistencias.create') }}" class="btn btn-primary btn-sm">
                                        <i class="fa-solid fa-circle-plus me-1"></i> Registrar la primera
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if(method_exists($asistencias, 'links'))
        <div class="card-footer d-flex justify-content-end">{{ $asistencias->withQueryString()->links() }}</div>
    @endif
</div>

<style>
.mb-avatar-sm {
    width: 32px; height: 32px;
    border-radius: 50%;
    background: rgba(99,102,241,.15);
    color: #6366f1;
    display: flex; align-items: center; justify-content: center;
    font-size: .8rem;
    flex-shrink: 0;
}
</style>

@endsection
