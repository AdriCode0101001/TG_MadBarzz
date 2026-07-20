<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte - Clientes Nuevos del Mes</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; }
        h2, h4 { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; table-layout: fixed; }
        th, td { border: 1px solid #000; padding: 5px; text-align: center; word-wrap: break-word; }
        th { background-color: #f2f2f2; }
        /* Anchos fijos para que la columna nueva (Celular) no desborde el A4 vertical */
        .col-num    { width: 5%; }
        .col-nombre { width: 33%; }
        .col-ci     { width: 15%; }
        .col-cel    { width: 22%; }
        .col-fecha  { width: 25%; }
    </style>
</head>
<body>
    <h2>Reporte de Clientes Nuevos</h2>
<BR></BR>
    @if(isset($mes) && isset($anio))
        <h4>Mes: {{ \Carbon\Carbon::createFromDate($anio, $mes, 1)->locale('es')->isoFormat('MMMM') }} / Año: {{ $anio }}</h4>
    @endif

    <table>
        <thead>
            <tr>
                <th class="col-num">#</th>
                <th class="col-nombre">Nombre Completo</th>
                <th class="col-ci">CI</th>
                <th class="col-cel">Celular</th>
                <th class="col-fecha">Fecha Registro</th>
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
                <td>{{ $cliente->persona?->telefonoLegible() ?? 'N/A' }}</td>
                <td>{{ \Carbon\Carbon::parse($cliente->created_at)->format('d/m/Y') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="text-center">No hay clientes nuevos en este mes.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
