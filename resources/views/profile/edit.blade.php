@extends('layouts.private')

@section('contenido')
<div class="col-12">

    <x-page-header title="Mi Perfil" subtitle="Edita tu nombre de usuario, correo y contraseña" icon="fa-solid fa-circle-user" />

    <div class="row">
        {{-- ── Información del perfil ── --}}
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header"><h5 class="mb-0"><i class="fa-solid fa-id-card me-2"></i>Información de la cuenta</h5></div>
                <div class="card-body">

                    @if (session('status') === 'profile-updated')
                        <div class="alert alert-success border-0 rounded-3">Perfil actualizado correctamente.</div>
                    @endif

                    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
                        @csrf
                        @method('PATCH')

                        {{-- Foto de perfil --}}
                        <div class="mb-3 text-center">
                            <label class="form-label d-block">Foto de perfil</label>
                            <div class="position-relative d-inline-block">
                                @if(Auth::user()->foto)
                                    <img id="preview-foto" src="{{ route('usuario.foto', Auth::user()->id) }}"
                                         class="rounded-circle border border-3 border-primary"
                                         style="width:120px;height:120px;object-fit:cover;" alt="Foto de perfil">
                                @else
                                    <img id="preview-foto" src="{{ asset('assets_private/images/profile/17.jpg') }}"
                                         class="rounded-circle border border-3 border-secondary"
                                         style="width:120px;height:120px;object-fit:cover;" alt="Sin foto">
                                @endif
                            </div>
                            <div class="mt-2">
                                <label for="foto" class="btn btn-sm btn-outline-primary">
                                    <i class="fa-solid fa-camera me-1"></i> Cambiar foto
                                </label>
                                <input type="file" name="foto" id="foto" class="d-none" accept="image/jpeg,image/png,image/webp">
                            </div>
                            @error('foto')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            <small class="text-muted d-block mt-1">JPG, PNG o WebP. Máx 2 MB.</small>
                        </div>

                        <div class="mb-3">
                            <label for="name" class="form-label">Nombre de usuario</label>
                            <input type="text" name="name" id="name"
                                   class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name', Auth::user()->name) }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <small class="text-muted">Debe ser único: si ya está en uso, se rechazará.</small>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Correo electrónico</label>
                            <input type="email" name="email" id="email"
                                   class="form-control @error('email') is-invalid @enderror"
                                   value="{{ old('email', Auth::user()->email) }}" required>
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Guardar cambios
                        </button>
                    </form>

                </div>
            </div>
        </div>

        {{-- ── Cambiar contraseña ── --}}
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header"><h5 class="mb-0"><i class="fa-solid fa-key me-2"></i>Cambiar contraseña</h5></div>
                <div class="card-body">

                    @if (session('status') === 'password-updated')
                        <div class="alert alert-success border-0 rounded-3">Contraseña actualizada correctamente.</div>
                    @endif

                    <form method="POST" action="{{ route('password.update') }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="current_password" class="form-label">Contraseña actual</label>
                            <input type="password" name="current_password" id="current_password"
                                   class="form-control @error('current_password', 'updatePassword') is-invalid @enderror"
                                   autocomplete="current-password" required>
                            @error('current_password', 'updatePassword')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Contraseña nueva</label>
                            <input type="password" name="password" id="password"
                                   class="form-control @error('password', 'updatePassword') is-invalid @enderror"
                                   autocomplete="new-password" required>
                            @error('password', 'updatePassword')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label">Confirmar contraseña nueva</label>
                            <input type="password" name="password_confirmation" id="password_confirmation"
                                   class="form-control" autocomplete="new-password" required>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-key me-1"></i> Actualizar contraseña
                        </button>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('foto')?.addEventListener('change', function () {
    const file = this.files[0];
    if (!file) return;
    if (file.size > 2 * 1024 * 1024) {
        alert('La imagen no debe superar los 2 MB.');
        this.value = '';
        return;
    }
    const reader = new FileReader();
    reader.onload = e => document.getElementById('preview-foto').src = e.target.result;
    reader.readAsDataURL(file);
});
</script>
@endpush
