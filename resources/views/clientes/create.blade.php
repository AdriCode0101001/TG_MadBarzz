@extends('layouts.private')

@section('contenido')
<div class="col-12">

    <x-page-header title="Registrar cliente" subtitle="Crea la persona, su usuario de acceso y su ficha de cliente" icon="fa-solid fa-user-plus">
        <a href="{{ route('clientes.index') }}" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i> Volver
        </a>
    </x-page-header>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('clientes.store') }}" method="POST" autocomplete="off">
                @csrf

                {{-- ---------- Datos de la persona ---------- --}}
                <fieldset class="mb-field-group">
                    <legend class="mb-field-legend"><i class="fa-solid fa-id-card"></i> Datos de la persona</legend>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="nombre_completo">Nombres</label>
                            <input type="text" name="nombre_completo" class="form-control @error('nombre_completo') is-invalid @enderror" value="{{ old('nombre_completo') }}" placeholder="Ej. Juan Carlos" required id="nombre_completo">
                            @error('nombre_completo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="apellido_paterno">Apellido paterno</label>
                            <input type="text" name="apellido_paterno" class="form-control @error('apellido_paterno') is-invalid @enderror" value="{{ old('apellido_paterno') }}" required id="apellido_paterno">
                            @error('apellido_paterno')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="apellido_materno">Apellido materno</label>
                            <input type="text" name="apellido_materno" class="form-control @error('apellido_materno') is-invalid @enderror" value="{{ old('apellido_materno') }}" required id="apellido_materno">
                            @error('apellido_materno')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="ci">Cédula de identidad</label>
                            <input type="text" name="ci" class="form-control @error('ci') is-invalid @enderror" value="{{ old('ci') }}" required id="ci">
                            @error('ci')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Teléfono</label>
                            <x-phone-input :required="true" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="genero">Género</label>
                            <select name="genero" class=" @error('genero') is-invalid @enderror" required id="genero">
                                <option value="">Seleccione…</option>
                                <option value="masculino" @selected(old('genero')=='masculino')>Masculino</option>
                                <option value="femenino"  @selected(old('genero')=='femenino')>Femenino</option>
                                <option value="otro"      @selected(old('genero')=='otro')>Otro</option>
                            </select>
                            @error('genero')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="fecha_nacimiento">Fecha de nacimiento</label>
                            <input type="date" name="fecha_nacimiento" class="form-control @error('fecha_nacimiento') is-invalid @enderror" value="{{ old('fecha_nacimiento') }}" required id="fecha_nacimiento">
                            @error('fecha_nacimiento')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </fieldset>

                {{-- ---------- Datos del usuario ---------- --}}
                <fieldset class="mb-field-group">
                    <legend class="mb-field-legend"><i class="fa-solid fa-circle-user"></i> Datos de acceso (usuario)</legend>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="mbUsernamePreview">Nombre de usuario (automático)</label>
                            <input type="text" id="mbUsernamePreview" class="form-control"
                                   value="Se genera solo: nombre.apellido.inicial" readonly disabled>
                            <small class="text-muted">Vista previa — si ya existe, el sistema le agrega un número. Podrá cambiarse después desde la edición.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="email">Correo electrónico</label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required id="email">
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="password">Contraseña</label>
                            <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required id="password">
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="password_confirmation">Confirmar contraseña</label>
                            <input type="password" name="password_confirmation" class="form-control" required id="password_confirmation">
                        </div>
                    </div>
                </fieldset>

                {{-- ---------- Consentimiento de huella (Grupo 3 legal, parte 2) ---------- --}}
                <x-consentimiento-huella />

                {{-- ---------- Datos del cliente ---------- --}}
                <fieldset class="mb-field-group">
                    <legend class="mb-field-legend"><i class="fa-solid fa-dumbbell"></i> Datos del cliente</legend>
                    <div class="row g-3">
                        {{-- Los días asistidos siempre inician en 0 (lo fija el servidor). La
                             plantilla de huella en sí se enrola después desde el agente
                             biométrico, no aquí — acá solo se registra el CONSENTIMIENTO (arriba),
                             que el agente exige antes de dejar enrolarla. --}}
                        <div class="col-md-4">
                            <label class="form-label" for="estado">Estado</label>
                            <select name="estado" class="" required id="estado">
                                <option value="activo"   @selected(old('estado','activo')=='activo')>Activo</option>
                                <option value="inactivo" @selected(old('estado')=='inactivo')>Inactivo</option>
                            </select>
                        </div>
                    </div>
                </fieldset>

                <div class="d-flex gap-2 justify-content-end">
                    <a href="{{ route('clientes.index') }}" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-floppy-disk"></i> Guardar cliente
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Vista previa EN VIVO del nombre de usuario. Usa la misma regla que el
// servidor (UsernameService): primer nombre + "." + apellido paterno + "."
// + inicial del materno, en minúsculas y sin tildes/espacios. Es solo
// informativa: el username definitivo lo genera el servidor (con el número
// incremental si ya existe).
(function () {
    var inNombre  = document.querySelector('input[name="nombre_completo"]');
    var inPaterno = document.querySelector('input[name="apellido_paterno"]');
    var inMaterno = document.querySelector('input[name="apellido_materno"]');
    var out       = document.getElementById('mbUsernamePreview');

    if (!inNombre || !inPaterno || !inMaterno || !out) return;

    var TEXTO_VACIO = 'Se genera solo: nombre.apellido.inicial';

    // minúsculas, sin tildes (ñ → n) y solo letras/números
    function limpiar(texto) {
        return texto.normalize('NFD')
            .replace(/[̀-ͯ]/g, '')
            .toLowerCase()
            .replace(/[^a-z0-9]/g, '');
    }

    function actualizar() {
        var primerNombre = limpiar((inNombre.value.trim().split(/\s+/)[0] || ''));
        var paterno      = limpiar(inPaterno.value);
        var inicialMat   = limpiar(inMaterno.value).charAt(0);

        var partes = [primerNombre, paterno, inicialMat].filter(Boolean);

        out.value = partes.length ? partes.join('.') : TEXTO_VACIO;
    }

    // Mayúscula automática en la primera letra de cada palabra mientras se
    // escribe (juan carlos → Juan Carlos). Conserva la posición del cursor.
    function capitalizarEnVivo(campo) {
        campo.addEventListener('input', function () {
            var pos   = campo.selectionStart;
            var nuevo = campo.value.replace(/(^|[\s])([a-záéíóúñü])/g, function (m, antes, letra) {
                return antes + letra.toUpperCase();
            });

            if (nuevo !== campo.value) {
                campo.value = nuevo;
                campo.setSelectionRange(pos, pos);
            }
        });
    }

    [inNombre, inPaterno, inMaterno].forEach(function (campo) {
        capitalizarEnVivo(campo);          // primero capitaliza…
        campo.addEventListener('input', actualizar); // …y luego refresca la vista previa
    });

    actualizar();
})();
</script>
@endsection
