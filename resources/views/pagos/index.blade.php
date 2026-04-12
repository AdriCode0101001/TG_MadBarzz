@extends('layouts.private')

@section('contenido')
<div class="container">
    <h2 class="mb-4">Listado de Pagos</h2>
    <br>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-3">
        {{-- Botón Nuevo Pago --}}
        <a href="{{ route('pagos.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-circle-plus me-2"></i> Nuevo Pago
        </a>

        {{-- Buscador --}}
        <form method="GET" action="{{ route('pagos.index') }}" class="d-flex">
            <input type="text" name="buscar" class="form-control me-2"
                   placeholder="Buscar por cliente, membresía o fecha"
                   value="{{ $buscar ?? '' }}" style="width: 400px;">
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
                    <th>Monto</th>
                    <th>Fecha</th>
                    <th>Método</th>
                    <th>Observaciones</th>
                    <th>QR</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pagos as $pago)
                    <tr>
                        <td>{{ $pago->id_pago }}</td>
                        <td>
                            {{ $pago->cliente->persona->nombre_completo ?? '' }}
                            {{ $pago->cliente->persona->apellido_paterno ?? '' }}
                            {{ $pago->cliente->persona->apellido_materno ?? '' }}
                        </td>
                        <td>{{ $pago->membresia->tipo_membresia ?? '' }}</td>
                        <td>{{ number_format($pago->monto, 2) }}</td>
                        <td>{{ \Carbon\Carbon::parse($pago->fecha_pago)->format('d/m/Y') }}</td>
                        <td>{{ ucfirst($pago->metodo_pago) }}</td>
                        <td>{{ $pago->observaciones }}</td>
                        <td class="text-center">
                            @if($pago->imagen_qr)
                                <img src="{{ asset('storage/'.$pago->imagen_qr) }}" alt="QR" width="100">
                            @else
                                No hay imagen
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="d-inline-flex gap-1">
                                <a href="{{ route('pagos.edit', $pago) }}" 
                                   class="btn btn-warning btn-sm" 
                                   title="Editar">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                                <form action="{{ route('pagos.destroy', $pago) }}" 
                                      method="POST" 
                                      onsubmit="return confirm('¿Eliminar pago?')">
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
                        <td colspan="9" class="text-center">No hay pagos registrados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
