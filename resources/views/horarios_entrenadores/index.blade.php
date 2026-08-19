@extends('layouts.private')

@section('contenido')
<div class="col-12 mb-horario-page">

    <x-page-header title="Horario de Entrenadores" subtitle="Asigná qué entrenador cubre cada franja horaria, de lunes a sábado" icon="fa-solid fa-clock" />

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @error('asignaciones')
        <div class="alert alert-danger">{{ $message }}</div>
    @enderror

    <div class="card">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <h5 class="mb-0"><i class="fa-solid fa-table me-2"></i>Grilla semanal</h5>
                <p class="mb-0 text-muted" style="font-size:.82rem;">
                    Este es el horario BASE: se repite todas las semanas hasta que lo cambies acá. El domingo el
                    gimnasio cierra, por eso no aparece como columna.
                    @if ($puedeEditar)
                        Armá los cambios que quieras y después tocá "Guardar cambios" — nada se guarda solo.
                    @else
                        Solo lectura — la edición es exclusiva del Administrador.
                    @endif
                </p>
            </div>
            @if ($puedeEditar)
                <div class="d-flex align-items-center gap-2">
                    <span id="mbHorarioDirty" class="badge text-bg-warning" style="display:none;">
                        <i class="fa-solid fa-triangle-exclamation me-1"></i>Cambios sin guardar
                    </span>
                    <button type="submit" form="mbHorarioForm" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Guardar cambios
                    </button>
                </div>
            @endif
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('horarios_entrenadores.guardar') }}" id="mbHorarioForm">
                @csrf
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-horario-tabla">
                        <thead>
                            <tr>
                                <th>Horario</th>
                                @foreach ($dias as $nombreDia)
                                    <th class="text-center">{{ $nombreDia }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($franjas as $hora)
                                @php
                                    $horaFin = sprintf('%02d:00', ((int) substr($hora, 0, 2)) + 1);
                                @endphp
                                <tr>
                                    <td class="fw-bold text-nowrap">{{ $hora }} - {{ $horaFin }}</td>
                                    @foreach ($dias as $isoDia => $nombreDia)
                                        @php
                                            $asignacion = $asignaciones->get($isoDia . '_' . $hora);
                                            $entrenadorAsignado = optional($asignacion)->entrenador;
                                            $nombreAsignado = optional(optional($entrenadorAsignado)->persona)->nombre_completo;
                                        @endphp
                                        <td>
                                            @if ($puedeEditar)
                                                <select name="asignaciones[{{ $isoDia }}][{{ $hora }}]" class="form-select form-select-sm mb-horario-select">
                                                    <option value="">— Sin asignar —</option>
                                                    @foreach ($entrenadores as $entrenador)
                                                        <option value="{{ $entrenador->id_entrenador }}" @selected(optional($asignacion)->id_entrenador === $entrenador->id_entrenador)>
                                                            {{ optional($entrenador->persona)->nombre_completo ?? ('Entrenador #' . $entrenador->id_entrenador) }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            @else
                                                <span class="mb-horario-readonly {{ $nombreAsignado ? '' : 'mb-horario-readonly--vacio' }}">
                                                    {{ $nombreAsignado ?? '— Sin asignar —' }}
                                                </span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($puedeEditar)
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Guardar cambios
                        </button>
                    </div>
                @endif
            </form>
        </div>
    </div>
</div>

@if ($puedeEditar)
{{--
    Misma solución que calendario_musculos/index.blade.php: la plantilla
    (Gymove/custom.min.js) convierte TODO <select> de la página en un widget
    "bootstrap-select" sin forma de excluirlos. Con 6 días x 14 franjas = 84
    selects en una tabla compacta, ese widget rompe el layout. Se sacan del
    DOM antes de que ese script corra y se reinsertan después (ver el
    comentario largo en calendario_musculos/index.blade.php para el detalle
    de por qué el reattach va en @push('scripts') y no en window.load).
--}}
<script>
window.__mbHorarioSlots = (function () {
    var slots = [];
    document.querySelectorAll('.mb-horario-select').forEach(function (el) {
        var marker = document.createComment('mb-horario-select');
        el.parentNode.insertBefore(marker, el);
        el.parentNode.removeChild(el);
        slots.push({ marker: marker, el: el });
    });
    return slots;
})();
</script>

@push('scripts')
<script>
jQuery(function () {
    (window.__mbHorarioSlots || []).forEach(function (s) {
        s.marker.parentNode.insertBefore(s.el, s.marker);
        s.marker.parentNode.removeChild(s.marker);
    });

    // ── Aviso de cambios sin guardar ──────────────────────────────────────
    // Nada se guarda solo: el admin arma la grilla entera y recién se
    // persiste al tocar "Guardar cambios". Este aviso es solo para que no
    // se le pase por alto que dejó algo sin guardar — no bloquea nada.
    var form  = document.getElementById('mbHorarioForm');
    var badge = document.getElementById('mbHorarioDirty');
    var sucio = false;

    if (form) {
        form.addEventListener('change', function (e) {
            if (e.target.classList.contains('mb-horario-select')) {
                sucio = true;
                if (badge) badge.style.display = '';
            }
        });

        form.addEventListener('submit', function () {
            sucio = false;
        });
    }

    window.addEventListener('beforeunload', function (e) {
        if (sucio) {
            e.preventDefault();
            e.returnValue = '';
        }
    });
});
</script>
@endpush
@endif

@push('styles')
<style>
.mb-horario-tabla { font-size: .85rem; }
.mb-horario-tabla th { text-align: center; white-space: nowrap; }
.mb-horario-tabla td { min-width: 150px; }
.mb-horario-select { width: 100%; font-size: .78rem; padding: .3rem .5rem; }
.mb-horario-readonly { font-size: .82rem; }
.mb-horario-readonly--vacio { opacity: .45; font-style: italic; }

@media (max-width: 991.98px) {
    .mb-horario-tabla td { min-width: 130px; }
}
</style>
@endpush
@endsection
