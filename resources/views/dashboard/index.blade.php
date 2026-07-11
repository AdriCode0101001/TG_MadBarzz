@extends('layouts.private')

@php $__isAdminDash = Auth::user()->rol->nombre === 'Administrador'; @endphp
{{-- Marca el dashboard del ADMIN para el realce visual del modo claro (mb-skin-filament.css) --}}
@section('body-class', $__isAdminDash ? 'mb-dash' : '')

@section('contenido')

@php
    $norm     = fn($g) => match(true) { str_starts_with(strtolower(trim((string)$g)),'m') => 'masculino', str_starts_with(strtolower(trim((string)$g)),'f') => 'femenino', default => 'otro' };
    $esActivo = fn($e) => strtolower((string)$e) === 'activo';
    $nombre   = fn($p) => trim(($p->nombre_completo ?? '') . ' ' . ($p->apellido_paterno ?? '') . ' ' . ($p->apellido_materno ?? '')) ?: '—';

    $cliActivos   = $clientes->filter(fn($c) => $esActivo($c->estado))->count();
    $cliInactivos = $clientes->count() - $cliActivos;
    $entActivos   = $entrenadores->filter(fn($e) => $esActivo($e->estado))->count();
    $entInactivos = $entrenadores->count() - $entActivos;

    $genM = $clientes->filter(fn($c) => $norm(optional($c->persona)->genero) === 'masculino')->count();
    $genF = $clientes->filter(fn($c) => $norm(optional($c->persona)->genero) === 'femenino')->count();
    $genO = $clientes->count() - $genM - $genF;

    $totalCli   = $clientes->count();
    $pctActivos = $totalCli > 0 ? round($cliActivos / $totalCli * 100) : 0;
    $hora       = now()->hour;
    $saludo     = $hora < 12 ? 'Buenos días' : ($hora < 19 ? 'Buenas tardes' : 'Buenas noches');
    $adminNombre = Auth::user()?->nombreCompleto() ?? Auth::user()?->name;
    $isAdmin     = Auth::user()->rol->nombre === 'Administrador';
@endphp

{{-- ══ HERO ══ --}}
<div class="col-12 mb-4">
    <div class="mb-hero-admin">
        <div class="mb-hero-admin__bg"></div>
        <div class="mb-hero-admin__orb mb-hero-admin__orb--1"></div>
        <div class="mb-hero-admin__orb mb-hero-admin__orb--2"></div>
        <div class="mb-hero-admin__orb mb-hero-admin__orb--3"></div>

        <div class="mb-hero-admin__content">
            <div class="mb-hero-admin__left">
                <div class="mb-hero-admin__eyebrow">
                    <i class="fa-solid fa-bolt me-1"></i> Panel de Control
                </div>
                <h2 class="mb-hero-admin__title">{{ $saludo }}, {{ $adminNombre }} 👋</h2>
                <p class="mb-hero-admin__sub">
                    {{ now()->translatedFormat('l, d \d\e F Y') }} · Resumen general de Calistenia MadBarzz
                </p>
                <div class="mb-hero-admin__actions">
                    <a href="{{ route('clientes.create') }}" class="mb-hero-btn">
                        <i class="fa-solid fa-user-plus"></i> Nuevo cliente
                    </a>
                    <a href="{{ route('pagos.create') }}" class="mb-hero-btn mb-hero-btn--ghost">
                        <i class="fa-solid fa-money-bill-wave"></i> Registrar pago
                    </a>
                </div>
            </div>
            <div class="mb-hero-admin__stats">
                <div class="mb-hero-stat">
                    <div class="mb-hero-stat__num">{{ $totalCli }}</div>
                    <div class="mb-hero-stat__lbl">Clientes</div>
                </div>
                <div class="mb-hero-stat__div"></div>
                <div class="mb-hero-stat">
                    <div class="mb-hero-stat__num">{{ $entrenadores->count() }}</div>
                    <div class="mb-hero-stat__lbl">Entrenadores</div>
                </div>
                <div class="mb-hero-stat__div"></div>
                <div class="mb-hero-stat">
                    <div class="mb-hero-stat__num">{{ $pctActivos }}<span style="font-size:1rem;">%</span></div>
                    <div class="mb-hero-stat__lbl">Activos</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ══ AVISO: QR de pago por vencer o vencido (solo Administrador) ══ --}}
