@extends('layouts.private')

@section('contenido')
<div class="container">
    <h2>Listado de Personas</h2>
<BR></BR>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-3">
        {{-- Botón Agregar Persona --}}
        <a href="{{ route('personas.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-circle-plus me-2"></i> Agregar Persona
        </a>

        {{-- Buscador por nombre completo o CI --}}
        <form method="GET" action="{{ route('personas.index') }}" class="d-flex">
            <input type="text" name="buscar" class="form-control me-2" 
                   placeholder="Buscar por nombre o CI" 
                   value="{{ request('buscar') }}" style="width: 400px;">
            <button type="submit" class="btn btn-primary">Buscar</button>
        </form>
    </div>

    <div class="table-responsive" style="overflow-x:auto; -webkit-overflow-scrolling:touch;">
        <table class="table table-bordered table-striped" style="min-width:760px;">
            <thead>
                <tr>
                    <th>Nombre Completo</th>
                    <th>CI</th>
                    <th>Teléfono</th>
                    <th>Género</th>
                    <th>Fecha de Nacimiento</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($personas as $persona)
                    <tr>
                        <td>{{ $persona->nombre_completo }} {{ $persona->apellido_paterno }} {{ $persona->apellido_materno }}</td>
                        <td>{{ $persona->ci }}</td>
                        <td>{{ $persona->telefono }}</td>
                        <td>{{ ucfirst($persona->genero) }}</td>
                        <td>{{ $persona->fecha_nacimiento }}</td>
                        <td class="d-flex justify-content-center align-items-center gap-1">
                            <a href="{{ route('personas.edit', $persona) }}"
                               class="btn btn-warning btn-sm"
                               title="Editar">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <form action="{{ route('personas.destroy', $persona) }}"
                                  method="POST"
                                  style="display:inline;"
                                  onsubmit="return confirm('¿Estás seguro de eliminar esta persona?');">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-danger btn-sm" title="Eliminar">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center">No hay personas registradas.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
