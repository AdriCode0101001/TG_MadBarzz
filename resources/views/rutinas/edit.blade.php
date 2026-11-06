@extends('layouts.private')

@section('contenido')
<div class="col-12 mb-rutinas-page">

    <x-page-header title="{{ $rutina ? 'Editar rutina' : 'Armar rutina' }}"
                   subtitle="{{ $fechaCarbon->translatedFormat('l, d \d\e F Y') }}"
                   icon="fa-solid fa-dumbbell">
        <span class="badge bg-primary" style="font-size:.85rem;">{{ $calendario->grupoMuscular->nombre ?? '—' }}</span>
        <a href="{{ route('rutinas.index', ['anio' => $fechaCarbon->year, 'mes' => $fechaCarbon->month]) }}" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-arrow-left me-1"></i> Volver al calendario
        </a>
    </x-page-header>

    @if ($errors->any())
        <div class="alert alert-danger border-0 rounded-3 mb-4">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div x-data="mbRutinaBuilder(@js($ejerciciosDelGrupo), @js($seleccionados), @js($esFullBody), @js($gruposParaTabs))">
        <form method="POST" action="{{ route('rutinas.update', $fechaCarbon->toDateString()) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="row g-4">
                {{-- ── Buscador de ejercicios del grupo del día (o, en Full body, de TODOS los grupos por pestaña) ── --}}
                <div class="col-lg-5">
                    <div class="card h-100">
                        <div class="card-header">
                            @if ($esFullBody)
                                <h5 class="mb-2"><i class="fa-solid fa-list-check me-2"></i>Ejercicios de <span x-text="grupoActivoNombre"></span></h5>
                                <div class="mb-rut-tabs mb-2">
                                    <template x-for="g in gruposConSinGrupo" :key="g.id">
                                        <button type="button" class="btn btn-sm mb-rut-tab"
                                                :class="grupoActivo === g.id ? 'btn-primary' : 'btn-outline-secondary'"
                                                @click="grupoActivo = g.id" x-text="g.nombre">
                                        </button>
                                    </template>
                                </div>
                            @else
                                <h5 class="mb-2"><i class="fa-solid fa-list-check me-2"></i>Ejercicios de {{ $calendario->grupoMuscular->nombre ?? '' }}</h5>
                            @endif
                            <div class="mb-searchbar">
                                <i class="fa-solid fa-magnifying-glass mb-search-icon"></i>
                                <input type="text" x-model="busqueda" class="form-control form-control-sm" placeholder="Buscar ejercicio…">
                            </div>
                        </div>
                        <div class="card-body p-2 mb-rut-pick-list">
                            <template x-for="ej in filtrados" :key="ej.id">
                                <div class="mb-rut-pick" :class="{ 'mb-rut-pick--elegido': yaElegido(ej.id) }" @click="agregar(ej)">
                                    <div class="mb-rut-pick__img">
                                        <img :src="ej.imagen ? '/storage/' + ej.imagen : ''" x-show="ej.imagen" loading="lazy" alt="">
                                        <i class="fa-solid fa-image" x-show="!ej.imagen"></i>
                                    </div>
                                    <span class="mb-rut-pick__nombre text-capitalize" x-text="ej.nombre"></span>
                                    <i class="fa-solid" :class="yaElegido(ej.id) ? 'fa-check text-success' : 'fa-plus'"></i>
                                </div>
                            </template>
                            <p class="text-muted text-center py-4" x-show="filtrados.length === 0">Ningún ejercicio coincide con la búsqueda.</p>
                        </div>
                    </div>
                </div>

                {{-- ── Mi rutina ── --}}
                <div class="col-lg-7">
                    <div class="card h-100">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h5 class="mb-0">Mi rutina <span class="text-muted fw-normal" style="font-size:.8rem;">(<span x-text="seleccionados.length"></span> ejercicio(s))</span></h5>
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fa-solid fa-floppy-disk me-1"></i> Guardar rutina
                            </button>
                        </div>
                        <div class="card-body">
                            <p class="text-muted text-center py-4" x-show="seleccionados.length === 0">
                                Todavía no agregaste ejercicios. Elegí de la lista de la izquierda.
                            </p>

                            <template x-for="(item, idx) in seleccionados" :key="item.id_ejercicio">
                                <div class="mb-rut-sel">
                                    <input type="hidden" :name="'ejercicios[' + idx + '][id_ejercicio]'" :value="item.id_ejercicio">

                                    <div class="mb-rut-sel__img">
                                        <img :src="item.imagen ? '/storage/' + item.imagen : ''" x-show="item.imagen" alt="">
                                        <i class="fa-solid fa-image" x-show="!item.imagen"></i>
                                    </div>

                                    <div class="mb-rut-sel__body">
                                        <div class="mb-rut-sel__nombre text-capitalize" x-text="item.nombre"></div>
                                        <div class="d-flex flex-wrap gap-2 mt-2">
                                            <div class="mb-rut-field" style="width:80px">
                                                <label class="mb-rut-field__label">Series</label>
                                                <input type="number" min="1" max="50" required
                                                       x-model.number="item.series"
                                                       :name="'ejercicios[' + idx + '][series]'"
                                                       class="form-control form-control-sm"
                                                       aria-label="Series">
                                            </div>
                                            <div class="mb-rut-field" style="width:110px">
                                                <label class="mb-rut-field__label">Repeticiones</label>
                                                <input type="text" required maxlength="20"
                                                       x-model="item.repeticiones"
                                                       :name="'ejercicios[' + idx + '][repeticiones]'"
                                                       class="form-control form-control-sm"
                                                       aria-label="Repeticiones"
                                                       placeholder="ej. 8-12">
                                            </div>
                                            <div class="mb-rut-field flex-grow-1" style="min-width:140px">
                                                <label class="mb-rut-field__label">Nota (opcional)</label>
                                                <input type="text" maxlength="255"
                                                       x-model="item.nota"
                                                       :name="'ejercicios[' + idx + '][nota]'"
                                                       class="form-control form-control-sm"
                                                       aria-label="Nota"
                                                       placeholder="ej. subir peso">
                                            </div>
                                        </div>
                                        <div class="mb-rut-sel__resumen">
                                            <span x-text="'Series: ' + (item.series || '—')"></span>
                                            <span class="mx-1">·</span>
                                            <span x-text="'Reps: ' + (item.repeticiones || '—')"></span>
                                        </div>

                                        <div class="mb-rut-media mt-2">
                                            <label class="mb-rut-field__label d-block mb-1">Tu gif/foto/video (opcional)</label>
                                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                                <template x-if="item.media_propio && !item.quitar_media">
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                                                        <i class="fa-solid fa-circle-check me-1"></i>Ya subiste tu archivo
                                                    </span>
                                                </template>
                                                <template x-if="item.quitar_media">
                                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle">
                                                        Se va a quitar: se mostrará el del catálogo
                                                    </span>
                                                </template>
                                                <input type="file" accept=".jpg,.jpeg,.png,.gif,.mp4,.webm"
                                                       :name="'ejercicios[' + idx + '][media]'"
                                                       @change="item.nuevoArchivoNombre = $event.target.files[0]?.name || ''; if ($event.target.files[0]) item.quitar_media = false;"
                                                       class="form-control form-control-sm" style="max-width:230px">
                                                <button type="button" class="btn btn-sm btn-outline-danger"
                                                        x-show="item.media_propio && !item.quitar_media" @click="item.quitar_media = true">
                                                    Quitar
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-secondary"
                                                        x-show="item.quitar_media" @click="item.quitar_media = false">
                                                    Deshacer
                                                </button>
                                            </div>
                                            <p class="text-muted mb-0 mt-1" style="font-size:.72rem;" x-show="item.nuevoArchivoNombre" x-text="'Seleccionado: ' + item.nuevoArchivoNombre"></p>
                                            <input type="hidden" :name="'ejercicios[' + idx + '][quitar_media]'" :value="item.quitar_media ? 1 : 0">
                                        </div>
                                    </div>

                                    <div class="mb-rut-sel__acciones">
                                        <button type="button" class="btn btn-sm btn-outline-secondary" @click="subir(idx)" :disabled="idx === 0" title="Subir">
                                            <i class="fa-solid fa-chevron-up"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" @click="bajar(idx)" :disabled="idx === seleccionados.length - 1" title="Bajar">
                                            <i class="fa-solid fa-chevron-down"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger" @click="quitar(idx)" title="Quitar">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                        @if ($rutina)
                            <div class="card-footer d-flex justify-content-end">
                                <button type="button" class="btn btn-sm btn-outline-danger"
                                        data-confirm="¿Eliminar toda la rutina de este día?"
                                        onclick="document.getElementById('mbFormEliminarRutina').submit()">
                                    <i class="fa-solid fa-trash me-1"></i> Eliminar rutina
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </form>
    </div>

    @if ($rutina)
        <form id="mbFormEliminarRutina" action="{{ route('rutinas.destroy', $rutina) }}" method="POST" class="d-none">
            @csrf
            @method('DELETE')
        </form>
    @endif
