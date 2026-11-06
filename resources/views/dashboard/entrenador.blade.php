@extends('layouts.private')

@section('contenido')

@php
    $nombre  = $entrenador?->persona?->nombre_completo ?? Auth::user()->name;
    $hora    = now()->hour;
    $saludo  = $hora < 12 ? 'Buenos días' : ($hora < 19 ? 'Buenas tardes' : 'Buenas noches');
    $especialidades = $entrenador?->especialidad ?? [];
    $disponibilidad = $entrenador?->disponibilidad ?? [];
@endphp

<div class="mb-cli-wrap">

    {{-- ── HERO ── --}}
    <div class="mb-cli-hero mb-4" style="background:linear-gradient(135deg,#0f2027,#203a43,#2c5364);">
        <div class="mb-cli-hero__text">
            <p class="mb-cli-hero__saludo">{{ $saludo }},</p>
            <h2 class="mb-cli-hero__nombre">{{ $nombre }}</h2>
            <p class="mb-cli-hero__sub">Bienvenido a tu panel de entrenador en MadBarzz.</p>
            @if(count($especialidades))
                <div class="d-flex flex-wrap gap-2 mt-3">
                    @foreach($especialidades as $esp)
                        <span class="badge" style="background:rgba(255,255,255,.15);font-size:.78rem;">
                            <i class="fa-solid fa-dumbbell me-1"></i>{{ $esp }}
                        </span>
                    @endforeach
                </div>
            @endif
        </div>
        <div class="mb-cli-hero__logo">
            <img src="{{ \App\Services\ImagenHomeService::logoEmblema() }}" alt="MadBarzz">
        </div>
    </div>

    {{-- ── KPIs ── --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="mb-cli-kpi">
                <div class="mb-cli-kpi__icon" style="background:rgba(255, 107, 26,.15);color:#ff6b1a;">
                    <i class="fa-solid fa-dumbbell"></i>
                </div>
                <div class="mb-cli-kpi__val">{{ $rutinas->count() }}</div>
                <div class="mb-cli-kpi__label">Rutinas armadas</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="mb-cli-kpi">
                <div class="mb-cli-kpi__icon" style="background:rgba(16,185,129,.15);color:#10b981;">
                    <i class="fa-solid fa-users"></i>
                </div>
                <div class="mb-cli-kpi__val">{{ $clientes->count() }}</div>
                <div class="mb-cli-kpi__label">Clientes activos</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="mb-cli-kpi">
                <div class="mb-cli-kpi__icon" style="background:rgba(245,158,11,.15);color:#f59e0b;">
                    <i class="fa-solid fa-clipboard-user"></i>
                </div>
                <div class="mb-cli-kpi__val">{{ $asistenciasHoy }}</div>
                <div class="mb-cli-kpi__label">Asistencias hoy</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="mb-cli-kpi">
                <div class="mb-cli-kpi__icon" style="background:rgba(59,130,246,.15);color:#3b82f6;">
                    <i class="fa-solid fa-star"></i>
                </div>
                <div class="mb-cli-kpi__val">{{ $entrenador?->experiencia ?? '—' }}</div>
                <div class="mb-cli-kpi__label">Experiencia</div>
            </div>
        </div>
    </div>

    <div class="row g-4">

        {{-- ── MIS RUTINAS ── --}}
        <div class="col-lg-8">
            <div class="mb-cli-card h-100">
                <div class="mb-cli-card__head d-flex justify-content-between align-items-center">
                    <span><i class="fa-solid fa-dumbbell me-2"></i> Mis Rutinas</span>
                    <a href="{{ route('rutinas.index') }}" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-calendar-days me-1"></i> Armar rutina
                    </a>
                </div>
                <div class="mb-cli-card__body p-0">
                    @if($rutinas->count())
                        <ul class="mb-cli-pay-list">
                            @foreach($rutinas->take(6) as $rutina)
                                <li class="mb-cli-pay-list__item">
                                    <div style="width:44px;height:44px;border-radius:.5rem;overflow:hidden;flex-shrink:0;background:rgba(255, 107, 26,.1);display:flex;align-items:center;justify-content:center;">
                                        <i class="fa-solid fa-dumbbell" style="color:#ff6b1a;font-size:.9rem;"></i>
                                    </div>
                                    <div class="mb-cli-pay-list__info flex-grow-1">
                                        <div style="font-weight:600;color:#e2e8f0;font-size:.9rem;">{{ $rutina->fecha->translatedFormat('d \d\e F') }}</div>
                                        <div style="font-size:.75rem;color:#94a3b8;">
                                            {{ $rutina->grupoMuscular->nombre ?? '—' }} · {{ $rutina->ejercicios_count }} ejercicio(s)
                                        </div>
                                    </div>
                                    <div class="d-flex gap-1">
                                        <a href="{{ route('rutinas.edit', $rutina->fecha->format('Y-m-d')) }}" class="btn btn-sm btn-warning">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </a>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                        @if($rutinas->count() > 6)
                            <div class="text-center py-3">
                                <a href="{{ route('rutinas.index') }}" class="btn btn-sm btn-outline-primary">
                                    Ver todas ({{ $rutinas->count() }})
                                </a>
                            </div>
                        @endif
                    @else
                        <div class="mb-cli-empty" style="padding:2.5rem;">
                            <i class="fa-solid fa-dumbbell fa-2x mb-2 opacity-25"></i>
                            <p>Aún no has armado rutinas.</p>
                            <a href="{{ route('rutinas.index') }}" class="btn btn-primary btn-sm mt-1">
                                <i class="fa-solid fa-calendar-days me-1"></i> Armar mi primera rutina
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ── MI PERFIL ── --}}
        <div class="col-lg-4">
            <div class="mb-cli-card h-100">
                <div class="mb-cli-card__head">
                    <i class="fa-solid fa-id-badge me-2"></i> Mi Perfil
                </div>
                <div class="mb-cli-card__body">

                    <div class="text-center mb-3">
                        <div style="width:72px;height:72px;border-radius:50%;background:linear-gradient(135deg,#ff6b1a,#ff8a3d);
                                    display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
                            <i class="fa-solid fa-user-tie fa-2x text-white"></i>
                        </div>
                        <div style="font-weight:700;color:#e2e8f0;font-size:1.05rem;">{{ $nombre }}</div>
                        <div style="font-size:.8rem;color:#94a3b8;">Entrenador</div>
                    </div>

                    @if($entrenador)
                        <ul class="list-unstyled d-flex flex-column gap-3 mt-3">
                            <li class="d-flex align-items-start gap-2">
                                <i class="fa-solid fa-star mt-1" style="color:#f59e0b;font-size:.85rem;flex-shrink:0;"></i>
                                <div>
                                    <div style="font-size:.72rem;color:#94a3b8;">Experiencia</div>
                                    <div style="font-size:.88rem;color:#e2e8f0;">{{ $entrenador->experiencia ?? '—' }}</div>
                                </div>
                            </li>
                            @if(count($disponibilidad))
                            <li class="d-flex align-items-start gap-2">
                                <i class="fa-solid fa-calendar-days mt-1" style="color:#10b981;font-size:.85rem;flex-shrink:0;"></i>
                                <div>
                                    <div style="font-size:.72rem;color:#94a3b8;">Disponibilidad</div>
                                    <div class="d-flex flex-wrap gap-1 mt-1">
                                        @foreach($disponibilidad as $dia)
                                            <span class="badge" style="background:rgba(16,185,129,.15);color:#10b981;font-size:.72rem;">{{ $dia }}</span>
                                        @endforeach
                                    </div>
                                </div>
                            </li>
                            @endif
                            <li class="d-flex align-items-center gap-2">
                                <i class="fa-solid fa-circle {{ $entrenador->estado === 'activo' ? 'text-success' : 'text-danger' }}"
                                   style="font-size:.6rem;"></i>
                                <span style="font-size:.88rem;color:#e2e8f0;">{{ ucfirst($entrenador->estado ?? 'activo') }}</span>
                            </li>
                        </ul>
                    @endif

                    <div class="mt-4">
                        <a href="{{ route('users.edit', Auth::id()) }}" class="btn btn-outline-primary w-100 btn-sm">
                            <i class="fa-solid fa-pen-to-square me-1"></i> Editar perfil
                        </a>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

@endsection
