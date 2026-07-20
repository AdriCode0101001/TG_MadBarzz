@extends('layouts.private')

@section('contenido')
@php
    $meses = [1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'];
    $diasSemana = [1=>'Lunes',2=>'Martes',3=>'Miércoles',4=>'Jueves',5=>'Viernes',6=>'Sábado'];

    $mesAnterior = $mes === 1 ? 12 : $mes - 1;
    $anioMesAnterior = $mes === 1 ? $anio - 1 : $anio;
    $mesSiguiente = $mes === 12 ? 1 : $mes + 1;
    $anioMesSiguiente = $mes === 12 ? $anio + 1 : $anio;
@endphp
<div class="col-12 mb-cal-page">

    <x-page-header title="Calendario de grupos musculares" subtitle="Asigna qué músculo se trabaja cada día (lunes a sábado)" icon="fa-solid fa-calendar-days">
        <a href="{{ route('calendario_musculos.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-calendar-day me-1"></i> Hoy
        </a>
    </x-page-header>

    {{-- ── Patrón semanal ── --}}
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fa-solid fa-repeat me-2"></i>Patrón semanal</h5>
            <p class="mb-0 text-muted" style="font-size:.82rem;">
                Define un músculo por día de la semana y aplícalo a todo {{ $meses[$mes] }} {{ $anio }} de una vez.
                Deja "— No aplicar —" en los días que no quieras tocar.
            </p>
        </div>
        <div class="card-body">
            <form method="POST"
                  action="{{ route('calendario_musculos.patron') }}"
                  id="mbFormPatron"
                  onsubmit="return mbConfirmarPatron({{ $diasYaAsignados }})">
                @csrf
                <input type="hidden" name="anio" value="{{ $anio }}">
                <input type="hidden" name="mes" value="{{ $mes }}">
                <div class="mb-cal-patron-grid">
                    @foreach ($diasSemana as $iso => $nombreDia)
                        <div class="mb-cal-patron-item">
                            <label class="form-label">{{ $nombreDia }}</label>
                            <select name="patron[{{ $iso }}]" class="form-select form-select-sm mb-cal-select">
                                <option value="">— No aplicar —</option>
                                @foreach ($gruposMusculares as $g)
                                    <option value="{{ $g->id }}">{{ $g->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endforeach
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-check me-1"></i> Aplicar patrón a {{ $meses[$mes] }} {{ $anio }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ── Calendario ── --}}
    <div class="card">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('calendario_musculos.index', ['anio' => $anioMesAnterior, 'mes' => $mesAnterior]) }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fa-solid fa-chevron-left"></i>
                </a>
                <h5 class="mb-0">{{ $meses[$mes] }} {{ $anio }}</h5>
                <a href="{{ route('calendario_musculos.index', ['anio' => $anioMesSiguiente, 'mes' => $mesSiguiente]) }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fa-solid fa-chevron-right"></i>
                </a>
            </div>
            <form method="GET" action="{{ route('calendario_musculos.index') }}" class="d-flex gap-2">
                <select name="mes" class="form-select form-select-sm mb-cal-select" onchange="this.form.submit()">
                    @foreach ($meses as $num => $nombre)
                        <option value="{{ $num }}" @selected($num === $mes)>{{ $nombre }}</option>
                    @endforeach
                </select>
                <select name="anio" class="form-select form-select-sm mb-cal-select" onchange="this.form.submit()">
                    {{-- Rango SIEMPRE calculado a partir del año que se está viendo ($anio, que a su
                         vez por defecto es el año actual en America/La_Paz — ver el controlador). No
                         hay años fijos aquí: en 2029 esto mostrará 2028-2032 solo, sin tocar código. --}}
                    @foreach (range($anio - 1, $anio + 3) as $a)
                        <option value="{{ $a }}" @selected($a === $anio)>{{ $a }}</option>
                    @endforeach
                </select>
            </form>
        </div>
        <div class="card-body">
            <div class="mb-cal-grid">
                <div class="mb-cal-head">Lun</div>
                <div class="mb-cal-head">Mar</div>
                <div class="mb-cal-head">Mié</div>
                <div class="mb-cal-head">Jue</div>
                <div class="mb-cal-head">Vie</div>
                <div class="mb-cal-head">Sáb</div>
                <div class="mb-cal-head mb-cal-head--dom">Dom</div>

                @foreach ($semanas as $semana)
                    @foreach ($semana as $dia)
                        @php $fechaStr = $dia['fecha']->toDateString(); @endphp
                        <div class="mb-cal-day {{ !$dia['enElMes'] ? 'mb-cal-day--fuera' : '' }} {{ $dia['esDomingo'] ? 'mb-cal-day--cerrado' : '' }}">
                            <div class="mb-cal-day__num">{{ $dia['fecha']->day }}</div>

                            @if ($dia['esDomingo'])
                                <div class="mb-cal-day__cerrado">Cerrado</div>
                            @elseif ($dia['enElMes'])
                                <form method="POST" action="{{ route('calendario_musculos.dia', $fechaStr) }}" class="mb-cal-day__form">
                                    @csrf
                                    @method('PUT')
                                    <select name="id_grupo_muscular" class="form-select form-select-sm mb-cal-select" onchange="this.form.submit()">
                                        <option value="">— Sin asignar —</option>
                                        @foreach ($gruposMusculares as $g)
                                            <option value="{{ $g->id }}" @selected(optional($dia['asignacion'])->id_grupo_muscular === $g->id)>{{ $g->nombre }}</option>
                                        @endforeach
                                    </select>
                                </form>
                            @endif
                        </div>
                    @endforeach
                @endforeach
            </div>
        </div>
    </div>
</div>

{{--
    La plantilla (Gymove/custom.min.js) convierte TODO <select> de la página en
    un widget "bootstrap-select" (jQuery("select").selectpicker()), sin forma de
    excluir elementos. Con ~40 selects metidos en la cuadrícula compacta del
    calendario, ese widget rompe el layout, duplica la flecha y hace que los
    selects se amontonen.

    Solución: sacar estos <select> del DOM ANTES de que ese script los vea, y
    reinsertarlos en su lugar exacto DESPUÉS de que la plantilla ya inicializó.

    - El DETACH va aquí, inline, en JS puro (sin jQuery: todavía no está
      cargado en este punto del documento) y se ejecuta de forma síncrona al
      parsear este bloque — es decir, ANTES de que se cargue custom.min.js.
    - El REATTACH va en @push('scripts'), que en layouts/private.blade.php se
      renderiza DESPUÉS de custom.min.js. Ahí registramos nuestro propio
      jQuery(document).ready(...). jQuery dispara los callbacks de ready() en
      el orden en que se registraron, así que el nuestro SIEMPRE corre después
      del de la plantilla (Gymove.init, que es quien llama a selectpicker()),
      sin importar si ese ready() dispara de forma inmediata o diferida.
      (Nota: encadenar el reattach a window.load en vez de a este mismo
      ready() es una carrera que puede perderse si el documento ya no está en
      readyState "loading" cuando la plantilla registra su ready() — jQuery lo
      difiere con setTimeout y puede terminar corriendo después del load.)
--}}
<script>
window.__mbCalSlots = (function () {
    var slots = [];
    document.querySelectorAll('.mb-cal-select').forEach(function (el) {
        var marker = document.createComment('mb-cal-select');
        el.parentNode.insertBefore(marker, el);
        el.parentNode.removeChild(el);
        slots.push({ marker: marker, el: el });
    });
    return slots;
})();

function mbConfirmarPatron(diasYaAsignados) {
    if (diasYaAsignados > 0) {
        return confirm('Ya hay ' + diasYaAsignados + ' día(s) asignado(s) este mes. Aplicar el patrón sobrescribirá los días de la semana que elijas (los que dejes en "— No aplicar —" no se tocan). ¿Continuar?');
    }
    return true;
}
</script>

@push('scripts')
<script>
jQuery(function () {
    (window.__mbCalSlots || []).forEach(function (s) {
        s.marker.parentNode.insertBefore(s.el, s.marker);
        s.marker.parentNode.removeChild(s.marker);
    });
});
</script>
@endpush

@push('styles')
<style>
/* ── La plantilla pone .card { height: calc(100% - 30px) } (pensado para
   grids de tarjetas del mismo alto en una fila). El .content-body tiene
   min-height:940px y el .row es flex con stretch, así que ese % se resuelve
   contra la fila entera. Con DOS tarjetas apiladas (no en fila) eso infla
   cada una a ~940px de alto vacío y empuja la segunda fuera de la vista.
   Estas tarjetas están apiladas, no en grid: que midan por su contenido. ── */
.mb-cal-page .card { height: auto; }

/* ── Patrón semanal: grid que respira y se acomoda solo en pantallas angostas ── */
.mb-cal-patron-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
    gap: 1rem 1.25rem;
}
.mb-cal-patron-item .form-label { font-size: .78rem; margin-bottom: .3rem; }
.mb-cal-select { width: 100%; }

/* ── Cuadrícula del almanaque ── */
.mb-cal-grid {
    display: grid;
    grid-template-columns: repeat(7, minmax(0, 1fr));
    gap: .5rem;
}
.mb-cal-head {
    text-align: center;
    font-weight: 700;
    font-size: .75rem;
    text-transform: uppercase;
    letter-spacing: .06em;
    opacity: .65;
    padding-bottom: .4rem;
}
.mb-cal-head--dom { color: #f5365c; }
.mb-cal-day {
    min-height: 90px;
    min-width: 0;
    border-radius: .6rem;
    border: 1px solid rgba(128,128,128,.16);
    padding: .5rem;
    display: flex;
    flex-direction: column;
    gap: .4rem;
}
.mb-cal-day--fuera { opacity: .35; }
.mb-cal-day--cerrado { background: rgba(128,128,128,.08); }
.mb-cal-day__num { font-weight: 700; font-size: .85rem; }
.mb-cal-day__cerrado { font-size: .72rem; opacity: .6; text-transform: uppercase; letter-spacing: .04em; margin-top: auto; }
.mb-cal-day__form { min-width: 0; }
.mb-cal-day__form select { font-size: .72rem; padding: .3rem .5rem; }

@media (max-width: 767.98px) {
    .mb-cal-grid { grid-template-columns: repeat(7, minmax(70px, 1fr)); overflow-x: auto; }
    .mb-cal-day { min-height: 70px; }
    .mb-cal-patron-grid { grid-template-columns: repeat(auto-fit, minmax(110px, 1fr)); gap: .75rem; }
}
</style>
@endpush
@endsection
