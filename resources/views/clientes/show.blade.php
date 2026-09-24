@extends('layouts.private')

@section('contenido')
<div class="col-12">

    @php
        $nombreCompleto = trim(($cliente->persona->nombre_completo ?? '') . ' ' . ($cliente->persona->apellido_paterno ?? '') . ' ' . ($cliente->persona->apellido_materno ?? ''));
    @endphp

    <x-page-header title="{{ $nombreCompleto ?: 'Detalle del cliente' }}" subtitle="Ficha del cliente" icon="fa-solid fa-id-card">
        <a href="{{ route('clientes.edit', $cliente->id_cliente) }}" class="btn btn-warning">
            <i class="fa-solid fa-pen-to-square"></i> Editar
        </a>
        <a href="{{ route('clientes.index') }}" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i> Volver
        </a>
    </x-page-header>

    <div class="row g-4">
        {{-- ---------- Datos personales ---------- --}}
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header"><h5 class="mb-0"><i class="fa-solid fa-id-card me-2"></i>Datos personales</h5></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5">Nombre completo</dt>
                        <dd class="col-sm-7">{{ $nombreCompleto ?: '—' }}</dd>

                        <dt class="col-sm-5">CI</dt>
                        <dd class="col-sm-7">{{ $cliente->persona->ci ?? '—' }}</dd>

                        <dt class="col-sm-5">Teléfono</dt>
                        <dd class="col-sm-7">{{ $cliente->persona->telefono ?? '—' }}</dd>

                        <dt class="col-sm-5">Género</dt>
                        <dd class="col-sm-7">{{ ucfirst($cliente->persona->genero ?? '—') }}</dd>

                        <dt class="col-sm-5">Fecha de nacimiento</dt>
                        <dd class="col-sm-7">{{ $cliente->persona?->fecha_nacimiento ? \Carbon\Carbon::parse($cliente->persona->fecha_nacimiento)->format('d/m/Y') : '—' }}</dd>

                        <dt class="col-sm-5">Usuario</dt>
                        <dd class="col-sm-7">{{ $cliente->user->name ?? '—' }}</dd>

                        <dt class="col-sm-5">Correo</dt>
                        <dd class="col-sm-7">{{ $cliente->user->email ?? '—' }}</dd>

                        <dt class="col-sm-5">Días asistidos</dt>
                        <dd class="col-sm-7">{{ $cliente->dias_asistidos }}</dd>

                        <dt class="col-sm-5">Estado</dt>
                        <dd class="col-sm-7">
                            <span class="badge {{ $cliente->estado == 'activo' ? 'bg-success' : 'bg-danger' }}">
                                {{ ucfirst($cliente->estado) }}
                            </span>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>

        {{-- ---------- Membresía vigente ---------- --}}
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header"><h5 class="mb-0"><i class="fa-solid fa-id-badge me-2"></i>Membresía vigente</h5></div>
                <div class="card-body">
                    @if ($membresia)
                        <dl class="row mb-0">
                            <dt class="col-sm-5">Tipo</dt>
                            <dd class="col-sm-7">{{ $membresia->tipo_membresia }}</dd>

                            <dt class="col-sm-5">Fecha inicio</dt>
                            <dd class="col-sm-7">{{ \Carbon\Carbon::parse($membresia->pivot->fecha_inicio)->format('d/m/Y') }}</dd>

                            <dt class="col-sm-5">Fecha fin</dt>
                            <dd class="col-sm-7">{{ \Carbon\Carbon::parse($membresia->pivot->fecha_fin)->format('d/m/Y') }}</dd>

                            <dt class="col-sm-5">Precio final</dt>
                            <dd class="col-sm-7">Bs {{ number_format($membresia->pivot->precio_final, 2) }}</dd>

                            <dt class="col-sm-5">Estado</dt>
                            <dd class="col-sm-7"><span class="badge bg-success">Activa</span></dd>
                        </dl>
                    @else
                        <p class="text-muted mb-0">Este cliente no tiene una membresía activa.</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- ---------- Últimos pagos ---------- --}}
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header"><h5 class="mb-0"><i class="fa-solid fa-money-bill-wave me-2"></i>Pagos ({{ $pagos->count() }})</h5></div>
                <div class="card-body p-0">
                    @if ($pagos->isEmpty())
                        <p class="text-muted p-3 mb-0">Sin pagos registrados.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead>
                                    <tr><th>Fecha</th><th>Monto</th><th>Método</th></tr>
                                </thead>
                                <tbody>
                                    @foreach ($pagos->sortByDesc('fecha_pago')->take(10) as $pago)
                                        <tr>
                                            <td>{{ \Carbon\Carbon::parse($pago->fecha_pago)->format('d/m/Y') }}</td>
                                            <td>Bs {{ number_format($pago->monto, 2) }}</td>
                                            <td>{{ ucfirst($pago->metodo_pago ?? '—') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ---------- Últimas asistencias ---------- --}}
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header"><h5 class="mb-0"><i class="fa-solid fa-calendar-check me-2"></i>Asistencias ({{ $asistencias->count() }})</h5></div>
                <div class="card-body p-0">
                    @if ($asistencias->isEmpty())
                        <p class="text-muted p-3 mb-0">Sin asistencias registradas.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead>
                                    <tr><th>Fecha y hora</th></tr>
                                </thead>
                                <tbody>
                                    @foreach ($asistencias->sortByDesc('fecha_hora_entrada')->take(10) as $asistencia)
                                        <tr>
                                            <td>{{ \Carbon\Carbon::parse($asistencia->fecha_hora_entrada)->format('d/m/Y H:i') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