</div>

<script>
function mbRutinaBuilder(disponibles, seleccionadosIniciales, esFullBody, grupos) {
    disponibles = disponibles || [];
    grupos = grupos || [];

    // "Sin grupo" es una pestaña más si hay ejercicios del catálogo sin
    // grupo muscular asignado (existen: el mapeo del dataset no cubre todas
    // las categorías).
    const gruposConSinGrupo = grupos.map(g => ({ ...g }));
    if (disponibles.some(e => e.id_grupo_muscular === null)) {
        gruposConSinGrupo.push({ id: 'sin_grupo', nombre: 'Sin grupo' });
    }

    return {
        disponibles,
        seleccionados: (seleccionadosIniciales || []).map(s => ({ ...s })),
        busqueda: '',
        esFullBody: !!esFullBody,
        gruposConSinGrupo,
        grupoActivo: gruposConSinGrupo.length ? gruposConSinGrupo[0].id : null,
        get grupoActivoNombre() {
            const g = this.gruposConSinGrupo.find(g => g.id === this.grupoActivo);
            return g ? g.nombre : '';
        },
        get filtrados() {
            let lista = this.disponibles;
            if (this.esFullBody) {
                lista = this.grupoActivo === 'sin_grupo'
                    ? lista.filter(e => e.id_grupo_muscular === null)
                    : lista.filter(e => e.id_grupo_muscular === this.grupoActivo);
            }
            const q = this.busqueda.trim().toLowerCase();
            if (q) lista = lista.filter(e => e.nombre.toLowerCase().includes(q));
            return lista;
        },
        yaElegido(id) {
            return this.seleccionados.some(s => s.id_ejercicio === id);
        },
        agregar(ej) {
            if (this.yaElegido(ej.id)) return;
            this.seleccionados.push({
                id_ejercicio: ej.id,
                nombre: ej.nombre,
                imagen: ej.imagen,
                series: 3,
                repeticiones: '10',
                nota: '',
                media_propio: null,
                quitar_media: false,
                nuevoArchivoNombre: '',
            });
        },
        quitar(idx) {
            this.seleccionados.splice(idx, 1);
        },
        subir(idx) {
            if (idx === 0) return;
            const arr = this.seleccionados;
            [arr[idx - 1], arr[idx]] = [arr[idx], arr[idx - 1]];
        },
        bajar(idx) {
            if (idx === this.seleccionados.length - 1) return;
            const arr = this.seleccionados;
            [arr[idx + 1], arr[idx]] = [arr[idx], arr[idx + 1]];
        },
    };
}
</script>

