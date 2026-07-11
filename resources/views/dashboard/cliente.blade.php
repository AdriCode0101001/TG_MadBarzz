@extends('layouts.private')

@section('contenido')

@php
    $mc           = $membresia ? $membresia->pivot : null;
    $diasTotal    = $mc ? \Carbon\Carbon::parse($mc->fecha_inicio)->diffInDays(\Carbon\Carbon::parse($mc->fecha_fin)) : 0;
    $diasRestantes = $mc ? max(0, (int) now()->diffInDays(\Carbon\Carbon::parse($mc->fecha_fin), false)) : 0;
    $progreso     = ($diasTotal > 0) ? min(100, round((($diasTotal - $diasRestantes) / $diasTotal) * 100)) : 0;
    $nombreCarnet = trim(
        ($cliente->persona->nombre_completo ?? '') . ' ' .
        ($cliente->persona->apellido_paterno ?? '') . ' ' .
        ($cliente->persona->apellido_materno ?? '')
    ) ?: Auth::user()?->name;
    $nombre       = $nombreCarnet;
    $hora         = now()->hour;
    $saludo       = $hora < 12 ? 'Buenos días' : ($hora < 19 ? 'Buenas tardes' : 'Buenas noches');
@endphp

<div class="mb-cli-wrap">

    {{-- Alertas --}}
    @if(session('success'))
        <div class="alert alert-success border-0 rounded-3 mb-4">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 rounded-3 mb-4">{{ session('error') }}</div>
    @endif

    {{-- ── HERO BANNER ── --}}
    <div class="mb-cli-hero mb-4">
        <div class="mb-cli-hero__text">
            <p class="mb-cli-hero__saludo">{{ $saludo }},</p>
            <h2 class="mb-cli-hero__nombre">{{ $nombre }}</h2>
            <p class="mb-cli-hero__sub">Aquí tienes el resumen de tu membresía y progreso en MadBarzz.</p>
        </div>
        <div class="mb-cli-hero__logo">
            <img src="{{ asset('assets_private/images/logo_mad.png') }}" alt="MadBarzz">
        </div>
    </div>

    {{-- ── KPIs ── --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="mb-cli-kpi">
                <div class="mb-cli-kpi__icon" style="background:rgba(99,102,241,.15);color:#6366f1;">
                    <i class="fa-solid fa-id-card"></i>
                </div>
                <div class="mb-cli-kpi__val">{{ $membresia ? $membresia->tipo_membresia : '—' }}</div>
                <div class="mb-cli-kpi__label">{{ $membresia ? 'Plan activo' : 'Plan no activo' }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="mb-cli-kpi">
                <div class="mb-cli-kpi__icon" style="background:rgba(16,185,129,.15);color:#10b981;">
                    <i class="fa-solid fa-calendar-check"></i>
                </div>
                <div class="mb-cli-kpi__val">{{ $diasRestantes }}</div>
                <div class="mb-cli-kpi__label">Días restantes</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="mb-cli-kpi">
                <div class="mb-cli-kpi__icon" style="background:rgba(245,158,11,.15);color:#f59e0b;">
                    <i class="fa-solid fa-money-bill-wave"></i>
                </div>
                <div class="mb-cli-kpi__val">{{ $pagos->count() }}</div>
                <div class="mb-cli-kpi__label">Pagos realizados</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="mb-cli-kpi">
                <div class="mb-cli-kpi__icon" style="background:rgba(59,130,246,.15);color:#3b82f6;">
                    <i class="fa-solid fa-dumbbell"></i>
                </div>
                <div class="mb-cli-kpi__val">{{ $rutinas->count() }}</div>
                <div class="mb-cli-kpi__label">Rutinas disponibles</div>
            </div>
        </div>
    </div>

    {{-- ── MEMBRESÍA + PAGOS ── --}}
    <div class="row g-4 mb-4">

        {{-- Tarjeta membresía --}}
        <div class="col-lg-7">
            <div class="mb-cli-card h-100">
                <div class="mb-cli-card__head">
                    <i class="fa-solid fa-id-card me-2"></i> Mi Membresía
                </div>
                <div class="mb-cli-card__body">
                    @if($membresia)
                        <div class="mb-cli-plan">
                            <div class="mb-cli-plan__badge">
                                <i class="fa-solid fa-circle-check me-1"></i> Activa
                            </div>
                            <div class="mb-cli-plan__name">{{ $membresia->tipo_membresia }}</div>
                            <div class="mb-cli-plan__price">
                                Bs. {{ number_format($mc->precio_final ?? $membresia->precio, 2) }}
                                @if($mc->descuento > 0)
                                    <span class="mb-cli-plan__old">Bs. {{ number_format($membresia->precio, 2) }}</span>
                                    <span class="mb-cli-plan__disc">-{{ $mc->descuento }}%</span>
                                @endif
                            </div>
                        </div>

                        <div class="row g-3 mt-1 mb-3">
                            <div class="col-6">
                                <div class="mb-cli-date-box">
                                    <div class="mb-cli-date-box__label">Fecha inicio</div>
                                    <div class="mb-cli-date-box__val">{{ \Carbon\Carbon::parse($mc->fecha_inicio)->format('d M Y') }}</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="mb-cli-date-box">
                                    <div class="mb-cli-date-box__label">Vencimiento</div>
                                    <div class="mb-cli-date-box__val">{{ \Carbon\Carbon::parse($mc->fecha_fin)->format('d M Y') }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-cli-prog__label">
                            <span>Tiempo transcurrido</span><span>{{ $progreso }}%</span>
                        </div>
                        <div class="mb-cli-prog">
                            <div class="mb-cli-prog__bar" style="width:{{ $progreso }}%"></div>
                        </div>

                        @if($mc->nombre_descuento)
                            <div class="mb-cli-disc-tag mt-3">
                                <i class="fa-solid fa-tag me-1"></i>
                                Descuento: {{ $mc->nombre_descuento }} — {{ $mc->descuento }}%
                            </div>
                        @endif
                    @else
                        <div class="mb-cli-empty">
                            <i class="fa-solid fa-id-card fa-2x mb-2 opacity-25"></i>
                            <p>No tienes una membresía activa.<br><small>Acércate a recepción para adquirir tu plan.</small></p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Pagos recientes --}}
        <div class="col-lg-5">
            <div class="mb-cli-card h-100">
                <div class="mb-cli-card__head">
                    <i class="fa-solid fa-receipt me-2"></i> Pagos recientes
                </div>
                <div class="mb-cli-card__body p-0">
                    @if($pagos->count())
                        <ul class="mb-cli-pay-list">
                            @foreach($pagos->take(6) as $pago)
                                <li class="mb-cli-pay-list__item">
                                    <div class="mb-cli-pay-list__icon">
                                        <i class="fa-solid fa-circle-check"></i>
                                    </div>
                                    <div class="mb-cli-pay-list__info">
                                        <span class="mb-cli-pay-list__date">
                                            {{ \Carbon\Carbon::parse($pago->fecha_pago)->format('d M Y') }}
                                        </span>
                                    </div>
                                    <div class="mb-cli-pay-list__amount">Bs. {{ number_format($pago->monto, 2) }}</div>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <div class="mb-cli-empty" style="padding:2rem;">
                            <i class="fa-solid fa-receipt fa-2x mb-2 opacity-25"></i>
                            <p>Aún no realizaste pagos.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

    </div>

    {{-- ── MI CARNET ── --}}
    <div class="mb-cli-card mb-4">
        <div class="mb-cli-card__head">
            <i class="fa-solid fa-address-card me-2"></i> Mi carnet
        </div>
        <div class="mb-cli-card__body">
            @if($qrCarnet)
                <div class="row g-4 align-items-center">
                    <div class="col-md-5 d-flex justify-content-center">
                        {{-- Vista previa del carnet --}}
                        <div class="mb-cli-carnet">
                            <div class="mb-cli-carnet__stripe"></div>
                            <div class="mb-cli-carnet__header">
                                <img src="{{ asset('assets_private/images/logo_mad.png') }}" alt="MadBarzz">
                                <div class="mb-cli-carnet__brand">MAD<span>BARZZ</span></div>
                                <div class="mb-cli-carnet__sub">Carnet de Socio</div>
                            </div>
                            <div class="mb-cli-carnet__qr">
                                <img src="{{ $qrCarnet }}" alt="Código QR">
                            </div>
                            <div class="mb-cli-carnet__nombre @if(mb_strlen($nombreCarnet) > 26) mb-cli-carnet__nombre--largo @endif">{{ $nombreCarnet }}</div>
                            <div class="mb-cli-carnet__nro">Cliente N° {{ str_pad($cliente->id_cliente, 5, '0', STR_PAD_LEFT) }}</div>
                            <div class="mb-cli-carnet__detalle">
                                <span>{{ $membresia->tipo_membresia ?? 'Sin membresía activa' }}</span>
                                @if($cliente->sede)
                                    <span>· {{ $cliente->sede->nombre }}</span>
                                @endif
                            </div>
                            <div class="mb-cli-carnet__stripe mb-cli-carnet__stripe--bottom"></div>
                        </div>
                    </div>
                    <div class="col-md-7">
                        <h5 style="color:#e2e8f0;font-weight:700;margin-bottom:.6rem;">Tu identificación digital</h5>
                        <p style="font-size:.88rem;color:#94a3b8;">
                            Este carnet con código QR es tu identificación como socio de MadBarzz.
                            Descárgalo y guárdalo en tu teléfono o imprímelo: pronto podrás usarlo
                            para registrar tu asistencia escaneándolo al ingresar.
                        </p>
                        <a href="{{ route('portal.carnet.descargar') }}" class="btn" style="background:#6366f1;color:#fff;border:none;font-weight:600;">
                            <i class="fa-solid fa-download me-2"></i> Descargar carnet
                        </a>
                    </div>
                </div>
            @else
                <div class="mb-cli-empty">
                    <i class="fa-solid fa-address-card fa-2x mb-2 opacity-25"></i>
                    <p>Tu carnet aún no está disponible.<br><small>Acércate a recepción para activarlo.</small></p>
                </div>
            @endif
        </div>
    </div>

    {{-- ── RUTINAS ── --}}
    @if($rutinas->count())
    <div class="mb-cli-card mb-4">
        <div class="mb-cli-card__head">
            <i class="fa-solid fa-dumbbell me-2"></i> Rutinas disponibles
        </div>
        <div class="mb-cli-card__body">
            <div class="row g-3">
                @foreach($rutinas as $rutina)
                <div class="col-md-4 col-sm-6">
                    <div class="mb-cli-rutina">
                        @if($rutina->archivo)
                            <img src="{{ asset('storage/'.$rutina->archivo) }}" class="mb-cli-rutina__img" alt="">
                        @else
                            <div class="mb-cli-rutina__placeholder">
                                <i class="fa-solid fa-dumbbell"></i>
                            </div>
                        @endif
                        <div class="mb-cli-rutina__body">
                            <div class="mb-cli-rutina__title">{{ $rutina->titulo }}</div>
                            <div class="mb-cli-rutina__trainer">
                                <i class="fa-solid fa-user-tie me-1"></i>
                                {{ $rutina->entrenador->persona->nombre_completo ?? 'Sin asignar' }}
                            </div>
                            <p class="mb-cli-rutina__desc">{{ \Illuminate\Support\Str::limit($rutina->descripcion, 90) }}</p>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @else
    <div class="mb-cli-card mb-4">
        <div class="mb-cli-card__head">
            <i class="fa-solid fa-dumbbell me-2"></i> Rutinas disponibles
        </div>
        <div class="mb-cli-card__body text-center py-4" style="color:#94a3b8;">
            <i class="fa-solid fa-dumbbell d-block mb-2" style="font-size:2rem;opacity:.35;"></i>
            @if(!$tieneMembresiaVigente)
                <p class="mb-0">Las rutinas están disponibles para socios con membresía activa.</p>
                <small>Renueva tu membresía para acceder.</small>
            @else
                <p class="mb-0">No hay rutinas publicadas todavía.</p>
            @endif
        </div>
    </div>
    @endif

    {{-- ── VINCULACIÓN DE TELEGRAM ── --}}
    @php $chatId = $cliente->persona->telegram_chat_id ?? null; @endphp
    <div class="mb-cli-card mb-4">
        <div class="mb-cli-card__head">
            <i class="fa-brands fa-telegram me-2" style="color:#229ed9;"></i> Notificaciones por Telegram
        </div>
        <div class="mb-cli-card__body">
            @if(session('success'))
                <div class="alert alert-success border-0 rounded-3 mb-3">{{ session('success') }}</div>
            @endif
            @if($chatId)
                <div class="d-flex align-items-center gap-3 mb-3 flex-wrap">
                    <span style="background:rgba(34,158,217,.15);color:#229ed9;font-size:.8rem;font-weight:600;padding:.4rem 1rem;border-radius:99px;display:inline-flex;align-items:center;gap:.4rem;">
                        <i class="fa-solid fa-circle-check"></i> Conectado
                    </span>
                    <span style="font-size:.82rem;color:#94a3b8;">Chat ID: {{ $chatId }}</span>
                </div>
                <p style="font-size:.84rem;color:#94a3b8;margin-bottom:1rem;">
                    Tu Telegram está vinculado. Recibirás un aviso cuando tu membresía esté por vencer.
                </p>
                <form method="POST" action="{{ route('portal.telegram.desvincular') }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger">
                        <i class="fa-solid fa-unlink me-1"></i> Desvincular
                    </button>
                </form>
            @else
                <p style="font-size:.88rem;color:#94a3b8;margin-bottom:1rem;">
                    Vincula tu Telegram para recibir recordatorios cuando tu membresía esté por vencer.
                </p>
                <ol style="font-size:.84rem;color:#94a3b8;padding-left:1.2rem;margin-bottom:1rem;">
                    @if(config('services.telegram.bot_username'))
                        <li>Abre Telegram y busca el bot <strong style="color:#e2e8f0;">{{ config('services.telegram.bot_username') }}</strong></li>
                    @else
                        <li>Abre Telegram y busca el bot de MadBarzz (pide el nombre en recepción)</li>
                    @endif
                    <li>Envíale el comando <code>/start</code></li>
                    <li>Busca el bot <code>@userinfobot</code> en Telegram — te dirá tu Chat ID</li>
                    <li>Escribe ese número aquí abajo y haz clic en <strong style="color:#e2e8f0;">Vincular</strong></li>
                </ol>
                <form method="POST" action="{{ route('portal.telegram.vincular') }}" class="d-flex gap-2 flex-wrap">
                    @csrf
                    <input
                        type="text"
                        name="telegram_chat_id"
                        class="form-control form-control-sm"
                        placeholder="Tu Chat ID (ej: 123456789)"
                        style="max-width:240px;"
                        required
                        pattern="-?\d+"
                        title="Solo números (puede empezar con -)"
                    >
                    <button type="submit" class="btn btn-sm" style="background:#229ed9;color:#fff;border:none;">
                        <i class="fa-brands fa-telegram me-1"></i> Vincular
                    </button>
                </form>
            @endif
        </div>
    </div>

</div>

<style>
/* ── layout ── */
.mb-cli-wrap { padding: 1.5rem; }

/* ── hero ── */
.mb-cli-hero {
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
    border-radius: 1.25rem;
    padding: 2rem 2.5rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    overflow: hidden;
    position: relative;
}
.mb-cli-hero::before {
    content: '';
    position: absolute;
    width: 300px; height: 300px;
    background: rgba(99,102,241,.15);
    border-radius: 50%;
    top: -80px; right: 120px;
    pointer-events: none;
}
.mb-cli-hero__saludo { color: rgba(255,255,255,.6); margin: 0; font-size: .9rem; }
.mb-cli-hero__nombre { color: #fff; font-size: 1.9rem; font-weight: 700; margin: .1rem 0 .5rem; }
.mb-cli-hero__sub    { color: rgba(255,255,255,.55); margin: 0; font-size: .88rem; }
.mb-cli-hero__logo img { width: 90px; opacity: .9; filter: drop-shadow(0 4px 16px rgba(0,0,0,.4)); }

/* ── kpi ── */
.mb-cli-kpi {
    background: var(--card-bg, #1e2139);
    border-radius: 1rem;
    padding: 1.25rem;
    text-align: center;
    border: 1px solid rgba(255,255,255,.05);
    transition: transform .2s;
}
.mb-cli-kpi:hover { transform: translateY(-3px); }
.mb-cli-kpi__icon {
    width: 48px; height: 48px;
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.2rem;
    margin: 0 auto .75rem;
}
.mb-cli-kpi__val   { font-size: 1.3rem; font-weight: 700; color: #e2e8f0; line-height: 1.2; }
.mb-cli-kpi__label { font-size: .75rem; color: #94a3b8; margin-top: .2rem; }

/* ── card ── */
.mb-cli-card {
    background: var(--card-bg, #1e2139);
    border-radius: 1.25rem;
    border: 1px solid rgba(255,255,255,.06);
    overflow: hidden;
}
.mb-cli-card__head {
    padding: 1rem 1.5rem;
    font-weight: 600;
    font-size: .95rem;
    color: #e2e8f0;
    border-bottom: 1px solid rgba(255,255,255,.06);
    display: flex;
    align-items: center;
}
.mb-cli-card__body { padding: 1.5rem; }

/* ── plan ── */
.mb-cli-plan { display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; margin-bottom: 1rem; }
.mb-cli-plan__badge {
    background: rgba(16,185,129,.15);
    color: #10b981;
    font-size: .78rem; font-weight: 600;
    padding: .3rem .8rem;
    border-radius: 99px;
}
.mb-cli-plan__name  { font-size: 1.25rem; font-weight: 700; color: #e2e8f0; }
.mb-cli-plan__price { font-size: 1.1rem; font-weight: 700; color: #6366f1; margin-left: auto; }
.mb-cli-plan__old   { font-size: .8rem; color: #94a3b8; text-decoration: line-through; margin-left: .4rem; }
.mb-cli-plan__disc  { font-size: .78rem; background: rgba(16,185,129,.15); color: #10b981; padding: .15rem .5rem; border-radius: 99px; margin-left: .3rem; }

/* ── date boxes ── */
.mb-cli-date-box { background: rgba(255,255,255,.04); border-radius: .75rem; padding: .8rem 1rem; }
.mb-cli-date-box__label { font-size: .72rem; color: #94a3b8; margin-bottom: .2rem; }
.mb-cli-date-box__val   { font-size: .95rem; font-weight: 600; color: #e2e8f0; }

/* ── progress ── */
.mb-cli-prog__label { display: flex; justify-content: space-between; font-size: .75rem; color: #94a3b8; margin-bottom: .4rem; }
.mb-cli-prog { background: rgba(255,255,255,.08); border-radius: 99px; height: 7px; }
.mb-cli-prog__bar { background: linear-gradient(90deg,#6366f1,#8b5cf6); border-radius: 99px; height: 100%; transition: width .6s ease; }

/* ── discount tag ── */
.mb-cli-disc-tag { font-size: .8rem; color: #10b981; background: rgba(16,185,129,.1); padding: .4rem .9rem; border-radius: 99px; display: inline-flex; align-items: center; }

/* ── payments list ── */
.mb-cli-pay-list { list-style: none; margin: 0; padding: 0; }
.mb-cli-pay-list__item {
    display: flex; align-items: center; gap: .75rem;
    padding: .85rem 1.5rem;
    border-bottom: 1px solid rgba(255,255,255,.05);
    transition: background .15s;
}
.mb-cli-pay-list__item:last-child { border-bottom: none; }
.mb-cli-pay-list__item:hover { background: rgba(255,255,255,.03); }
.mb-cli-pay-list__icon { color: #10b981; font-size: .9rem; flex-shrink: 0; }
.mb-cli-pay-list__info { flex: 1; }
.mb-cli-pay-list__date { font-size: .85rem; color: #94a3b8; }
.mb-cli-pay-list__amount { font-weight: 700; color: #e2e8f0; font-size: .9rem; }

/* ── rutinas ── */
.mb-cli-rutina { background: rgba(255,255,255,.03); border-radius: 1rem; overflow: hidden; border: 1px solid rgba(255,255,255,.06); transition: transform .2s, box-shadow .2s; height: 100%; }
.mb-cli-rutina:hover { transform: translateY(-4px); box-shadow: 0 8px 30px rgba(0,0,0,.3); }
.mb-cli-rutina__img { width: 100%; height: 140px; object-fit: cover; }
.mb-cli-rutina__placeholder { width: 100%; height: 140px; background: rgba(99,102,241,.12); display: flex; align-items: center; justify-content: center; font-size: 2rem; color: #6366f1; opacity: .5; }
.mb-cli-rutina__body   { padding: 1rem; }
.mb-cli-rutina__title  { font-weight: 700; color: #e2e8f0; margin-bottom: .3rem; }
.mb-cli-rutina__trainer { font-size: .78rem; color: #6366f1; margin-bottom: .5rem; }
.mb-cli-rutina__desc   { font-size: .8rem; color: #94a3b8; margin: 0; }

/* ── carnet ── */
.mb-cli-carnet {
    width: 250px;
    background: linear-gradient(160deg, #16213e 0%, #1a1a2e 100%);
    border: 1px solid rgba(99,102,241,.35);
    border-radius: 1rem;
    overflow: hidden;
    text-align: center;
    box-shadow: 0 12px 40px rgba(0,0,0,.45);
}
.mb-cli-carnet__stripe { height: 6px; background: linear-gradient(90deg,#6366f1,#8b5cf6); }
.mb-cli-carnet__stripe--bottom { margin-top: 1rem; }
.mb-cli-carnet__header { padding: 1rem 1rem .4rem; }
.mb-cli-carnet__header img { width: 46px; }
.mb-cli-carnet__brand { font-size: 1.15rem; font-weight: 800; color: #fff; letter-spacing: 3px; margin-top: .3rem; }
.mb-cli-carnet__brand span { color: #6366f1; }
.mb-cli-carnet__sub { font-size: .62rem; color: #94a3b8; letter-spacing: 2.5px; text-transform: uppercase; margin-top: .1rem; }
.mb-cli-carnet__qr {
    width: 150px;
    margin: .7rem auto 0;
    background: #fff;
    border-radius: .7rem;
    padding: .5rem;
}
.mb-cli-carnet__qr img { width: 100%; display: block; }
.mb-cli-carnet__nombre { color: #fff; font-weight: 700; font-size: .95rem; line-height: 1.25; margin-top: .8rem; padding: 0 .8rem; overflow-wrap: break-word; }
.mb-cli-carnet__nombre--largo { font-size: .8rem; }
.mb-cli-carnet__nro { color: #6366f1; font-size: .68rem; letter-spacing: 1.5px; text-transform: uppercase; margin-top: .15rem; }
.mb-cli-carnet__detalle { color: #94a3b8; font-size: .74rem; margin-top: .45rem; padding: 0 .8rem; }

/* ── empty state ── */
.mb-cli-empty { text-align: center; color: #64748b; padding: 2.5rem 1rem; }
.mb-cli-empty p { margin: 0; font-size: .9rem; }
</style>

@endsection