@if($isAdmin && (!$qrPago || $qrPago->requiereAviso()))
<div class="col-12 mb-4">
    <div class="alert {{ (!$qrPago || $qrPago->estaVencido()) ? 'alert-danger' : 'alert-warning' }} border-0 rounded-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
        <span>
            <i class="fa-solid fa-triangle-exclamation me-2"></i>
            @if(!$qrPago)
                No has configurado ningún QR de pago. Los cobros por QR no podrán mostrarlo a los clientes.
            @elseif($qrPago->estaVencido())
                El QR de pago está <strong>VENCIDO</strong> desde el {{ $qrPago->fecha_vencimiento->format('d/m/Y') }}.
            @else
                El QR de pago vence en <strong>{{ $qrPago->diasParaVencer() }} día(s)</strong> ({{ $qrPago->fecha_vencimiento->format('d/m/Y') }}).
            @endif
        </span>
        <a href="{{ route('qr_pago.edit') }}" class="btn btn-sm btn-primary">
            <i class="fa-solid fa-qrcode me-1"></i> {{ $qrPago ? 'Actualizar QR' : 'Configurar QR' }}
        </a>
    </div>
</div>
@endif

{{-- ══ KPIs ══ --}}
@php $kpiCol = $isAdmin ? 'col-xl-3 col-sm-6' : 'col-xl-6 col-sm-6'; @endphp
<div class="{{ $kpiCol }} mb-4">
    <div class="mb-kpi2" style="--kpi-from:#6366f1;--kpi-to:#8b5cf6;">
        <div class="mb-kpi2__icon"><i class="fa-solid fa-user-check"></i></div>
        <div class="mb-kpi2__body">
            <div class="mb-kpi2__num">{{ $cliActivos }}</div>
            <div class="mb-kpi2__lbl">Clientes activos</div>
        </div>
        <div class="mb-kpi2__bar" style="width:{{ $pctActivos }}%"></div>
    </div>
</div>
<div class="{{ $kpiCol }} mb-4">
    <div class="mb-kpi2" style="--kpi-from:#f5365c;--kpi-to:#c9123a;">
        <div class="mb-kpi2__icon"><i class="fa-solid fa-user-xmark"></i></div>
        <div class="mb-kpi2__body">
            <div class="mb-kpi2__num">{{ $cliInactivos }}</div>
            <div class="mb-kpi2__lbl">Clientes inactivos</div>
        </div>
        <div class="mb-kpi2__bar" style="width:{{ $totalCli > 0 ? round($cliInactivos/$totalCli*100) : 0 }}%"></div>
    </div>
</div>
@if($isAdmin)
<div class="col-xl-3 col-sm-6 mb-4">
    <div class="mb-kpi2" style="--kpi-from:#1ec98b;--kpi-to:#0ea374;">
        <div class="mb-kpi2__icon"><i class="fa-solid fa-dumbbell"></i></div>
        <div class="mb-kpi2__body">
            <div class="mb-kpi2__num">{{ $entActivos }}</div>
            <div class="mb-kpi2__lbl">Entrenadores activos</div>
        </div>
        <div class="mb-kpi2__bar" style="width:{{ $entrenadores->count() > 0 ? round($entActivos/$entrenadores->count()*100) : 0 }}%"></div>
    </div>
