@extends('layouts.private')

@section('contenido')
<div class="container">
    <h2 class="mb-4">Lista de Recepcionistas</h2>
<BR></BR>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-3">
        {{-- Botón Agregar Recepcionista --}}
        <a href="{{ route('recepcionistas.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-circle-plus me-2"></i> Agregar Recepcionista
        </a>

        {{-- Buscador por nombre completo, CI o correo --}}
        <form method="GET" action="{{ route('recepcionistas.index') }}" class="d-flex">
            <input type="text" name="buscar" class="form-control me-2" 
                   placeholder="Buscar por nombre, CI o email" 
                   value="{{ request('buscar') }}" style="width: 400px;">
            <button type="submit" class="btn btn-primary">Buscar</button>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle w-100">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre Completo</th>
                    <th>CI</th>
                    <th>Teléfono</th>
                    <th>Correo</th>
                    <th>Usuario</th>
                    <th>Turno</th>
                    <th>Punto de Atención</th>
                    <th>Estado</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recepcionistas as $recepcionista)
                    <tr>
                        <td>{{ $recepcionista->id_recepcionista }}</td>
                        <td>
                            {{ $recepcionista->persona->nombre_completo ?? '' }}
                            {{ $recepcionista->persona->apellido_paterno ?? '' }}
                            {{ $recepcionista->persona->apellido_materno ?? '' }}
                        </td>
                        <td>{{ $recepcionista->persona->ci ?? 'Sin CI' }}</td>
                        <td>{{ $recepcionista->persona->telefono ?? 'Sin teléfono' }}</td>
                        <td>{{ $recepcionista->user->email ?? 'Sin correo' }}</td>
                        <td>{{ $recepcionista->user->name ?? 'Sin usuario' }}</td>
                        <td>{{ ucfirst($recepcionista->turno) }}</td>
                        <td>{{ $recepcionista->punto_atencion ?? 'N/A' }}</td>
                        <td>
                            <span class="badge {{ $recepcionista->estado === 'activo' ? 'bg-success' : 'bg-danger' }}">
                                {{ ucfirst($recepcionista->estado) }}
                            </span>
                        </td>
                        <td class="text-center">
                            <div class="d-inline-flex gap-1">
                                <a href="{{ route('recepcionistas.edit', $recepcionista->id_recepcionista) }}" 
                                   class="btn btn-warning btn-sm" 
                                   title="Editar">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                                <form action="{{ route('recepcionistas.destroy', $recepcionista->id_recepcionista) }}" 
                                      method="POST" 
                                      onsubmit="return confirm('¿Eliminar recepcionista?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-danger btn-sm" title="Eliminar">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center">No hay recepcionistas registrados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
