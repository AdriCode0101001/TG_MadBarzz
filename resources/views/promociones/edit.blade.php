@extends('layouts.private')

@section('contenido')
<div class="container">
    <h2 class="text-center mb-4">Editar Promoción</h2>

    <div class="card p-4 shadow-lg rounded-3">
        <form action="{{ route('promociones.update', $promocion->id_promocion) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label for="titulo" class="form-label fw-bold">Título</label>
                <input type="text" name="titulo" id="titulo" class="form-control" value="{{ old('titulo', $promocion->titulo) }}" required>
            </div>

            <div class="mb-3">
                <label for="descripcion" class="form-label fw-bold">Descripción</label>
                <textarea name="descripcion" id="descripcion" class="form-control" rows="4" required>{{ old('descripcion', $promocion->descripcion) }}</textarea>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="fecha_inicio" class="form-label fw-bold">Fecha de Inicio</label>
                    <input type="date" name="fecha_inicio" id="fecha_inicio" class="form-control" value="{{ old('fecha_inicio', $promocion->fecha_inicio) }}" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="fecha_fin" class="form-label fw-bold">Fecha de Fin</label>
                    <input type="date" name="fecha_fin" id="fecha_fin" class="form-control" value="{{ old('fecha_fin', $promocion->fecha_fin) }}" required>
                </div>
            </div>

            <div class="mb-3">
                <label for="estado" class="form-label fw-bold">Estado</label>
                <select name="estado" id="estado" class="form-control" required>
                    <option value="activa" {{ $promocion->estado == 'activa' ? 'selected' : '' }}>Activa</option>
                    <option value="inactiva" {{ $promocion->estado == 'inactiva' ? 'selected' : '' }}>Inactiva</option>
                    <option value="finalizada" {{ $promocion->estado == 'finalizada' ? 'selected' : '' }}>Finalizada</option>
                </select>
            </div>

            <div class="mb-3">
                <label for="imagen" class="form-label fw-bold">Imagen Actual</label><br>
                @if($promocion->imagen)
                    <img src="{{ asset('storage/'.$promocion->imagen) }}" alt="Imagen Promoción" width="150" class="mb-2 rounded">
                @else
                    <p>No hay imagen registrada.</p>
                @endif
                <input type="file" name="imagen" id="imagen" class="form-control" accept="image/*">
            </div>

            <div class="text-end">
                <a href="{{ route('promociones.index') }}" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">Actualizar Promoción</button>
            </div>
        </form>
    </div>
</div>
@endsection
