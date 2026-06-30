{{--
    Tarjeta de estadística reutilizable.
    Uso:
    <x-stat-card label="Clientes activos" :value="$clientes->count()" icon="fa-solid fa-user-check" variant="brand" />
    Variantes: brand | success | warning | info | danger
--}}
@props([
    'label',
    'value',
    'icon' => 'fa-solid fa-chart-simple',
    'variant' => 'brand',
    'trend' => null,
])

@php
    $isBrand = $variant === 'brand';
    $soft = 'bg-soft-' . $variant;
@endphp

<div {{ $attributes->merge(['class' => 'mb-stat ' . ($isBrand ? 'mb-stat--brand' : 'card')]) }}>
    <span class="mb-stat-icon {{ $isBrand ? '' : $soft }}">
        <i class="{{ $icon }}"></i>
    </span>
    <div class="mb-stat-value">{{ $value }}</div>
    <p class="mb-stat-label">{{ $label }}</p>
    @if ($trend)
        <span class="mb-stat-trend text-success"><i class="fa-solid fa-arrow-trend-up me-1"></i>{{ $trend }}</span>
    @endif
</div>
