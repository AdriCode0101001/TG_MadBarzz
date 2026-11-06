@extends('layouts.private')
@section('contenido')

<x-page-header title="Notificaciones" subtitle="Mensajes enviados a los clientes" icon="fa-solid fa-bell">
    <a href="{{ route('notificaciones.create') }}" class="btn btn-primary">
        <i class="fa-solid fa-paper-plane me-1"></i> Enviar notificación
    </a>
</x-page-header>

@if(session('success'))
    <div class="alert alert-success border-0 rounded-3 mb-4">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger border-0 rounded-3 mb-4">{{ session('error') }}</div>
@endif

<div class="card custom-card">
    <div class="card-body p-0">
        <div class="table-responsive" style="overflow-x:auto; -webkit-overflow-scrolling:touch;">
            <table class="table table-hover align-middle mb-0" style="min-width:700px;">
                <thead>
                    <tr>
                        <th style="min-width:50px;">#</th>
                        <th style="min-width:180px;">Cliente</th>
                        <th style="min-width:120px;">Tipo</th>
                        <th style="min-width:220px;">Mensaje</th>
                        <th style="min-width:140px; white-space:nowrap;">Fecha envío</th>
                        <th style="min-width:90px;">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($notificaciones as $n)
                        <tr>
                            <td class="text-muted">{{ $n->id }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="mb-avatar-sm">
                                        <i class="fa-solid fa-user"></i>
                                    </div>
                                    {{ $n->cliente->persona->nombre_completo ?? '—' }}
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-primary-subtle text-primary">
                                    {{ ucfirst(str_replace('_', ' ', $n->tipo)) }}
                                </span>
                            </td>
                            <td class="text-truncate" style="max-width:220px;">{{ $n->mensaje }}</td>
                            <td>
                                @if($n->fecha_envio)
                                    <i class="fa-regular fa-clock text-muted me-1"></i>
                                    {{ \Carbon\Carbon::parse($n->fecha_envio)->format('d/m/Y H:i') }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @php $estado = strtolower($n->estado ?? 'pendiente'); @endphp
                                <span class="badge {{ $estado === 'enviado' ? 'bg-success' : 'bg-warning text-dark' }}">
                                    {{ ucfirst($estado) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="mb-empty">
                                    <div class="mb-empty-icon"><i class="fa-solid fa-bell-slash"></i></div>
                                    <p class="mb-2">No hay notificaciones enviadas.</p>
                                    <a href="{{ route('notificaciones.create') }}" class="btn btn-primary btn-sm">
                                        <i class="fa-solid fa-paper-plane me-1"></i> Enviar la primera
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.mb-avatar-sm {
    width:32px;height:32px;border-radius:50%;
    background:rgba(255, 107, 26,.15);color:#ff6b1a;
    display:flex;align-items:center;justify-content:center;
    font-size:.8rem;flex-shrink:0;
}
</style>

@endsection
