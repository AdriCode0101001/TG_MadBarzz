@extends('layouts.auth')
@section('contenido')

@php
    $telefonoRecepcionista = app('App\Http\Controllers\RecepcionistaController')->getRecepcionistaActivo();
    $whatsappLink = $telefonoRecepcionista
        ? 'https://wa.me/591' . $telefonoRecepcionista . '?text=Hola,%20quiero%20solicitar%20acceso%20a%20MadBarzz'
        : '#';

    // ╔══════════════════════════════════════════════════════════════╗
    // ║  IMAGEN DEL LOGIN                                             ║
    // ║  Coloca tu foto en:  public/assets/images/                   ║
    // ║  y cambia el nombre del archivo aquí abajo:                  ║
    // ╚══════════════════════════════════════════════════════════════╝
    $loginImage = asset('assets/images/login-bg.jpg');   // <-- CAMBIA ESTE ARCHIVO por tu imagen (public/assets/images/login-bg.jpg)
@endphp

<div class="mb-auth">
  <div class="mb-auth__wrap">

    {{-- Panel visual (imagen de calistenia) --}}
    <div class="mb-auth__visual">
      <div class="bg" style="background-image:url('{{ $loginImage }}');"></div>
      <div class="ov"></div>
      <div class="mb-auth-logo"><img src="{{ asset('assets_private/images/log_text_mad.png') }}" alt="MadBarzz"></div>
      <div class="mb-auth-quote">
        <h2>Tu disciplina<br>construye tu fuerza.</h2>
        <p>Accede a tu cuenta para ver tus rutinas, pagos y progreso en Calistenia MadBarzz.</p>
      </div>
    </div>

    {{-- Formulario --}}
    <div class="mb-auth__form">
      <div class="mb-auth-card">
        <h1 class="mb-auth-h">Bienvenido de vuelta 👋</h1>
        <p class="mb-auth-sub">Inicia sesión para continuar tu entrenamiento</p>

        <form method="POST" action="{{ route('login') }}">
          @csrf
          <label for="email">Correo o usuario</label>
          <div class="mb-input mb-input--no-icon">
            <input type="text" name="email" id="email" placeholder="tucorreo@ejemplo.com o usuario" value="{{ old('email') }}" required autofocus autocomplete="username">
            <i class="fa-solid fa-envelope mb-icon-right"></i>
          </div>
          @error('email')<div class="mb-err">{{ $message }}</div>@enderror

          <label for="password">Contraseña</label>
          <div class="mb-input mb-input--no-icon">
            <input type="password" name="password" id="password" placeholder="••••••••" required>
            <i class="fa-solid fa-eye mb-pass-toggle" data-target="password" title="Mostrar/ocultar"></i>
          </div>
          @error('password')<div class="mb-err">{{ $message }}</div>@enderror

          <button type="submit" class="mb-submit"><i class="fa-solid fa-right-to-bracket me-2"></i> Iniciar sesión</button>
        </form>

        <p class="mb-auth-foot">¿Olvidaste tu contraseña o no tienes cuenta?<br>
          <a href="{{ $whatsappLink }}" target="_blank"><i class="fa-brands fa-whatsapp"></i> Solicita acceso en recepción</a>
        </p>
      </div>
    </div>

  </div>
</div>
@endsection
