<?php

namespace App\Http\Controllers;

use App\Models\QrPago;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * QR de pago único del gimnasio: solo el Administrador lo configura;
 * lo ve (para cobrar) también Recepcionista y Entrenador, servido siempre
 * por ruta protegida (nunca URL pública).
 */
class QrPagoController extends Controller
{
    /**
     * Configuración (solo Administrador): muestra el QR actual y el
     * formulario para reemplazarlo.
     */
    public function edit()
    {
        $qrPago = QrPago::actual();

        return view('configuracion.qr_pago', compact('qrPago'));
    }

    /**
     * Sube un QR nuevo (reemplaza al actual). Se crea un registro nuevo:
     * se conserva el historial de QR anteriores.
     */
    public function update(Request $request)
    {
        $data = $request->validate([
            'imagen'            => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'fecha_vencimiento' => 'required|date|after:today',
        ]);

        $ruta = $request->file('imagen')->store('qr-pago', 'privado');

        QrPago::create([
            'imagen'            => $ruta,
            'fecha_vencimiento' => $data['fecha_vencimiento'],
            'id_usuario'        => Auth::id(),
        ]);

        return redirect()->route('qr_pago.edit')
            ->with('success', 'QR de pago actualizado correctamente.');
    }

    /**
     * Sirve la imagen del QR actual (esté vigente o vencido) desde el disco
     * privado. Quien decide si corresponde MOSTRARLA (cobro vs. configuración)
     * es la vista que arma el <img>: la de cobro solo la pide si está vigente;
     * la de configuración siempre la muestra para que el Admin vea qué tiene
     * cargado, con su badge de Vigente/Vencido.
     */
    public function imagen()
    {
        $qrPago = QrPago::actual();

        abort_unless($qrPago && Storage::disk('privado')->exists($qrPago->imagen), 404);

        return Storage::disk('privado')->response($qrPago->imagen);
    }
}
