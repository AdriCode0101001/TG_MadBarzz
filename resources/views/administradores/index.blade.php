@extends('layouts.private')

@section('contenido')
<div class="container">
    <h2 class="mb-4">Lista de Administradores</h2>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-3">
        {{-- Botón Nuevo Administrador --}}
        <a href="{{ route('administradores.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-circle-plus me-2"></i> Nuevo Administrador
        </a>

        {{-- Buscador --}}
        <form method="GET" action="{{ route('administradores.index') }}" class="d-flex">
            <input type="text" name="buscar" class="form-control me-2" 
                   placeholder="Buscar por nombre, CI o correo" 
                   value="{{ request('buscar') }}" style="width: 400px;">
            <button type="submit" class="btn btn-primary">Buscar</button>
        </form>
    </div>

    <div class="table-responsive" style="overflow-x:auto; -webkit-overflow-scrolling:touch;">
        <table class="table table-bordered table-hover align-middle" style="min-width:780px;">
            <thead>
                <tr>
                    <th style="min-width:50px;">ID</th>
                    <th style="min-width:200px;">Nombre Completo</th>
                    <th style="min-width:180px;">Email</th>
                    <th style="min-width:120px;">Nivel de Acceso</th>
                    <th style="min-width:140px;">Área Responsable</th>
                    <th style="min-width:80px;">Estado</th>
                    <th class="text-center" style="min-width:100px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($administradores as $admin)
                    <tr>
                        <td>{{ $admin->id_administrador }}</td>
                        <td>
                            {{ $admin->persona->nombre_completo ?? '' }} 
                            {{ $admin->persona->apellido_paterno ?? '' }} 
                            {{ $admin->persona->apellido_materno ?? '' }}
                        </td>
                        <td>{{ $admin->user->email ?? 'Sin correo' }}</td>
                        <td>{{ ucfirst($admin->nivel_acceso ?? 'N/A') }}</td>
                        <td>{{ $admin->area_responsable ?? 'N/A' }}</td>
                        <td>
                            <span class="badge {{ $admin->estado === 'activo' ? 'bg-success' : 'bg-danger' }}">
                                {{ ucfirst($admin->estado ?? 'N/A') }}
                            </span>
                        </td>
                        <td class="text-center">
                            <div class="d-inline-flex gap-1">
                                <a href="{{ route('administradores.edit', $admin->id_administrador) }}" 
                                   class="btn btn-warning btn-sm" 
                                   title="Editar">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                                <form action="{{ route('administradores.destroy', $admin->id_administrador) }}" 
                                      method="POST" 
                                      onsubmit="return confirm('¿Seguro que deseas eliminar este administrador?')">
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
                        <td colspan="7" class="text-center">No hay administradores registrados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if (method_exists($administradores, 'links'))
        <div class="d-flex justify-content-end mt-3">
            {{ $administradores->withQueryString()->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>
@endsection
