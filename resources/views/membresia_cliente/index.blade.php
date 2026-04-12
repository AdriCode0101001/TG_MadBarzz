@extends('layouts.private')

@section('contenido')
<div class="container">
    <h2>Membresías por Cliente</h2>
    <br>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-3">
        {{-- Botón Asignar Membresía --}}
        <a href="{{ route('membresia_cliente.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-circle-plus me-2"></i> Asignar Membresía
        </a>

        {{-- Buscador --}}
        <form method="GET" action="{{ route('membresia_cliente.index') }}" class="d-flex">
            <input type="text" name="buscar" class="form-control me-2" 
                   placeholder="Buscar por cliente, CI o membresía" 
                   value="{{ request('buscar') }}" style="width: 400px;">
            <button type="submit" class="btn btn-primary">Buscar</button>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Cliente</th>
                    <th>Membresía</th>
                    <th>Fecha Inicio</th>
                    <th>Fecha Fin</th>
                    <th>Nombre Descuento</th>
                    <th>Descuento (%)</th>
                    <th>Precio Final (Bs)</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($membresiasClientes as $mc)
                    <tr>
                        <td>{{ $mc->id }}</td>
                        <td>
                            {{ $mc->cliente->persona->nombre_completo ?? 'N/A' }}
                            {{ $mc->cliente->persona->apellido_paterno ?? '' }}
                            {{ $mc->cliente->persona->apellido_materno ?? '' }}
                        </td>
                        <td>{{ $mc->membresia->tipo_membresia ?? 'N/A' }}</td>
                        <td>{{ $mc->fecha_inicio }}</td>
                        <td>{{ $mc->fecha_fin }}</td>
                        <td>{{ $mc->nombre_descuento ?? 'N/A' }}</td>
                        <td>{{ $mc->descuento ?? '0' }}</td>
                        <td>{{ number_format($mc->precio_final ?? 0, 2) }}</td>
                        <td class="text-center">
                            <div class="d-inline-flex gap-1">
                                <a href="{{ route('membresia_cliente.edit', $mc->id) }}" 
                                   class="btn btn-warning btn-sm" 
                                   title="Editar">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                                <form action="{{ route('membresia_cliente.destroy', $mc->id) }}" 
                                      method="POST" 
                                      onsubmit="return confirm('¿Eliminar registro?')">
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
                        <td colspan="9" class="text-center">No hay asignaciones de membresía.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
