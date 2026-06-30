@extends('layouts.private')

@section('contenido')
<div class="col-12">

    <x-page-header title="Membresías" subtitle="Planes disponibles para los clientes" icon="fa-solid fa-id-card-clip">
        <a href="{{ route('membresias.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-circle-plus"></i> Nueva membresía
        </a>
    </x-page-header>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Tipo de membresía</th>
                            <th>Precio</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($membresias as $membresia)
                            <tr>
                                <td class="text-muted">{{ $membresia->id_membresia }}</td>
                                <td><i class="fa-solid fa-ticket text-primary me-2"></i>{{ $membresia->tipo_membresia }}</td>
                                <td><span class="badge bg-success">{{ number_format($membresia->precio, 2) }} Bs</span></td>
                                <td class="text-end">
                                    <div class="mb-actions justify-content-end">
                                        <a href="{{ route('membresias.edit', $membresia->id_membresia) }}"
                                           class="btn btn-sm btn-warning" title="Editar">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </a>
                                        <form action="{{ route('membresias.destroy', $membresia->id_membresia) }}"
                                              method="POST" data-confirm="¿Eliminar la membresía «{{ $membresia->tipo_membresia }}»?">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-danger" title="Eliminar">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">
                                    <div class="mb-empty">
                                        <div class="mb-empty-icon"><i class="fa-solid fa-id-card-clip"></i></div>
                                        <p class="mb-2">No hay membresías registradas.</p>
                                        <a href="{{ route('membresias.create') }}" class="btn btn-primary btn-sm">
                                            <i class="fa-solid fa-circle-plus"></i> Crear la primera
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if (method_exists($membresias, 'links'))
            <div class="card-footer d-flex justify-content-end">{{ $membresias->withQueryString()->links() }}</div>
        @endif
    </div>
</div>
@endsection
