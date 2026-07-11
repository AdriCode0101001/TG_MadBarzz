@extends('layouts.private')

@section('contenido')
<div class="col-12">

    <x-page-header title="QR de Pago" subtitle="El QR bancario único que se muestra al cobrar por QR" icon="fa-solid fa-qrcode" />

    @if(session('success'))
        <div class="alert alert-success border-0 rounded-3 mb-4">{{ session('success') }}</div>
    @endif

    @if($qrPago && $qrPago->requiereAviso())
        <div class="alert {{ $qrPago->estaVencido() ? 'alert-danger' : 'alert-warning' }} border-0 rounded-3 mb-4">
            <i class="fa-solid fa-triangle-exclamation me-2"></i>
            @if($qrPago->estaVencido())
                El QR de pago está <strong>VENCIDO</strong> desde el {{ $qrPago->fecha_vencimiento->format('d/m/Y') }}.
                Los cobros por QR no podrán mostrarlo hasta que subas uno nuevo.
            @else
                El QR de pago vence en <strong>{{ $qrPago->diasParaVencer() }} día(s)</strong>
                ({{ $qrPago->fecha_vencimiento->format('d/m/Y') }}). Actualízalo pronto para no interrumpir los cobros por QR.
            @endif
        </div>
    @endif

    <div class="row">
        <div class="col-lg-5 mb-4">
            <div class="card h-100">
                <div class="card-header"><h5 class="mb-0"><i class="fa-solid fa-image me-2"></i>QR actual</h5></div>
                <div class="card-body text-center">
                    @if($qrPago)
                        <img src="{{ route('qr_pago.imagen') }}" alt="QR de pago"
                             style="max-width:100%;max-height:320px;border-radius:12px;border:1px solid rgba(128,128,128,.25);">
                        <div class="mt-3">
                            <span class="badge {{ $qrPago->estaVigente() ? 'bg-success' : 'bg-danger' }} mb-badge-dot">
                                {{ $qrPago->estaVigente() ? 'Vigente' : 'Vencido' }}
                            </span>
                            <p class="text-muted mt-2 mb-0" style="font-size:.85rem;">
                                Vence: {{ $qrPago->fecha_vencimiento->format('d/m/Y') }}
                            </p>
                        </div>
                    @else
                        <div class="mb-empty py-4">
                            <div class="mb-empty-icon"><i class="fa-solid fa-qrcode"></i></div>
                            <p class="mb-0">Aún no configuraste un QR de pago.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-7 mb-4">
            <div class="card h-100">
                <div class="card-header"><h5 class="mb-0"><i class="fa-solid fa-arrow-up-from-bracket me-2"></i>{{ $qrPago ? 'Reemplazar QR' : 'Subir QR' }}</h5></div>
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger mb-3">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('qr_pago.update') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-3">
                            <label for="imagen" class="form-label">Imagen del QR (foto del QR de tu banco/billetera)</label>
                            <input type="file" name="imagen" id="imagen" class="form-control @error('imagen') is-invalid @enderror"
                                   accept="image/*" required>
                            @error('imagen')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label for="fecha_vencimiento" class="form-label">Fecha de vencimiento del QR</label>
                            <input type="date" name="fecha_vencimiento" id="fecha_vencimiento"
                                   class="form-control @error('fecha_vencimiento') is-invalid @enderror"
                                   value="{{ old('fecha_vencimiento') }}" min="{{ now()->addDay()->toDateString() }}" required>
                            @error('fecha_vencimiento')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <small class="text-muted">Los QR bancarios caducan; el sistema te avisará cuando se acerque esta fecha.</small>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-save me-1"></i> {{ $qrPago ? 'Guardar como el nuevo QR vigente' : 'Guardar QR' }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
