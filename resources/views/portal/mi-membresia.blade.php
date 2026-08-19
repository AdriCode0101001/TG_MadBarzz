@extends('layouts.private')

@section('contenido')
<div class="col-12 mb-portal">

    <x-page-header title="Mi Membresía" subtitle="Estado actual e historial de tus membresías" icon="fa-solid fa-id-badge" />

    @if($vigente)
        @php
            $fin  = \Carbon\Carbon::parse($vigente->pivot->fecha_fin);
            $dias = max(0, (int) now()->startOfDay()->diffInDays($fin->startOfDay(), false));
            $saldoVigente = (float) ($vigente->pivot->saldo_pendiente ?? 0);
        @endphp
        <div class="card mb-4">
            <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                    <span class="mb-section-ico" style="background:rgba(30,201,139,.14);color:#1ec98b;width:48px;height:48px;font-size:1.3rem;">
                        <i class="fa-solid fa-circle-check"></i>
                    </span>
                    <div>
                        <h5 class="mb-1">Membresía {{ $vigente->tipo_membresia }} <span class="badge bg-success mb-badge-dot ms-1">Vigente</span></h5>
                        <div class="text-muted" style="font-size:.85rem;">
                            Del {{ \Carbon\Carbon::parse($vigente->pivot->fecha_inicio)->format('d/m/Y') }}
                            al {{ $fin->format('d/m/Y') }}
                        </div>
                    </div>
                </div>
                <div class="text-end">
                    <div style="font-size:1.8rem;font-weight:800;line-height:1;">{{ $dias }}</div>
                    <div class="text-muted" style="font-size:.75rem;text-transform:uppercase;letter-spacing:.06em;">días restantes</div>
                </div>
            </div>
            {{-- Pago en 2 Cuotas (Etapa 6): activa, pero con saldo — no
                 bloquea el acceso todavía, solo avisa antes de que venza. --}}
            @if($saldoVigente > 0)
                <div class="alert alert-warning border-0 rounded-0 mb-0" style="border-bottom-left-radius:1rem !important;border-bottom-right-radius:1rem !important;">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i>
                    Tienes un saldo pendiente de <strong>Bs {{ number_format($saldoVigente, 2) }}</strong>,
                    vence el <strong>{{ \Carbon\Carbon::parse($vigente->pivot->fecha_limite_saldo)->format('d/m/Y') }}</strong>.
                    Acércate a pagarlo para no perder el acceso.
                </div>
            @endif
        </div>
    @elseif($masReciente && $masReciente->estaSuspendidaPorSaldo())
        {{-- Distinto del "nunca pagó": este cliente SÍ pagó una cuota, entró
             con huella con normalidad, y quedó suspendido por no pagar el
             saldo a tiempo — el mensaje tiene que dejar eso claro. --}}
        <div class="card mb-4">
            <div class="card-body">
                <div class="mb-empty py-4">
                    <div class="mb-empty-icon" style="color:#f5365c;"><i class="fa-solid fa-lock"></i></div>
                    <h5 class="mb-2">Tu membresía está suspendida</h5>
                    <p class="mb-1">
                        No se pagó el saldo pendiente de <strong>Bs {{ number_format((float) $masReciente->saldo_pendiente, 2) }}</strong>
                        antes del {{ $masReciente->fecha_limite_saldo?->format('d/m/Y') }}.
                    </p>
                    <p class="mb-0">Acércate a recepción a pagarlo para reactivarla.</p>
                </div>
            </div>
        </div>
    @else
        <div class="card mb-4">
            <div class="card-body">
                <div class="mb-empty py-4">
                    <div class="mb-empty-icon"><i class="fa-solid fa-id-badge"></i></div>
                    <h5 class="mb-2">No tienes una membresía activa</h5>
                    <p class="mb-0">Pasa por recepción para adquirir o renovar tu membresía y seguir entrenando.</p>
                </div>
            </div>
        </div>
    @endif

    <div class="card">
        <div class="card-header"><h5 class="mb-0"><i class="fa-solid fa-clock-rotate-left me-2"></i>Historial</h5></div>
        <div class="card-body p-0">
            @if($historial->count())
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Membresía</th>
                                <th>Inicio</th>
                                <th>Fin</th>
                                <th class="text-center">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($historial as $m)
                                @php
                                    $suspendidaSaldo = $m->pivot->estado === 'suspendida_saldo';
                                    $pendiente = !$suspendidaSaldo && $m->pivot->estado !== 'activa';
                                    $vigente   = !$pendiente && !$suspendidaSaldo && \Carbon\Carbon::parse($m->pivot->fecha_fin)->gte(now()->startOfDay());
                                    $saldoFila = (float) ($m->pivot->saldo_pendiente ?? 0);
                                @endphp
                                <tr>
                                    <td>{{ $m->tipo_membresia }}</td>
                                    <td>{{ \Carbon\Carbon::parse($m->pivot->fecha_inicio)->format('d/m/Y') }}</td>
                                    <td>{{ \Carbon\Carbon::parse($m->pivot->fecha_fin)->format('d/m/Y') }}</td>
                                    <td class="text-center">
                                        <span class="badge {{ $suspendidaSaldo ? 'bg-danger' : ($pendiente ? 'bg-warning text-dark' : ($vigente ? 'bg-success' : 'bg-secondary')) }} mb-badge-dot">
                                            {{ $suspendidaSaldo ? 'Suspendida (saldo)' : ($pendiente ? 'Pendiente de pago' : ($vigente ? 'Vigente' : 'Vencida')) }}
                                        </span>
                                        @if($saldoFila > 0)
                                            <div class="text-muted" style="font-size:.72rem;margin-top:.2rem;">
                                                Debe Bs {{ number_format($saldoFila, 2) }}
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="mb-empty">
                    <div class="mb-empty-icon"><i class="fa-solid fa-id-badge"></i></div>
                    <p class="mb-0">Aún no tienes membresías registradas.</p>
                </div>
            @endif
        </div>
    </div>
</div>

@push('styles')
<style>
/* La plantilla estira .card a la altura de la columna (height: calc(100% - 30px));
   con dos tarjetas apiladas en una misma columna eso infla la primera y deja el
   historial fuera de la vista sin scroll. Aquí las tarjetas usan altura natural. */
.mb-portal .card { height: auto; }
</style>
@endpush
@endsection
