@extends('layouts.private')

@section('contenido')

<div class="col-12">
    <div class="row justify-content-center">

        {{-- ── Escáner ── --}}
        <div class="col-lg-5 col-md-6 mb-4">
            <div class="card mb-qr-card">
                <div class="card-header border-0 pb-0">
                    <h4 class="card-title"><i class="fa-solid fa-qrcode me-2"></i>Registrar asistencia por QR</h4>
                </div>
                <div class="card-body">
                    <p class="mb-qr-ayuda">
                        Apunta la cámara al código QR del carnet del cliente.
                        El registro es automático al detectar el código.
                    </p>

                    <div class="mb-3">
                        <label for="mbQrCamara" class="form-label">Cámara</label>
                        <select id="mbQrCamara" class="form-control"></select>
                    </div>

                    <div id="mbQrLector" class="mb-qr-lector"></div>

                    <div id="mbQrEstadoCam" class="mb-qr-estado-cam">Iniciando cámara…</div>

                    <button id="mbQrBoton" type="button" class="btn btn-primary w-100 mt-3" style="display:none;">
                        <i class="fa-solid fa-camera me-2"></i>Iniciar cámara
                    </button>
                </div>
            </div>
        </div>

        {{-- ── Resultado ── --}}
        <div class="col-lg-5 col-md-6 mb-4">
            <div class="card mb-qr-card">
                <div class="card-header border-0 pb-0">
                    <h4 class="card-title"><i class="fa-solid fa-clipboard-check me-2"></i>Resultado</h4>
                </div>
                <div class="card-body">

                    <div id="mbQrResultado" class="mb-qr-resultado mb-qr-resultado--espera">
                        <div class="mb-qr-resultado__icono"><i class="fa-solid fa-expand"></i></div>
                        <div class="mb-qr-resultado__titulo">Esperando escaneo…</div>
                        <div class="mb-qr-resultado__detalle">El resultado del registro aparecerá aquí.</div>
                    </div>

                    <div class="mb-qr-log">
                        <div class="mb-qr-log__titulo">Últimos escaneos</div>
                        <ul id="mbQrLog" class="mb-qr-log__lista"></ul>
                    </div>

                </div>
            </div>
        </div>

    </div>
</div>

