@extends('layouts.private')

@section('contenido')
<div class="col-12">

    <x-page-header title="Editar cliente" subtitle="Actualiza los datos de la persona, su usuario y su ficha" icon="fa-solid fa-user-pen">
        <a href="{{ route('clientes.index') }}" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i> Volver
        </a>
    </x-page-header>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('clientes.update', $cliente->id_cliente) }}" method="POST" autocomplete="off">
                @csrf
                @method('PUT')

                {{-- ---------- Datos de la persona ---------- --}}
                <fieldset class="mb-field-group">
                    <legend class="mb-field-legend"><i class="fa-solid fa-id-card"></i> Datos de la persona</legend>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="nombre_completo">Nombres</label>
                            <input type="text" name="nombre_completo" id="nombre_completo" class="form-control @error('nombre_completo') is-invalid @enderror" value="{{ old('nombre_completo', $cliente->persona->nombre_completo ?? '') }}" required>
                            @error('nombre_completo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="apellido_paterno">Apellido paterno</label>
                            <input type="text" name="apellido_paterno" id="apellido_paterno" class="form-control @error('apellido_paterno') is-invalid @enderror" value="{{ old('apellido_paterno', $cliente->persona->apellido_paterno ?? '') }}" required>
                            @error('apellido_paterno')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="apellido_materno">Apellido materno</label>
                            <input type="text" name="apellido_materno" id="apellido_materno" class="form-control @error('apellido_materno') is-invalid @enderror" value="{{ old('apellido_materno', $cliente->persona->apellido_materno ?? '') }}" required>
                            @error('apellido_materno')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="ci">Cédula de identidad</label>
                            <input type="text" name="ci" id="ci" class="form-control @error('ci') is-invalid @enderror" value="{{ old('ci', $cliente->persona->ci ?? '') }}" required>
                            @error('ci')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Teléfono</label>
                            <x-phone-input :value="old('telefono', $cliente->persona->telefono ?? '')" :required="true" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="genero">Género</label>
                            <select name="genero" class=" @error('genero') is-invalid @enderror" required id="genero">
                                <option value="">Seleccione…</option>
                                <option value="masculino" @selected(old('genero', $cliente->persona->genero ?? '')=='masculino')>Masculino</option>
                                <option value="femenino"  @selected(old('genero', $cliente->persona->genero ?? '')=='femenino')>Femenino</option>
                                <option value="otro"      @selected(old('genero', $cliente->persona->genero ?? '')=='otro')>Otro</option>
                            </select>
                            @error('genero')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="fecha_nacimiento">Fecha de nacimiento</label>
                            <input type="date" name="fecha_nacimiento" id="fecha_nacimiento" class="form-control @error('fecha_nacimiento') is-invalid @enderror" value="{{ old('fecha_nacimiento', $cliente->persona->fecha_nacimiento ?? '') }}" required>
                            @error('fecha_nacimiento')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </fieldset>

                {{-- ---------- Datos del usuario ---------- --}}
                <fieldset class="mb-field-group">
                    <legend class="mb-field-legend"><i class="fa-solid fa-circle-user"></i> Datos de acceso (usuario)</legend>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="name">Nombre de usuario</label>
                            <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $cliente->user->name ?? '') }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="email">Correo electrónico</label>
                            <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $cliente->user->email ?? '') }}" required>
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="password">Contraseña <span class="text-muted">(dejar vacío para no cambiar)</span></label>
                            <input type="password" name="password" id="password" class="form-control" autocomplete="new-password" placeholder="Nueva contraseña">
                        </div>
                    </div>
                </fieldset>

                {{-- ---------- Consentimiento de huella (Grupo 3 legal, parte 2) ---------- --}}
                <x-consentimiento-huella :cliente="$cliente" />

                {{-- ---------- Datos del cliente ---------- --}}
                <fieldset class="mb-field-group">
                    <legend class="mb-field-legend"><i class="fa-solid fa-dumbbell"></i> Datos del cliente</legend>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label" for="dias_asistidos">Días asistidos</label>
                            <input type="number" name="dias_asistidos" id="dias_asistidos" class="form-control" value="{{ old('dias_asistidos', $cliente->dias_asistidos ?? 0) }}" min="0" required>
                        </div>
                        {{-- La plantilla de huella en sí se enrola desde el agente biométrico con el
                             lector, no desde la web — acá solo el consentimiento (arriba). --}}
                        <div class="col-md-4">
                            <label class="form-label" for="estado">Estado</label>
                            <select name="estado" class="" required id="estado">
                                <option value="activo"   @selected(old('estado', $cliente->estado ?? '')=='activo')>Activo</option>
                                <option value="inactivo" @selected(old('estado', $cliente->estado ?? '')=='inactivo')>Inactivo</option>
                            </select>
                        </div>
                    </div>
                </fieldset>

                <div class="d-flex gap-2 justify-content-end">
                    <a href="{{ route('clientes.index') }}" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-floppy-disk"></i> Actualizar cliente
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ---------- Huellas biométricas: borrado (Grupo 2 / Etapa 3 de la auditoría de
         privacidad) ---------- La huella se ENROLA desde el agente biométrico con el
         lector, no desde acá — esta sección es solo para el derecho al olvido: borrado
         real (forceDelete), irreversible. Oculto para quien no sea Administrador; la
         protección real es la ruta (role:Administrador en routes/web.php), esto es
         defensa en profundidad para no mostrar un botón que igual daría 403. --}}
    @if (Auth::user()->hasRole('Administrador'))
        <div class="card mt-4">
            <div class="card-body">
                <h5 class="mb-1"><i class="fa-solid fa-fingerprint me-2"></i>Huellas biométricas</h5>
                <p class="text-muted small mb-3">
                    Borrado real e irreversible (no una baja lógica). El agente deja de reconocer la
                    huella borrada recién en su próxima sincronización (hasta ~5 minutos según la
                    configuración del agente) — o de inmediato si el operador usa
                    «Sincronizar Ahora» en la aplicación del agente.
                </p>

                @if ($cliente->huellas->isEmpty())
                    <p class="text-muted mb-0">Este cliente no tiene huellas enroladas.</p>
                @else
                    <ul class="list-group mb-3">
                        @foreach ($cliente->huellas as $huella)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><i class="fa-solid fa-hand-point-up me-2"></i>{{ ucfirst(str_replace('_', ' ', $huella->dedo)) }}</span>
                                <form action="{{ route('clientes.huellas.destroy', [$cliente, $huella]) }}" method="POST"
                                      data-confirm="¿Borrar la huella «{{ str_replace('_', ' ', $huella->dedo) }}» de este cliente? Esta acción es irreversible: el dato biométrico se elimina por completo y no se puede deshacer.">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        <i class="fa-solid fa-trash"></i> Borrar
                                    </button>
                                </form>
                            </li>
                        @endforeach
                    </ul>

                    @if ($cliente->huellas->count() > 1)
                        <form action="{{ route('clientes.huellas.destroy_todas', $cliente) }}" method="POST"
                              data-confirm="¿Borrar TODAS las huellas ({{ $cliente->huellas->count() }}) de este cliente? Esta acción es irreversible: los datos biométricos se eliminan por completo y no se pueden deshacer.">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="fa-solid fa-trash-can"></i> Borrar todas las huellas
                            </button>
                        </form>
                    @endif
                @endif
            </div>
        </div>
    @endif
</div>

<script>
// Mayúscula automática en la primera letra de cada palabra de nombre y
// apellidos mientras se escribe (misma regla que en el alta de cliente).
(function () {
    ['nombre_completo', 'apellido_paterno', 'apellido_materno'].forEach(function (nombre) {
        var campo = document.querySelector('input[name="' + nombre + '"]');
        if (!campo) return;

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
    });
})();
</script>
@endsection
