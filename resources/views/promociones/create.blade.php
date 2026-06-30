@extends('layouts.private')

@section('contenido')
<div class="container mt-4">
    <h2 class="text-center mb-4">Agregar Nueva Promoción</h2>

    {{-- Mostrar errores de validación --}}
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Mostrar mensaje de éxito --}}
    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="card p-4 shadow-lg rounded-3">
        <form action="{{ route('promociones.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            {{-- Título --}}
            <div class="mb-3">
                <label for="titulo" class="form-label fw-bold">Título</label>
                <input 
                    type="text" 
                    name="titulo" 
                    id="titulo" 
                    class="form-control @error('titulo') is-invalid @enderror" 
                    value="{{ old('titulo') }}" 
                    required>
                @error('titulo')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Descripción --}}
            <div class="mb-3">
                <label for="descripcion" class="form-label fw-bold">Descripción</label>
                <textarea 
                    name="descripcion" 
                    id="descripcion" 
                    class="form-control @error('descripcion') is-invalid @enderror" 
                    rows="4" 
                    required>{{ old('descripcion') }}</textarea>
                @error('descripcion')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Fechas --}}
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="fecha_inicio" class="form-label fw-bold">Fecha de Inicio</label>
                    <input 
                        type="date" 
                        name="fecha_inicio" 
                        id="fecha_inicio" 
                        class="form-control @error('fecha_inicio') is-invalid @enderror" 
                        value="{{ old('fecha_inicio') }}" 
                        required>
                    @error('fecha_inicio')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label for="fecha_fin" class="form-label fw-bold">Fecha de Fin</label>
                    <input 
                        type="date" 
                        name="fecha_fin" 
                        id="fecha_fin" 
                        class="form-control @error('fecha_fin') is-invalid @enderror" 
                        value="{{ old('fecha_fin') }}" 
                        required>
                    @error('fecha_fin')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            {{-- Estado --}}
            <div class="mb-3">
                <label for="estado" class="form-label fw-bold">Estado</label>
                <select 
                    name="estado" 
                    id="estado" 
                    class=" @error('estado') is-invalid @enderror" 
                    required>
                    <option value="">-- Seleccionar estado --</option>
                    <option value="activa" {{ old('estado') == 'activa' ? 'selected' : '' }}>Activa</option>
                    <option value="inactiva" {{ old('estado') == 'inactiva' ? 'selected' : '' }}>Inactiva</option>
                    <option value="finalizada" {{ old('estado') == 'finalizada' ? 'selected' : '' }}>Finalizada</option>
                </select>
                @error('estado')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Imagen --}}
            <div class="mb-3">
                <label for="imagen" class="form-label fw-bold">Imagen (opcional)</label>
                <input 
                    type="file" 
                    name="imagen" 
                    id="imagen" 
                    class="form-control @error('imagen') is-invalid @enderror" 
                    accept="image/*">
                @error('imagen')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Botones --}}
            <div class="text-end">
                <a href="{{ route('promociones.index') }}" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-success">Guardar Promoción</button>
            </div>
        </form>
    </div>
</div>
@endsection