</div>
<div class="col-xl-3 col-sm-6 mb-4">
    <div class="mb-kpi2" style="--kpi-from:#f7b500;--kpi-to:#d99a00;">
        <div class="mb-kpi2__icon"><i class="fa-solid fa-user-slash"></i></div>
        <div class="mb-kpi2__body">
            <div class="mb-kpi2__num">{{ $entInactivos }}</div>
            <div class="mb-kpi2__lbl">Entrenadores inactivos</div>
        </div>
        <div class="mb-kpi2__bar" style="width:{{ $entrenadores->count() > 0 ? round($entInactivos/$entrenadores->count()*100) : 0 }}%"></div>
    </div>
</div>
@endif

{{-- ══ CHARTS ══ --}}
@php $chartCol = $isAdmin ? 'col-xl-4' : 'col-xl-6'; @endphp
<div class="{{ $chartCol }} mb-4">
    <div class="card h-100">
        <div class="card-header d-flex align-items-center gap-2">
            <span class="mb-section-ico" style="background:rgba(99,102,241,.12);color:#6366f1">
                <i class="fa-solid fa-venus-mars"></i>
            </span>
            <h5 class="mb-0">Distribución por género</h5>
        </div>
        <div class="card-body d-flex flex-column align-items-center">
            <div style="position:relative;height:190px;width:190px;">
                <canvas id="mbGeneroChart"></canvas>
                <div class="mb-donut-center">
                    <span class="mb-donut-num">{{ $totalCli }}</span>
                    <span class="mb-donut-sub">clientes</span>
                </div>
            </div>
            <div class="mb-legend mt-3 w-100">
                <div class="mb-legend-item"><span class="mb-legend-dot" style="background:#6366f1"></span><span>Masculino</span><strong>{{ $genM }}</strong></div>
                <div class="mb-legend-item"><span class="mb-legend-dot" style="background:#1ec98b"></span><span>Femenino</span><strong>{{ $genF }}</strong></div>
                <div class="mb-legend-item"><span class="mb-legend-dot" style="background:#f59e0b"></span><span>Otro</span><strong>{{ $genO }}</strong></div>
            </div>
            <div class="d-flex flex-wrap gap-2 mt-3 w-100">
                <button class="btn btn-sm btn-primary mb-genfilter active" data-genero="todos">Todos</button>
                <button class="btn btn-sm btn-outline-secondary mb-genfilter" data-genero="masculino">Masculino</button>
                <button class="btn btn-sm btn-outline-secondary mb-genfilter" data-genero="femenino">Femenino</button>
                <button class="btn btn-sm btn-outline-secondary mb-genfilter" data-genero="otro">Otro</button>
            </div>
        </div>
    </div>
</div>

