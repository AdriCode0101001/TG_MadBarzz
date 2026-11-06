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
        <h2>Ya casi<br>estás adentro.</h2>
        <p>Confirmá tu correo para empezar a usar tu cuenta de Calistenia MadBarzz.</p>
      </div>
    </div>

    <div class="mb-auth__form">
      <div class="mb-auth-card">
        <h1 class="mb-auth-h">Verificá tu correo</h1>
        <p class="mb-auth-sub">
          ¡Gracias por registrarte! Antes de empezar, confirmá tu dirección de correo haciendo clic en el
          enlace que te enviamos. Si no te llegó, te podemos mandar otro.
        </p>

        @if (session('status') == 'verification-link-sent')
          <div class="mb-ok">Te enviamos un nuevo enlace de verificación al correo que usaste para registrarte.</div>
        @endif

        <form method="POST" action="{{ route('verification.send') }}">
          @csrf
          <button type="submit" class="mb-submit"><i class="fa-solid fa-paper-plane me-2"></i> Reenviar enlace de verificación</button>
        </form>

        <form method="POST" action="{{ route('logout') }}" style="margin-top:1.2rem;">
          @csrf
          <button type="submit" class="mb-auth-foot" style="background:none;border:none;padding:0;cursor:pointer;text-decoration:underline;">
            Cerrar sesión
          </button>
        </form>
      </div>
    </div>

  </div>
</div>
@endsection
