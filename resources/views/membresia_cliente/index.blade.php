@extends('layouts.private')
@section('contenido')

<x-page-header title="Membresías de Clientes" subtitle="Estado de los planes asignados a los miembros" icon="fa-solid fa-id-badge">
    <form method="GET" action="{{ route('membresia_cliente.index') }}" class="mb-searchbar" style="max-width:320px">
        <i class="fa-solid fa-magnifying-glass mb-search-icon"></i>
        <input type="text" name="buscar" class="form-control" placeholder="Buscar cliente, CI o plan…" value="{{ request('buscar') }}">
    </form>
    {{-- Para darle una membresía a un cliente, la única vía es Registrar Pago
         (crea la asignación y su pago vinculado en un solo paso, activa de inmediato) --}}
    <a href="{{ route('pagos.create') }}" class="btn btn-primary">
        <i class="fa-solid fa-money-bill-wave me-1"></i> Registrar pago (nueva membresía)
    </a>
</x-page-header>

@if(session('success'))
    <div class="alert alert-success border-0 rounded-3 mb-4">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger border-0 rounded-3 mb-4">{{ session('error') }}</div>
@endif

<div class="card custom-card">
    <div class="card-body p-0">
        <div class="table-responsive" style="overflow-x:auto; -webkit-overflow-scrolling:touch;">
            <table class="table table-hover align-middle mb-0" style="min-width:780px;">
                <thead>
                    <tr>
                        <th style="min-width:200px;">Cliente</th>
                        <th style="min-width:130px;">Plan</th>
                        <th style="min-width:100px; white-space:nowrap;">Inicio</th>
                        <th style="min-width:110px; white-space:nowrap;">Vencimiento</th>
                        <th style="min-width:160px;">Descuento</th>
                        <th style="min-width:110px; white-space:nowrap;">Precio final</th>
                        <th class="text-center" style="min-width:90px;">Estado</th>
                        <th class="text-end" style="min-width:90px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($membresiasClientes as $mc)
                        @php
                            // suspendida_saldo (Pago en 2 Cuotas, Etapa 4) es un caso propio,
                            // distinto de "nunca pagó nada" — se separa del resto para no
                            // mezclarlo bajo la misma etiqueta "Pendiente de pago".
                            $suspendidaSaldo = $mc->estado === 'suspendida_saldo';
                            $pendiente = !$suspendidaSaldo && $mc->estado !== 'activa';
                            $vencida   = !$pendiente && !$suspendidaSaldo && \Carbon\Carbon::parse($mc->fecha_fin)->isPast();
                            $nombre    = trim(($mc->cliente->persona->nombre_completo ?? '') . ' ' . ($mc->cliente->persona->apellido_paterno ?? '') . ' ' . ($mc->cliente->persona->apellido_materno ?? ''));
                            $inicial   = strtoupper(mb_substr($nombre ?: 'C', 0, 1));
                        @endphp
                        <tr>
                            <td>
                                <div class="mb-user-cell">
                                    <span class="mb-avatar">{{ $inicial }}</span>
                                    <div>
                                        <div class="fw-semibold" style="font-size:.88rem">{{ $nombre ?: '—' }}</div>
                                        <small class="text-muted">{{ $mc->cliente->persona->ci ?? '' }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-primary-subtle text-primary fw-semibold">
                                    <i class="fa-solid fa-ticket me-1"></i>{{ $mc->membresia->tipo_membresia ?? '—' }}
                                </span>
                            </td>
                            <td style="white-space:nowrap;">{{ \Carbon\Carbon::parse($mc->fecha_inicio)->format('d/m/Y') }}</td>
                            <td style="white-space:nowrap;">{{ \Carbon\Carbon::parse($mc->fecha_fin)->format('d/m/Y') }}</td>
                            <td>
                                @if($mc->descuento > 0)
                                    <span class="badge bg-success-subtle text-success">
                                        {{ $mc->nombre_descuento }} — {{ $mc->descuento }}%
                                    </span>
                                @else
                                    <span class="text-muted small">Sin descuento</span>
                                @endif
                            </td>
                            <td class="fw-bold" style="white-space:nowrap;">Bs. {{ number_format($mc->precio_final ?? 0, 2) }}</td>
                            <td class="text-center">
                                <span class="badge {{ $suspendidaSaldo ? 'bg-danger' : ($pendiente ? 'bg-warning text-dark' : ($vencida ? 'bg-danger' : 'bg-success')) }} mb-badge-dot">
                                    {{ $suspendidaSaldo ? 'Suspendida (saldo)' : ($pendiente ? 'Pendiente de pago' : ($vencida ? 'Vencida' : 'Activa')) }}
                                </span>
                                {{-- Pago en 2 Cuotas (Etapa 6): indicador de saldo, sea cual sea
                                     el estado actual (activa con saldo, o ya suspendida). --}}
                                @if($mc->tieneSaldoPendiente())
                                    <div class="text-muted" style="font-size:.7rem;margin-top:.2rem;white-space:nowrap;">
                                        <i class="fa-solid fa-circle-exclamation me-1" style="color:#f59e0b;"></i>Debe Bs {{ number_format($mc->saldo_pendiente, 2) }}
                                        <br>vence {{ $mc->fecha_limite_saldo?->format('d/m/Y') }}
                                    </div>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="mb-actions justify-content-end">
                                    @if($mc->tieneSaldoPendiente())
                                        <button type="button" class="btn btn-sm btn-success mb-btn-pagar-saldo"
                                                data-url="{{ route('pagos.saldo', $mc->id) }}"
                                                data-monto="{{ number_format($mc->saldo_pendiente, 2) }}"
                                                data-cliente="{{ $nombre }}"
                                                title="Pagar saldo (Bs {{ number_format($mc->saldo_pendiente, 2) }})">
                                            <i class="fa-solid fa-money-bill-wave"></i>
                                        </button>
                                    @endif
                                    <a href="{{ route('membresia_cliente.edit', $mc->id) }}" class="btn btn-sm btn-warning" title="Editar">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                    <form action="{{ route('membresia_cliente.destroy', $mc->id) }}" method="POST"
                                          data-confirm="¿Eliminar esta membresía de {{ $nombre }}?">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-danger" title="Eliminar">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="mb-empty">
                                    <div class="mb-empty-icon"><i class="fa-solid fa-id-badge"></i></div>
                                    <p class="mb-2">No hay membresías asignadas.</p>
                                    <a href="{{ route('pagos.create') }}" class="btn btn-primary btn-sm">
                                        <i class="fa-solid fa-money-bill-wave me-1"></i> Registrar el primer pago
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if(method_exists($membresiasClientes, 'links'))
        {{-- pagination::bootstrap-5: ver fix/motivo en ejercicios/index.blade.php --}}
        <div class="card-footer d-flex justify-content-end">{{ $membresiasClientes->withQueryString()->links('pagination::bootstrap-5') }}</div>
    @endif
</div>

{{-- Pago en 2 Cuotas (Etapa 6): un solo modal compartido para todas las
     filas — cada botón "Pagar saldo" solo rellena su action/monto/cliente
     antes de mostrarlo. Mismos tokens de tema (--mbf-*) que modalAcumular
     en pagos/create.blade.php, así se ve bien en claro y oscuro. --}}
<div id="modalPagarSaldo" style="display:none; position:fixed; inset:0; z-index:1080; background:rgba(0,0,0,.5); align-items:center; justify-content:center; padding:16px;">
    <div style="background:var(--mbf-surface,#fff); color:var(--mbf-text,#18181b); max-width:420px; width:100%; border:1px solid var(--mbf-border,rgba(128,128,128,.2)); border-radius:12px; overflow:hidden; box-shadow:var(--mbf-shadow,0 10px 40px rgba(0,0,0,.3));">
        <div style="padding:18px 22px; border-bottom:1px solid var(--mbf-border,rgba(128,128,128,.2));">
            <h5 class="mb-0 fw-bold" style="color:var(--mbf-text,#18181b);"><i class="fa-solid fa-money-bill-wave me-2 text-success"></i>Pagar saldo</h5>
        </div>
        <form id="formPagarSaldo" method="POST" enctype="multipart/form-data">
            @csrf
            <div style="padding:22px; color:var(--mbf-text,#18181b);">
                <p class="mb-3">
                    Vas a registrar el pago del saldo de <strong id="pagarSaldoCliente"></strong>
                    por <strong id="pagarSaldoMonto"></strong>.
                </p>
                <label for="pagarSaldoMetodo" class="form-label fw-bold">Método de pago</label>
                <select name="metodo_pago" id="pagarSaldoMetodo" class="form-control" required>
                    <option value="efectivo">Efectivo</option>
                    <option value="QR">QR</option>
                </select>
                <div id="pagarSaldoQrSection" class="mt-3" style="display:none;">
                    <label for="pagarSaldoImagen" class="form-label fw-bold">Comprobante</label>
                    <input type="file" name="imagen_qr" id="pagarSaldoImagen" class="form-control" accept="image/*">
                </div>
            </div>
            <div style="padding:16px 22px; border-top:1px solid var(--mbf-border,rgba(128,128,128,.2)); display:flex; gap:10px; justify-content:flex-end;">
                <button type="button" id="pagarSaldoCancelar" class="btn btn-secondary">Cancelar</button>
                <button type="submit" class="btn btn-success">
                    <i class="fa-solid fa-check me-2"></i>Confirmar pago
                </button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    var modal = document.getElementById('modalPagarSaldo');
    if (!modal) return;

    var form         = document.getElementById('formPagarSaldo');
    var clienteSpan  = document.getElementById('pagarSaldoCliente');
    var montoSpan    = document.getElementById('pagarSaldoMonto');
    var metodoSel    = document.getElementById('pagarSaldoMetodo');
    var qrSection    = document.getElementById('pagarSaldoQrSection');
    var imagenInp    = document.getElementById('pagarSaldoImagen');

    document.querySelectorAll('.mb-btn-pagar-saldo').forEach(function (btn) {
        btn.addEventListener('click', function () {
            form.action = btn.dataset.url;
            clienteSpan.textContent = btn.dataset.cliente;
            montoSpan.textContent = 'Bs ' + btn.dataset.monto;
            modal.style.display = 'flex';
        });
    });

    document.getElementById('pagarSaldoCancelar').addEventListener('click', function () {
        modal.style.display = 'none';
    });

    function aplicarMetodo(valor) {
        if (valor === 'QR') {
            qrSection.style.display = 'block';
            imagenInp.setAttribute('required', 'required');
        } else {
            qrSection.style.display = 'none';
            imagenInp.removeAttribute('required');
            imagenInp.value = '';
        }
    }

    metodoSel.addEventListener('change', function () { aplicarMetodo(this.value); });
})();
</script>

@endsection
