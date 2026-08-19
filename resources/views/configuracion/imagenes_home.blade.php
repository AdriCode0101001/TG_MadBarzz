@extends('layouts.private')

@push('styles')
    <style>
        /* ===== Selector "Posición del recorte": Brave (y otros Chromium con
           "oscurecer sitios automáticamente") re-pintaba su PROPIO control
           nativo oscuro encima del ícono que Bootstrap dibuja vía
           appearance:none + background-image — de ahí la flecha doble que
           se reportó. Se probó forzar color-scheme y reforzar appearance,
           pero el conflicto es justamente CONTRA esa combinación (suprimir
           el nativo + dibujar uno propio) — Brave lo re-agrega igual. Se
           sacó la clase "form-select" del select de esta tarjeta para dejar
           de pelear con el navegador: SIN appearance:none ni background-image
           propios, cada navegador dibuja su ÚNICA flecha nativa, sin nada
           superpuesto. Acá solo se repone color/fondo/borde a mano (mismas
           variables --mbf-* que ya usa el resto del panel) para que no quede
           un cuadro blanco sin estilo en medio de la tarjeta oscura — sin
           tocar appearance/background-image, que es lo que causaba el
           choque. Scoped a este campo puntual, no toca ningún otro campo
           del panel. ===== */
        select[name="posicion"] {
            width: 100%;
            min-height: 44px;
            padding: .6rem .85rem;
            font-size: .92rem;
            border-radius: var(--mb-radius-sm, .5rem);
            border: 1px solid var(--mbf-border, rgba(128,128,128,.28));
            background-color: var(--mbf-surface, transparent);
            color: var(--mbf-text, inherit);
        }
    </style>
@endpush