@if($isAdmin)
<div class="col-xl-4 mb-4">
    <div class="card h-100">
        <div class="card-header d-flex align-items-center gap-2">
            <span class="mb-section-ico" style="background:rgba(30,201,139,.12);color:#1ec98b">
                <i class="fa-solid fa-dumbbell"></i>
            </span>
            <h5 class="mb-0">Estado de entrenadores</h5>
        </div>
        <div class="card-body d-flex flex-column align-items-center">
            <div style="position:relative;height:190px;width:190px;">
                <canvas id="mbEntChart"></canvas>
                <div class="mb-donut-center">
                    <span class="mb-donut-num">{{ $entrenadores->count() }}</span>
                    <span class="mb-donut-sub">total</span>
                </div>
            </div>
            <div class="mb-legend mt-3 w-100">
                <div class="mb-legend-item"><span class="mb-legend-dot" style="background:#1ec98b"></span><span>Activos</span><strong>{{ $entActivos }}</strong></div>
                <div class="mb-legend-item"><span class="mb-legend-dot" style="background:#f5365c"></span><span>Inactivos</span><strong>{{ $entInactivos }}</strong></div>
            </div>
            <div class="mt-3 w-100">
                <div class="d-flex justify-content-between mb-1" style="font-size:.8rem;opacity:.7">
                    <span>Actividad</span>
                    <span>{{ $entrenadores->count() > 0 ? round($entActivos/$entrenadores->count()*100) : 0 }}%</span>
                </div>
                <div class="mb-progress-bar">
                    <div class="mb-progress-fill" style="width:{{ $entrenadores->count() > 0 ? round($entActivos/$entrenadores->count()*100) : 0 }}%;background:linear-gradient(90deg,#1ec98b,#0ea374)"></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<div class="{{ $chartCol }} mb-4">
    <div class="card h-100">
        <div class="card-header d-flex align-items-center gap-2">
            <span class="mb-section-ico" style="background:rgba(99,102,241,.12);color:#6366f1">
                <i class="fa-solid fa-chart-pie"></i>
            </span>
            <h5 class="mb-0">Clientes activos</h5>
        </div>
        <div class="card-body d-flex flex-column justify-content-between">
            <div class="mb-big-metric">
                <div class="mb-big-num" style="background:linear-gradient(135deg,#6366f1,#8b5cf6);-webkit-background-clip:text;-webkit-text-fill-color:transparent;">
                    {{ $pctActivos }}<span class="mb-big-unit">%</span>
                </div>
                <p class="mb-big-sub">de clientes están activos hoy</p>
            </div>
            <div>
                <div class="d-flex justify-content-between mb-1" style="font-size:.8rem;opacity:.7">
                    <span>Activos ({{ $cliActivos }})</span>
                    <span>Inactivos ({{ $cliInactivos }})</span>
                </div>
                <div class="mb-progress-bar mb-3">
                    <div class="mb-progress-fill" style="width:{{ $pctActivos }}%;background:linear-gradient(90deg,#6366f1,#8b5cf6)"></div>
                </div>
                <div class="mb-mini-stats">
                    <div class="mb-mini-stat"><i class="fa-solid fa-circle" style="color:#6366f1;font-size:.55rem"></i> Activos <b>{{ $cliActivos }}</b></div>
                    <div class="mb-mini-stat"><i class="fa-solid fa-circle" style="color:#f5365c;font-size:.55rem"></i> Inactivos <b>{{ $cliInactivos }}</b></div>
                    <div class="mb-mini-stat"><i class="fa-solid fa-circle" style="color:#94a3b8;font-size:.55rem"></i> Total <b>{{ $totalCli }}</b></div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ══ TABLA CLIENTES ══ --}}