@push('styles')
<style>
.mb-qr-card { border-radius: 1rem; }
.mb-qr-ayuda { color: #94a3b8; font-size: .85rem; }
.mb-qr-lector {
    width: 100%;
    min-height: 260px;
    border: 2px dashed #ff6b1a;
    border-radius: .75rem;
    overflow: hidden;
    background: #0f172a;
}
.mb-qr-lector video { border-radius: .6rem; }
.mb-qr-estado-cam { color: #94a3b8; font-size: .8rem; margin-top: .5rem; text-align: center; }

.mb-qr-resultado {
    border-radius: .75rem;
    padding: 1.6rem 1rem;
    text-align: center;
    transition: background .2s ease;
    user-select: text;
}
.mb-qr-log { user-select: text; }
.mb-qr-resultado--espera    { background: rgba(148,163,184,.08); color: #94a3b8; }
.mb-qr-resultado--ok        { background: rgba(16,185,129,.14);  color: #10b981; }
.mb-qr-resultado--error     { background: rgba(239,68,68,.14);   color: #ef4444; }
.mb-qr-resultado--duplicado { background: rgba(245,158,11,.14);  color: #f59e0b; }

.mb-qr-resultado__icono  { font-size: 2.6rem; }
.mb-qr-resultado__avatar {
    width: 84px; height: 84px; margin: 0 auto .6rem;
    border-radius: 50%; overflow: hidden;
    display: flex; align-items: center; justify-content: center;
    background: rgba(255,255,255,.12);
    font-size: 2rem; font-weight: 800; color: inherit;
}
.mb-qr-resultado__avatar img { width: 100%; height: 100%; object-fit: cover; }
.mb-qr-resultado__titulo  { font-size: 1.35rem; font-weight: 800; margin-top: .4rem; overflow-wrap: break-word; }
.mb-qr-resultado__detalle { font-size: .95rem; margin-top: .3rem; color: inherit; opacity: .9; }
.mb-qr-resultado__hora    { font-size: 1.05rem; font-weight: 700; margin-top: .35rem; }

.mb-qr-log { margin-top: 1.2rem; }
.mb-qr-log__titulo { color: #94a3b8; font-size: .75rem; letter-spacing: 1.5px; text-transform: uppercase; margin-bottom: .4rem; }
.mb-qr-log__lista { list-style: none; padding: 0; margin: 0; }
.mb-qr-log__lista li {
    display: flex; justify-content: space-between; gap: .5rem;
    padding: .35rem 0; font-size: .85rem;
    border-bottom: 1px solid rgba(148,163,184,.15);
}
.mb-qr-log__lista li .mb-ok        { color: #10b981; }
.mb-qr-log__lista li .mb-error     { color: #ef4444; }
.mb-qr-log__lista li .mb-duplicado { color: #f59e0b; }
</style>
@endpush

@push('scripts')
<script src="{{ asset('assets_private/vendor/html5-qrcode/html5-qrcode.min.js') }}"></script>
<script>
(function () {
    var CSRF        = @json(csrf_token());
    var URL_REG     = @json(route('asistencias.qr.registrar'));
    var PAUSA_MS    = 3500;  // tiempo que se muestra el resultado antes de volver a escanear
    var ANTIRREBOTE = 5000;  // ignora el mismo token repetido dentro de esta ventana

    var lector      = null;
    var camaraId    = null;
    var ocupado     = false;
    var ultimoToken = null;
    var ultimoTs    = 0;

    var $sel    = document.getElementById('mbQrCamara');
    var $estado = document.getElementById('mbQrEstadoCam');
    var $boton  = document.getElementById('mbQrBoton');
    var $res    = document.getElementById('mbQrResultado');
    var $log    = document.getElementById('mbQrLog');

    // ── beep de confirmación (WebAudio, sin archivos) ──
    function beep(tipo) {
        try {
            var ctx = new (window.AudioContext || window.webkitAudioContext)();
            var osc = ctx.createOscillator();
            var gan = ctx.createGain();
            osc.connect(gan); gan.connect(ctx.destination);
            osc.frequency.value = tipo === 'ok' ? 880 : (tipo === 'duplicado' ? 520 : 220);
            gan.gain.setValueAtTime(.15, ctx.currentTime);
            osc.start();
            osc.stop(ctx.currentTime + (tipo === 'ok' ? .15 : .35));
        } catch (e) { /* sin audio, no es crítico */ }
    }

    // ── panel de resultado ──
    function esc(t) {
        var d = document.createElement('div');
        d.textContent = t == null ? '' : t;
        return d.innerHTML;
    }

    function mostrarResultado(data) {
        var clase, icono;
        if (data.estado === 'ok')             { clase = 'ok';        icono = 'fa-circle-check'; }
        else if (data.estado === 'duplicado') { clase = 'duplicado'; icono = 'fa-clock-rotate-left'; }
        else                                  { clase = 'error';     icono = 'fa-circle-xmark'; }

        var avatar = '';
        if (data.cliente) {
            avatar = data.cliente.foto
                ? '<div class="mb-qr-resultado__avatar"><img src="' + esc(data.cliente.foto) + '" alt=""></div>'
                : '<div class="mb-qr-resultado__avatar">' + esc(data.cliente.iniciales || '?') + '</div>';
        } else {
            avatar = '<div class="mb-qr-resultado__icono"><i class="fa-solid ' + icono + '"></i></div>';
        }

        // Fecha/hora de la marcación (hora local de Bolivia, la calcula el
        // servidor): en "registrada" se ve la fecha completa; en "ya marcó
        // hoy"/"duplicado" basta la hora (siempre es el mismo día). En los
        // rechazos por membresía no aplica (el servidor no manda 'fecha').
        var lineaHora = '';
        if (data.estado === 'ok' && data.fecha && data.hora) {
            lineaHora = '<div class="mb-qr-resultado__hora">Registrada el ' + esc(data.fecha) + ' a las ' + esc(data.hora) + '</div>';
        } else if (data.estado === 'duplicado' && data.hora) {
            lineaHora = '<div class="mb-qr-resultado__hora">Marcó a las ' + esc(data.hora) + '</div>';
        }

        $res.className = 'mb-qr-resultado mb-qr-resultado--' + clase;
        $res.innerHTML =
            avatar +
            (data.cliente ? '<div class="mb-qr-resultado__titulo">' + esc(data.cliente.nombre) + '</div>' : '') +
            '<div class="mb-qr-resultado__detalle"><i class="fa-solid ' + icono + ' me-1"></i>' + esc(data.mensaje) + '</div>' +
            lineaHora;

        // registro en la lista de últimos escaneos (con la hora del evento)
        var li = document.createElement('li');
        li.innerHTML =
            '<span>' + esc(data.cliente ? data.cliente.nombre : 'Carnet no válido') + '</span>' +
            '<span class="mb-' + clase + '">' + esc(data.estado === 'ok' ? '✔ Registrada' : data.mensaje) +
                (data.hora ? ' · ' + esc(data.hora) : '') + '</span>';
        $log.prepend(li);
        while ($log.children.length > 6) $log.removeChild($log.lastChild);
    }

    // ── envío al servidor ──
    function registrar(token) {
        fetch(URL_REG, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF
            },
            body: JSON.stringify({ qr_token: token })
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            mostrarResultado(data);
            beep(data.estado === 'ok' ? 'ok' : (data.estado === 'duplicado' ? 'duplicado' : 'error'));
        })
        .catch(function () {
            mostrarResultado({ estado: 'error', mensaje: 'Error de conexión con el servidor. Intenta de nuevo.' });
            beep('error');
        })
        .finally(function () {
            // tras unos segundos vuelve a escanear (flujo de fila); el resultado
            // queda en pantalla (se puede leer y copiar) hasta el siguiente escaneo
            setTimeout(function () {
                try { lector.resume(); } catch (e) {}
                ocupado = false;
            }, PAUSA_MS);
        });
    }

    // ── lectura del QR ──
    function alLeer(texto) {
        var ahora = Date.now();
        if (ocupado) return;
        if (texto === ultimoToken && (ahora - ultimoTs) < ANTIRREBOTE) return;

        ocupado     = true;
        ultimoToken = texto;
        ultimoTs    = ahora;

        try { lector.pause(true); } catch (e) {}
        registrar(texto);
    }

    // ── cámara ──
    function iniciar(id) {
        $estado.textContent = 'Iniciando cámara…';
        lector.start(
            id,
            { fps: 10, qrbox: { width: 240, height: 240 } },
            alLeer,
            function () { /* frames sin QR: silencio */ }
        ).then(function () {
            camaraId = id;
            $estado.textContent = 'Cámara activa. Listo para escanear.';
            $boton.style.display = 'none';
        }).catch(function (err) {
            $estado.textContent = 'No se pudo iniciar la cámara: ' + err +
                '. Verifica los permisos del navegador (la cámara requiere HTTPS o localhost).';
            $boton.style.display = 'block';
        });
    }

    function cambiarCamara(id) {
        if (!lector) return;
        var p = camaraId ? lector.stop() : Promise.resolve();
        camaraId = null;
        p.then(function () { iniciar(id); })
         .catch(function () { iniciar(id); });
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (typeof Html5Qrcode === 'undefined') {
            $estado.textContent = 'No se pudo cargar la librería de escaneo.';
            return;
        }

        lector = new Html5Qrcode('mbQrLector');

        Html5Qrcode.getCameras().then(function (camaras) {
            if (!camaras || !camaras.length) {
                $estado.textContent = 'No se encontró ninguna cámara en este dispositivo.';
                return;
            }

            camaras.forEach(function (c, i) {
                var op = document.createElement('option');
                op.value = c.id;
                op.textContent = c.label || ('Cámara ' + (i + 1));
                $sel.appendChild(op);
            });

            // en celulares, preferir la cámara trasera si se puede identificar
            var trasera = camaras.find(function (c) {
                return /back|rear|trasera|environment/i.test(c.label || '');
            });
            var inicial = (trasera || camaras[0]).id;
            $sel.value = inicial;

            $sel.addEventListener('change', function () { cambiarCamara($sel.value); });
            $boton.addEventListener('click', function () { iniciar($sel.value); });

            iniciar(inicial);
        }).catch(function (err) {
            $estado.textContent = 'No se pudo acceder a las cámaras: ' + err +
                '. Concede el permiso de cámara (requiere HTTPS o localhost).';
            $boton.style.display = 'block';
        });
    });
})();
</script>
@endpush

@endsection
