<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $titulo }} · Calistenia MadBarzz</title>
  <link rel="stylesheet" href="{{ asset('assets_gymon/css/bootstrap.min.css') }}">
  <link rel="icon" href="{{ \App\Services\ImagenHomeService::logoEmblema() }}">
  <link rel="shortcut icon" href="{{ \App\Services\ImagenHomeService::logoEmblema() }}">
  <link rel="stylesheet" href="{{ asset('assets_gymon/font/flaticon_mycollection.css') }}">
  <link rel="stylesheet" href="{{ asset('assets_gymon/css/fontawesome.min.css') }}">
  <link rel="stylesheet" href="{{ asset('assets_gymon/css/style.css') }}">
  <link rel="stylesheet" type="text/css" href="{{ asset('assets_gymon/css/style-dark.css') }}">
  <link rel="stylesheet" href="{{ asset('assets_gymon/css/responsive.css') }}">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root { --theme-color: #FD6A01; }

    .mb-legal-header{padding:28px 0;border-bottom:1px solid rgba(255,255,255,.08);}
    .mb-legal-header .container{display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;}
    .mb-legal-header img{height:42px;}
    .mb-legal-header a.volver{color:#fff;opacity:.85;text-decoration:none;font-size:.95rem;}
    .mb-legal-header a.volver:hover{opacity:1;color:var(--theme-color);}

    .mb-legal-banner{
      background:linear-gradient(90deg,#FD6A01,#ff8c3a);
      color:#111;font-weight:600;text-align:center;
      padding:14px 16px;margin:0 0 2rem;border-radius:.5rem;
      display:flex;align-items:center;justify-content:center;gap:.6rem;flex-wrap:wrap;
    }

    .mb-legal-wrap{max-width:860px;margin:0 auto;padding:2.5rem 1rem 5rem;color:#e7e7ea;}
    .mb-legal-wrap h1{font-size:2rem;font-weight:800;color:#fff;margin-bottom:1.5rem;}
    .mb-legal-wrap h2{font-size:1.4rem;font-weight:700;color:#fff;margin-top:2.2rem;margin-bottom:.9rem;padding-top:.6rem;border-top:1px solid rgba(255,255,255,.08);}
    .mb-legal-wrap h3{font-size:1.1rem;font-weight:700;color:#fff;margin-top:1.5rem;margin-bottom:.6rem;}
    .mb-legal-wrap p, .mb-legal-wrap li{line-height:1.7;color:#cfcfd6;}
    .mb-legal-wrap ul, .mb-legal-wrap ol{padding-left:1.4rem;margin-bottom:1rem;}
    .mb-legal-wrap strong{color:#fff;}
    .mb-legal-wrap blockquote{
      border-left:3px solid var(--theme-color);
      background:rgba(253,106,1,.08);
      padding:.9rem 1.1rem;margin:1.2rem 0;border-radius:0 .4rem .4rem 0;color:#f0e6da;
    }
    .mb-legal-wrap blockquote p{color:inherit;margin-bottom:0;}
    .mb-legal-wrap table{width:100%;border-collapse:collapse;margin:1.2rem 0;font-size:.92rem;}
    .mb-legal-wrap th, .mb-legal-wrap td{border:1px solid rgba(255,255,255,.12);padding:.6rem .7rem;text-align:left;vertical-align:top;}
    .mb-legal-wrap th{background:rgba(255,255,255,.06);color:#fff;}
    .mb-legal-wrap hr{border-color:rgba(255,255,255,.1);margin:2rem 0;}
    .mb-legal-wrap code{background:rgba(255,255,255,.08);padding:.1rem .35rem;border-radius:.25rem;color:#ffd8ae;}
    .mb-legal-pendiente{
      background:rgba(253,106,1,.18);color:#ffb877;border:1px dashed var(--theme-color);
      padding:.05rem .4rem;border-radius:.3rem;font-weight:600;white-space:normal;
    }
  </style>
</head>
<body style="background:var(--mb-ink,#0c0d10);">

  <header class="mb-legal-header">
    <div class="container">
      <a href="{{ route('home') }}"><img src="{{ \App\Services\ImagenHomeService::logoEmblema() }}" alt="MadBarzz"></a>
      <a href="{{ route('home') }}" class="volver"><i class="fa-solid fa-arrow-left me-1"></i> Volver al inicio</a>
    </div>
  </header>

  <div class="mb-legal-wrap">
    @if ($esBorrador)
      <div class="mb-legal-banner">
        <i class="fa-solid fa-triangle-exclamation"></i>
        Documento preliminar en revisión — todavía no es la versión legal definitiva.
      </div>
    @endif

    {!! $html !!}
  </div>

  @include('partials.gymon-footer')

  <script src="{{ asset('assets_gymon/js/jquery.min.js') }}"></script>
  <script src="{{ asset('assets_gymon/js/bootstrap.min.js') }}"></script>
</body>
</html>