@section('contenido')
    <div class="col-12">

        <x-page-header title="Imágenes de la Home"
            subtitle="Subí o reemplazá las fotos que se ven en la página pública. Si no subís una, se sigue mostrando el placeholder actual."
            icon="fa-solid fa-images" />

        @if (session('success'))
            <div class="alert alert-success border-0 rounded-3 mb-4">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger border-0 rounded-3 mb-4">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @php
            // preserveKeys=true: sin esto, groupBy() reindexa cada grupo con
            // 0,1,2... y se pierde la CLAVE real (ej. "home_hero_1"), rompiendo
            // el id del preview y la URL del formulario de cada tarjeta.
            $grupos = collect($claves)->groupBy(fn($info) => $info[2], true);
        @endphp

        @foreach ($grupos as $nombreGrupo => $itemsDelGrupo)
            <h5 class="mb-3 mt-2">{{ $nombreGrupo }}</h5>
            <div class="row mb-4">
                @foreach ($itemsDelGrupo as $clave => $info)
                    @php
                        [$etiqueta, $tamanoRecomendado] = $info;
                        $config = $configuradas->get($clave);
                        $tieneImagen = $config && $config->ruta;

                        // Solo las claves con recorte REAL en la home (object-fit /
                        // background-size:cover sobre una caja fija) ofrecen
                        // reencuadre — "home_cta" y "home_modal_unete" se muestran
                        // COMPLETAS en la home, sin recortar, así que ahí no hay
                        // nada que reencuadrar.
                        $permiteReencuadre = \App\Services\ImagenHomeService::permiteReencuadre($clave);
                        $posicionActual = \App\Services\ImagenHomeService::posicionDe($config, $clave);
                        $objectPosition = \App\Services\ImagenHomeService::posicionCss($posicionActual);
                        $aspectRatio = str_replace('x', ' / ', $tamanoRecomendado);
                        $urlActual = $tieneImagen
                            ? \Illuminate\Support\Facades\Storage::disk('public')->url($config->ruta)
                            : null;
                    @endphp
                    <div class="col-lg-4 col-md-6 mb-3">
                        <div class="card h-100">
                            <div class="card-header">
                                <h6 class="mb-0">{{ $etiqueta }}</h6>
                            </div>
                            <div class="card-body text-center">

                                <p class="text-muted mb-2" style="font-size:.72rem;">
                                    <i class="fa-solid fa-eye me-1"></i>
                                    <strong>Así se verá en la home</strong>
                                    @if ($permiteReencuadre)
                                        — recortada a {{ $tamanoRecomendado }}
                                    @else
                                        — se muestra completa, sin recorte
                                    @endif
                                </p>

                                @if ($permiteReencuadre)
                                    {{-- Caja con el MISMO aspect-ratio + object-fit:cover + object-position
                                     que usa welcome-gymon.blade.php para esta clave: lo que se ve acá
                                     es EXACTAMENTE el recorte real de la home, no la imagen entera. --}}
                                    <div class="mb-imagen-preview mb-2" id="preview-{{ $clave }}"
                                        style="width:100%; aspect-ratio: {{ $aspectRatio }}; border-radius:8px; overflow:hidden; border:1px solid rgba(128,128,128,.25); background:#111;">
                                        @if ($tieneImagen)
                                            <img src="{{ $urlActual }}" alt="{{ $etiqueta }}"
                                                style="width:100%;height:100%;object-fit:cover;display:block;object-position:{{ $objectPosition }};">
                                        @else
                                            <div class="mb-empty"
                                                style="height:100%;display:flex;flex-direction:column;align-items:center;justify-content:center;">
                                                <div class="mb-empty-icon"><i class="fa-solid fa-image"></i></div>
                                                <p class="mb-0" style="font-size:.85rem;">Sin foto — usando placeholder
                                                </p>
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    {{-- Sin recorte real en la home: se muestra la imagen COMPLETA
                                     (object-fit:contain), no una caja recortada — mostrar un recorte
                                     acá sería mentirle al Admin sobre cómo se ve de verdad. --}}
                                    <div class="mb-imagen-preview mb-2" id="preview-{{ $clave }}"
                                        style="width:100%; min-height:140px; max-height:220px; border-radius:8px; overflow:hidden; border:1px solid rgba(128,128,128,.25); background:#111; display:flex; align-items:center; justify-content:center;">
                                        @if ($tieneImagen)
                                            <img src="{{ $urlActual }}" alt="{{ $etiqueta }}"
                                                style="max-width:100%;max-height:220px;object-fit:contain;display:block;">
                                        @else
                                            <div class="mb-empty py-3">
                                                <div class="mb-empty-icon"><i class="fa-solid fa-image"></i></div>
                                                <p class="mb-0" style="font-size:.85rem;">Sin foto — usando placeholder
                                                </p>
                                            </div>
                                        @endif
                                    </div>
                                @endif

                                <p class="text-muted mt-1 mb-3" style="font-size:.78rem;">Tamaño recomendado:
                                    {{ $tamanoRecomendado }}</p>

                                <form action="{{ route('configuracion.imagenes_home.update', $clave) }}" method="POST"
                                    enctype="multipart/form-data">
                                    @csrf

                                    @if ($permiteReencuadre)
                                        <div class="mb-2 text-start">
                                            <label class="form-label" style="font-size:.78rem;">Posición del recorte</label>
                                            <select name="posicion" class=""
                                                data-preview-target="preview-{{ $clave }}"
                                                onchange="cambiarPosicionPreviewImagenHome(this)">
                                                <option value="arriba"
                                                    {{ $posicionActual === 'arriba' ? 'selected' : '' }}>Arriba</option>
                                                <option value="centro"
                                                    {{ $posicionActual === 'centro' ? 'selected' : '' }}>Centro</option>
                                                <option value="abajo"
                                                    {{ $posicionActual === 'abajo' ? 'selected' : '' }}>Abajo</option>
                                            </select>
                                            <small class="text-muted">Cambiala y mirá el preview de arriba — se guarda al
                                                tocar {{ $tieneImagen ? 'Reemplazar' : 'Subir' }}, sin necesidad de volver
                                                a elegir el archivo.</small>
                                        </div>
                                    @endif

                                    <div class="mb-2 text-start">
                                        <input type="file" name="imagen" class="form-control form-control-sm"
                                            accept="image/*" data-preview-target="preview-{{ $clave }}"
                                            data-crop="{{ $permiteReencuadre ? '1' : '0' }}"
                                            onchange="previsualizarImagenHome(this)">
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-sm w-100">
                                        <i class="fa-solid fa-save me-1"></i> {{ $tieneImagen ? 'Reemplazar' : 'Subir' }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>

    @push('scripts')
        <script>
            // Previsualización EN EL NAVEGADOR (antes de guardar), reutilizada por las
            // ~10 tarjetas: al elegir un archivo, lo pinta en la caja de preview con el
            // MISMO aspect-ratio/object-fit/object-position que usa la home real para
            // esa clave (ver welcome-gymon.blade.php), así lo que se ve acá es el
            // recorte real, no la imagen entera. Libera la URL anterior con
            // revokeObjectURL para no acumular memoria si el Admin cambia de archivo
            // varias veces antes de guardar.
            function previsualizarImagenHome(input) {
                const previewId = input.dataset.previewTarget;
                const contenedor = document.getElementById(previewId);
                if (!contenedor || !input.files || !input.files[0]) return;

                let img = contenedor.querySelector('img');

                if (img && img.dataset.blobUrl) {
                    URL.revokeObjectURL(img.dataset.blobUrl);
                }

                if (!img) {
                    contenedor.innerHTML = '';
                    img = document.createElement('img');
                    img.style.display = 'block';
                    if (input.dataset.crop === '1') {
                        img.style.width = '100%';
                        img.style.height = '100%';
                        img.style.objectFit = 'cover';
                    } else {
                        img.style.maxWidth = '100%';
                        img.style.maxHeight = '220px';
                        img.style.objectFit = 'contain';
                    }
                    contenedor.appendChild(img);
                }

                const url = URL.createObjectURL(input.files[0]);
                img.src = url;
                img.dataset.blobUrl = url;

                if (input.dataset.crop === '1') {
                    const selector = document.querySelector('select[data-preview-target="' + previewId + '"]');
                    img.style.objectPosition = mapaPosicionImagenHome(selector ? selector.value : 'centro');
                }
            }

            // Cambiar la posición del recorte actualiza el preview EN VIVO (sea la foto
            // ya guardada o la recién elegida), sin necesidad de volver a subir nada.
            function cambiarPosicionPreviewImagenHome(select) {
                const contenedor = document.getElementById(select.dataset.previewTarget);
                const img = contenedor ? contenedor.querySelector('img') : null;
                if (img) img.style.objectPosition = mapaPosicionImagenHome(select.value);
            }

            function mapaPosicionImagenHome(valor) {
                return {
                    arriba: 'center top',
                    centro: 'center center',
                    abajo: 'center bottom'
                } [valor] || 'center center';
            }
        </script>
    @endpush
@endsection
