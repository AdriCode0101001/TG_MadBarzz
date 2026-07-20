@extends('layouts.private')

@section('contenido')
<div class="col-12">

    <x-page-header title="Nuevo grupo muscular" subtitle="Estará disponible al asignar rutinas" icon="fa-solid fa-dumbbell" />

    <div class="card">
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

            <form action="{{ route('grupos_musculares.store') }}" method="POST">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="nombre" class="form-label">Nombre</label>
                        <input type="text" name="nombre" id="nombre" class="form-control"
                               value="{{ old('nombre') }}" placeholder="Ej. Pecho y tríceps" required>
                    </div>
                    <div class="col-md-6">
                        <label for="descripcion" class="form-label">Descripción (opcional)</label>
                        <input type="text" name="descripcion" id="descripcion" class="form-control"
                               value="{{ old('descripcion') }}" placeholder="Ej. Empuje de tren superior">
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <div class="form-check">
                            <input type="hidden" name="activo" value="0">
                            <input class="form-check-input" type="checkbox" name="activo" id="activo" value="1" checked>
                            <label class="form-check-label" for="activo">Activo</label>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save me-2"></i> Guardar</button>
                    <a href="{{ route('grupos_musculares.index') }}" class="btn btn-secondary ms-2">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
