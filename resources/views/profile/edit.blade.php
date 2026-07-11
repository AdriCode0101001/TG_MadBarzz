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

                    <form method="POST" action="{{ route('profile.update') }}">
                        @csrf
                        @method('PATCH')

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
