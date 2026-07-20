{{--
    Modal reutilizable para "Ver GIF" de un ejercicio: gif/foto/video a buen
    tamaño y responsive + instrucciones en español (pasos numerados) +
    atribución.

    Uso: incluir <x-ejercicio-gif-modal /> una vez en la página, y en cada
    botón "Ver GIF" usar la clase `mb-ver-gif` con estos data-*:
      data-gif (url de la imagen/gif o del video), data-nombre,
      data-atribucion, data-instrucciones
    El tipo (imagen o video) se detecta por la extensión del archivo.
--}}
<div class="modal fade" id="mbGifModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable mb-gif-modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-capitalize" id="mbGifModalNombre">Ejercicio</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="text-center">
                    <img id="mbGifModalImg" src="" alt="" class="img-fluid mb-gif-modal-img">
                    <video id="mbGifModalVideo" class="mb-gif-modal-img" controls playsinline style="display:none;"></video>
                </div>
                <div class="mb-gif-modal-instrucciones mt-3" id="mbGifModalInstruccionesWrap">
                    <h6 class="mb-gif-modal-instrucciones__title">
                        <i class="fa-solid fa-list-check me-1"></i>Cómo hacer el ejercicio
                    </h6>
                    <ol class="mb-gif-modal-instrucciones__lista" id="mbGifModalInstrucciones"></ol>
                </div>
                <p class="text-muted mb-0 mt-2 small text-center" id="mbGifModalAtribucion"></p>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.mb-gif-modal-dialog { max-width: 640px; }
.mb-gif-modal-dialog .modal-content { max-height: 88vh; }
.mb-gif-modal-img {
    display: block;
    width: 100%;
    max-width: 420px;
    height: auto;
    max-height: 42vh;
    margin: 0 auto;
    object-fit: contain;
    border-radius: .5rem;
    background: #fff;
}
.mb-gif-modal-instrucciones__title {
    font-size: .85rem;
    font-weight: 700;
    margin-bottom: .5rem;
}
.mb-gif-modal-instrucciones__lista {
    list-style: decimal outside;
    padding-left: 1.1rem;
    margin-bottom: 0;
    font-size: .85rem;
    line-height: 1.5;
}
.mb-gif-modal-instrucciones__lista li { list-style-type: decimal; margin-bottom: .4rem; }
.mb-gif-modal-instrucciones__lista li:last-child { margin-bottom: 0; }
@media (max-width: 575.98px) {
    .mb-gif-modal-dialog { max-width: 100%; margin: .5rem; }
    .mb-gif-modal-dialog .modal-content { max-height: 92vh; }
    .mb-gif-modal-img { max-width: 100%; max-height: 34vh; }
    .mb-gif-modal-instrucciones__lista { font-size: .82rem; }
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var modalEl = document.getElementById('mbGifModal');
    if (!modalEl) return;

    document.querySelectorAll('.mb-ver-gif').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var img = document.getElementById('mbGifModalImg');
            var video = document.getElementById('mbGifModalVideo');
            var src = btn.dataset.gif || '';
            var esVideo = /\.(mp4|webm)(\?.*)?$/i.test(src);

            if (esVideo) {
                img.style.display = 'none';
                img.src = '';
                video.src = src;
                video.style.display = '';
            } else {
                video.pause();
                video.removeAttribute('src');
                video.load();
                video.style.display = 'none';
                img.src = src;
                img.style.display = '';
            }

            document.getElementById('mbGifModalNombre').textContent = btn.dataset.nombre || 'Ejercicio';
            document.getElementById('mbGifModalAtribucion').textContent = btn.dataset.atribucion || '';

            var lista = document.getElementById('mbGifModalInstrucciones');
            var wrap = document.getElementById('mbGifModalInstruccionesWrap');
            var texto = (btn.dataset.instrucciones || '').trim();
            lista.innerHTML = '';

            if (texto === '') {
                wrap.style.display = 'none';
            } else {
                wrap.style.display = '';
                texto.split(/(?<=[.!?])\s+/).map(function (paso) {
                    return paso.trim();
                }).filter(Boolean).forEach(function (paso) {
                    var li = document.createElement('li');
                    li.textContent = paso;
                    lista.appendChild(li);
                });
            }

            var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        });
    });

    modalEl.addEventListener('hidden.bs.modal', function () {
        document.getElementById('mbGifModalImg').src = '';
        var video = document.getElementById('mbGifModalVideo');
        video.pause();
        video.removeAttribute('src');
        video.load();
    });
});
</script>
@endpush
