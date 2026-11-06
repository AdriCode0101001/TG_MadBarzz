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
        <h2>¿Olvidaste tu<br>contraseña?</h2>
        <p>Sin problema. Escribí tu correo y te enviamos un enlace para elegir una nueva.</p>
      </div>
    </div>

    <div class="mb-auth__form">
      <div class="mb-auth-card">
        <h1 class="mb-auth-h">Recuperar contraseña</h1>
        <p class="mb-auth-sub">Te enviaremos un enlace a tu correo para restablecerla</p>

        @if (session('status'))
          <div class="mb-ok">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('password.email') }}">
          @csrf
          <label for="email">Correo electrónico</label>
          <div class="mb-input mb-input--no-icon">
            <input type="email" name="email" id="email" placeholder="tucorreo@ejemplo.com" value="{{ old('email') }}" required autofocus>
            <i class="fa-solid fa-envelope mb-icon-right"></i>
          </div>
          @error('email')<div class="mb-err">{{ $message }}</div>@enderror

          <button type="submit" class="mb-submit"><i class="fa-solid fa-paper-plane me-2"></i> Enviar enlace</button>
        </form>

        <p class="mb-auth-foot"><a href="{{ route('login') }}"><i class="fa-solid fa-arrow-left me-1"></i> Volver a iniciar sesión</a></p>
      </div>
    </div>

  </div>
</div>
@endsection
