<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('titulo', 'Error') · MadBarzz</title>
  <link rel="icon" type="image/png" href="{{ asset('assets_private/images/logo_mad.png') }}">

  <script>
    // Mismo mecanismo que layouts/private.blade.php: leer el tema guardado
    // ANTES de pintar, para que la página de error no "parpadee" con el
    // tema equivocado si el usuario tenía el panel en oscuro/claro.
    (function () {
      var tema = 'dark';
      try { tema = localStorage.getItem('theme') || 'dark'; } catch (e) {}
      document.documentElement.setAttribute('data-theme', tema);
      window.__mbTemaError = tema;
    })();
  </script>

  <link href="{{ asset('assets_private/css/style.css') }}" rel="stylesheet">
  <link href="{{ asset('assets_private/icons/fontawesome/css/all.min.css') }}" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

  <style>
    /* ===== Acento MadBarzz: la plantilla Gymove trae ".error-page .error-text"
       y ".error-page h4" en violeta propio (#A02CFA) — se reemplaza por el
       naranja de marca (#FD6A01, el mismo que usa la home pública) para que
       la página de error se sienta parte de MadBarzz, no de la plantilla
       genérica. ===== */
    :root { --mb-error-accent: #FD6A01; --mb-error-accent-dark: #e05f00; }

    body { font-family: 'Poppins', sans-serif; margin: 0; }

    /* Fondo de página: Gymove no define un dark real para <body> (solo para
       componentes puntuales como ".authincation-content") — se agrega acá,
       con los mismos tonos que ya usa el resto del panel, para que se sienta
       consistente con el dashboard en vez de quedar gris claro fijo. */
    body { background-color: #f4f4f5; }
    body[data-theme-version="dark"] { background-color: #0a0a0b; }

    .mb-error-shell {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 24px 16px;
      box-sizing: border-box;
    }

    .mb-error-card { max-width: 560px; width: 100%; text-align: center; }

    /* !important necesario: style.css fuerza "[data-theme-version=dark] h1...h6
       { color:#fff !important }" — sin esto, en modo oscuro el número y el
       título quedarían blancos en vez de naranja (cualquier especificidad
       normal pierde contra un !important de la plantilla). */
    .error-page .error-text { color: var(--mb-error-accent) !important; }
    .error-page h4, .error-page .h4 { color: var(--mb-error-accent) !important; }
    .error-page .error-icon { color: var(--mb-error-accent); margin-right: .4rem; }
    .error-page p { margin-top: .5rem; }
    body[data-theme-version="dark"] .error-page p { color: #a1a1aa; }

    .mb-error-actions { margin-top: 1.25rem; }
    .mb-error-actions .btn-primary {
      background: var(--mb-error-accent);
      border-color: var(--mb-error-accent);
      font-weight: 600;
    }
    .mb-error-actions .btn-primary:hover,
    .mb-error-actions .btn-primary:focus {
      background: var(--mb-error-accent-dark);
      border-color: var(--mb-error-accent-dark);
      color: #fff;
    }

    @media (max-width: 575.98px) {
      .mb-error-shell { padding: 16px; }
    }
  </style>

  @stack('estilos')
</head>

<body class="h-100">
  @php
    // Robusto a propósito: si por lo que sea Auth::check()/route() fallaran
    // en medio de un error real, la página de error NO debe romperse por eso.
    try {
        $mbVolverUrlDefault = \Illuminate\Support\Facades\Auth::check() ? route('dashboard') : route('home');
    } catch (\Throwable $e) {
        $mbVolverUrlDefault = url('/');
    }
  @endphp

  <div class="mb-error-shell">
    <div class="mb-error-card">
      <div class="form-input-content text-center error-page">
        <h1 class="error-text font-weight-bold">@yield('codigo')</h1>
        <h4><i class="@yield('icono', 'fa-solid fa-triangle-exclamation') error-icon"></i>@yield('titulo')</h4>
        <p>@yield('mensaje')</p>
        <div class="mb-error-actions">
          <a class="btn btn-primary" href="@yield('boton_url', $mbVolverUrlDefault)">@yield('boton_texto', 'Volver al inicio')</a>
        </div>
      </div>
    </div>
  </div>

  <script>
    // Aplica el atributo REAL que usa el CSS de la plantilla para sus
    // overrides oscuro/claro puntuales (ej. ".authincation-content"):
    // [data-theme-version=dark] en el <body> — mismo mecanismo que
    // layouts/private.blade.php (mbApplyTheme).
    document.body.setAttribute('data-theme-version', window.__mbTemaError || 'dark');
  </script>
</body>
</html>
