@extends('layouts.private')

@section('contenido')
<div class="container">
    <h2 class="text-center mb-4">Gestión de Promociones</h2>
    <a href="{{ route('promociones.create') }}" class="btn btn-primary mb-3">Nueva Promoción</a>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>#</th>
                <th>Título</th>
                <th>Administrador</th>
                <th>Inicio</th>
                <th>Fin</th>
                <th>Estado</th>
                <th>Imagen</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($promociones as $index => $promocion)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $promocion->titulo }}</td>
                <td>{{ $promocion->administrador->persona->nombre_completo ?? 'N/A' }}</td>
                <td>{{ \Carbon\Carbon::parse($promocion->fecha_inicio)->format('d/m/Y') }}</td>
                <td>{{ \Carbon\Carbon::parse($promocion->fecha_fin)->format('d/m/Y') }}</td>
                <td>{{ ucfirst($promocion->estado) }}</td>
                <td>
                    @if($promocion->imagen)
                        <img src="{{ asset('storage/'.$promocion->imagen) }}" width="70">
                    @else
                        Sin imagen
                    @endif
                </td>
                <td>
                    <a href="{{ route('promociones.edit', $promocion->id_promocion) }}" class="btn btn-warning btn-sm">Editar</a>
                    <form action="{{ route('promociones.destroy', $promocion->id_promocion) }}" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar esta promoción?')">Eliminar</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="text-center">No hay promociones registradas.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
