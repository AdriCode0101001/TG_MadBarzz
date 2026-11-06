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
                                                data-fecha-fin="{{ \Carbon\Carbon::parse($membresia->fecha_fin_prevista)->format('d/m/Y') }}"
                                                data-fecha-fin-sin-regalo="{{ \Carbon\Carbon::parse($membresia->fecha_fin_sin_regalo)->format('d/m/Y') }}">
                                            {{ $membresia->tipo_membresia }} - Bs {{ number_format($membresia->precio, 2) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Fechas de la membresía: SOLO informativas, las calcula el servidor --}}
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark dark-text-white" for="fecha_inicio_info">Inicio de membresía (automático)</label>
                                <input type="text" id="fecha_inicio_info" class="form-control" value="{{ now()->format('d/m/Y') }}" readonly disabled>
                                <small class="text-muted">Siempre es la fecha de hoy.</small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark dark-text-white" for="fecha_fin_info">Fin de membresía (automático)</label>
                                <input type="text" id="fecha_fin_info" class="form-control" value="Elija una membresía" readonly disabled>
                                <small class="text-muted">Se calcula según el cliente: el día de regalo solo se suma en su primera inscripción.</small>
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
                                <label class="form-label fw-bold text-dark dark-text-white" for="monto_info">Monto a pagar (Bs)</label>
                                <input type="text" id="monto_info" class="form-control" value="—" readonly disabled>
                                <small class="text-muted">Precio de la membresía menos el descuento. Lo calcula el sistema.</small>
                            </div>

                            {{-- Pago en 2 Cuotas (Etapa 6): apagado por defecto — los campos
                                 monto_pagado/fecha_limite_saldo nacen "disabled" y solo se
                                 habilitan por JS al tildar el checkbox. Un input disabled NO
                                 se manda en el POST, así que con el checkbox destildado el
                                 formulario viaja EXACTAMENTE igual que antes de esta etapa. --}}
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="pagoEnCuotas">
                                    <label class="form-check-label fw-bold text-dark dark-text-white" for="pagoEnCuotas">
                                        <i class="fa-solid fa-layer-group me-1"></i> Pago en 2 cuotas (el cliente paga una parte ahora)
                                    </label>
                                </div>
                            </div>

                            <div id="seccionCuotas" class="col-12" style="display:none;">
                                <div class="row g-3 p-3 mt-1" style="background:var(--mbf-primary-soft,rgba(255, 107, 26,.08));border-radius:12px;border:1px solid var(--mbf-border,rgba(128,128,128,.2));">
                                    <div class="col-md-4">
                                        <label for="monto_pagado" class="form-label fw-bold text-dark dark-text-white">Monto que paga ahora (Bs)</label>
                                        <input type="number" name="monto_pagado" id="monto_pagado" class="form-control" step="0.01" min="0.01" disabled>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="fecha_limite_saldo" class="form-label fw-bold text-dark dark-text-white">Fecha límite del saldo</label>
                                        <input type="date" name="fecha_limite_saldo" id="fecha_limite_saldo" class="form-control" disabled>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold text-dark dark-text-white" for="saldo_info">Saldo que quedará pendiente</label>
                                        <input type="text" id="saldo_info" class="form-control" value="—" readonly disabled>
                                    </div>
                                    <div class="col-12">
                                        <small class="text-muted">
                                            El cliente entra con huella con normalidad. Si no paga el saldo antes de la fecha límite,
                                            la membresía se suspende automáticamente (sin perder los días de vencimiento ya calculados).
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <h5 class="dark-text-white mb-3"><i class="fa-solid fa-money-bill-wave me-2"></i>Datos del pago</h5>
                        <div class="row g-3">

                            {{-- Fecha de pago: SOLO informativa, siempre hoy --}}
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark dark-text-white" for="fecha_pago_info">Fecha de pago (automática)</label>
                                <input type="text" id="fecha_pago_info" class="form-control" value="{{ now()->format('d/m/Y') }}" readonly disabled>
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

        // La fecha depende del cliente (mismo criterio que el servidor):
        //  - con membresía vigente → se acumula (fin calculado por el servidor)
        //  - ya tuvo membresía antes → renovación exacta, sin día de regalo
        //  - primera inscripción → con día de regalo
        var cid  = parseInt(clienteSel.value, 10);
        var acu  = ACUMULACION[clienteSel.value];
        if (acu && acu.nuevoFin && acu.nuevoFin[op.value]) {
            fechaFinInfo.value = acu.nuevoFin[op.value];
        } else if (cid && CON_HISTORIAL.indexOf(cid) !== -1) {
            fechaFinInfo.value = op.dataset.fechaFinSinRegalo;
        } else {
            fechaFinInfo.value = op.dataset.fechaFin;
        }

        var precio = parseFloat(op.dataset.precio) || 0;
        var od = descuentoSel.options[descuentoSel.selectedIndex];
        var pct = od ? (parseFloat(od.dataset.porcentaje) || 0) : 0;
        montoInfo.value = (precio * (1 - pct / 100)).toFixed(2);
    }

    var ACUMULACION  = @json($acumulacion);
    var CON_HISTORIAL = @json($conHistorial);

    membresiaSel.addEventListener('change', actualizar);
    descuentoSel.addEventListener('change', actualizar);
    clienteSel.addEventListener('change', actualizar);
    actualizar();

    // ── Pago en 2 Cuotas (Etapa 6) ───────────────────────────────────────────
    var chkCuotas      = document.getElementById('pagoEnCuotas');
    var seccionCuotas  = document.getElementById('seccionCuotas');
    var montoPagadoInp = document.getElementById('monto_pagado');
    var fechaLimiteInp = document.getElementById('fecha_limite_saldo');
    var saldoInfo      = document.getElementById('saldo_info');

    function precioActual() {
        return parseFloat(montoInfo.value) || 0;
    }

    // Solo VISTA PREVIA (igual que el resto del formulario): el saldo real
    // lo calcula el servidor con el precio final que él mismo recalcula.
    function actualizarSaldoCuotas() {
        var precio = precioActual();
        var pagado = parseFloat(montoPagadoInp.value) || 0;
        var saldo  = precio - pagado;
        saldoInfo.value = saldo > 0 ? saldo.toFixed(2) : '0.00';
    }

    function limitesFechaSaldo() {
        var manana = new Date();
        manana.setDate(manana.getDate() + 1);
        var limite = new Date();
        limite.setMonth(limite.getMonth() + 3);
        // Mismo margen que valida el servidor (posterior a hoy, hasta 3 meses)
        fechaLimiteInp.min = manana.toISOString().slice(0, 10);
        fechaLimiteInp.max = limite.toISOString().slice(0, 10);
    }

    function toggleCuotas() {
        var activo = chkCuotas.checked;
        seccionCuotas.style.display = activo ? 'block' : 'none';
        montoPagadoInp.disabled = !activo;
        fechaLimiteInp.disabled = !activo;

        if (activo) {
            montoPagadoInp.max = precioActual();
            limitesFechaSaldo();
            actualizarSaldoCuotas();
        } else {
            montoPagadoInp.value = '';
            fechaLimiteInp.value = '';
            saldoInfo.value = '—';
        }
    }

    chkCuotas.addEventListener('change', toggleCuotas);
    montoPagadoInp.addEventListener('input', actualizarSaldoCuotas);
    membresiaSel.addEventListener('change', function () {
        if (chkCuotas.checked) { montoPagadoInp.max = precioActual(); actualizarSaldoCuotas(); }
    });
    descuentoSel.addEventListener('change', function () {
        if (chkCuotas.checked) { montoPagadoInp.max = precioActual(); actualizarSaldoCuotas(); }
    });

    // ── Aviso de ACUMULACIÓN antes de guardar ───────────────────────────────
    // Mapa (del servidor) de clientes con membresía vigente: hasta cuándo llega
    // y qué fin tendría la nueva por tipo de membresía. Solo es un aviso: el
    // servidor recalcula y decide igual, aunque se salte este diálogo.
    var form       = document.getElementById('formPago');
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
