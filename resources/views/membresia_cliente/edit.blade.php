@extends('layouts.private')

@section('contenido')

<div class="row">
    <div class="col-xl-12">
        <div class="card custom-card">

            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="card-title fs-24 fw-bold text-dark dark-text-white">
                    <h2 class="fw-bold mb-4">Editar Membresía Cliente</h2>
                </div>
            </div>

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

                <form action="{{ route('membresia_cliente.update', $membresiaCliente->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <h4 class="dark-text-white mt-3">Datos de la Membresía Cliente</h4>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="id_cliente" class="form-label fw-bold text-dark dark-text-white">Cliente</label>
                            <select name="id_cliente" id="id_cliente" class="form-control" required>
                                <option value="">Seleccione un cliente</option>
                                @foreach($clientes as $cliente)
                                    <option value="{{ $cliente->id_cliente }}" {{ $membresiaCliente->id_cliente == $cliente->id_cliente ? 'selected' : '' }}>
                                        {{ $cliente->persona->nombre_completo ?? 'N/A' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="id_membresia" class="form-label fw-bold text-dark dark-text-white">Membresía</label>
                            <select name="id_membresia" id="id_membresia" class="form-control" required>
                                <option value="">Seleccione una membresía</option>
                                @foreach($membresias as $membresia)
                                    <option value="{{ $membresia->id_membresia }}"
                                            data-precio="{{ $membresia->precio }}"
                                            data-fecha-fin="{{ \Carbon\Carbon::parse($membresia->fecha_fin_prevista)->format('d/m/Y') }}"
                                            {{ $membresiaCliente->id_membresia == $membresia->id_membresia ? 'selected' : '' }}>
                                        {{ $membresia->tipo_membresia }} - Bs {{ number_format($membresia->precio, 2) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Fechas SOLO informativas: las calcula y guarda el servidor --}}
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark dark-text-white">Fecha Inicio (automática)</label>
                            <input type="text" id="fecha_inicio_info" class="form-control"
                                   value="{{ \Carbon\Carbon::parse($membresiaCliente->fecha_inicio)->format('d/m/Y') }}" readonly disabled>
                            <small class="text-muted">Se conserva la fecha original del registro.</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark dark-text-white">Fecha Fin (automática)</label>
                            <input type="text" id="fecha_fin_info" class="form-control" value="Elija una membresía" readonly disabled>
                            <small class="text-muted">Duración de la membresía + 1 día de regalo, desde la fecha de inicio.</small>
                        </div>

                        <div class="col-md-6">
                            <label for="id_descuento" class="form-label fw-bold text-dark dark-text-white">Descuento</label>
                            <select name="id_descuento" id="id_descuento" class="form-control" required>
                                @foreach($descuentos as $descuento)
                                    <option value="{{ $descuento->id }}"
                                            data-porcentaje="{{ $descuento->porcentaje }}"
                                            @selected($membresiaCliente->id_descuento
                                                ? $membresiaCliente->id_descuento == $descuento->id
                                                : ($descuento->porcentaje == 0 && $descuento->nombre === 'Sin descuento'))>
                                        {{ $descuento->nombre }} ({{ rtrim(rtrim(number_format($descuento->porcentaje, 2), '0'), '.') }}%)
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark dark-text-white">Precio Final (Bs)</label>
                            <input type="text" id="precio_final_info" class="form-control" value="—" readonly disabled>
                            <small class="text-muted">Precio de la membresía menos el descuento. Lo calcula el sistema.</small>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-save me-2"></i> Actualizar
                        </button>
                        <a href="{{ route('membresia_cliente.index') }}" class="btn btn-secondary ms-2">
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>

<script>
    // Solo VISTA PREVIA: el servidor recalcula fechas y precio al guardar.
    (function () {
        var membresiaSel = document.getElementById('id_membresia');
        var descuentoSel = document.getElementById('id_descuento');
        var fechaFinInfo = document.getElementById('fecha_fin_info');
        var precioInfo   = document.getElementById('precio_final_info');

        function actualizar() {
            var op = membresiaSel.options[membresiaSel.selectedIndex];

            if (!op || !op.value) {
                fechaFinInfo.value = 'Elija una membresía';
                precioInfo.value = '—';
                return;
            }

            fechaFinInfo.value = op.dataset.fechaFin;

            var precio = parseFloat(op.dataset.precio) || 0;
            var od = descuentoSel.options[descuentoSel.selectedIndex];
            var pct = od ? (parseFloat(od.dataset.porcentaje) || 0) : 0;
            precioInfo.value = (precio * (1 - pct / 100)).toFixed(2);
        }

        membresiaSel.addEventListener('change', actualizar);
        descuentoSel.addEventListener('change', actualizar);
        actualizar();
    })();
</script>

@endsection
