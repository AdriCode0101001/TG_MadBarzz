@extends('layouts.private')

@section('contenido')
<div class="container">

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <h2 class="text-center">Clientes Activos e Inactivos</h2>
    <br><br>

    <!-- 🔹 Filtro y botón Exportar en la misma fila -->
    <div class="d-flex justify-content-between mb-3">
        <!-- Botón Exportar PDF -->
        <a href="{{ route('reportes.clientes.estado.pdf', ['filtro' => $filtro]) }}" class="btn btn-danger">
            Exportar PDF
        </a>

        <!-- Formulario de filtro -->
        <form method="GET" action="{{ route('reportes.clientes.estado') }}" class="d-flex">
            <select name="filtro" class="me-2">
                <option value="todos" {{ $filtro == 'todos' ? 'selected' : '' }}>Todos</option>
                <option value="activos" {{ $filtro == 'activos' ? 'selected' : '' }}>Activos</option>
                <option value="inactivos" {{ $filtro == 'inactivos' ? 'selected' : '' }}>Inactivos</option>
            </select>
            <button type="submit" class="btn btn-primary">Filtrar</button>
        </form>
    </div>

    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>#</th>
                <th>Nombre Completo</th>
                <th>CI</th>
                <th>Teléfono</th>
                <th>Email</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse($clientes as $index => $cliente)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        {{ $cliente->persona->nombre_completo ?? 'N/A' }}
                        {{ $cliente->persona->apellido_paterno ?? '' }}
                        {{ $cliente->persona->apellido_materno ?? '' }}
                    </td>
                    <td>{{ $cliente->persona->ci ?? 'N/A' }}</td>
                    <td>{{ $cliente->persona->telefono ?? 'N/A' }}</td>
                    <td>{{ $cliente->user->email ?? 'N/A' }}</td>
                    <td class="{{ strtolower($cliente->estado) === 'activo' ? 'text-success fw-bold' : 'text-danger fw-bold' }}">
                        {{ ucfirst($cliente->estado) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center">No hay clientes registrados según el filtro seleccionado.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
