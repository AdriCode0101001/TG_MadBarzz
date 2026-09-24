@extends('layouts.auth')
@section('contenido')

@php
    $telefonoRecepcionista = app('App\Http\Controllers\RecepcionistaController')->getRecepcionistaActivo();
    $whatsappLink = $telefonoRecepcionista
        ? 'https://wa.me/591' . $telefonoRecepcionista . '?text=Hola,%20quiero%20solicitar%20acceso%20a%20MadBarzz'
        : '#';

    $loginImage = asset('assets/images/login-bg.jpg');
    $sedes = $sedes ?? collect();
    $sedeSeleccionada = old('id_sede', $sedes->count() === 1 ? $sedes->first()->id_sede : '');

    // Qué pestaña abre primero: /login entra en "login", /register entra
    // directo en "register" (ver AuthenticatedSessionController::create() y
    // RegisteredUserController::create()). Si el formulario de registro
    // vuelve con errores de validación, se queda en "register" aunque el
    // valor por defecto fuera otro.
    $tabActiva = old('_tab', $tabActiva ?? 'login');
    if ($errors->has('nombre_completo') || $errors->has('ci') || $errors->has('id_sede')) {
        $tabActiva = 'register';
    }
@endphp

{{-- ===== Login y registro juntos en una sola pantalla, con pestañas
     ("Iniciar sesión" / "Crear cuenta"), como propone login.html de GymOn
     dark (que trae ambos formularios lado a lado). El registro de MadBarzz
     tiene 11 campos (mucho más largo que el de GymOn), así que en vez de
     mostrar las dos cajas siempre visibles una al lado de la otra (se vería
     aplastado/desbordado), se resuelve con pestañas: un solo formulario
     visible a la vez, la tarjeta se ensancha solo cuando la pestaña activa
     es "Crear cuenta". La pestaña inicial se decide en el servidor
     ($tabActiva) para que /login y /register abran cada uno en la suya sin
     depender de JS. Todo scoped a esta vista. ===== --}}
