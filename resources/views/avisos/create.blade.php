@extends('layouts.private')

@section('contenido')
<div class="col-12">

    <x-page-header title="Nuevo aviso" subtitle="Se envía a TODOS los clientes — lo van a ver en su portal." icon="fa-solid fa-bullhorn">
        <a href="{{ route('avisos.index') }}" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left me-1"></i> Volver
        </a>
    </x-page-header>

    @if ($errors->any())
        <div class="alert alert-danger border-0 rounded-3 mb-4">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-body p-4">
                    <form action="{{ route('avisos.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Título</label>
                            <input type="text" name="titulo" class="form-control @error('titulo') is-invalid @enderror"
                                   value="{{ old('titulo') }}" maxlength="150" required>
                            @error('titulo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Texto</label>
                            <textarea name="texto" class="form-control @error('texto') is-invalid @enderror"
                                      rows="4" maxlength="2000" required>{{ old('texto') }}</textarea>
                            @error('texto')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">
                                Imagen <small class="text-muted fw-normal">(opcional)</small>
                            </label>

                            {{-- Previsualización EN EL NAVEGADOR antes de guardar — mismo patrón
                                 que "Imágenes de la Home" (createObjectURL / revokeObjectURL). --}}
                            <div class="mb-2" id="preview-aviso-imagen"
                                 style="display:none; width:100%; max-height:260px; border-radius:8px; overflow:hidden; border:1px solid rgba(128,128,128,.25); background:#111; align-items:center; justify-content:center;">
                                <img alt="Vista previa" style="max-width:100%;max-height:260px;object-fit:contain;display:block;margin:0 auto;">
                            </div>

                            <input type="file" name="imagen" class="form-control @error('imagen') is-invalid @enderror"
                                   accept="image/*" data-preview-target="preview-aviso-imagen"
                                   onchange="previsualizarImagenAviso(this)">
                            <small class="text-muted">JPG, PNG o WEBP — máx. 2MB.</small>
                            @error('imagen')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fa-solid fa-bullhorn me-1"></i> Enviar a todos los clientes
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function previsualizarImagenAviso(input) {
    const previewId = input.dataset.previewTarget;
    const contenedor = document.getElementById(previewId);
    if (!contenedor || !input.files || !input.files[0]) return;

    const img = contenedor.querySelector('img');
    if (img.dataset.blobUrl) {
        URL.revokeObjectURL(img.dataset.blobUrl);
    }

    const url = URL.createObjectURL(input.files[0]);
    img.src = url;
    img.dataset.blobUrl = url;
    contenedor.style.display = 'flex';
}
</script>
@endpush
@endsection
