@extends('layouts.private')

@section('contenido')

<div class="row">
    <div class="col-xl-12">
        <div class="card custom-card">

            <div class="card-header">
                <h2 class="fw-bold mb-4">Editar Recepcionista</h2>
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

                <form action="{{ route('recepcionistas.update', $recepcionista->id_recepcionista) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <h4 class="dark-text-white mt-3">Datos de la Persona</h4>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark dark-text-white" for="nombre_completo">Nombres</label>
                            <input type="text" name="nombre_completo" class="form-control" value="{{ old('nombre_completo', $recepcionista- id="nombre_completo">persona->nombre_completo) }}" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark dark-text-white" for="apellido_paterno">Apellido Paterno</label>
                            <input type="text" name="apellido_paterno" class="form-control" value="{{ old('apellido_paterno', $recepcionista- id="apellido_paterno">persona->apellido_paterno) }}" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark dark-text-white" for="apellido_materno">Apellido Materno</label>
                            <input type="text" name="apellido_materno" class="form-control" value="{{ old('apellido_materno', $recepcionista- id="apellido_materno">persona->apellido_materno) }}" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark dark-text-white" for="ci">Cédula de Identidad</label>
                            <input type="text" name="ci" class="form-control" value="{{ old('ci', $recepcionista- id="ci">persona->ci) }}" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark dark-text-white">Teléfono</label>
                            <x-phone-input :value="old('telefono', $recepcionista->persona->telefono)" />
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark dark-text-white" for="genero">Género</label>
                            <select name="genero" class="form-control" required id="genero">
                                <option value="masculino" {{ old('genero', $recepcionista->persona->genero) == 'masculino' ? 'selected' : '' }}>Masculino</option>
                                <option value="femenino" {{ old('genero', $recepcionista->persona->genero) == 'femenino' ? 'selected' : '' }}>Femenino</option>
                                <option value="otro" {{ old('genero', $recepcionista->persona->genero) == 'otro' ? 'selected' : '' }}>Otro</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark dark-text-white" for="fecha_nacimiento">Fecha de Nacimiento</label>
                            <input type="date" name="fecha_nacimiento" class="form-control" value="{{ old('fecha_nacimiento', $recepcionista- id="fecha_nacimiento">persona->fecha_nacimiento) }}" required>
                        </div>
                    </div>

                    <h4 class="dark-text-white mt-4">Datos de Usuario</h4>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark dark-text-white" for="name">Nombre de Usuario</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $recepcionista- id="name">user->name) }}" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark dark-text-white" for="email">Email</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email', $recepcionista- id="email">user->email) }}" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark dark-text-white" for="password">Nueva Contraseña (opcional)</label>
                            <input type="password" name="password" class="form-control" id="password">
                        </div>
                    </div>

                    <h4 class="dark-text-white mt-4">Datos del Recepcionista</h4>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark dark-text-white" for="turno">Turno</label>
                            <select name="turno" class="form-control" required id="turno">
                                <option value="mañana" {{ old('turno', $recepcionista->turno) == 'mañana' ? 'selected' : '' }}>Mañana</option>
                                <option value="tarde" {{ old('turno', $recepcionista->turno) == 'tarde' ? 'selected' : '' }}>Tarde</option>
                                <option value="noche" {{ old('turno', $recepcionista->turno) == 'noche' ? 'selected' : '' }}>Noche</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark dark-text-white" for="punto_atencion">Punto de Atención</label>
                            <input type="text" name="punto_atencion" class="form-control" value="{{ old('punto_atencion', $recepcionista- id="punto_atencion">punto_atencion) }}" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark dark-text-white" for="estado">Estado</label>
                            <select name="estado" class="form-control" required id="estado">
                                <option value="activo" {{ old('estado', $recepcionista->estado) == 'activo' ? 'selected' : '' }}>Activo</option>
                                <option value="inactivo" {{ old('estado', $recepcionista->estado) == 'inactivo' ? 'selected' : '' }}>Inactivo</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-save me-2"></i> Actualizar
                        </button>
                            <a href="{{ route('recepcionistas.index') }}" class="btn btn-secondary ms-2">
                            Cancelar
                        </a>
                    </div>

                </form>
            </div>

        </div>
    </div>
</div>

@endsection
