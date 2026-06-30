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
                            <label class="form-label">Nombres</label>
                            <input type="text" name="nombre_completo" class="form-control @error('nombre_completo') is-invalid @enderror" value="{{ old('nombre_completo') }}" placeholder="Ej. Juan Carlos" required>
                            @error('nombre_completo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Apellido paterno</label>
                            <input type="text" name="apellido_paterno" class="form-control @error('apellido_paterno') is-invalid @enderror" value="{{ old('apellido_paterno') }}" required>
                            @error('apellido_paterno')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Apellido materno</label>
                            <input type="text" name="apellido_materno" class="form-control @error('apellido_materno') is-invalid @enderror" value="{{ old('apellido_materno') }}" required>
                            @error('apellido_materno')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Cédula de identidad</label>
                            <input type="text" name="ci" class="form-control @error('ci') is-invalid @enderror" value="{{ old('ci') }}" required>
                            @error('ci')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="">Teléfono</label>
                            <x-phone-input :required="true" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Género</label>
                            <select name="genero" class=" @error('genero') is-invalid @enderror" required>
                                <option value="">Seleccione…</option>
                                <option value="masculino" @selected(old('genero')=='masculino')>Masculino</option>
                                <option value="femenino"  @selected(old('genero')=='femenino')>Femenino</option>
                                <option value="otro"      @selected(old('genero')=='otro')>Otro</option>
                            </select>
                            @error('genero')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Fecha de nacimiento</label>
                            <input type="date" name="fecha_nacimiento" class="form-control @error('fecha_nacimiento') is-invalid @enderror" value="{{ old('fecha_nacimiento') }}" required>
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
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Correo electrónico</label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required>
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contraseña</label>
                            <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Confirmar contraseña</label>
                            <input type="password" name="password_confirmation" class="form-control" required>
                        </div>
                    </div>
                </fieldset>

                {{-- ---------- Datos del cliente ---------- --}}
                <fieldset class="mb-field-group">
                    <legend class="mb-field-legend"><i class="fa-solid fa-dumbbell"></i> Datos del cliente</legend>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Días asistidos</label>
                            <input type="number" name="dias_asistidos" class="form-control @error('dias_asistidos') is-invalid @enderror" value="{{ old('dias_asistidos', 0) }}" min="0" required>
                            @error('dias_asistidos')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Huella digital <span class="text-muted">(opcional)</span></label>
                            <input type="text" name="huella_digital" class="form-control" value="{{ old('huella_digital') }}" placeholder="Hash o identificador">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Estado</label>
                            <select name="estado" class="" required>
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
@endsection
