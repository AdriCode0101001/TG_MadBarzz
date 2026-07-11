@extends('layouts.private')

@section('contenido')

<div class="container-fluid">
    <div class="row justify-content-center mt-4">
        <div class="col-xl-12">
            <div class="card custom-card">

                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="card-title fs-24 fw-bold text-dark dark-text-white">
                        <h2 class="fw-bold mb-0">Registrar Pago</h2>
                    </div>
                </div>

                <div class="card-body">

                    <div class="alert alert-info border-0">
                        <i class="fa-solid fa-circle-info me-2"></i>
                        Aquí se elige la membresía del cliente Y se registra su pago en un solo paso.
                        Al guardar, la membresía queda <strong>activa de inmediato</strong> (ya está pagada).
                    </div>

                    @if ($errors->any())
                        <div class="alert alert-danger mb-3">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form id="formPago" action="{{ route('pagos.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <h5 class="dark-text-white mb-3"><i class="fa-solid fa-id-card me-2"></i>Membresía a cobrar</h5>
                        <div class="row g-3">

                            {{-- Cliente --}}
                            <div class="col-md-6">
                                <label for="id_cliente" class="form-label fw-bold text-dark dark-text-white">Cliente</label>
                                <select name="id_cliente" id="id_cliente" class="form-control" required>
                                    <option value="">Seleccione un cliente</option>
                                    @foreach($clientes as $cliente)
                                        <option value="{{ $cliente->id_cliente }}">
                                            {{ trim(($cliente->persona->nombre_completo ?? '') . ' ' . ($cliente->persona->apellido_paterno ?? '') . ' ' . ($cliente->persona->apellido_materno ?? '')) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Tipo de membresía --}}
                            <div class="col-md-6">
                                <label for="id_membresia" class="form-label fw-bold text-dark dark-text-white">Membresía</label>
                                <select name="id_membresia" id="id_membresia" class="form-control" required>
                                    <option value="">Seleccione una membresía</option>
                                    @foreach($membresias as $membresia)
                                        <option value="{{ $membresia->id_membresia }}"
                                                data-precio="{{ $membresia->precio }}"
                                                data-fecha-fin="{{ \Carbon\Carbon::parse($membresia->fecha_fin_prevista)->format('d/m/Y') }}">
                                            {{ $membresia->tipo_membresia }} - Bs {{ number_format($membresia->precio, 2) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Fechas de la membresía: SOLO informativas, las calcula el servidor --}}
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark dark-text-white">Inicio de membresía (automático)</label>
                                <input type="text" class="form-control" value="{{ now()->format('d/m/Y') }}" readonly disabled>
                                <small class="text-muted">Siempre es la fecha de hoy.</small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark dark-text-white">Fin de membresía (automático)</label>
                                <input type="text" id="fecha_fin_info" class="form-control" value="Elija una membresía" readonly disabled>
                                <small class="text-muted">Duración de la membresía + 1 día de regalo.</small>
                            </div>

                            {{-- Descuento --}}
                            <div class="col-md-6">
                                <label for="id_descuento" class="form-label fw-bold text-dark dark-text-white">Descuento</label>
                                <select name="id_descuento" id="id_descuento" class="form-control" required>
                                    @foreach($descuentos as $descuento)
                                        <option value="{{ $descuento->id }}"
                                                data-porcentaje="{{ $descuento->porcentaje }}"
                                                @selected($descuento->porcentaje == 0 && $descuento->nombre === 'Sin descuento')>
                                            {{ $descuento->nombre }} ({{ rtrim(rtrim(number_format($descuento->porcentaje, 2), '0'), '.') }}%)
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Monto: SOLO informativo, lo calcula el servidor --}}
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark dark-text-white">Monto a pagar (Bs)</label>
                                <input type="text" id="monto_info" class="form-control" value="—" readonly disabled>
                                <small class="text-muted">Precio de la membresía menos el descuento. Lo calcula el sistema.</small>
                            </div>
                        </div>

                        <hr class="my-4">

                        <h5 class="dark-text-white mb-3"><i class="fa-solid fa-money-bill-wave me-2"></i>Datos del pago</h5>
                        <div class="row g-3">

                            {{-- Fecha de pago: SOLO informativa, siempre hoy --}}
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark dark-text-white">Fecha de pago (automática)</label>
                                <input type="text" class="form-control" value="{{ now()->format('d/m/Y') }}" readonly disabled>
                                <small class="text-muted">Siempre es la fecha de hoy, el día en que se registra.</small>
                            </div>

                            {{-- Método de pago: solo Efectivo y QR --}}
                            <div class="col-md-6">
                                <label for="metodo_pago" class="form-label fw-bold text-dark dark-text-white">Método de pago</label>
                                <select name="metodo_pago" id="metodo_pago" class="form-control" required>
                                    <option value="efectivo">Efectivo</option>
                                    <option value="QR">QR</option>
                                </select>
                            </div>

                            {{-- Observaciones --}}
                            <div class="col-md-6">
                                <label for="observaciones" class="form-label fw-bold text-dark dark-text-white">Observaciones</label>
                                <textarea name="observaciones" id="observaciones" class="form-control"></textarea>
                            </div>

                            {{-- Sección QR --}}
                            <div id="qrSection" style="display:none;">
                                <div class="col-md-12 text-center">
                                    <label class="form-label fw-bold text-dark dark-text-white">QR de pago del gimnasio</label><br>
                                    @if($qrVigente)
                                        {{-- Servido por ruta protegida (disco privado), no URL pública --}}
                                        <img src="{{ route('qr_pago.imagen') }}" alt="QR de pago" style="max-width: 300px; margin-bottom: 20px; border-radius:12px;border:1px solid rgba(128,128,128,.25);">
                                        <p class="text-muted" style="font-size:.85rem;">Que el cliente lo escanee y transfiera el monto.</p>
                                    @else
                                        <div class="alert alert-warning" style="max-width:420px;margin:0 auto 20px;">
                                            <i class="fa-solid fa-triangle-exclamation me-2"></i>
                                            No hay un QR de pago vigente. Pídele al administrador que lo actualice.
                                        </div>
                                    @endif
                                </div>

                                <div class="col-md-12 text-center">
                                    <label for="imagen_qr" class="form-label fw-bold text-dark dark-text-white">Comprobante (subir imagen)</label>
                                    <input type="file" name="imagen_qr" id="imagen_qr" class="form-control" accept="image/*" onchange="previewImage(event)">
                                    <div class="mt-3">
                                        <img id="preview" src="#" alt="Vista previa" style="max-width: 300px; display: none; border: 1px solid #ccc; padding: 5px;">
                                    </div>
                                </div>
                            </div>

                        </div>

                        {{-- Botones --}}
                        <div class="mt-4">
                            <button type="submit" class="btn btn-success">
                                <i class="fa-solid fa-save me-2"></i> Activar membresía y guardar pago
                            </button>
                            <a href="{{ route('pagos.index') }}" class="btn btn-secondary ms-2">
                                Cancelar
                            </a>
                        </div>

                    </form>

                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal de aviso de ACUMULACIÓN (autocontenido, sin depender de Bootstrap JS) --}}
