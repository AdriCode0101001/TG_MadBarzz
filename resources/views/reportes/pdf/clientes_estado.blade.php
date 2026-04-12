<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte - Clientes Activos e Inactivos</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        h2, h3 { text-align: center; margin: 0; }
        h2 { margin-bottom: 5px; }
        h3 { margin-bottom: 20px; color: #555; font-weight: normal; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #000; padding: 8px; text-align: center; }
        th { background-color: #f2f2f2; }
        .activo { background-color: #d4edda; }    /* verde claro */
        .inactivo { background-color: #f8d7da; }  /* rojo claro */
    </style>
</head>
<body>
    <h2>Reporte de Clientes Activos e Inactivos</h2>

    {{-- Subtítulo dinámico según el filtro --}}
    @if(isset($filtro))
        @if($filtro === 'activos')
            <h3>Clientes Activos</h3>
        @elseif($filtro === 'inactivos')
            <h3>Clientes Inactivos</h3>
        @else
            <h3>Todos los Clientes</h3>
        @endif
    @endif

    <table>
        <thead>
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
            @foreach($clientes as $index => $cliente)
            <tr class="{{ $cliente->estado }}">
                <td>{{ $index + 1 }}</td>
                <td>
                    {{ $cliente->persona->nombre_completo ?? 'N/A' }}
                    {{ $cliente->persona->apellido_paterno ?? 'N/A' }}
                    {{ $cliente->persona->apellido_materno ?? 'N/A' }}
                </td>
                <td>{{ $cliente->persona->ci ?? 'N/A' }}</td>
                <td>{{ $cliente->persona->telefono ?? 'N/A' }}</td>
                <td>{{ $cliente->user->email ?? 'N/A' }}</td>
                <td>{{ ucfirst($cliente->estado) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
