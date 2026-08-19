@extends('layouts.private')

@section('contenido')
<div class="container">

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    
    <h2 class="text-center">Clientes con Membresía Vencida</h2>
<BR></BR>
<BR></BR>
    <div class="d-flex justify-content-between mb-3">
        <!-- Botón Exportar PDF -->
        <a href="{{ route('reportes.clientes.membresia_vencida.pdf', ['mes' => $mes, 'anio' => $anio]) }}" class="btn btn-danger">Exportar PDF</a>
        <!-- Formulario de filtro: precargado con el mes/año actual (ver ReporteController) -->
        <form method="GET" action="{{ route('reportes.clientes.membresia_vencida') }}" class="d-flex">
            <select name="mes" class="me-2">
                <option value="">Mes</option>
                @for($m=1; $m<=12; $m++)
                    <option value="{{ $m }}" {{ $mes == $m ? 'selected' : '' }}>
                        {{ \Carbon\Carbon::create()->month($m)->locale('es')->isoFormat('MMMM') }}
                    </option>
                @endfor
            </select>

<select name="anio" class="me-2">
    <option value="">Año</option>
    @for($y = 2025; $y <= 2040; $y++)
        <option value="{{ $y }}" {{ $anio == $y ? 'selected' : '' }}>{{ $y }}</option>
    @endfor
</select>


            <button type="submit" class="btn btn-primary">Filtrar</button>
        </form>
    </div>

    <div class="table-responsive" style="overflow-x:auto; -webkit-overflow-scrolling:touch;">
        <table class="table table-bordered table-striped" style="min-width:820px;">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Nombre Completo</th>
                    <th>CI</th>
                    <th>Teléfono</th>
                    <th>Email</th>
                    <th>Fecha Vencimiento</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                @forelse($clientes as $index => $cliente)
                    @php
                        // Última membresía por fecha_fin
                        $ultimaMembresia = $cliente->membresias()
                            ->orderBy('fecha_fin', 'desc')
                            ->first();

                        $fechaFin = $ultimaMembresia ? \Carbon\Carbon::parse($ultimaMembresia->pivot->fecha_fin)->format('d/m/Y') : 'No registrada';

                        $colorEstado = strtolower($cliente->estado) === 'activo' ? 'text-success' : 'text-danger';
                    @endphp
                    @if($ultimaMembresia)
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
                        <td>{{ $fechaFin }}</td>
                        <td class="fw-bold {{ $colorEstado }}">{{ ucfirst($cliente->estado) }}</td>
                    </tr>
                    @endif
                @empty
                <tr>
                    <td colspan="7" class="text-center">No hay clientes con membresía vencida o por vencer.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