<div id="modalAcumular" style="display:none; position:fixed; inset:0; z-index:1080; background:rgba(0,0,0,.5); align-items:center; justify-content:center;">
    {{-- Superficie con los tokens de la piel (--mbf-*): fondo y texto cambian
         solos entre modo claro y oscuro, coherente con las tarjetas del sistema. --}}
    <div style="background:var(--mbf-surface,#fff); color:var(--mbf-text,#18181b); max-width:520px; width:92%; border:1px solid var(--mbf-border,rgba(128,128,128,.2)); border-radius:12px; overflow:hidden; box-shadow:var(--mbf-shadow,0 10px 40px rgba(0,0,0,.3));">
        <div style="padding:18px 22px; border-bottom:1px solid var(--mbf-border,rgba(128,128,128,.2));">
            <h5 class="mb-0 fw-bold" style="color:var(--mbf-text,#18181b);"><i class="fa-solid fa-triangle-exclamation me-2 text-warning"></i>Membresía activa vigente</h5>
        </div>
        <div style="padding:22px; color:var(--mbf-text,#18181b);">
            <p class="mb-2">
                Este cliente ya tiene una membresía activa hasta
                <strong id="acuHasta"></strong>.
            </p>
            <p class="mb-2">
                Al continuar, la nueva membresía <strong>se sumará a partir de esa fecha</strong>
                (no pierde los días restantes).
            </p>
            <p class="mb-0" id="acuNuevoFinWrap">
                Nueva fecha de fin tras acumular: <strong id="acuNuevoFin"></strong>.
            </p>
        </div>
        <div style="padding:16px 22px; border-top:1px solid var(--mbf-border,rgba(128,128,128,.2)); display:flex; gap:10px; justify-content:flex-end;">
            <button type="button" id="acuCancelar" class="btn btn-secondary">Cancelar</button>
            <button type="button" id="acuAceptar" class="btn btn-success">
                <i class="fa-solid fa-check me-2"></i>Aceptar y acumular
            </button>
        </div>
    </div>
