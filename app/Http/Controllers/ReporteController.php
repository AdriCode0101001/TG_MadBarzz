<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Pago;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class ReporteController extends Controller
{
    // =============================
    // 1. Clientes activos e inactivos
    // =============================
public function clientesEstado(Request $request)
{
    // Obtener el valor del filtro (por defecto 'todos')
    $filtro = $request->get('filtro', 'todos');

    // Consulta base con relación persona
    $query = Cliente::with('persona');

    // Aplicar filtro según el estado
    if ($filtro === 'activos') {
        $query->where('estado', 'activo');
    } elseif ($filtro === 'inactivos') {
        $query->where('estado', 'inactivo');
    }

    // Obtener los clientes filtrados
    $clientes = $query->get();

    // Retornar la vista con los datos y el filtro seleccionado
    return view('reportes.clientes_estado', compact('clientes', 'filtro'));
}

public function exportClientesEstadoPDF(Request $request)
{
    // Tomar el mismo filtro usado en la vista
    $filtro = $request->get('filtro', 'todos');

    $query = Cliente::with('persona');

    if ($filtro === 'activos') {
        $query->where('estado', 'activo');
    } elseif ($filtro === 'inactivos') {
        $query->where('estado', 'inactivo');
    }

    $clientes = $query->get();

    // Generar PDF con los datos filtrados
    $pdf = Pdf::loadView('reportes.pdf.clientes_estado', compact('clientes', 'filtro'))
              ->setPaper('a4', 'landscape');

    // Nombre del archivo dinámico según filtro
    $nombreArchivo = 'clientes_' . $filtro . '.pdf';

    return $pdf->download($nombreArchivo);
}

    // =============================
    // 2. Clientes nuevos por mes
    // =============================
    public function clientesNuevos(Request $request)
    {
        Carbon::setLocale('es');

        $mes = $request->input('mes', Carbon::now()->month);
        $anio = $request->input('anio', Carbon::now()->year);

        $clientes = Cliente::with('persona')
            ->whereYear('created_at', $anio)
            ->whereMonth('created_at', $mes)
            ->get();

        // Pasar datos para el select de meses y años
        $meses = collect(range(1,12))->mapWithKeys(function($m){
            return [$m => Carbon::create()->month($m)->locale('es')->monthName];
        });

        // Años distintos con clientes registrados, para el <select> del filtro.
        // Se calcula en PHP (no con YEAR() de SQL) para no depender del motor
        // de base de datos.
        $anios = Cliente::pluck('created_at')
            ->map(fn ($fecha) => Carbon::parse($fecha)->year)
            ->unique()
            ->sortDesc()
            ->values();

        return view('reportes.clientes_nuevos', compact('clientes', 'meses', 'anios', 'mes', 'anio'));
    }

    public function exportClientesNuevosPDF(Request $request)
    {
        Carbon::setLocale('es');

        $mes = $request->input('mes', Carbon::now()->month);
        $anio = $request->input('anio', Carbon::now()->year);

        $clientes = Cliente::with('persona')
            ->whereYear('created_at', $anio)
            ->whereMonth('created_at', $mes)
            ->get();

        $pdf = Pdf::loadView('reportes.pdf.clientes_nuevos', compact('clientes', 'mes', 'anio'))
                  ->setPaper('a4', 'portrait');

        return $pdf->download("clientes_nuevos_{$mes}_{$anio}.pdf");
    }

// =============================
// 3. Clientes con membresías vencidas o por vencer
// =============================
public function clientesMembresiaVencida(Request $request)
{
    // Mes/año actuales por defecto: el filtro llega precargado con el
    // período en curso, sin que el usuario tenga que elegirlo a mano.
    $mes = $request->input('mes', Carbon::now()->month);
    $anio = $request->input('anio', Carbon::now()->year);

    // Traer solo clientes que tienen al menos una membresía
    $clientes = Cliente::whereHas('membresias')
        ->with(['persona', 'membresias'])
        ->get()
        ->filter(function ($cliente) use ($mes, $anio) {
            $ultimaMembresia = $cliente->membresias()->orderBy('fecha_fin', 'desc')->first();
            if (!$ultimaMembresia) return false;

            $fechaFin = \Carbon\Carbon::parse($ultimaMembresia->pivot->fecha_fin);

            // Revisar si la membresía vence en los próximos 7 días
            $venceProximo = $fechaFin->lessThanOrEqualTo(\Carbon\Carbon::now()->addDays(7));

            // Filtrar por mes y año si se proporciona
            $mesValido = $mes ? $fechaFin->month == $mes : true;
            $anioValido = $anio ? $fechaFin->year == $anio : true;

            return $venceProximo && $mesValido && $anioValido;
        });

    return view('reportes.clientes_membresia_vencida', compact('clientes', 'mes', 'anio'));
}

public function exportClientesMembresiaVencidaPDF(Request $request)
{
    // Mismo default que la pantalla: mes/año actuales si no se especifican.
    $mes = $request->input('mes', Carbon::now()->month);
    $anio = $request->input('anio', Carbon::now()->year);

    $clientes = Cliente::whereHas('membresias')
        ->with(['persona', 'membresias'])
        ->get()
        ->filter(function ($cliente) use ($mes, $anio) {
            $ultimaMembresia = $cliente->membresias()->orderBy('fecha_fin', 'desc')->first();
            if (!$ultimaMembresia) return false;

            $fechaFin = \Carbon\Carbon::parse($ultimaMembresia->pivot->fecha_fin);

            // Verifica si vence próximamente (7 días) y coincide con mes y año del filtro
            $venceProximo = $fechaFin->lessThanOrEqualTo(\Carbon\Carbon::now()->addDays(7));
            $mesValido = $mes ? $fechaFin->month == $mes : true;
            $anioValido = $anio ? $fechaFin->year == $anio : true;

            return $venceProximo && $mesValido && $anioValido;
        });

    // Pasar también mes y año al Blade
    $pdf = Pdf::loadView('reportes.pdf.clientes_membresia_vencida', compact('clientes', 'mes', 'anio'))
              ->setPaper('a4', 'portrait');

    return $pdf->download('clientes_membresia_vencida.pdf');
}


// =============================
// 4. Pagos por mes con filtros dinámicos
// =============================
public function pagosPorMes()
{
    // Obtener mes y año de la solicitud, si no vienen, usar el mes y año actual
    $mes = request('mes', Carbon::now()->month);
    $anio = request('anio', Carbon::now()->year);

    $pagos = Pago::with(['cliente.persona'])
        ->when($anio, function($query, $anio) {
            $query->whereYear('fecha_pago', $anio);
        })
        ->when($mes, function($query, $mes) {
            $query->whereMonth('fecha_pago', $mes);
        })
        ->orderBy('fecha_pago', 'desc')
        ->get();

    // Se pasan mes/año a la vista para que el <select> quede precargado
    // con el período actual (y no solo la consulta detrás de cámaras).
    return view('reportes.pagos_por_mes', compact('pagos', 'mes', 'anio'));
}

public function exportPagosPorMesPDF()
{
    $mes = request('mes', Carbon::now()->month);
    $anio = request('anio', Carbon::now()->year);

    $pagos = Pago::with(['cliente.persona'])
        ->when($anio, function ($query, $anio) {
            $query->whereYear('fecha_pago', $anio);
        })
        ->when($mes, function ($query, $mes) {
            $query->whereMonth('fecha_pago', $mes);
        })
        ->orderBy('fecha_pago', 'desc')
        ->get();

    // ✅ Se envían también $mes y $anio al PDF
    $pdf = Pdf::loadView('reportes.pdf.pagos_por_mes', compact('pagos', 'mes', 'anio'))
              ->setPaper('a4', 'landscape');

    // ✅ El nombre del archivo incluirá el mes y año
    return $pdf->download("pagos_por_mes_{$mes}_{$anio}.pdf");
}

}
