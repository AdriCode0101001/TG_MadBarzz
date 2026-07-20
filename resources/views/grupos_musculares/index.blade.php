@extends('layouts.private')

@section('contenido')
@php $esAdmin = Auth::user()->hasRole('Administrador'); @endphp
<div class="col-12">

    <x-page-header title="Grupos musculares" subtitle="Catálogo base para asignar rutinas por músculo" icon="fa-solid fa-dumbbell">
        @if ($esAdmin)
            <a href="{{ route('grupos_musculares.create') }}" class="btn btn-primary">
                <i class="fa-solid fa-circle-plus"></i> Nuevo grupo muscular
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
                            <th>Descripción</th>
                            <th class="text-center">Estado</th>
                            @if ($esAdmin)
                                <th class="text-end">Acciones</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($gruposMusculares as $grupo)
                            <tr>
                                <td>{{ $grupo->nombre }}</td>
                                <td>{{ $grupo->descripcion ?? '—' }}</td>
                                <td class="text-center">
                                    <span class="badge {{ $grupo->activo ? 'bg-success' : 'bg-danger' }} mb-badge-dot">
                                        {{ $grupo->activo ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>
                                @if ($esAdmin)
                                    <td class="text-end">
                                        <div class="mb-actions justify-content-end">
                                            <a href="{{ route('grupos_musculares.edit', $grupo) }}" class="btn btn-sm btn-warning" title="Editar">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </a>
                                            <form action="{{ route('grupos_musculares.destroy', $grupo) }}" method="POST"
                                                  data-confirm="¿Eliminar el grupo muscular «{{ $grupo->nombre }}»?">
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
                                        <div class="mb-empty-icon"><i class="fa-solid fa-dumbbell"></i></div>
                                        <p class="mb-2">No hay grupos musculares registrados.</p>
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
