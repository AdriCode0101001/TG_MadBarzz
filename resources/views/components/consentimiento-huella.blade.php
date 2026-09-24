{{--
    Grupo 3 legal, parte 2: sección de consentimiento para enrolar la huella
    dactilar, usada en clientes/create.blade.php y clientes/edit.blade.php.

    Props:
      cliente: instancia de Cliente ya persistida (edit), o null (create).

    Muestra UN bloque u otro según la edad calculada a partir de
    #fecha_nacimiento (que debe existir YA en el DOM antes de este
    componente): adulto → su propio checkbox; menor → datos del tutor +
    checkbox del tutor. El toggle es en vivo con JS si el operador cambia la
    fecha de nacimiento en la misma pantalla.
--}}
@props(['cliente' => null])

@php
    $fechaNacimientoActual = old('fecha_nacimiento', $cliente->persona->fecha_nacimiento ?? '');
    $esMenorInicial = $fechaNacimientoActual
        ? \Illuminate\Support\Carbon::parse($fechaNacimientoActual)->age < \App\Models\Cliente::EDAD_MINIMA_ADULTO
        : false;

    // Tras un error de validación, old() manda — igual que el resto del
    // formulario. Sin errores previos, se parte de lo que el cliente ya
    // tenía guardado (nunca premarcado por default para uno nuevo).
    $consentimientoHuellaMarcado = old('consentimiento_huella', (bool) ($cliente->consentimiento_huella_en ?? false));
    $consentimientoTutorMarcado  = old('consentimiento_tutor', (bool) ($cliente->consentimiento_tutor_en ?? false));
@endphp

<fieldset class="mb-field-group">
    <legend class="mb-field-legend"><i class="fa-solid fa-fingerprint"></i> Consentimiento de huella dactilar</legend>
    <p class="text-muted small mb-3">
        Sin este consentimiento registrado, el agente biométrico rechaza el enrolamiento de la huella
        de este cliente (puede seguir accediendo con su carnet/código QR igual).
    </p>

    {{-- ---------- Bloque ADULTO ---------- --}}
    <div id="mb-consentimiento-adulto" class="row g-3" style="{{ $esMenorInicial ? 'display:none;' : '' }}">
        <div class="col-12">
            <div class="form-check">
                <input type="checkbox" class="form-check-input" name="consentimiento_huella" id="consentimiento_huella" value="1"
                       @checked($consentimientoHuellaMarcado)>
                <label class="form-check-label" for="consentimiento_huella">
                    El cliente autorizó el registro de su huella dactilar
                </label>
            </div>
            @error('consentimiento_huella')<div class="mb-err">{{ $message }}</div>@enderror
            @if (($cliente->consentimiento_huella_en ?? null) && !$esMenorInicial)
                <small class="text-muted d-block mt-1">
                    Autorizado el {{ $cliente->consentimiento_huella_en->format('d/m/Y H:i') }}.
                </small>
            @endif
        </div>
    </div>

    {{-- ---------- Bloque MENOR ---------- --}}
    <div id="mb-consentimiento-menor" class="row g-3" style="{{ $esMenorInicial ? '' : 'display:none;' }}">
        <div class="col-12">
            <p class="text-muted small mb-2">
                Este cliente es menor de edad: para enrolar su huella hace falta el consentimiento de
                su madre/padre/tutor. Si no se completa, el cliente igual se puede registrar, pero sin
                huella habilitada.
            </p>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="tutor_nombre">Nombre del tutor</label>
            <input type="text" name="tutor_nombre" id="tutor_nombre"
                   class="form-control @error('tutor_nombre') is-invalid @enderror"
                   value="{{ old('tutor_nombre', $cliente->tutor_nombre ?? '') }}">
            @error('tutor_nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label" for="tutor_ci">Cédula de identidad del tutor</label>
            <input type="text" name="tutor_ci" id="tutor_ci"
                   class="form-control @error('tutor_ci') is-invalid @enderror"
                   value="{{ old('tutor_ci', $cliente->tutor_ci ?? '') }}">
            @error('tutor_ci')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-12">
            <div class="form-check">
                <input type="checkbox" class="form-check-input" name="consentimiento_tutor" id="consentimiento_tutor" value="1"
                       @checked($consentimientoTutorMarcado)>
                <label class="form-check-label" for="consentimiento_tutor">
                    El tutor autoriza el registro de la huella del menor
                </label>
            </div>
            @error('consentimiento_tutor')<div class="mb-err">{{ $message }}</div>@enderror
            @if ($cliente->consentimiento_tutor_en ?? null)
                <small class="text-muted d-block mt-1">
                    Autorizado el {{ $cliente->consentimiento_tutor_en->format('d/m/Y H:i') }}.
                </small>
            @endif
        </div>
    </div>
</fieldset>

<script>
(function () {
    var fecha         = document.getElementById('fecha_nacimiento');
    var bloqueAdulto   = document.getElementById('mb-consentimiento-adulto');
    var bloqueMenor    = document.getElementById('mb-consentimiento-menor');
    if (!fecha || !bloqueAdulto || !bloqueMenor) return;

    function calcularEdad(fechaStr) {
        if (!fechaStr) return null;
        var nacimiento = new Date(fechaStr);
        if (isNaN(nacimiento.getTime())) return null;

        var hoy = new Date();
        var edad = hoy.getFullYear() - nacimiento.getFullYear();
        var m = hoy.getMonth() - nacimiento.getMonth();
        if (m < 0 || (m === 0 && hoy.getDate() < nacimiento.getDate())) edad--;
        return edad;
    }

    function actualizar() {
        var edad = calcularEdad(fecha.value);
        var esMenor = edad !== null && edad < {{ \App\Models\Cliente::EDAD_MINIMA_ADULTO }};
        bloqueAdulto.style.display = esMenor ? 'none' : '';
        bloqueMenor.style.display  = esMenor ? '' : 'none';
    }

    fecha.addEventListener('change', actualizar);
    fecha.addEventListener('input', actualizar);
})();
</script>
