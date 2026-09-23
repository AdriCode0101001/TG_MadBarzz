@extends('layouts.private')

@section('contenido')

<div class="row">
    <div class="col-xl-12">
        <div class="card custom-card">

            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="card-title fs-24 fw-bold text-dark dark-text-white">
                    <h2 class="fw-bold mb-4">Editar Administrador</h2>
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

                <form action="{{ route('administradores.update', $administrador->id_administrador) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <h4 class="dark-text-white mt-3">Datos de Persona</h4>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark dark-text-white" for="nombre_completo">Nombres</label>
                            <input type="text" name="nombre_completo" id="nombre_completo" class="form-control" value="{{ old('nombre_completo', $administrador->persona->nombre_completo) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark dark-text-white" for="apellido_paterno">Apellido Paterno</label>
                            <input type="text" name="apellido_paterno" id="apellido_paterno" class="form-control" value="{{ old('apellido_paterno', $administrador->persona->apellido_paterno) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark dark-text-white" for="apellido_materno">Apellido Materno</label>
                            <input type="text" name="apellido_materno" id="apellido_materno" class="form-control" value="{{ old('apellido_materno', $administrador->persona->apellido_materno) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark dark-text-white" for="ci">Cédula de Identidad</label>
                            <input type="text" name="ci" id="ci" class="form-control" value="{{ old('ci', $administrador->persona->ci) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark dark-text-white">Teléfono</label>
                            <x-phone-input :value="old('telefono', $administrador->persona->telefono)" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark dark-text-white" for="genero">Género</label>
                            <select name="genero" class="form-control" required id="genero">
                                <option value="">Seleccione</option>
                                <option value="masculino" {{ old('genero', $administrador->persona->genero)=='masculino'?'selected':'' }}>Masculino</option>
                                <option value="femenino" {{ old('genero', $administrador->persona->genero)=='femenino'?'selected':'' }}>Femenino</option>
                                <option value="otro" {{ old('genero', $administrador->persona->genero)=='otro'?'selected':'' }}>Otro</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark dark-text-white" for="fecha_nacimiento">Fecha de Nacimiento</label>
                            <input type="date" name="fecha_nacimiento" id="fecha_nacimiento" class="form-control" value="{{ old('fecha_nacimiento', $administrador->persona->fecha_nacimiento) }}" required>
                        </div>
                    </div>

                    <h4 class="dark-text-white mt-4">Datos de Usuario</h4>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark dark-text-white" for="name">Nombre de Usuario</label>
                            <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $administrador->user->name) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark dark-text-white" for="email">Email</label>
                            <input type="email" name="email" id="email" class="form-control" value="{{ old('email', $administrador->user->email) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark dark-text-white" for="password">Contraseña (opcional)</label>
                            <input type="password" name="password" class="form-control" placeholder="Nueva contraseña" id="password">
                        </div>
                    </div>

                    <h4 class="dark-text-white mt-4">Datos del Administrador</h4>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark dark-text-white" for="nivel_acceso">Nivel de Acceso</label>
                            <select name="nivel_acceso" class="form-control" required id="nivel_acceso">
                                <option value="superadmin" {{ old('nivel_acceso', $administrador->nivel_acceso)=='superadmin'?'selected':'' }}>Superadmin</option>
                                <option value="gestion" {{ old('nivel_acceso', $administrador->nivel_acceso)=='gestion'?'selected':'' }}>Gestión</option>
                                <option value="finanzas" {{ old('nivel_acceso', $administrador->nivel_acceso)=='finanzas'?'selected':'' }}>Finanzas</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark dark-text-white" for="area_responsable">Área Responsable</label>
                            <input type="text" name="area_responsable" id="area_responsable" class="form-control" value="{{ old('area_responsable', $administrador->area_responsable) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark dark-text-white" for="estado">Estado</label>
                            <select name="estado" class="form-control" required id="estado">
                                <option value="">Seleccione estado</option>
                                <option value="activo" {{ old('estado', $administrador->estado)=='activo'?'selected':'' }}>Activo</option>
                                <option value="inactivo" {{ old('estado', $administrador->estado)=='inactivo'?'selected':'' }}>Inactivo</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-save me-2"></i> Actualizar
                        </button>
                        <a href="{{ route('administradores.index') }}" class="btn btn-secondary ms-2">
                            Cancelar
                        </a>
                    </div>

                </form>
            </div>

        </div>
    </div>
</div>

@endsection
