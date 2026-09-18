@extends('layouts.private')

@section('contenido')
<div class="col-12">

    <x-page-header title="Bloqueos de Login" subtitle="Intentos fallidos de inicio de sesión — bloqueo progresivo por usuario + IP" icon="fa-solid fa-user-lock">
        <span class="badge bg-secondary">{{ $registros->total() }} registros</span>
    </x-page-header>

    @if (session('success'))
        <div class="alert alert-success border-0 rounded-3 mb-4">{{ session('success') }}</div>
    @endif

    <div class="card custom-card">
        <div class="card-body p-0">
            <div class="table-responsive" style="overflow-x:auto; -webkit-overflow-scrolling:touch;">
                <table class="table table-hover align-middle mb-0" style="min-width:640px;">
                    <thead>
                        <tr>
                            <th>Usuario (login)</th>
                            <th>IP</th>
                            <th>Intentos fallidos</th>
                            <th>Escalón</th>
                            <th>Estado</th>
                            <th>Bloqueado hasta</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($registros as $registro)
                            <tr>
                                <td>{{ $registro->login }}</td>
                                <td><span class="badge bg-secondary">{{ $registro->ip }}</span></td>
                                <td>{{ $registro->attempts }}</td>
                                <td>{{ $registro->lockout_level }}</td>
                                <td>
                                    @if ($registro->estaBloqueadoAhora())
                                        <span class="badge bg-danger">Bloqueado</span>
                                    @else
                                        <span class="badge bg-secondary">Sin bloqueo activo</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($registro->locked_until)
                                        <i class="fa-regular fa-clock text-muted me-1"></i>{{ $registro->locked_until->format('d/m/Y H:i:s') }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="text-end">
                                    <form method="POST" action="{{ route('login_lockouts.destroy', $registro) }}" onsubmit="return confirm('¿Desbloquear a &quot;{{ $registro->login }}&quot; ({{ $registro->ip }}) ahora mismo?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-success">
                                            <i class="fa-solid fa-lock-open me-1"></i>Desbloquear
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <div class="mb-empty">
                                        <div class="mb-empty-icon"><i class="fa-solid fa-user-lock"></i></div>
                                        <p class="mb-2">No hay intentos fallidos de login registrados.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($registros->hasPages())
            <div class="card-footer d-flex justify-content-end">
                {{ $registros->withQueryString()->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
</div>
@endsection
