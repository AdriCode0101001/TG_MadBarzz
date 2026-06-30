@extends('layouts.private')

@section('contenido')
<div class="col-12">

    <x-page-header title="Clientes" subtitle="Gestiona los miembros del gimnasio" icon="fa-solid fa-users">
        <a href="{{ route('clientes.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-circle-plus"></i> Agregar cliente
        </a>
    </x-page-header>

    <div class="card">
        <div class="card-header">
            <form method="GET" action="{{ route('clientes.index') }}" class="mb-searchbar w-100" style="max-width:480px">
                <i class="fa-solid fa-magnifying-glass mb-search-icon"></i>
                <input type="text" name="buscar" class="form-control"
                       placeholder="Buscar por nombre, CI o correo…"
                       value="{{ request('buscar') }}">
            </form>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive" style="overflow-x:auto; -webkit-overflow-scrolling:touch;">
                <table class="table table-hover align-middle" style="min-width:820px;">
                    <thead>
                        <tr>
                            <th style="min-width:50px;">#</th>
                            <th style="min-width:210px;">Cliente</th>
                            <th style="min-width:90px; white-space:nowrap;">CI</th>
                            <th style="min-width:110px; white-space:nowrap;">Teléfono</th>
                            <th style="min-width:130px;">Usuario</th>
                            <th class="text-center" style="min-width:60px;">Días</th>
                            <th class="text-center" style="min-width:80px;">Huella</th>
                            <th class="text-center" style="min-width:80px;">Estado</th>
                            <th class="text-end" style="min-width:110px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($clientes as $cliente)
                            @php
                                $nombre = trim(($cliente->persona->nombre_completo ?? '') . ' ' . ($cliente->persona->apellido_paterno ?? '') . ' ' . ($cliente->persona->apellido_materno ?? ''));
                                $inicial = strtoupper(mb_substr($nombre ?: 'C', 0, 1));
                            @endphp
                            <tr>
                                <td class="text-muted">{{ $cliente->id_cliente }}</td>
                                <td>
                                    <div class="mb-user-cell">
                                        <span class="mb-avatar">{{ $inicial }}</span>
                                        <div>
                                            <div>{{ $nombre ?: '—' }}</div>
                                            <small>{{ optional($cliente->user)->email ?? 'Sin correo' }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ optional($cliente->persona)->ci ?? '—' }}</td>
                                <td>{{ optional($cliente->persona)->telefono ?? '—' }}</td>
                                <td>{{ optional($cliente->user)->name ?? '—' }}</td>
                                <td class="text-center">{{ $cliente->dias_asistidos }}</td>
                                <td class="text-center">
                                    @if ($cliente->huella_digital)
                                        <span class="badge bg-success"><i class="fa-solid fa-fingerprint me-1"></i>Sí</span>
                                    @else
                                        <span class="badge bg-secondary">No</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge {{ $cliente->estado == 'activo' ? 'bg-success' : 'bg-danger' }} mb-badge-dot">
                                        {{ ucfirst($cliente->estado) }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="mb-actions justify-content-end">
                                        <a href="{{ route('clientes.show', $cliente->id_cliente) }}"
                                           class="btn btn-sm btn-info text-white" title="Ver detalle">
                                            <i class="fa-solid fa-eye"></i>
                                        </a>
                                        <a href="{{ route('clientes.edit', $cliente->id_cliente) }}"
                                           class="btn btn-sm btn-warning" title="Editar">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </a>
                                        <form action="{{ route('clientes.destroy', $cliente->id_cliente) }}"
                                              method="POST"
                                              data-confirm="¿Eliminar al cliente «{{ $nombre }}»? Esta acción no se puede deshacer.">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-danger" title="Eliminar">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9">
                                    <div class="mb-empty">
                                        <div class="mb-empty-icon"><i class="fa-solid fa-user-slash"></i></div>
                                        <p class="mb-2">No se encontraron clientes.</p>
                                        <a href="{{ route('clientes.create') }}" class="btn btn-primary btn-sm">
                                            <i class="fa-solid fa-circle-plus"></i> Registrar el primero
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if (method_exists($clientes, 'links'))
            <div class="card-footer d-flex justify-content-end">
                {{ $clientes->withQueryString()->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
