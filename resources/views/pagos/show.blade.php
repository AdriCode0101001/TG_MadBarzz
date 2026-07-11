@extends('layouts.private')

@section('contenido')
<div class="col-12">

    <x-page-header title="Detalle del pago" subtitle="Pago N° {{ $pago->id_pago }}" icon="fa-solid fa-money-bill-wave">
        <a href="{{ route('pagos.index') }}" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left me-1"></i> Volver a pagos
        </a>
    </x-page-header>

    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header"><h5 class="mb-0"><i class="fa-solid fa-receipt me-2"></i>Datos del pago</h5></div>
                <div class="card-body p-0">
                    <table class="table align-middle mb-0">
                        <tbody>
                            <tr>
                                <td class="text-muted" style="width:40%;">Cliente</td>
                                <td class="fw-semibold">
                                    {{ trim(($pago->cliente->persona->nombre_completo ?? '') . ' ' . ($pago->cliente->persona->apellido_paterno ?? '') . ' ' . ($pago->cliente->persona->apellido_materno ?? '')) ?: '—' }}
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Membresía</td>
                                <td>{{ $pago->membresia->tipo_membresia ?? '—' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Monto</td>
                                <td><span class="badge bg-success">Bs {{ number_format($pago->monto, 2) }}</span></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Fecha de pago</td>
                                <td>{{ \Carbon\Carbon::parse($pago->fecha_pago)->format('d/m/Y') }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Método</td>
                                <td><span class="badge bg-secondary">{{ ucfirst($pago->metodo_pago) }}</span></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Observaciones</td>
                                <td>{{ $pago->observaciones ?: 'Sin observaciones' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header"><h5 class="mb-0"><i class="fa-solid fa-image me-2"></i>Comprobante</h5></div>
                <div class="card-body text-center">
                    @if($pago->imagen_qr)
                        {{-- Imagen privada servida por ruta protegida por rol --}}
                        <a href="{{ route('pagos.comprobante', $pago->id_pago) }}" target="_blank" title="Abrir en tamaño completo">
                            <img src="{{ route('pagos.comprobante', $pago->id_pago) }}" alt="Comprobante del pago"
                                 style="max-width:100%;max-height:420px;border-radius:12px;border:1px solid rgba(128,128,128,.25);">
                        </a>
                        <p class="text-muted mt-2 mb-0" style="font-size:.8rem;">Clic en la imagen para verla en tamaño completo.</p>
                    @else
                        <div class="mb-empty py-5">
                            <div class="mb-empty-icon"><i class="fa-solid fa-image"></i></div>
                            <p class="mb-0">Este pago no tiene comprobante adjunto (pago en efectivo).</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