</div>

{{-- Scripts --}}
<script>
(function () {
    var membresiaSel = document.getElementById('id_membresia');
    var descuentoSel = document.getElementById('id_descuento');
    var fechaFinInfo = document.getElementById('fecha_fin_info');
    var montoInfo    = document.getElementById('monto_info');
    var metodoPago   = document.getElementById('metodo_pago');
    var clienteSel   = document.getElementById('id_cliente');

    // Solo VISTA PREVIA: el servidor recalcula fechas y monto al guardar,
    // ignorando cualquier valor manipulado en el envío.
    function actualizar() {
        var op = membresiaSel.options[membresiaSel.selectedIndex];

        if (!op || !op.value) {
            fechaFinInfo.value = 'Elija una membresía';
            montoInfo.value = '—';
            return;
        }

        fechaFinInfo.value = op.dataset.fechaFin;

        var precio = parseFloat(op.dataset.precio) || 0;
        var od = descuentoSel.options[descuentoSel.selectedIndex];
        var pct = od ? (parseFloat(od.dataset.porcentaje) || 0) : 0;
        montoInfo.value = (precio * (1 - pct / 100)).toFixed(2);
    }

    membresiaSel.addEventListener('change', actualizar);
    descuentoSel.addEventListener('change', actualizar);
    actualizar();

    // ── Aviso de ACUMULACIÓN antes de guardar ───────────────────────────────
    // Mapa (del servidor) de clientes con membresía vigente: hasta cuándo llega
    // y qué fin tendría la nueva por tipo de membresía. Solo es un aviso: el
    // servidor recalcula y decide igual, aunque se salte este diálogo.
    var ACUMULACION = @json($acumulacion);

    var form        = document.getElementById('formPago');
    var modal       = document.getElementById('modalAcumular');
    var acuHasta    = document.getElementById('acuHasta');
    var acuNuevoFin = document.getElementById('acuNuevoFin');
    var acuNuevoWrap= document.getElementById('acuNuevoFinWrap');
    var confirmado  = false;

    function abrirModal(info) {
        acuHasta.textContent = info.hasta;

        var op = membresiaSel.options[membresiaSel.selectedIndex];
        var fin = (op && op.value && info.nuevoFin) ? info.nuevoFin[op.value] : null;
        if (fin) {
            acuNuevoFin.textContent = fin;
            acuNuevoWrap.style.display = '';
        } else {
            acuNuevoWrap.style.display = 'none';
        }

        modal.style.display = 'flex';
    }

    function cerrarModal() { modal.style.display = 'none'; }

    form.addEventListener('submit', function (e) {
        if (confirmado) { return; } // ya aceptó: dejar pasar

        var info = ACUMULACION[clienteSel.value];
        if (!info) { return; } // sin membresía vigente: flujo normal

        e.preventDefault();
        abrirModal(info);
    });

    document.getElementById('acuCancelar').addEventListener('click', cerrarModal);
    document.getElementById('acuAceptar').addEventListener('click', function () {
        confirmado = true;
        cerrarModal();
        form.submit();
    });

    function aplicarMetodo(valor) {
        var qrSection = document.getElementById('qrSection');
        var imagenInp = document.getElementById('imagen_qr');
        if (valor === 'QR') {
            qrSection.style.display = 'block';
            // El comprobante es obligatorio para QR (también validado en el servidor)
            imagenInp.setAttribute('required', 'required');
        } else {
            qrSection.style.display = 'none';
            imagenInp.removeAttribute('required');
            imagenInp.value = '';
            document.getElementById('preview').style.display = 'none';
        }
    }

    metodoPago.addEventListener('change', function() { aplicarMetodo(this.value); });
    aplicarMetodo(metodoPago.value); // estado inicial coherente
})();

function previewImage(event) {
    const preview = document.getElementById('preview');
    const file = event.target.files[0];
    if(file) {
        preview.src = URL.createObjectURL(file);
        preview.style.display = 'block';
    } else {
        preview.style.display = 'none';
    }
}
</script>

@endsection
