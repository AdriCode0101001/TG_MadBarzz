@extends('layouts.auth')
@section('contenido')

<div class="mb-auth">
  <div class="mb-auth__wrap">

    <div class="mb-auth__visual">
      @php $loginBg = \App\Services\ImagenHomeService::fondoLogin(); @endphp
      <div class="bg" style="background-image:url('{{ $loginBg['url'] }}'); background-position: {{ $loginBg['pos'] }};"></div>
      <div class="ov"></div>
      <div class="mb-auth-logo"><img src="{{ \App\Services\ImagenHomeService::logoTexto() }}" alt="MadBarzz"></div>
      <div class="mb-auth-quote">
        <h2>Elegí una<br>nueva contraseña.</h2>
        <p>Ya casi está — poné tu nueva contraseña para volver a entrenar.</p>
      </div>
    </div>

    <div class="mb-auth__form">
      <div class="mb-auth-card">
        <h1 class="mb-auth-h">Restablecer contraseña</h1>
        <p class="mb-auth-sub">Definí tu nueva contraseña de acceso</p>

        <form method="POST" action="{{ route('password.store') }}">
          @csrf
          <input type="hidden" name="token" value="{{ $request->route('token') }}">

          <label for="email">Correo electrónico</label>
          <div class="mb-input mb-input--no-icon">
            <input type="email" name="email" id="email" placeholder="tucorreo@ejemplo.com"
                   value="{{ old('email', $request->email) }}" required autofocus autocomplete="username">
            <i class="fa-solid fa-envelope mb-icon-right"></i>
          </div>
          @error('email')<div class="mb-err">{{ $message }}</div>@enderror

          <label for="password">Nueva contraseña</label>
          <div class="mb-input mb-input--no-icon">
            <input type="password" name="password" id="password" placeholder="••••••••" required autocomplete="new-password">
            <i class="fa-solid fa-eye mb-pass-toggle" data-target="password" title="Mostrar/ocultar"></i>
          </div>
          @error('password')<div class="mb-err">{{ $message }}</div>@enderror

          <label for="password_confirmation">Confirmar contraseña</label>
          <div class="mb-input mb-input--no-icon">
            <input type="password" name="password_confirmation" id="password_confirmation" placeholder="••••••••" required autocomplete="new-password">
            <i class="fa-solid fa-eye mb-pass-toggle" data-target="password_confirmation" title="Mostrar/ocultar"></i>
          </div>
          @error('password_confirmation')<div class="mb-err">{{ $message }}</div>@enderror

          <button type="submit" class="mb-submit"><i class="fa-solid fa-key me-2"></i> Restablecer contraseña</button>
        </form>
      </div>
    </div>

  </div>
</div>
@endsection
