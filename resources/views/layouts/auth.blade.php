<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MadBarzz · Acceso</title>
  <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/bootstrap.min.css') }}">
  <link rel="icon" type="image/png" href="{{ asset('assets_private/images/logo_mad.png') }}">
  <link rel="stylesheet" href="{{ asset('assets/font/flaticon_mycollection.css') }}">
  <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/fontawesome.min.css') }}">
  <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/style.css') }}">
  <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/style-dark.css') }}">
  <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/responsive.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/css/public-ui.css') }}">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">
  <style>
    body{font-family:'Poppins',sans-serif;}
    .mb-auth-shell{min-height:100vh;background:var(--mb-ink,#0c0d10);display:flex;flex-direction:column;}
    /* registro centrado sobre fondo oscuro */
    .mb-auth-shell .login-register{width:100%;padding:90px 16px 60px;}
    .mb-auth-shell .box h3{color:#fff;}
  </style>
</head>
<body class="light-d mb-auth-body">
  <a href="{{ route('home') }}" class="mb-back-home"><i class="fa-solid fa-arrow-left"></i> Volver al inicio</a>
  <main class="mb-auth-shell">
    @yield('contenido')
  </main>

  <script src="{{ asset('assets/js/jquery.min.js') }}"></script>
  <script src="{{ asset('assets/js/bootstrap.min.js') }}"></script>
  <script>
    // mostrar/ocultar contraseña
    document.addEventListener('click', function(e){
      var t = e.target.closest('.mb-pass-toggle'); if(!t) return;
      var inp = document.getElementById(t.dataset.target); if(!inp) return;
      var show = inp.type === 'password'; inp.type = show ? 'text' : 'password';
      t.classList.toggle('fa-eye', !show); t.classList.toggle('fa-eye-slash', show);
    });
  </script>
</body>
</html>