@push('styles')
<style>
.mb-rutinas-page .card { height: auto; }

/* La plantilla pone .card-header en flex-row por defecto: sin esto, el
   título "Ejercicios de X", las pestañas y el buscador (3 hijos) quedan uno
   al lado del otro en vez de apilados. La cabecera de "Mi rutina" no se ve
   afectada: usa la utilidad Bootstrap d-flex, que tiene !important. */
.mb-rutinas-page .card-header { display: block; }

.mb-rut-tabs { display: flex; flex-wrap: wrap; gap: .4rem; }
.mb-rut-tab { font-size: .78rem; }

.mb-rut-pick-list { max-height: 620px; overflow-y: auto; }
.mb-rut-pick {
    display: flex; align-items: center; gap: .6rem;
    padding: .5rem .6rem; border-radius: .5rem; cursor: pointer;
    transition: background .12s;
}
.mb-rut-pick:hover { background: rgba(255, 107, 26,.1); }
.mb-rut-pick--elegido { background: rgba(30,201,139,.1); }
.mb-rut-pick__img {
    width: 40px; height: 40px; border-radius: .4rem; overflow: hidden; flex: none;
    background: rgba(128,128,128,.1); display: flex; align-items: center; justify-content: center;
}
.mb-rut-pick__img img { width: 100%; height: 100%; object-fit: cover; }
.mb-rut-pick__img i { opacity: .3; }
.mb-rut-pick__nombre { flex: 1; font-size: .85rem; }

.mb-rut-sel {
    display: flex; align-items: flex-start; gap: .75rem;
    padding: .75rem 0; border-bottom: 1px solid rgba(128,128,128,.14);
}
.mb-rut-sel:last-child { border-bottom: none; }
.mb-rut-sel__img {
    width: 48px; height: 48px; border-radius: .5rem; overflow: hidden; flex: none;
    background: rgba(128,128,128,.1); display: flex; align-items: center; justify-content: center;
}
.mb-rut-sel__img img { width: 100%; height: 100%; object-fit: cover; }
.mb-rut-sel__img i { opacity: .3; }
.mb-rut-sel__body { flex: 1; min-width: 0; }
.mb-rut-sel__nombre { font-weight: 600; font-size: .88rem; }
.mb-rut-sel__acciones { display: flex; flex-direction: column; gap: .25rem; flex: none; }

/* ── etiquetas Series / Repeticiones / Nota ── */
.mb-rut-field { display: flex; flex-direction: column; }
.mb-rut-field__label {
    font-size: .68rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: .04em; opacity: .6; margin-bottom: .2rem;
}
.mb-rut-sel__resumen { font-size: .74rem; opacity: .6; margin-top: .4rem; }

@media (max-width: 575.98px) {
    .mb-rut-sel { flex-wrap: wrap; }
    .mb-rut-sel__acciones { flex-direction: row; }
}
</style>
@endpush
@endsection
