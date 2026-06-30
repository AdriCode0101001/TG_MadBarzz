{{--
    Encabezado de página reutilizable.
    Uso:
    <x-page-header title="Clientes" subtitle="Gestiona los miembros del gimnasio" icon="fa-solid fa-users">
        <a href="..." class="btn btn-primary"><i class="fa-solid fa-plus"></i> Nuevo</a>
    </x-page-header>
--}}
@props([
    'title',
    'subtitle' => null,
    'icon' => null,
])

<div class="mb-page-header">
    <div>
        <h1 class="mb-page-title">
            @if ($icon)
                <span class="mb-title-icon"><i class="{{ $icon }}"></i></span>
            @endif
            {{ $title }}
        </h1>
        @if ($subtitle)
            <p class="mb-page-subtitle">{{ $subtitle }}</p>
        @endif
    </div>
    @if (! $slot->isEmpty())
        <div class="d-flex flex-wrap gap-2 align-items-center">
            {{ $slot }}
        </div>
    @endif
</div>
