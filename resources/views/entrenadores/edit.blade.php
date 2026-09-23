@extends('layouts.private')

@section('contenido')

<div class="row">
    <div class="col-xl-12">
        <div class="card custom-card">

            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="card-title fs-24 fw-bold text-dark dark-text-white">
                    <h2 class="fw-bold mb-4">Editar Entrenador</h2>
                </div>

            </div>

            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger mb-3">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('entrenadores.update', $entrenador->id_entrenador) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <h4 class="dark-text-white mt-3">Datos de Persona</h4>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark dark-text-white" for="nombre_completo">Nombres</label>
                            <input type="text" name="nombre_completo" class="form-control" value="{{ old('nombre_completo', $entrenador- id="nombre_completo">persona->nombre_completo) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark dark-text-white" for="apellido_paterno">Apellido Paterno</label>
                            <input type="text" name="apellido_paterno" class="form-control" value="{{ old('apellido_paterno', $entrenador- id="apellido_paterno">persona->apellido_paterno) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark dark-text-white" for="apellido_materno">Apellido Materno</label>
                            <input type="text" name="apellido_materno" class="form-control" value="{{ old('apellido_materno', $entrenador- id="apellido_materno">persona->apellido_materno) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark dark-text-white" for="ci">Cédula de Identidad</label>
                            <input type="text" name="ci" class="form-control" value="{{ old('ci', $entrenador- id="ci">persona->ci) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark dark-text-white">Teléfono</label>
                            <x-phone-input :value="old('telefono', $entrenador->persona->telefono)" :required="true" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark dark-text-white" for="genero">Género</label>
                            <select name="genero" class="form-control" required id="genero">
                                <option value="">Seleccione</option>
                                <option value="masculino" {{ old('genero', $entrenador->persona->genero)=='masculino'?'selected':'' }}>Masculino</option>
                                <option value="femenino" {{ old('genero', $entrenador->persona->genero)=='femenino'?'selected':'' }}>Femenino</option>
                                <option value="otro" {{ old('genero', $entrenador->persona->genero)=='otro'?'selected':'' }}>Otro</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark dark-text-white" for="fecha_nacimiento">Fecha de Nacimiento</label>
                            <input type="date" name="fecha_nacimiento" class="form-control" value="{{ old('fecha_nacimiento', $entrenador- id="fecha_nacimiento">persona->fecha_nacimiento) }}" required>
                        </div>

                        <!-- Foto (se muestra en la home pública) -->
                        <div class="col-md-12">
                            <label class="form-label fw-bold text-dark dark-text-white">
                                Foto <small class="text-muted fw-normal">(opcional — se muestra en la sección de Entrenadores de la home pública)</small>
                            </label>
                            <div class="d-flex align-items-center gap-3 flex-wrap">
                                <div id="preview-edit" style="width:90px;height:90px;border-radius:50%;overflow:hidden;border:2px dashed #ccc;display:flex;align-items:center;justify-content:center;background:#f5f5f5;flex-shrink:0;">
                                    @if($entrenador->user->foto)
                                        <img src="{{ route('entrenadores.foto_publica', $entrenador->id_entrenador) }}" style="width:100%;height:100%;object-fit:cover;">
                                    @else
                                        <i class="fa-solid fa-user" style="font-size:2rem;color:#bbb;"></i>
                                    @endif
                                </div>
                                <div class="flex-grow-1">
                                    <input type="file" class="form-control @error('foto') is-invalid @enderror"
                                           name="foto" id="foto" accept="image/jpg,image/jpeg,image/png,image/webp"
                                           onchange="previewFoto(this,'preview-edit')">
                                    <small class="text-muted">JPG, PNG o WEBP — máx. 2MB. Dejar vacío para mantener la foto actual.</small>
                                    @error('foto')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <h4 class="dark-text-white mt-4">Datos de Usuario</h4>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark dark-text-white" for="name">Nombre de Usuario</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $entrenador- id="name">user->name) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark dark-text-white" for="email">Email</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email', $entrenador- id="email">user->email) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark dark-text-white" for="password">Contraseña (opcional)</label>
                            <input type="password" name="password" class="form-control" placeholder="Nueva contraseña" id="password">
                        </div>
                    </div>

                    <h4 class="dark-text-white mt-4">Datos del Entrenador</h4>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark dark-text-white" for="especialidad">Especialidad</label>
                            <select name="especialidad[]" id="especialidad" class="form-control" multiple required>
                                @php
                                    $opcionesEspecialidad = [
                                        'Calistenia básica',
                                        'Calistenia avanzada',
                                        'Entrenamiento de fuerza',
                                        'Resistencia',
                                        'Flexibilidad y movilidad',
                                        'Preparación física para competencias',
                                        'Nutrición y hábitos saludables'
                                    ];
                                    $seleccionadasEspecialidad = old('especialidad', $entrenador->especialidad ?? []);
                                @endphp
                                @foreach ($opcionesEspecialidad as $opcion)
                                    <option value="{{ $opcion }}" {{ in_array($opcion, $seleccionadasEspecialidad) ? 'selected' : '' }}>
                                        {{ $opcion }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark dark-text-white" for="experiencia">Experiencia</label>
                            <input type="text" name="experiencia" class="form-control" value="{{ old('experiencia', $entrenador- id="experiencia">experiencia) }}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark dark-text-white" for="disponibilidad">Disponibilidad</label>
                            <select name="disponibilidad[]" id="disponibilidad" class="form-control" multiple required>
                                @php
                                    $dias = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
                                    $seleccionados = old('disponibilidad', $entrenador->disponibilidad ?? []);
                                @endphp
                                @foreach ($dias as $dia)
                                    <option value="{{ $dia }}" {{ in_array($dia, $seleccionados) ? 'selected' : '' }}>{{ $dia }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark dark-text-white" for="estado">Estado</label>
                            <select name="estado" class="form-control" required id="estado">
                                <option value="">Seleccione estado</option>
                                <option value="activo" {{ old('estado', $entrenador->estado)=='activo'?'selected':'' }}>Activo</option>
                                <option value="inactivo" {{ old('estado', $entrenador->estado)=='inactivo'?'selected':'' }}>Inactivo</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-save me-2"></i> Actualizar
                        </button>
                        <a href="{{ route('entrenadores.index') }}" class="btn btn-secondary ms-2">
                            Cancelar
                        </a>
                    </div>

                </form>
            </div>

        </div>
    </div>
</div>

@push('scripts')
<script>
function previewFoto(input, previewId) {
    const preview = document.getElementById(previewId);
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            preview.innerHTML = `<img src="${e.target.result}" style="width:100%;height:100%;object-fit:cover;">`;
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
@endpush

@endsection
