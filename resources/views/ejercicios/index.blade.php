@extends('layouts.private')

@section('contenido')
<div class="col-12 mb-ejercicios-page">

    <x-page-header title="Ejercicios" subtitle="Catálogo base importado del dataset — verificación de la importación" icon="fa-solid fa-book-open">
        <span class="badge bg-secondary">{{ $ejercicios->total() }} en total</span>
        <span class="badge bg-warning text-dark">{{ $totalSinGrupo }} sin grupo</span>
    </x-page-header>

    <div class="card">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
            <form method="GET" action="{{ route('ejercicios.index') }}" class="d-flex flex-wrap gap-2 align-items-center">
                <div class="mb-searchbar" style="min-width:220px">
                    <i class="fa-solid fa-magnifying-glass mb-search-icon"></i>
                    <input type="text" name="buscar" class="form-control form-control-sm" placeholder="Buscar por nombre…" value="{{ request('buscar') }}">
                </div>
                <select name="grupo" class="form-select form-select-sm" style="max-width:220px" onchange="this.form.submit()">
                    <option value="">— Todos los grupos —</option>
                    @foreach ($gruposMusculares as $g)
                        <option value="{{ $g->id }}" @selected(request('grupo') == $g->id)>{{ $g->nombre }}</option>
                    @endforeach
                    <option value="sin_grupo" @selected(request('grupo') === 'sin_grupo')>— Sin grupo —</option>
                </select>
                <button type="submit" class="btn btn-sm btn-primary">Filtrar</button>
                @if (request('buscar') || request('grupo'))
                    <a href="{{ route('ejercicios.index') }}" class="btn btn-sm btn-outline-secondary">Limpiar</a>
                @endif
            </form>
        </div>
        <div class="card-body">
            @if ($ejercicios->isEmpty())
                <div class="mb-empty">
                    <div class="mb-empty-icon"><i class="fa-solid fa-book-open"></i></div>
                    <p class="mb-2">No hay ejercicios que coincidan con el filtro.</p>
                </div>
            @else
                <div class="mb-ejercicios-grid">
                    @foreach ($ejercicios as $ej)
                        <div class="mb-ejercicio-card">
                            <div class="mb-ejercicio-card__media">
                                @if ($ej->imagen)
                                    <img src="{{ asset('storage/' . $ej->imagen) }}" alt="{{ $ej->nombre }}" loading="lazy">
                                @else
                                    <div class="mb-ejercicio-card__sinmedia"><i class="fa-solid fa-image"></i></div>
                                @endif
                            </div>
                            <div class="mb-ejercicio-card__body">
                                <div class="mb-ejercicio-card__nombre text-capitalize">{{ $ej->nombre }}</div>
                                <div class="mb-ejercicio-card__meta">
                                    @if ($ej->grupoMuscular)
                                        <span class="badge bg-primary">{{ $ej->grupoMuscular->nombre }}</span>
                                    @else
                                        <span class="badge bg-secondary">Sin grupo</span>
                                    @endif
                                    @if ($ej->category)
                                        <span class="badge bg-light text-dark text-capitalize">{{ $ej->category }}</span>
                                    @endif
                                </div>
                                <div class="mb-ejercicio-card__sub text-capitalize">
                                    @if ($ej->target)<span><i class="fa-solid fa-bullseye me-1"></i>{{ $ej->target }}</span>@endif
                                    @if ($ej->equipment)<span><i class="fa-solid fa-dumbbell me-1"></i>{{ $ej->equipment }}</span>@endif
                                </div>
                                @if ($ej->gif)
                                    <button type="button" class="btn btn-link p-0 mb-ejercicio-card__gif mb-ver-gif"
                                            data-gif="{{ asset('storage/' . $ej->gif) }}"
                                            data-nombre="{{ $ej->nombre }}"
                                            data-atribucion="{{ $ej->atribucion }}"
                                            data-instrucciones="{{ $ej->instrucciones }}">
                                        <i class="fa-solid fa-film me-1"></i>Ver GIF
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-3">
                    {{-- La vista de paginación por defecto de Laravel es Tailwind (SVGs con
                         clases w-5/h-5 que no existen aquí: este layout es Bootstrap, no
                         Tailwind). Sin esas clases el <svg> no tiene tamaño y el navegador lo
                         renderiza a su tamaño intrínseco (~300px), lo que se veía como una
                         forma gigante mal renderizada e inflaba el alto de la página. La
                         vista bootstrap-5 (incluida en Laravel) usa <ul class="pagination">
                         normal, ya estilizado por el Bootstrap que esta pantalla sí carga. --}}
                    {{ $ejercicios->withQueryString()->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </div>
    </div>
</div>

<x-ejercicio-gif-modal />

@push('styles')
<style>
/* La plantilla pone .card { height: calc(100% - 30px) } (pensado para grids
   de tarjetas del mismo alto en una fila). El .content-body/.row de este
   layout puede terminar más alto que el contenido real (ej. por el sidebar
   con "Operaciones" expandido), y ese % infla la tarjeta con espacio vacío
   debajo de la paginación — mismo bug ya corregido en el calendario. Que
   esta tarjeta mida por su propio contenido. */
.mb-ejercicios-page .card { height: auto; }

.mb-ejercicios-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 1rem;
}
.mb-ejercicio-card {
    border: 1px solid rgba(128,128,128,.16);
    border-radius: .75rem;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}
.mb-ejercicio-card__media {
    width: 100%;
    aspect-ratio: 1 / 1;
    background: rgba(128,128,128,.08);
    display: flex;
    align-items: center;
    justify-content: center;
}
.mb-ejercicio-card__media img { width: 100%; height: 100%; object-fit: cover; }
.mb-ejercicio-card__sinmedia { font-size: 2rem; opacity: .3; }
.mb-ejercicio-card__body { padding: .65rem .75rem; display: flex; flex-direction: column; gap: .35rem; }
.mb-ejercicio-card__nombre { font-weight: 700; font-size: .85rem; line-height: 1.25; }
.mb-ejercicio-card__meta { display: flex; flex-wrap: wrap; gap: .3rem; }
.mb-ejercicio-card__sub { display: flex; flex-direction: column; gap: .15rem; font-size: .72rem; opacity: .7; text-transform: capitalize; }
.mb-ejercicio-card__gif { font-size: .72rem; margin-top: .2rem; }
</style>
@endpush
@endsection
