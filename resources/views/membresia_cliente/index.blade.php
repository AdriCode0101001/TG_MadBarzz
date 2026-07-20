@extends('layouts.private')
@section('contenido')

<x-page-header title="Membresías de Clientes" subtitle="Estado de los planes asignados a los miembros" icon="fa-solid fa-id-badge">
    <form method="GET" action="{{ route('membresia_cliente.index') }}" class="mb-searchbar" style="max-width:320px">
        <i class="fa-solid fa-magnifying-glass mb-search-icon"></i>
        <input type="text" name="buscar" class="form-control" placeholder="Buscar cliente, CI o plan…" value="{{ request('buscar') }}">
    </form>
    {{-- Para darle una membresía a un cliente, la única vía es Registrar Pago
         (crea la asignación y su pago vinculado en un solo paso, activa de inmediato) --}}
    <a href="{{ route('pagos.create') }}" class="btn btn-primary">
        <i class="fa-solid fa-money-bill-wave me-1"></i> Registrar pago (nueva membresía)
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
            <table class="table table-hover align-middle mb-0" style="min-width:780px;">
                <thead>
                    <tr>
                        <th style="min-width:200px;">Cliente</th>
                        <th style="min-width:130px;">Plan</th>
                        <th style="min-width:100px; white-space:nowrap;">Inicio</th>
                        <th style="min-width:110px; white-space:nowrap;">Vencimiento</th>
                        <th style="min-width:160px;">Descuento</th>
                        <th style="min-width:110px; white-space:nowrap;">Precio final</th>
                        <th class="text-center" style="min-width:90px;">Estado</th>
                        <th class="text-end" style="min-width:90px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($membresiasClientes as $mc)
                        @php
                            $pendiente = $mc->estado !== 'activa';
                            $vencida   = !$pendiente && \Carbon\Carbon::parse($mc->fecha_fin)->isPast();
                            $nombre    = trim(($mc->cliente->persona->nombre_completo ?? '') . ' ' . ($mc->cliente->persona->apellido_paterno ?? '') . ' ' . ($mc->cliente->persona->apellido_materno ?? ''));
                            $inicial   = strtoupper(mb_substr($nombre ?: 'C', 0, 1));
                        @endphp
                        <tr>
                            <td>
                                <div class="mb-user-cell">
                                    <span class="mb-avatar">{{ $inicial }}</span>
                                    <div>
                                        <div class="fw-semibold" style="font-size:.88rem">{{ $nombre ?: '—' }}</div>
                                        <small class="text-muted">{{ $mc->cliente->persona->ci ?? '' }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-primary-subtle text-primary fw-semibold">
                                    <i class="fa-solid fa-ticket me-1"></i>{{ $mc->membresia->tipo_membresia ?? '—' }}
                                </span>
                            </td>
                            <td style="white-space:nowrap;">{{ \Carbon\Carbon::parse($mc->fecha_inicio)->format('d/m/Y') }}</td>
                            <td style="white-space:nowrap;">{{ \Carbon\Carbon::parse($mc->fecha_fin)->format('d/m/Y') }}</td>
                            <td>
                                @if($mc->descuento > 0)
                                    <span class="badge bg-success-subtle text-success">
                                        {{ $mc->nombre_descuento }} — {{ $mc->descuento }}%
                                    </span>
                                @else
                                    <span class="text-muted small">Sin descuento</span>
                                @endif
                            </td>
                            <td class="fw-bold" style="white-space:nowrap;">Bs. {{ number_format($mc->precio_final ?? 0, 2) }}</td>
                            <td class="text-center">
                                <span class="badge {{ $pendiente ? 'bg-warning text-dark' : ($vencida ? 'bg-danger' : 'bg-success') }} mb-badge-dot">
                                    {{ $pendiente ? 'Pendiente de pago' : ($vencida ? 'Vencida' : 'Activa') }}
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="mb-actions justify-content-end">
                                    <a href="{{ route('membresia_cliente.edit', $mc->id) }}" class="btn btn-sm btn-warning" title="Editar">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                    <form action="{{ route('membresia_cliente.destroy', $mc->id) }}" method="POST"
                                          data-confirm="¿Eliminar esta membresía de {{ $nombre }}?">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-danger" title="Eliminar">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="mb-empty">
                                    <div class="mb-empty-icon"><i class="fa-solid fa-id-badge"></i></div>
                                    <p class="mb-2">No hay membresías asignadas.</p>
                                    <a href="{{ route('pagos.create') }}" class="btn btn-primary btn-sm">
                                        <i class="fa-solid fa-money-bill-wave me-1"></i> Registrar el primer pago
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if(method_exists($membresiasClientes, 'links'))
        {{-- pagination::bootstrap-5: ver fix/motivo en ejercicios/index.blade.php --}}
        <div class="card-footer d-flex justify-content-end">{{ $membresiasClientes->withQueryString()->links('pagination::bootstrap-5') }}</div>
    @endif
</div>

@endsection
