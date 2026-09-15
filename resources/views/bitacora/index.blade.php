@extends('layouts.private')

@section('contenido')
<div class="col-12 mb-bitacora-page">

    <x-page-header title="Bitácora de Auditoría" subtitle="Historial de cambios del sistema: quién hizo qué y cuándo" icon="fa-solid fa-clock-rotate-left">
        <span class="badge bg-secondary">{{ $actividades->total() }} registros</span>
    </x-page-header>

    <div class="card custom-card">
        <div class="card-header">
            <form method="GET" action="{{ route('bitacora.index') }}" class="row g-2 align-items-end">
                <div class="col-6 col-md-3">
                    <label class="form-label small mb-1">Usuario</label>
                    <select name="causer_id" class="form-select form-select-sm">
                        <option value="">— Todos —</option>
                        @foreach ($causers as $c)
                            <option value="{{ $c['id'] }}" @selected((string) ($filtros['causer_id'] ?? '') === (string) $c['id'])>{{ $c['etiqueta'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Acción</label>
                    <select name="evento" class="form-select form-select-sm">
                        <option value="">— Todas —</option>
                        <option value="created" @selected(($filtros['evento'] ?? '') === 'created')>Creó</option>
                        <option value="updated" @selected(($filtros['evento'] ?? '') === 'updated')>Editó</option>
                        <option value="deleted" @selected(($filtros['evento'] ?? '') === 'deleted')>Eliminó</option>
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small mb-1">Entidad</label>
                    <select name="entidad" class="form-select form-select-sm">
                        <option value="">— Todas —</option>
                        @foreach ($entidades as $fqcn => $label)
                            <option value="{{ $fqcn }}" @selected(($filtros['entidad'] ?? '') === $fqcn)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Desde</label>
                    <input type="date" name="desde" class="form-control form-control-sm" value="{{ $filtros['desde'] ?? '' }}">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Hasta</label>
                    <input type="date" name="hasta" class="form-control form-control-sm" value="{{ $filtros['hasta'] ?? '' }}">
                </div>
                <div class="col-12 d-flex gap-2 mt-2">
                    <button type="submit" class="btn btn-sm btn-primary"><i class="fa-solid fa-filter me-1"></i>Filtrar</button>
                    @if (array_filter($filtros))
                        <a href="{{ route('bitacora.index') }}" class="btn btn-sm btn-outline-secondary">Limpiar</a>
                    @endif
                </div>
            </form>
        </div>
        <div class="card-body">
            @if ($actividades->isEmpty())
                <div class="mb-empty">
                    <div class="mb-empty-icon"><i class="fa-solid fa-clock-rotate-left"></i></div>
                    <p class="mb-2">No hay registros que coincidan con el filtro.</p>
                </div>
            @else
                <div class="accordion accordion-flush mb-bitacora-accordion" id="bitacoraAccordion">
                    @foreach ($actividades as $item)
                        @php
                            $icono = match ($item['evento']) {
                                'created' => 'fa-plus',
                                'updated' => 'fa-pen',
                                'deleted' => 'fa-trash',
                                default   => 'fa-circle-info',
                            };
                        @endphp
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed mb-bitacora-toggle" type="button"
                                        data-bs-toggle="collapse" data-bs-target="#bitacora-{{ $item['id'] }}">
                                    <span class="mb-bitacora-badge mb-bitacora-badge--{{ $item['evento'] }}">
                                        <i class="fa-solid {{ $icono }}"></i>
                                    </span>
                                    <span class="mb-bitacora-frase">{{ $item['frase'] }}</span>
                                    <span class="mb-bitacora-fecha">
                                        <i class="fa-regular fa-clock me-1"></i>{{ $item['fecha'] }}
                                    </span>
                                </button>
                            </h2>
                            <div id="bitacora-{{ $item['id'] }}" class="accordion-collapse collapse" data-bs-parent="#bitacoraAccordion">
                                <div class="accordion-body">
                                    @if (empty($item['detalle']))
                                        <p class="text-muted mb-0">Sin detalle de campos para este registro.</p>
                                    @elseif (array_key_exists('antes', $item['detalle'][0]))
                                        <div class="table-responsive">
                                            <table class="table table-sm mb-0">
                                                <thead><tr><th>Campo</th><th>Antes</th><th>Después</th></tr></thead>
                                                <tbody>
                                                    @foreach ($item['detalle'] as $fila)
                                                        <tr>
                                                            <td>{{ $fila['campo'] }}</td>
                                                            <td class="text-muted">{{ $fila['antes'] }}</td>
                                                            <td>{{ $fila['despues'] }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="table-responsive">
                                            <table class="table table-sm mb-0">
                                                <thead><tr><th>Campo</th><th>Valor</th></tr></thead>
                                                <tbody>
                                                    @foreach ($item['detalle'] as $fila)
                                                        <tr><td>{{ $fila['campo'] }}</td><td>{{ $fila['valor'] }}</td></tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-3 d-flex justify-content-end">
                    {{-- pagination::bootstrap-5: la vista default de ->links() es Tailwind,
                         ver el mismo fix ya aplicado en ejercicios/index. --}}
                    {{ $actividades->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </div>
    </div>
</div>

@push('styles')
<style>
.mb-bitacora-page .card { height: auto; }

.mb-bitacora-accordion .accordion-item { border-left: none; border-right: none; }

.mb-bitacora-toggle {
    display: flex;
    align-items: center;
    gap: .65rem;
    flex-wrap: wrap;
}
.mb-bitacora-badge {
    flex-shrink: 0;
    width: 30px; height: 30px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: .78rem;
}
.mb-bitacora-badge--created { background: rgba(25,135,84,.15); color: #198754; }
.mb-bitacora-badge--updated { background: rgba(13,110,253,.15); color: #0d6efd; }
.mb-bitacora-badge--deleted { background: rgba(220,53,69,.15); color: #dc3545; }

.mb-bitacora-frase { flex: 1 1 320px; text-align: left; font-size: .9rem; }
.mb-bitacora-fecha { flex-shrink: 0; font-size: .78rem; opacity: .65; white-space: nowrap; }

@media (max-width: 575.98px) {
    .mb-bitacora-frase { flex-basis: 100%; }
    .mb-bitacora-fecha { flex-basis: 100%; padding-left: calc(30px + .65rem); }
}
</style>
@endpush
@endsection
