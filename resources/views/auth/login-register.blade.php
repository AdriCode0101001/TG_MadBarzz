@extends('layouts.auth')
@section('contenido')

@php
    $telefonoRecepcionista = app('App\Http\Controllers\RecepcionistaController')->getRecepcionistaActivo();
    $whatsappLink = $telefonoRecepcionista
        ? 'https://wa.me/591' . $telefonoRecepcionista . '?text=Hola,%20quiero%20solicitar%20acceso%20a%20MadBarzz'
        : '#';

    $loginBg = \App\Services\ImagenHomeService::fondoLogin();
@endphp

{{-- ===== Pantalla de inicio de sesión. No hay auto-registro público: las
     cuentas de cliente las crea el personal desde el panel interno. ===== --}}
<style>
  .mb-select{
    width:100%; height:3.2rem; border-radius:.6rem; border:1px solid rgba(255,255,255,.14);
    background:rgba(255,255,255,.04); color:#fff; padding:0 1rem; font-size:.95rem;
  }
  .mb-select:focus{ outline:none; border-color:var(--mb-o); background:rgba(255,255,255,.08); box-shadow:0 0 0 4px rgba(255,106,1,.18); }
  .mb-select.is-invalid{ border-color:#ff8a8a; }
  .mb-select option{ color:#111; }
  .mb-field{ margin-bottom:1.2rem; }

  .mb-remember{
    display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:.6rem;
    margin:-.4rem 0 1.4rem;
  }
  .mb-remember .first{ display:flex; align-items:center; gap:.5rem; }
  .mb-remember input[type="checkbox"]{ width:18px; height:18px; accent-color:var(--mb-o); cursor:pointer; }
  .mb-remember label{ color:rgba(255,255,255,.75); font-size:.85rem; font-weight:600; margin:0; cursor:pointer; }
  .mb-remember .second a{ color:var(--mb-o2); font-size:.85rem; font-weight:600; text-decoration:none; }
  .mb-remember .second a:hover{ text-decoration:underline; }

  @media (max-width:575.98px){
    .mb-auth-card--wide{ max-width:100%; }
  }
</style>

<div class="mb-auth">
  <div class="mb-auth__wrap">

    {{-- Panel visual --}}
    <div class="mb-auth__visual">
      <div class="bg" style="background-image:url('{{ $loginBg['url'] }}'); background-position: {{ $loginBg['pos'] }};"></div>
      <div class="ov"></div>
      <div class="mb-auth-logo"><img src="{{ \App\Services\ImagenHomeService::logoTexto() }}" alt="MadBarzz"></div>

      <div class="mb-auth-quote">
        <h2>Tu disciplina<br>construye tu fuerza.</h2>
        <p>Accede a tu cuenta para ver tus rutinas, pagos y progreso en Calistenia MadBarzz.</p>
      </div>
    </div>

    {{-- Formulario --}}
    <div class="mb-auth__form">
      <div class="mb-auth-card" id="authCard">

        {{-- ── Panel: Iniciar sesión ───────────────────────────────────── --}}
        <div>
          <h1 class="mb-auth-h">Bienvenido de vuelta 👋</h1>
          <p class="mb-auth-sub">Inicia sesión para continuar tu entrenamiento</p>

          <form method="POST" action="{{ route('login') }}">
            @csrf
            <label for="email">Correo o usuario</label>
            <div class="mb-input mb-input--no-icon">
              <input type="text" name="email" id="email" placeholder="tucorreo@ejemplo.com o usuario" value="{{ old('email') }}" autocomplete="username">
              <i class="fa-solid fa-envelope mb-icon-right"></i>
            </div>
            @error('email')<div class="mb-err">{{ $message }}</div>@enderror

            <label for="password">Contraseña</label>
            <div class="mb-input mb-input--no-icon">
              <input type="password" name="password" id="password" placeholder="••••••••" autocomplete="current-password">
              <i class="fa-solid fa-eye mb-pass-toggle" data-target="password" title="Mostrar/ocultar"></i>
            </div>
            @error('password')<div class="mb-err">{{ $message }}</div>@enderror

            <div class="mb-remember">
              <div class="first">
                <input type="checkbox" name="remember" id="remember">
                <label for="remember">Recordarme</label>
              </div>
              <div class="second">
                <a href="{{ route('password.request') }}">¿Olvidaste tu contraseña?</a>
              </div>
            </div>

            <button type="submit" class="mb-submit"><i class="fa-solid fa-right-to-bracket me-2"></i> Iniciar sesión</button>
          </form>

          <p class="mb-auth-foot">¿Todavía no sos socio? Acercate a recepción para inscribirte.</p>
          <p class="mb-auth-foot" style="margin-top:.6rem;">
            <a href="{{ $whatsappLink }}" target="_blank"><i class="fa-brands fa-whatsapp"></i> ¿Problemas para acceder? Escribinos por WhatsApp</a>
          </p>
        </div>

      </div>
    </div>

  </div>
</div>

@endsection
