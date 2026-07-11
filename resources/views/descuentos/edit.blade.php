@extends('layouts.private')

@section('contenido')
<div class="col-12">

    <x-page-header title="Editar descuento" subtitle="{{ $descuento->nombre }}" icon="fa-solid fa-tags" />

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

            <form action="{{ route('descuentos.update', $descuento) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="nombre" class="form-label">Nombre</label>
                        <input type="text" name="nombre" id="nombre" class="form-control"
                               value="{{ old('nombre', $descuento->nombre) }}" required>
                    </div>
                    <div class="col-md-3">
                        <label for="porcentaje" class="form-label">Porcentaje (%)</label>
                        <input type="number" name="porcentaje" id="porcentaje" class="form-control"
                               value="{{ old('porcentaje', $descuento->porcentaje) }}" min="0" max="100" step="0.01" required>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <div class="form-check">
                            <input type="hidden" name="activo" value="0">
                            <input class="form-check-input" type="checkbox" name="activo" id="activo" value="1"
                                   @checked(old('activo', $descuento->activo))>
                            <label class="form-check-label" for="activo">Activo</label>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save me-2"></i> Actualizar</button>
                    <a href="{{ route('descuentos.index') }}" class="btn btn-secondary ms-2">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