<div class="{{ $isAdmin ? 'col-xl-8' : 'col-12' }} mb-4">
    <div class="card h-100">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="mb-section-ico" style="background:rgba(99,102,241,.12);color:#6366f1">
                    <i class="fa-solid fa-users"></i>
                </span>
                <h5 class="mb-0">Clientes <span class="text-muted fw-normal" style="font-size:.8rem;">({{ $totalCli }})</span></h5>
            </div>
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-primary mb-estfilter active" data-tabla="cli" data-estado="todos">Todos</button>
                    <button class="btn btn-outline-secondary mb-estfilter" data-tabla="cli" data-estado="activo">Activos</button>
                    <button class="btn btn-outline-secondary mb-estfilter" data-tabla="cli" data-estado="inactivo">Inactivos</button>
                </div>
                <div class="mb-searchbar" style="min-width:160px">
                    <i class="fa-solid fa-magnifying-glass mb-search-icon"></i>
                    <input type="text" class="form-control form-control-sm" id="mbCliSearch" placeholder="Buscar…">
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height:370px;overflow-y:auto;">
                <table class="table table-hover align-middle mb-0" id="mbCliTable">
                    <thead class="mb-sticky-head" style="position:sticky;top:0;z-index:2;">
                        <tr>
                            <th>Cliente</th>
                            <th>CI</th>
                            <th>Teléfono</th>
                            <th>Género</th>
                            <th class="text-center">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($clientes as $c)
                            @php $gen = $norm(optional($c->persona)->genero); $act = $esActivo($c->estado); @endphp
                            <tr data-estado="{{ $act ? 'activo' : 'inactivo' }}"
                                data-genero="{{ $gen }}"
                                data-buscar="{{ strtolower($nombre($c->persona) . ' ' . (optional($c->persona)->ci ?? '') . ' ' . (optional($c->user)->email ?? '')) }}">
                                <td>
                                    <div class="mb-user-cell">
                                        <span class="mb-avatar">{{ strtoupper(mb_substr($nombre($c->persona), 0, 1)) }}</span>
                                        <div>
                                            <div class="fw-semibold" style="font-size:.88rem">{{ $nombre($c->persona) }}</div>
                                            <small>{{ optional($c->user)->email ?? '' }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ optional($c->persona)->ci ?? '—' }}</td>
                                <td>{{ optional($c->persona)->telefono ?? '—' }}</td>
                                <td><span class="text-capitalize">{{ $gen }}</span></td>
                                <td class="text-center">
                                    <span class="badge {{ $act ? 'bg-success' : 'bg-danger' }} mb-badge-dot">
                                        {{ $act ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5">
                                <div class="mb-empty"><div class="mb-empty-icon"><i class="fa-solid fa-user-slash"></i></div>No hay clientes registrados.</div>
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-3 text-muted small text-center" id="mbCliEmpty" style="display:none;">Sin resultados.</div>
        </div>
        <div class="card-footer d-flex justify-content-end">
            <a href="{{ route('clientes.index') }}" class="btn btn-sm btn-outline-primary">
                Ver todos los clientes <i class="fa-solid fa-arrow-right ms-1"></i>
            </a>
        </div>
    </div>
</div>

{{-- ══ TABLA ENTRENADORES (solo admin) ══ --}}
@if($isAdmin)
<div class="col-xl-4 mb-4">
    <div class="card h-100">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="mb-section-ico" style="background:rgba(30,201,139,.12);color:#1ec98b">
                    <i class="fa-solid fa-dumbbell"></i>
                </span>
                <h5 class="mb-0">Entrenadores <span class="text-muted fw-normal" style="font-size:.8rem;">({{ $entrenadores->count() }})</span></h5>
            </div>
            <div class="btn-group btn-group-sm">
                <button class="btn btn-primary mb-estfilter active" data-tabla="ent" data-estado="todos">Todos</button>
                <button class="btn btn-outline-secondary mb-estfilter" data-tabla="ent" data-estado="activo">Activos</button>
                <button class="btn btn-outline-secondary mb-estfilter" data-tabla="ent" data-estado="inactivo">Inactivos</button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height:370px;overflow-y:auto;">
                <table class="table table-hover align-middle mb-0" id="mbEntTable">
                    <thead class="mb-sticky-head" style="position:sticky;top:0;z-index:2;">
                        <tr>
                            <th>Entrenador</th>
                            <th>Especialidad</th>
                            <th class="text-center">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($entrenadores as $e)
                            @php $act = $esActivo($e->estado); @endphp
                            <tr data-estado="{{ $act ? 'activo' : 'inactivo' }}">
                                <td>
                                    <div class="mb-user-cell">
                                        <span class="mb-avatar" style="background:linear-gradient(135deg,#1ec98b,#0ea374)">
                                            {{ strtoupper(mb_substr($nombre($e->persona), 0, 1)) }}
                                        </span>
                                        <div class="fw-semibold" style="font-size:.88rem">{{ $nombre($e->persona) }}</div>
                                    </div>
                                </td>
                                <td style="font-size:.82rem">
                                    {{ is_array($e->especialidad) ? implode(', ', $e->especialidad) : ($e->especialidad ?? '—') }}
                                </td>
                                <td class="text-center">
                                    <span class="badge {{ $act ? 'bg-success' : 'bg-danger' }} mb-badge-dot">
                                        {{ $act ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3">
                                <div class="mb-empty"><div class="mb-empty-icon"><i class="fa-solid fa-dumbbell"></i></div>No hay entrenadores.</div>
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-end">
            <a href="{{ route('entrenadores.index') }}" class="btn btn-sm btn-outline-primary">
                Ver todos <i class="fa-solid fa-arrow-right ms-1"></i>
            </a>
        </div>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    var elG = document.getElementById('mbGeneroChart');
    if (elG && typeof Chart !== 'undefined') {
        new Chart(elG, {
            type: 'doughnut',
            data: {
                labels: ['Masculino','Femenino','Otro'],
                datasets: [{ data: [{{ $genM }},{{ $genF }},{{ $genO }}],
                    backgroundColor: ['#6366f1','#1ec98b','#f59e0b'],
                    borderWidth: 3, borderColor: 'transparent', hoverOffset: 10 }]
            },
            options: { cutout:'70%', plugins:{legend:{display:false}}, responsive:true, maintainAspectRatio:false }
        });
    }

    var elE = document.getElementById('mbEntChart');
    if (elE && typeof Chart !== 'undefined') {
        new Chart(elE, {
            type: 'doughnut',
            data: {
                labels: ['Activos','Inactivos'],
                datasets: [{ data: [{{ $entActivos }},{{ $entInactivos }}],
                    backgroundColor: ['#1ec98b','#f5365c'],
                    borderWidth: 3, borderColor: 'transparent', hoverOffset: 10 }]
            },
            options: { cutout:'70%', plugins:{legend:{display:false}}, responsive:true, maintainAspectRatio:false }
        });
    }

    var cliEstado = 'todos', cliGenero = 'todos', cliQuery = '';
    function aplicarCli() {
        var rows = document.querySelectorAll('#mbCliTable tbody tr'), vis = 0;
        rows.forEach(function(r) {
            if (!r.dataset.estado) return;
            var ok = (cliEstado==='todos'||r.dataset.estado===cliEstado)
                  && (cliGenero==='todos'||r.dataset.genero===cliGenero)
                  && (cliQuery===''||(r.dataset.buscar||'').indexOf(cliQuery)!==-1);
            r.style.display = ok ? '' : 'none';
            if (ok) vis++;
        });
        var el = document.getElementById('mbCliEmpty');
        if (el) el.style.display = vis ? 'none' : 'block';
    }

    function activar(btns, btn) {
        btns.forEach(function(b){ b.classList.remove('active','btn-primary'); b.classList.add('btn-outline-secondary'); });
        btn.classList.add('active','btn-primary'); btn.classList.remove('btn-outline-secondary');
    }

    var genBtns = Array.from(document.querySelectorAll('.mb-genfilter'));
    genBtns.forEach(function(b){ b.addEventListener('click',function(){ cliGenero=b.dataset.genero; activar(genBtns,b); aplicarCli(); }); });

    var cliEstBtns = Array.from(document.querySelectorAll('.mb-estfilter[data-tabla="cli"]'));
    cliEstBtns.forEach(function(b){ b.addEventListener('click',function(){ cliEstado=b.dataset.estado; activar(cliEstBtns,b); aplicarCli(); }); });

    var search = document.getElementById('mbCliSearch');
    if (search) search.addEventListener('input',function(){ cliQuery=this.value.toLowerCase().trim(); aplicarCli(); });

    var entEstado = 'todos';
    function aplicarEnt() {
        document.querySelectorAll('#mbEntTable tbody tr').forEach(function(r){
            if (!r.dataset.estado) return;
            r.style.display = (entEstado==='todos'||r.dataset.estado===entEstado)?'':'none';
        });
    }
    var entBtns = Array.from(document.querySelectorAll('.mb-estfilter[data-tabla="ent"]'));
    entBtns.forEach(function(b){ b.addEventListener('click',function(){ entEstado=b.dataset.estado; activar(entBtns,b); aplicarEnt(); }); });
});
</script>
@endpush
