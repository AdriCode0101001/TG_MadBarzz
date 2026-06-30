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
                            <label class="form-label">Nombres</label>
                            <input type="text" name="nombre_completo" class="form-control @error('nombre_completo') is-invalid @enderror" value="{{ old('nombre_completo', $cliente->persona->nombre_completo ?? '') }}" required>
                            @error('nombre_completo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Apellido paterno</label>
                            <input type="text" name="apellido_paterno" class="form-control @error('apellido_paterno') is-invalid @enderror" value="{{ old('apellido_paterno', $cliente->persona->apellido_paterno ?? '') }}" required>
                            @error('apellido_paterno')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Apellido materno</label>
                            <input type="text" name="apellido_materno" class="form-control @error('apellido_materno') is-invalid @enderror" value="{{ old('apellido_materno', $cliente->persona->apellido_materno ?? '') }}" required>
                            @error('apellido_materno')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Cédula de identidad</label>
                            <input type="text" name="ci" class="form-control @error('ci') is-invalid @enderror" value="{{ old('ci', $cliente->persona->ci ?? '') }}" required>
                            @error('ci')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Teléfono</label>
                            <input type="text" name="telefono" class="form-control @error('telefono') is-invalid @enderror" value="{{ old('telefono', $cliente->persona->telefono ?? '') }}" required>
                            @error('telefono')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Género</label>
                            <select name="genero" class="form-select @error('genero') is-invalid @enderror" required>
                                <option value="">Seleccione…</option>
                                <option value="masculino" @selected(old('genero', $cliente->persona->genero ?? '')=='masculino')>Masculino</option>
                                <option value="femenino"  @selected(old('genero', $cliente->persona->genero ?? '')=='femenino')>Femenino</option>
                                <option value="otro"      @selected(old('genero', $cliente->persona->genero ?? '')=='otro')>Otro</option>
                            </select>
                            @error('genero')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Fecha de nacimiento</label>
                            <input type="date" name="fecha_nacimiento" class="form-control @error('fecha_nacimiento') is-invalid @enderror" value="{{ old('fecha_nacimiento', $cliente->persona->fecha_nacimiento ?? '') }}" required>
                            @error('fecha_nacimiento')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </fieldset>

                {{-- ---------- Datos del usuario ---------- --}}
                <fieldset class="mb-field-group">
                    <legend class="mb-field-legend"><i class="fa-solid fa-circle-user"></i> Datos de acceso (usuario)</legend>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nombre de usuario</label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $cliente->user->name ?? '') }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Correo electrónico</label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $cliente->user->email ?? '') }}" required>
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contraseña <span class="text-muted">(dejar vacío para no cambiar)</span></label>
                            <input type="password" name="password" class="form-control" autocomplete="new-password" placeholder="Nueva contraseña">
                        </div>
                    </div>
                </fieldset>

                {{-- ---------- Datos del cliente ---------- --}}
                <fieldset class="mb-field-group">
                    <legend class="mb-field-legend"><i class="fa-solid fa-dumbbell"></i> Datos del cliente</legend>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Días asistidos</label>
                            <input type="number" name="dias_asistidos" class="form-control" value="{{ old('dias_asistidos', $cliente->dias_asistidos ?? 0) }}" min="0" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Huella digital <span class="text-muted">(opcional)</span></label>
                            <input type="text" name="huella_digital" class="form-control" value="{{ old('huella_digital', $cliente->huella_digital ?? '') }}" placeholder="Hash o identificador">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Estado</label>
                            <select name="estado" class="form-select" required>
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
</div>
@endsection
