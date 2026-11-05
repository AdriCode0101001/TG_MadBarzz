<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Descuento;
use App\Models\Membresia;
use App\Models\MembresiaCliente;
use App\Services\MembresiaService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class MembresiaClienteController extends Controller
{
    // LISTAR
    public function index(Request $request)
    {
        $buscar = $request->input('buscar');

        $membresiasClientes = MembresiaCliente::with(['cliente.persona', 'membresia'])
            ->when($buscar, function ($query, $buscar) {
                $query->whereHas('cliente.persona', function ($q) use ($buscar) {
                    $q->whereRaw("CONCAT(nombre_completo, ' ', apellido_paterno, ' ', apellido_materno) LIKE ?", ["%{$buscar}%"])
                      ->orWhere('ci', 'LIKE', "%{$buscar}%");
                })
                ->orWhereHas('membresia', function ($q) use ($buscar) {
                    $q->where('tipo_membresia', 'LIKE', "%{$buscar}%");
                });
            })
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('membresia_cliente.index', compact('membresiasClientes'));
    }

    // FORMULARIO EDITAR
    public function edit($id, MembresiaService $servicio)
    {
        $membresiaCliente = MembresiaCliente::findOrFail($id);
        $clientes         = Cliente::with('persona')->get();

        // La fecha de inicio original se conserva; la fecha fin prevista de cada
        // membresía se calcula desde esa fecha de inicio.
        $inicioOriginal = Carbon::parse($membresiaCliente->fecha_inicio);

        $membresias = Membresia::all()->each(function ($m) use ($servicio, $inicioOriginal, $membresiaCliente) {
            $m->fecha_fin_prevista = $servicio->calcularFechas(
                $m,
                $inicioOriginal,
                $servicio->esPrimeraMembresia((int) $membresiaCliente->id_cliente, $membresiaCliente->id)
            )['fecha_fin'];
        });

        $descuentos = Descuento::activos()->orderBy('porcentaje')->get();

        return view('membresia_cliente.edit', compact('membresiaCliente', 'clientes', 'membresias', 'descuentos'));
    }

    // ACTUALIZAR
    public function update(Request $request, $id, MembresiaService $servicio)
    {
        $request->validate([
            'id_cliente'   => 'required|exists:clientes,id_cliente',
            'id_membresia' => 'required|exists:membresias,id_membresia',
            'id_descuento' => [
                'required',
                Rule::exists('descuentos', 'id')->where('activo', true)->whereNull('deleted_at'),
            ],
        ]);

        $membresiaCliente = MembresiaCliente::findOrFail($id);
        $membresia        = Membresia::findOrFail($request->id_membresia);
        $descuento        = Descuento::activos()->findOrFail($request->id_descuento);

        // Se conserva la fecha de inicio original del registro; la fecha fin se
        // recalcula en el servidor según el tipo de membresía elegido.
        // El regalo solo lo lleva la primera membresía del cliente.
        $fechas = $servicio->calcularFechas(
            $membresia,
            Carbon::parse($membresiaCliente->fecha_inicio),
            $servicio->esPrimeraMembresia((int) $request->id_cliente, $membresiaCliente->id)
        );

        $membresiaCliente->update([
            'id_cliente'       => $request->id_cliente,
            'id_membresia'     => $request->id_membresia,
            'fecha_inicio'     => $fechas['fecha_inicio'],
            'fecha_fin'        => $fechas['fecha_fin'],
            'id_descuento'     => $descuento->id,
            'nombre_descuento' => $descuento->nombre,
            'descuento'        => $descuento->porcentaje,
            'precio_final'     => $servicio->precioFinal($membresia, $descuento),
        ]);

        return redirect()->route('membresia_cliente.index')
            ->with('success', 'Membresía del cliente actualizada correctamente.');
    }

    // ELIMINAR LÓGICAMENTE
    public function destroy($id)
    {
        $membresiaCliente = MembresiaCliente::findOrFail($id);
        $membresiaCliente->delete();

        return redirect()->route('membresia_cliente.index')
            ->with('success', 'Membresía del cliente eliminada correctamente.');
    }
}
