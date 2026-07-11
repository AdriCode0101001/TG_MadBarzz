<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Carnet MadBarzz</title>
    <style>
        @page { margin: 0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Helvetica', Arial, sans-serif;
            background: #16213e;
            width: 320pt;
            height: 500pt;
        }
        .carnet {
            width: 320pt;
            height: 500pt;
            background: #16213e;
            position: relative;
        }
        .franja-top {
            height: 8pt;
            background: #6366f1;
        }
        .header {
            text-align: center;
            padding: 18pt 20pt 12pt;
        }
        .header img {
            width: 64pt;
        }
        .marca {
            font-size: 22pt;
            font-weight: bold;
            color: #ffffff;
            letter-spacing: 4pt;
            margin-top: 6pt;
        }
        .marca span { color: #6366f1; }
        .subtitulo {
            font-size: 8pt;
            color: #94a3b8;
            letter-spacing: 3pt;
            text-transform: uppercase;
            margin-top: 3pt;
        }
        .qr-box {
            width: 190pt;
            margin: 10pt auto 0;
            background: #ffffff;
            border-radius: 12pt;
            padding: 10pt;
            text-align: center;
        }
        .qr-box img {
            width: 170pt;
            height: 170pt;
        }
        .nombre {
            text-align: center;
            color: #ffffff;
            font-size: 15pt;
            font-weight: bold;
            line-height: 1.25;
            margin: 16pt 20pt 0;
            word-wrap: break-word;
        }
        .nombre--largo {
            font-size: 12pt;
        }
        .rol {
            text-align: center;
            color: #6366f1;
            font-size: 8.5pt;
            letter-spacing: 2pt;
            text-transform: uppercase;
            margin-top: 4pt;
        }
        .datos {
            margin: 14pt 30pt 0;
        }
        .datos table {
            width: 100%;
            border-collapse: collapse;
        }
        .datos td {
            padding: 5pt 0;
            border-bottom: 0.5pt solid #2c3a5e;
            font-size: 9pt;
        }
        .datos .etiqueta {
            color: #94a3b8;
            width: 40%;
        }
        .datos .valor {
            color: #e2e8f0;
            font-weight: bold;
            text-align: right;
        }
        .footer {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            text-align: center;
        }
        .footer .leyenda {
            color: #94a3b8;
            font-size: 7.5pt;
            padding-bottom: 8pt;
        }
        .franja-bottom {
            height: 8pt;
            background: #6366f1;
        }
    </style>
</head>
<body>
<div class="carnet">
    <div class="franja-top"></div>

    <div class="header">
        @if($logoDataUri)
            <img src="{{ $logoDataUri }}" alt="MadBarzz">
        @endif
        <div class="marca">MAD<span>BARZZ</span></div>
        <div class="subtitulo">Carnet de Socio</div>
    </div>

    <div class="qr-box">
        <img src="{{ $qrDataUri }}" alt="QR">
    </div>

    <div class="nombre @if(mb_strlen($nombreCompleto) > 26) nombre--largo @endif">{{ $nombreCompleto }}</div>
    <div class="rol">Cliente N° {{ str_pad($cliente->id_cliente, 5, '0', STR_PAD_LEFT) }}</div>

    <div class="datos">
        <table>
            <tr>
                <td class="etiqueta">Membresía</td>
                <td class="valor">{{ $membresia->tipo_membresia ?? 'Sin membresía activa' }}</td>
            </tr>
            @if($membresia)
            <tr>
                <td class="etiqueta">Válida hasta</td>
                <td class="valor">{{ \Carbon\Carbon::parse($membresia->pivot->fecha_fin)->format('d/m/Y') }}</td>
            </tr>
            @endif
            @if($cliente->sede)
            <tr>
                <td class="etiqueta">Sede</td>
                <td class="valor">{{ $cliente->sede->nombre }}</td>
            </tr>
            @endif
        </table>
    </div>

    <div class="footer">
        <div class="leyenda">Presenta este código QR al ingresar al gimnasio · madbarzz.com</div>
        <div class="franja-bottom"></div>
    </div>
</div>
</body>
</html>
