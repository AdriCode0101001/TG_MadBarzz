@extends('layouts.private')

@section('contenido')
@php $esAdmin = Auth::user()->hasRole('Administrador'); @endphp
<div class="col-12">

    <x-page-header title="Descuentos" subtitle="Descuentos aplicables al asignar membresías" icon="fa-solid fa-tags">
        @if ($esAdmin)
            <a href="{{ route('descuentos.create') }}" class="btn btn-primary">
                <i class="fa-solid fa-circle-plus"></i> Nuevo descuento
            </a>
        @endif
    </x-page-header>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th class="text-center">Porcentaje</th>
                            <th class="text-center">Estado</th>
                            @if ($esAdmin)
                                <th class="text-end">Acciones</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($descuentos as $descuento)
                            <tr>
                                <td>{{ $descuento->nombre }}</td>
                                <td class="text-center">
                                    <span class="badge bg-secondary">{{ rtrim(rtrim(number_format($descuento->porcentaje, 2), '0'), '.') }}%</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge {{ $descuento->activo ? 'bg-success' : 'bg-danger' }} mb-badge-dot">
                                        {{ $descuento->activo ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>
                                @if ($esAdmin)
                                    <td class="text-end">
                                        <div class="mb-actions justify-content-end">
                                            <a href="{{ route('descuentos.edit', $descuento) }}" class="btn btn-sm btn-warning" title="Editar">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </a>
                                            <form action="{{ route('descuentos.destroy', $descuento) }}" method="POST"
                                                  data-confirm="¿Eliminar el descuento «{{ $descuento->nombre }}»? Las membresías ya asignadas conservan sus datos.">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-danger" title="Eliminar">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">
                                    <div class="mb-empty">
                                        <div class="mb-empty-icon"><i class="fa-solid fa-tags"></i></div>
                                        <p class="mb-2">No hay descuentos registrados.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