<style>
  .mb-auth-tabs{
    display:flex; gap:.4rem; margin-bottom:2rem;
    background:rgba(255,255,255,.05); border-radius:13px; padding:.35rem;
  }
  .mb-auth-tabs .mb-auth-tab{
    flex:1; border:none; background:transparent; color:rgba(255,255,255,.6);
    font-weight:700; font-size:.92rem; padding:.75rem 1rem; border-radius:10px;
    cursor:pointer; transition:background .2s,color .2s;
  }
  .mb-auth-tabs .mb-auth-tab.is-active{
    background:linear-gradient(120deg,var(--mb-o),var(--mb-o-deep)); color:#fff;
    box-shadow:0 8px 20px rgba(255,106,1,.35);
  }
  .mb-auth-tab-panel{ display:none; }
  .mb-auth-tab-panel.is-active{ display:block; animation:mbFadeIn .25s ease; }
  @keyframes mbFadeIn{ from{opacity:0;transform:translateY(6px);} to{opacity:1;transform:translateY(0);} }

  #authCard{ transition:max-width .25s ease; }
  .mb-auth-card--wide{ max-width:720px; }
  .mb-auth-card--wide .mb-input{ margin-bottom:0; }
  .mb-auth-card--wide label{ margin-top:0; }

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
      <div class="bg" style="background-image:url('{{ $loginImage }}');"></div>
      <div class="ov"></div>
      <div class="mb-auth-logo"><img src="{{ asset('assets_private/images/log_text_mad.png') }}" alt="MadBarzz"></div>

      <div class="mb-auth-quote" data-quote="login" @if($tabActiva !== 'login') style="display:none;" @endif>
        <h2>Tu disciplina<br>construye tu fuerza.</h2>
        <p>Accede a tu cuenta para ver tus rutinas, pagos y progreso en Calistenia MadBarzz.</p>
      </div>
      <div class="mb-auth-quote" data-quote="register" @if($tabActiva !== 'register') style="display:none;" @endif>
        <h2>Sumate a<br>Calistenia MadBarzz.</h2>
        <p>Creá tu cuenta, elegí tu sede y arrancá tu progreso. La membresía se activa cuando pasás a pagar al gimnasio.</p>
      </div>
    </div>

    {{-- Formulario --}}
    <div class="mb-auth__form">
      <div class="mb-auth-card @if($tabActiva === 'register') mb-auth-card--wide @endif" id="authCard">

        <div class="mb-auth-tabs" role="tablist">
          <button type="button" class="mb-auth-tab @if($tabActiva === 'login') is-active @endif" data-tab="login">Iniciar sesión</button>
          <button type="button" class="mb-auth-tab @if($tabActiva === 'register') is-active @endif" data-tab="register">Crear cuenta</button>
        </div>

        {{-- ── Panel: Iniciar sesión ───────────────────────────────────── --}}
        <div class="mb-auth-tab-panel @if($tabActiva === 'login') is-active @endif" data-panel="login">
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

          <p class="mb-auth-foot">¿No tenés cuenta?
            <a href="javascript:void(0)" data-switch-to="register">Creá una desde acá</a>
          </p>
          <p class="mb-auth-foot" style="margin-top:.6rem;">
            <a href="{{ $whatsappLink }}" target="_blank"><i class="fa-brands fa-whatsapp"></i> ¿Problemas para acceder? Escribinos por WhatsApp</a>
          </p>
        </div>

        {{-- ── Panel: Crear cuenta ─────────────────────────────────────── --}}
        <div class="mb-auth-tab-panel @if($tabActiva === 'register') is-active @endif" data-panel="register">
          <h1 class="mb-auth-h">Creá tu cuenta</h1>
          <p class="mb-auth-sub">Completá tus datos para registrarte</p>

          <form method="POST" action="{{ route('register') }}">
            @csrf
            <input type="hidden" name="_tab" value="register">

            <div class="row g-3">
              <div class="col-md-6 mb-field">
                <label for="nombre_completo">Nombres</label>
                <div class="mb-input mb-input--no-icon">
                  <input type="text" name="nombre_completo" id="nombre_completo" placeholder="Ej. Juan Carlos"
                         value="{{ old('nombre_completo') }}" required>
                </div>
                @error('nombre_completo')<div class="mb-err">{{ $message }}</div>@enderror
              </div>

              <div class="col-md-6 mb-field">
                <label for="apellido_paterno">Apellido paterno</label>
                <div class="mb-input mb-input--no-icon">
                  <input type="text" name="apellido_paterno" id="apellido_paterno" value="{{ old('apellido_paterno') }}" required>
                </div>
                @error('apellido_paterno')<div class="mb-err">{{ $message }}</div>@enderror
              </div>

              <div class="col-md-6 mb-field">
                <label for="apellido_materno">Apellido materno</label>
                <div class="mb-input mb-input--no-icon">
                  <input type="text" name="apellido_materno" id="apellido_materno" value="{{ old('apellido_materno') }}" required>
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
                  <input type="date" name="fecha_nacimiento" id="fecha_nacimiento" value="{{ old('fecha_nacimiento') }}" required>
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
                <label for="email_registro">Correo electrónico</label>
                <div class="mb-input mb-input--no-icon">
                  <input type="email" name="email" id="email_registro" placeholder="tucorreo@ejemplo.com"
                         value="{{ old('email') }}" required autocomplete="username">
                  <i class="fa-solid fa-envelope mb-icon-right"></i>
                </div>
                @error('email')<div class="mb-err">{{ $message }}</div>@enderror
              </div>

              <div class="col-md-6 mb-field">
                <label for="password_registro">Contraseña</label>
                <div class="mb-input mb-input--no-icon">
                  <input type="password" name="password" id="password_registro" placeholder="••••••••" required>
                  <i class="fa-solid fa-eye mb-pass-toggle" data-target="password_registro" title="Mostrar/ocultar"></i>
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

            {{-- Grupo 3 legal, parte 1: casilla OBLIGATORIA, nunca premarcada
                 (sin el atributo "checked" fijo — old() solo repone lo que el
                 propio usuario ya había marcado si el formulario vuelve por
                 otro error, no la marca por default). Validación real del
                 lado del servidor en RegisteredUserController::store(). --}}
            <div class="mb-field" style="margin-bottom:1.1rem;">
              <div style="display:flex;align-items:flex-start;gap:.55rem;">
                <input type="checkbox" name="acepto_terminos" id="acepto_terminos" value="1" required
                       @checked(old('acepto_terminos'))
                       style="width:18px;height:18px;min-width:18px;accent-color:var(--mb-o);cursor:pointer;margin-top:.2rem;">
                <label for="acepto_terminos" style="margin:0;font-weight:400;">
                  Acepto los <a href="{{ route('legal.terminos') }}" target="_blank" rel="noopener">Términos y Condiciones</a>
                  y la <a href="{{ route('legal.privacidad') }}" target="_blank" rel="noopener">Política de Privacidad</a>.
                </label>
              </div>
              @error('acepto_terminos')<div class="mb-err">{{ $message }}</div>@enderror
            </div>

            <button type="submit" class="mb-submit"><i class="fa-solid fa-user-plus me-2"></i> Crear cuenta</button>
          </form>

          <p class="mb-auth-foot">¿Ya tenés cuenta?
            <a href="javascript:void(0)" data-switch-to="login">Iniciá sesión desde acá</a>
          </p>
        </div>

      </div>
    </div>

  </div>
</div>

<script>
  (function () {
    var card   = document.getElementById('authCard');
    var tabs   = card.querySelectorAll('.mb-auth-tab');
    var panels = card.querySelectorAll('.mb-auth-tab-panel');
    var quotes = document.querySelectorAll('.mb-auth-quote[data-quote]');

    function activar(nombre) {
      tabs.forEach(function (t) { t.classList.toggle('is-active', t.dataset.tab === nombre); });
      panels.forEach(function (p) { p.classList.toggle('is-active', p.dataset.panel === nombre); });
      quotes.forEach(function (q) { q.style.display = (q.dataset.quote === nombre) ? '' : 'none'; });
      card.classList.toggle('mb-auth-card--wide', nombre === 'register');
    }

    tabs.forEach(function (tab) {
      tab.addEventListener('click', function () { activar(tab.dataset.tab); });
    });

    document.querySelectorAll('[data-switch-to]').forEach(function (link) {
      link.addEventListener('click', function () { activar(link.dataset.switchTo); });
    });
  })();
</script>
@endsection
