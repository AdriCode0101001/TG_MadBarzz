@extends('layouts.private')

@section('contenido')
<div class="container">
    <h2>Listado de Usuarios</h2>
<BR></BR>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-3">
        {{-- Botón Agregar Usuario --}}
        <a href="{{ route('users.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-circle-plus me-2"></i> Agregar Usuario
        </a>

 {{-- Buscador un poco más largo --}}
        <form method="GET" action="{{ route('users.index') }}" class="d-flex">
            <input type="text" name="buscar" class="form-control me-2" 
                   placeholder="Buscar por nombre o email" 
                   value="{{ request('buscar') }}" style="width: 400px;">
            <button type="submit" class="btn btn-primary">Buscar</button>
        </form>
    </div>

    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre de Usuario</th>
                <th>Email</th>
                <th>Rol</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($usuarios as $usuario)
                <tr>
                    <td>{{ $usuario->id }}</td>
                    <td>{{ $usuario->name }}</td>
                    <td>{{ $usuario->email }}</td>
                    <td>{{ $usuario->rol->nombre ?? 'Sin rol' }}</td>
                    <td class="d-flex justify-content-center align-items-center gap-1">
                        <a href="{{ route('users.edit', $usuario->id) }}" 
                           class="btn btn-warning btn-sm" 
                           title="Editar {{ $usuario->name }}">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </a>
                        <form action="{{ route('users.destroy', $usuario->id) }}" 
                              method="POST" 
                              style="display:inline;" 
                              onsubmit="return confirm('¿Estás seguro de eliminar a {{ $usuario->name }}?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm" title="Eliminar">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center">No hay usuarios registrados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
