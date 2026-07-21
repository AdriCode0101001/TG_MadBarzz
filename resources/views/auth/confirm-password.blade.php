@extends('layouts.auth')
@section('contenido')

<div class="mb-auth">
  <div class="mb-auth__wrap">

    <div class="mb-auth__visual">
      <div class="bg" style="background-image:url('{{ asset('assets/images/login-bg.jpg') }}');"></div>
      <div class="ov"></div>
      <div class="mb-auth-logo"><img src="{{ asset('assets_private/images/log_text_mad.png') }}" alt="MadBarzz"></div>
      <div class="mb-auth-quote">
        <h2>Confirmá que<br>sos vos.</h2>
        <p>Es una zona segura — confirmá tu contraseña antes de continuar.</p>
      </div>
    </div>

    <div class="mb-auth__form">
      <div class="mb-auth-card">
        <h1 class="mb-auth-h">Confirmar contraseña</h1>
        <p class="mb-auth-sub">Esta es un área segura de la aplicación. Confirmá tu contraseña antes de continuar.</p>

        <form method="POST" action="{{ route('password.confirm') }}">
          @csrf
          <label for="password">Contraseña</label>
          <div class="mb-input mb-input--no-icon">
            <input type="password" name="password" id="password" placeholder="••••••••" required autofocus autocomplete="current-password">
            <i class="fa-solid fa-eye mb-pass-toggle" data-target="password" title="Mostrar/ocultar"></i>
          </div>
          @error('password')<div class="mb-err">{{ $message }}</div>@enderror

          <button type="submit" class="mb-submit"><i class="fa-solid fa-lock me-2"></i> Confirmar</button>
        </form>
      </div>
    </div>

  </div>
</div>
@endsection
