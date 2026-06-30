<?php

namespace App\Http\Controllers;

use App\Models\Pago;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\Membresia;

class PagoController extends Controller
{
public function index(Request $request)
{
    $buscar = $request->input('buscar');

    $pagos = Pago::with(['cliente.persona', 'membresia'])
        ->when($buscar, function($query, $buscar) {

            // Buscar por cliente o membresía
            $query->whereHas('cliente.persona', function($q) use ($buscar) {
                $q->where('nombre_completo', 'like', "%{$buscar}%")
                  ->orWhere('apellido_paterno', 'like', "%{$buscar}%")
                  ->orWhere('apellido_materno', 'like', "%{$buscar}%");
            })
            ->orWhereHas('membresia', function($q) use ($buscar) {
                $q->where('tipo_membresia', 'like', "%{$buscar}%");
            });

            // Buscar por fechas flexibles
            if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $buscar, $matches)) {
                // Formato dd/mm/yyyy
                $fecha = "{$matches[3]}-{$matches[2]}-{$matches[1]}";
                $query->orWhereDate('fecha_pago', $fecha);
            } elseif (preg_match('/^(\d{2})\/(\d{2})$/', $buscar, $matches)) {
                // Formato dd/mm (ignora año)
                $dia = $matches[1];
                $mes = $matches[2];
                $query->orWhereRaw('DAY(fecha_pago) = ? AND MONTH(fecha_pago) = ?', [$dia, $mes]);
            } elseif (preg_match('/^(\d{2})\/(\d{4})$/', $buscar, $matches)) {
                // Formato mm/yyyy
                $mes = $matches[1];
                $anio = $matches[2];
                $query->orWhereRaw('MONTH(fecha_pago) = ? AND YEAR(fecha_pago) = ?', [$mes, $anio]);
            } elseif (preg_match('/^\d{4}$/', $buscar, $matches)) {
                // Solo año
                $anio = $buscar;
                $query->orWhereYear('fecha_pago', $anio);
            }
        })
        ->latest()
        ->get();

    return view('pagos.index', compact('pagos', 'buscar'));
}




    public function create()
    {
        // Obtener clientes con su última membresía asignada
        $clientes = Cliente::with(['persona', 'membresiasClientes.membresia'])->get();

        return view('pagos.create', compact('clientes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_cliente'    => 'required|exists:clientes,id_cliente',
            'id_membresia'  => 'required|exists:membresias,id_membresia',
            'monto'         => 'required|numeric|min:0',
            'fecha_pago'    => 'required|date',
            'metodo_pago'   => 'required|in:efectivo,transferencia,QR',
            'observaciones' => 'nullable|string',
            'imagen_qr'     => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($request->hasFile('imagen_qr')) {
            $validated['imagen_qr'] = $request->file('imagen_qr')->store('qrs', 'privado');
        }

        Pago::create($validated);

        return redirect()->route('pagos.index')->with('success', 'Pago registrado correctamente.');
    }

    public function show($id)
    {
        $pago = Pago::with(['cliente.persona', 'membresia'])->findOrFail($id);
        return view('pagos.show', compact('pago'));
    }

    public function misPagos()
    {
        $cliente = Cliente::where('id_usuario', Auth::id())->first();

        if (!$cliente) {
            return redirect()->back()->with('error', 'No tienes un perfil de cliente asociado.');
        }

        $pagos = Pago::with('membresia')
                    ->where('id_cliente', $cliente->id_cliente)
                    ->orderBy('fecha_pago', 'desc')
                    ->get();

        return view('pagos.mis-pagos', compact('pagos', 'cliente'));
    }

public function edit($id)
{
    $pago = Pago::with(['cliente.persona', 'membresia'])->findOrFail($id);
    $clientes = Cliente::with('persona')->get();
    $membresias = Membresia::all(); // <--- Aquí traemos todas las membresías

    return view('pagos.edit', compact('pago', 'clientes', 'membresias'));
}


    public function update(Request $request, Pago $pago)
    {
        $validated = $request->validate([
            'id_cliente'    => 'required|exists:clientes,id_cliente',
            'id_membresia'  => 'required|exists:membresias,id_membresia',
            'monto'         => 'required|numeric|min:0',
            'fecha_pago'    => 'required|date',
            'metodo_pago'   => 'required|in:efectivo,transferencia,QR',
            'observaciones' => 'nullable|string',
            'imagen_qr'     => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($request->hasFile('imagen_qr')) {
            if ($pago->imagen_qr) {
                Storage::disk('privado')->delete($pago->imagen_qr);
            }
            $validated['imagen_qr'] = $request->file('imagen_qr')->store('qrs', 'privado');
        }

        $pago->update($validated);

        return redirect()->route('pagos.index')->with('success', 'Pago actualizado correctamente.');
    }

    public function destroy(Pago $pago)
    {
        $pago->delete();

        return redirect()->route('pagos.index')->with('success', 'Pago eliminado correctamente.');
    }
}
