@extends('layouts.private')
@section('contenido')

<x-page-header title="Nueva Membresía" subtitle="Crea un nuevo plan de membresía" icon="fa-solid fa-id-card-clip">
    <a href="{{ route('membresias.index') }}" class="btn btn-secondary">
        <i class="fa-solid fa-arrow-left me-1"></i> Volver
    </a>
</x-page-header>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card custom-card">
            <div class="card-body p-4">

                @if($errors->any())
                    <div class="alert alert-danger border-0 rounded-3 mb-4">
                        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                    </div>
                @endif

                <form action="{{ route('membresias.store') }}" method="POST">
                    @csrf
                    <div class="mb-4">
                        <label class="form-label fw-semibold" for="tipo_membresia">Tipo de membresía</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-ticket"></i></span>
                            <input type="text" name="tipo_membresia" id="tipo_membresia" class="form-control @error('tipo_membresia') is-invalid @enderror"
                                   placeholder="Ej. Mensual, Trimestral, Anual…"
                                   value="{{ old('tipo_membresia') }}" required>
                            @error('tipo_membresia')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold" for="precio">Precio (Bs)</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-bolivar-sign">Bs.</i></span>
                            <input type="number" step="0.01" min="0" name="precio" id="precio" class="form-control @error('precio') is-invalid @enderror"
                                   placeholder="0.00"
                                   value="{{ old('precio') }}" required>
                            @error('precio')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="d-flex gap-2 pt-2">
                        <button type="submit" class="btn btn-primary flex-grow-1">
                            <i class="fa-solid fa-circle-plus me-2"></i> Guardar membresía
                        </button>
                        <a href="{{ route('membresias.index') }}" class="btn btn-secondary">Cancelar</a>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

@endsection
