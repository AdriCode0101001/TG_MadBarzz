@extends('layouts.private')

@section('contenido')
<div class="col-12">

    <x-page-header title="Pagos" subtitle="Registro de pagos de membresías" icon="fa-solid fa-money-bill-wave">
        <a href="{{ route('pagos.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-circle-plus"></i> Nuevo pago
        </a>
    </x-page-header>

    <div class="card">
        <div class="card-header">
            <form method="GET" action="{{ route('pagos.index') }}" class="mb-searchbar w-100" style="max-width:480px">
                <i class="fa-solid fa-magnifying-glass mb-search-icon"></i>
                <input type="text" name="buscar" class="form-control"
                       placeholder="Buscar por cliente, membresía o fecha…"
                       value="{{ $buscar ?? request('buscar') }}">
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive" style="overflow-x:auto; -webkit-overflow-scrolling:touch;">
                <table class="table table-hover align-middle" style="min-width:860px;">
                    <thead>
                        <tr>
                            <th style="min-width:50px;">#</th>
                            <th style="min-width:180px;">Cliente</th>
                            <th style="min-width:120px;">Membresía</th>
                            <th style="min-width:100px; white-space:nowrap;">Monto</th>
                            <th style="min-width:100px; white-space:nowrap;">Fecha</th>
                            <th style="min-width:90px;">Método</th>
                            <th style="min-width:160px;">Observaciones</th>
                            <th class="text-center" style="min-width:70px;">QR</th>
                            <th class="text-end" style="min-width:90px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pagos as $pago)
                            @php
                                $nombre = trim(($pago->cliente->persona->nombre_completo ?? '') . ' ' . ($pago->cliente->persona->apellido_paterno ?? '') . ' ' . ($pago->cliente->persona->apellido_materno ?? ''));
                            @endphp
                            <tr>
                                <td class="text-muted">{{ $pago->id_pago }}</td>
                                <td>{{ $nombre ?: '—' }}</td>
                                <td>{{ $pago->membresia->tipo_membresia ?? '—' }}</td>
                                <td><span class="badge bg-success">{{ number_format($pago->monto, 2) }} Bs</span></td>
                                <td style="white-space:nowrap;">{{ \Carbon\Carbon::parse($pago->fecha_pago)->format('d/m/Y') }}</td>
                                <td><span class="badge bg-secondary">{{ ucfirst($pago->metodo_pago) }}</span></td>
                                <td class="text-truncate" style="max-width:160px">{{ $pago->observaciones }}</td>
                                <td class="text-center">
                                    @if($pago->imagen_qr)
                                        {{-- Comprobante servido por ruta protegida (almacenamiento privado) --}}
                                        <a href="{{ route('pagos.comprobante', $pago->id_pago) }}" target="_blank" title="Ver comprobante">
                                            <img src="{{ route('pagos.comprobante', $pago->id_pago) }}" alt="QR" width="48" height="48" style="object-fit:cover;border-radius:8px">
                                        </a>
                                    @else
                                        <span class="text-muted small">Sin QR</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="mb-actions justify-content-end">
                                        <a href="{{ route('pagos.show', $pago) }}" class="btn btn-sm btn-info text-white" title="Ver detalle y comprobante">
                                            <i class="fa-solid fa-eye"></i>
                                        </a>
                                        @unless (Auth::user()->hasRole('Entrenador'))
                                            <a href="{{ route('pagos.edit', $pago) }}" class="btn btn-sm btn-warning" title="Editar">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </a>
                                            <form action="{{ route('pagos.destroy', $pago) }}" method="POST"
                                                  data-confirm="¿Eliminar este pago de {{ $nombre ?: 'cliente' }}?">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-danger" title="Eliminar">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </form>
                                        @endunless
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9">
                                    <div class="mb-empty">
                                        <div class="mb-empty-icon"><i class="fa-solid fa-receipt"></i></div>
                                        <p class="mb-2">No hay pagos registrados.</p>
                                        <a href="{{ route('pagos.create') }}" class="btn btn-primary btn-sm">
                                            <i class="fa-solid fa-circle-plus"></i> Registrar pago
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if (method_exists($pagos, 'links'))
            {{-- pagination::bootstrap-5: ver fix/motivo en ejercicios/index.blade.php --}}
            <div class="card-footer d-flex justify-content-end">{{ $pagos->withQueryString()->links('pagination::bootstrap-5') }}</div>
        @endif
    </div>
</div>
@endsection
