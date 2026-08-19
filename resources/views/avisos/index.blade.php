@extends('layouts.private')

@section('contenido')
<div class="col-12">

    <x-page-header title="Avisos" subtitle="Enviados a todos los clientes — los ven en su portal" icon="fa-solid fa-bullhorn">
        <a href="{{ route('avisos.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-bullhorn me-1"></i> Nuevo aviso
        </a>
    </x-page-header>

    @if (session('success'))
        <div class="alert alert-success border-0 rounded-3 mb-4">{{ session('success') }}</div>
    @endif

    <div class="card custom-card">
        <div class="card-body p-0">
            <div class="table-responsive" style="overflow-x:auto; -webkit-overflow-scrolling:touch;">
                <table class="table table-hover align-middle mb-0" style="min-width:760px;">
                    <thead>
                        <tr>
                            <th style="min-width:50px;">#</th>
                            <th style="min-width:60px;">Imagen</th>
                            <th style="min-width:200px;">Título</th>
                            <th style="min-width:160px;">Enviado por</th>
                            <th style="min-width:140px; white-space:nowrap;">Fecha</th>
                            <th style="min-width:110px;">Leído</th>
                            <th style="min-width:90px;">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($avisos as $aviso)
                            <tr>
                                <td class="text-muted">{{ $aviso->id }}</td>
                                <td>
                                    @if ($aviso->imagen)
                                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($aviso->imagen) }}"
                                             alt="" style="width:44px;height:44px;object-fit:cover;border-radius:6px;">
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $aviso->titulo }}</div>
                                    <div class="text-muted text-truncate" style="max-width:260px;font-size:.82rem;">{{ $aviso->texto }}</div>
                                </td>
                                <td>{{ $aviso->usuario->name ?? '—' }}</td>
                                <td><i class="fa-regular fa-clock text-muted me-1"></i>{{ $aviso->created_at->format('d/m/Y H:i') }}</td>
                                <td>
                                    <span class="badge bg-primary-subtle text-primary">
                                        {{ $aviso->lecturas_count }} / {{ $totalClientes }}
                                    </span>
                                </td>
                                <td>
                                    <form action="{{ route('avisos.destroy', $aviso) }}" method="POST"
                                          onsubmit="return confirm('¿Retirar este aviso? Ya no va a estar disponible para los clientes.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <div class="mb-empty py-4 text-center">
                                        <div class="mb-empty-icon"><i class="fa-solid fa-bullhorn"></i></div>
                                        <p class="mb-0">Todavía no se envió ningún aviso.</p>
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
