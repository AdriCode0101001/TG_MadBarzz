@extends('layouts.auth')
@section('contenido')

@php
    $sedes = $sedes ?? collect();
    $sedeSeleccionada = old('id_sede', $sedes->count() === 1 ? $sedes->first()->id_sede : '');
@endphp

{{-- ===== Estilos propios de esta vista: el shell .mb-auth/.mb-input ya
     existe (lo usa login.blade.php, ver public-ui.css), pero está pensado
     para un form corto de 2 campos. Acá se ensancha la tarjeta y se agrega
     el estilo de <select>, que la plantilla nunca necesitó antes (login no
     tiene ninguno). Todo scoped a esta página, no toca public-ui.css. ===== */
--}}
<style>
  .mb-auth-card--wide { max-width: 720px; }
  .mb-auth-card--wide .mb-input { margin-bottom: 0; }
  .mb-auth-card--wide label { margin-top: 0; }
  .mb-select {
    width: 100%;
    height: 3.2rem;
    border-radius: .6rem;
    border: 1px solid rgba(255,255,255,.14);
    background: rgba(255,255,255,.04);
    color: #fff;
    padding: 0 1rem;
    font-size: .95rem;
  }
  .mb-select:focus {
    outline: none;
    border-color: var(--mb-o);
    background: rgba(255,255,255,.08);
    box-shadow: 0 0 0 4px rgba(255,106,1,.18);
  }
  .mb-select.is-invalid { border-color: #ff8a8a; }
  .mb-select option { color: #111; }
  .mb-field { margin-bottom: 1.2rem; }
  @media (max-width: 575.98px) {
    .mb-auth-card--wide { max-width: 100%; }
  }
</style>

<div class="mb-auth">
  <div class="mb-auth__wrap">

    {{-- Panel visual --}}
    <div class="mb-auth__visual">
      <div class="bg" style="background-image:url('{{ asset('assets/images/entrenaconnosotros.jpg') }}');"></div>
      <div class="ov"></div>
      <div class="mb-auth-logo"><img src="{{ asset('assets_private/images/log_text_mad.png') }}" alt="MadBarzz"></div>
      <div class="mb-auth-quote">
        <h2>Sumate a<br>Calistenia MadBarzz.</h2>
        <p>Creá tu cuenta, elegí tu sede y arrancá tu progreso. La membresía se activa cuando pasás a pagar al gimnasio.</p>
      </div>
    </div>

    {{-- Formulario --}}
    <div class="mb-auth__form">
      <div class="mb-auth-card mb-auth-card--wide">
        <h1 class="mb-auth-h">Creá tu cuenta</h1>
        <p class="mb-auth-sub">Completá tus datos para registrarte</p>

        <form method="POST" action="{{ route('register') }}">
          @csrf

          <div class="row g-3">
            <div class="col-md-6 mb-field">
              <label for="nombre_completo">Nombres</label>
              <div class="mb-input mb-input--no-icon">
                <input type="text" name="nombre_completo" id="nombre_completo" placeholder="Ej. Juan Carlos"
                       value="{{ old('nombre_completo') }}" required autofocus>
              </div>
              @error('nombre_completo')<div class="mb-err">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6 mb-field">
              <label for="apellido_paterno">Apellido paterno</label>
              <div class="mb-input mb-input--no-icon">
                <input type="text" name="apellido_paterno" id="apellido_paterno"
                       value="{{ old('apellido_paterno') }}" required>
              </div>
              @error('apellido_paterno')<div class="mb-err">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6 mb-field">
              <label for="apellido_materno">Apellido materno</label>
              <div class="mb-input mb-input--no-icon">
                <input type="text" name="apellido_materno" id="apellido_materno"
                       value="{{ old('apellido_materno') }}" required>
              </div>
              @error('apellido_materno')<div class="mb-err">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6 mb-field">
              <label for="ci">Cédula de identidad</label>
              <div class="mb-input mb-input--no-icon">
                <input type="text" name="ci" id="ci" placeholder="Ej. 8765432" value="{{ old('ci') }}" required>
              </div>
              @error('ci')<div class="mb-err">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6 mb-field">
              <label>Teléfono</label>
              <x-phone-input :required="true" />
            </div>

            <div class="col-md-6 mb-field">
              <label for="genero">Género</label>
              <select name="genero" id="genero" class="mb-select @error('genero') is-invalid @enderror" required>
                <option value="">Seleccioná…</option>
                <option value="masculino" @selected(old('genero') == 'masculino')>Masculino</option>
                <option value="femenino"  @selected(old('genero') == 'femenino')>Femenino</option>
                <option value="otro"      @selected(old('genero') == 'otro')>Otro</option>
              </select>
              @error('genero')<div class="mb-err">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6 mb-field">
              <label for="fecha_nacimiento">Fecha de nacimiento</label>
              <div class="mb-input mb-input--no-icon">
                <input type="date" name="fecha_nacimiento" id="fecha_nacimiento"
                       value="{{ old('fecha_nacimiento') }}" required>
              </div>
              @error('fecha_nacimiento')<div class="mb-err">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6 mb-field">
              <label for="id_sede">Sede</label>
              <select name="id_sede" id="id_sede" class="mb-select @error('id_sede') is-invalid @enderror" required>
                <option value="">Seleccioná una sede…</option>
                @foreach ($sedes as $sede)
                  <option value="{{ $sede->id_sede }}" @selected((string) $sedeSeleccionada === (string) $sede->id_sede)>
                    {{ $sede->nombre }}
                  </option>
                @endforeach
              </select>
              @error('id_sede')<div class="mb-err">{{ $message }}</div>@enderror
              @if ($sedes->isEmpty())
                <div class="mb-err">Todavía no hay sedes activas cargadas — avisale al gimnasio.</div>
              @endif
            </div>

            <div class="col-md-12 mb-field">
              <label for="email">Correo electrónico</label>
              <div class="mb-input mb-input--no-icon">
                <input type="email" name="email" id="email" placeholder="tucorreo@ejemplo.com"
                       value="{{ old('email') }}" required autocomplete="username">
                <i class="fa-solid fa-envelope mb-icon-right"></i>
              </div>
              @error('email')<div class="mb-err">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6 mb-field">
              <label for="password">Contraseña</label>
              <div class="mb-input mb-input--no-icon">
                <input type="password" name="password" id="password" placeholder="••••••••" required>
                <i class="fa-solid fa-eye mb-pass-toggle" data-target="password" title="Mostrar/ocultar"></i>
              </div>
              @error('password')<div class="mb-err">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6 mb-field">
              <label for="password_confirmation">Confirmar contraseña</label>
              <div class="mb-input mb-input--no-icon">
                <input type="password" name="password_confirmation" id="password_confirmation" placeholder="••••••••" required>
                <i class="fa-solid fa-eye mb-pass-toggle" data-target="password_confirmation" title="Mostrar/ocultar"></i>
              </div>
            </div>
          </div>

          <p class="mb-auth-foot" style="margin-top:.4rem;margin-bottom:1.2rem;">
            Tus datos se usan para gestionar tu acceso y tu ficha de socio en Calistenia MadBarzz.
          </p>

          <button type="submit" class="mb-submit"><i class="fa-solid fa-user-plus me-2"></i> Crear cuenta</button>
        </form>

        <p class="mb-auth-foot">¿Ya tenés cuenta?
          <a href="{{ route('login') }}">Iniciar sesión</a>
        </p>
      </div>
    </div>

  </div>
</div>

@endsection
