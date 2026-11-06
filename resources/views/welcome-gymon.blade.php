<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Calistenia MadBarzz · Domina tu propio cuerpo</title>
  <link rel="stylesheet" href="{{ asset('assets_gymon/css/bootstrap.min.css') }}">
  <link rel="icon" href="{{ \App\Services\ImagenHomeService::logoEmblema() }}">
  <link rel="shortcut icon" href="{{ \App\Services\ImagenHomeService::logoEmblema() }}">
  <link rel="stylesheet" href="{{ asset('assets_gymon/css/owl.carousel.min.css') }}">
  <link rel="stylesheet" href="{{ asset('assets_gymon/font/flaticon_mycollection.css') }}">
  <link rel="stylesheet" href="{{ asset('assets_gymon/css/owl.theme.default.min.css') }}">
  <link rel="stylesheet" href="{{ asset('assets_gymon/css/fontawesome.min.css') }}">
  <link rel="stylesheet" href="{{ asset('assets_gymon/css/jquery.fancybox.min.css') }}">
  <link rel="stylesheet" href="{{ asset('assets_gymon/css/nice-select.css') }}">
  <link rel="stylesheet" href="{{ asset('assets_gymon/css/style.css') }}">
  <link rel="stylesheet" type="text/css" href="{{ asset('assets_gymon/css/style-dark.css') }}">
  <link rel="stylesheet" href="{{ asset('assets_gymon/css/responsive.css') }}">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">
  <style>
    /* ===== ROJO → NARANJA: la plantilla centraliza casi todo su acento rojo
       en esta variable (--theme-color, usada ~100 veces en botones, bordes,
       tabla de horario, footer, etc.). Redefinirla acá alcanza para
       recolorear toda la página de una sola vez, en claro y oscuro por
       igual (ninguna de las dos hojas de estilo la vuelve a definir aparte
       por tema). Mismo naranja que ya se usaba en el banner de vista previa. ===== */
    :root { --theme-color: #FD6A01; }

    /* ===== Preloader (pantalla de carga inicial): style.css define
       ".preloader" DOS veces — una con fondo rojo fijo (#f42828, línea
       ~6316) y otra más abajo (línea ~7445) con fondo
       "background-color:var(--theme-color)", que GANA por venir después en
       el mismo archivo. Como --theme-color ya es nuestro naranja, quedaba
       fondo naranja con el texto ("CARGANDO") en blanco (color original de
       ".load-text") — bajo contraste, y encima cada letra hace un pulso de
       opacidad (100%→0%) que en los valles casi la borra. Se invierte: fondo
       oscuro (el mismo negro del resto del sitio) y letras en naranja, que
       sí contrastan fuerte contra el negro incluso a mitad del pulso. Solo
       color — no se toca el pulso ni la lógica de cuándo aparece/desaparece
       (eso sigue en custom.js / la clase "page-loaded" del body). ===== */
    .preloader { background: #0a0a0a; }
    .loader-wrap-heading .load-text { color: var(--theme-color, #FD6A01); }

    /* ===== Logo del header: la plantilla fija .header-logo a img a
       width:180px (pensado para el wordmark ancho de GymOn, 503x171). El
       logo de MadBarzz es un emblema casi cuadrado (664x622): a lo ancho
       quedaría gigante (~168px de alto). Se iguala por ALTO al del original
       (180px de ancho x su proporción 171/503 ≈ 61px de alto) para que
       ocupe el mismo espacio vertical en el header, sin importar el ancho. ===== */
    .header-logo a img.mb-header-logo-img { width: auto !important; height: 80px; }

    /* Logo del header: 80px de alto para que quede DENTRO de la barra negra
       (a 96px se pasaba hacia arriba y tapaba la franja naranja del top-bar).
       Contorno circular en degradé naranja, bien tenue (se nota poco): el
       degradé se ve en el padding de la imagen, recortado en círculo. */
    .header-logo a img.mb-header-logo-img {
      box-sizing: border-box;
      aspect-ratio: 1 / 1;
      object-fit: contain;
      border-radius: 50%;
      padding: .5px;
      background: linear-gradient(135deg, rgba(253,106,1,.7) 0%, rgba(253,106,1,.2) 55%, rgba(253,106,1,.5) 100%);
      box-shadow: none;
    }

    /* ===== Wordmark de MadBarzz como adorno de sección (arriba de
       "Horarios" y "Elegí tu Membresía"): antes era el emblema redondo
       (casi cuadrado); ahora es el logo de texto "MADBARZZ"
       (log_text_mad.png, 952x247 — bien ancho), así que en vez del
       width:50px genérico de la plantilla (pensado para un ícono cuadrado)
       se le da un ancho propio acorde a esa proporción.

       Letras NEGRAS sobre fondo oscuro: en vez de un halo/óvalo difuso
       DETRÁS de toda la imagen (como se probó antes), ahora el brillo sigue
       el CONTORNO real de las letras con filter:drop-shadow — a diferencia
       de box-shadow (que dibuja un rectángulo), drop-shadow usa el canal
       alfa del PNG, así que el resplandor abraza la silueta de cada letra.
       Se apilan 3 drop-shadow (chico y nítido + medio + grande y difuso)
       para un efecto de brillo con profundidad, no un solo borde plano.

       Arranca APAGADO (drop-shadow con alpha 0, letras casi invisibles) y
       se ENCIENDE/APAGA con transición suave cada vez que la sección entra
       o sale de pantalla — la clase ".mb-halo-lit" la agrega y saca un
       IntersectionObserver (ver el <script> antes de cerrar el body), así
       que el efecto se repite cada vez que se cruza esa sección, subiendo o
       bajando. Sin JS (o navegador muy viejo sin soporte), ese script deja
       el brillo encendido de una para que las letras nunca queden
       invisibles. Con "prefers-reduced-motion" el brillo queda fijo
       prendido, sin transición (ver el @media más abajo). ===== */
    .mb-heading-wordmark-halo {
      position: relative;
      display: inline-block;
      padding: 8px 12px;
    }
    .mb-gymon-page .heading img.mb-heading-wordmark-img {
      filter:
        drop-shadow(0 0 3px rgba(255,255,255,0))
        drop-shadow(0 0 9px rgba(255,255,255,0))
        drop-shadow(0 0 20px rgba(255,255,255,0));
      transition: filter .8s ease-out;
    }
    .mb-heading-wordmark-halo.mb-halo-lit img.mb-heading-wordmark-img {
      filter:
        drop-shadow(0 0 3px rgba(255,255,255,.95))
        drop-shadow(0 0 9px rgba(255,255,255,.75))
        drop-shadow(0 0 20px rgba(255,255,255,.5));
    }
    @media (prefers-reduced-motion: reduce) {
      .mb-gymon-page .heading img.mb-heading-wordmark-img {
        filter:
          drop-shadow(0 0 3px rgba(255,255,255,.95))
          drop-shadow(0 0 9px rgba(255,255,255,.75))
          drop-shadow(0 0 20px rgba(255,255,255,.5));
        transition: none;
      }
    }
    .mb-gymon-page .heading img.mb-heading-wordmark-img {
      display: block;
      width: 150px;
      height: auto;
      margin: 0 auto;
    }

    /* ===== Logo del header en móvil: medí la altura real de la barra
       (".desktop-nav", que a pesar del nombre es la que queda visible en
       todos los tamaños con el menú ya colapsado a hamburguesa) en los 3
       tamaños de referencia — 55px en escritorio (1280px), 90px en tablet
       (768px) y apenas 65px en celular (375px), donde el logo de 96px se
       sale de la barra y se mete sobre el contenido de abajo. Achicando por
       debajo de 992px (mismo escalón que la barra se achica) evita que se
       salga tanto en tablet como en celular. ===== */
    @media (max-width: 991.98px) {
      .header-logo a img.mb-header-logo-img { height: 70px; }
    }
    @media (max-width: 575.98px) {
      .header-logo a img.mb-header-logo-img { height: 55px; }
    }

    /* ===== Centrado del logo del header: la plantilla arma la barra con
       3 hijos flex (".nav-bar" izquierda con el menú hamburguesa + "Iniciar
       sesión", ".header-logo" en el medio, ".nav-bar" derecha con el menú
       Inicio/Nosotros/... + "Únete hoy") y "justify-content:space-between".
       Eso solo garantiza que el primer y el último hijo queden pegados a
       cada borde — el del medio NO queda centrado en la pantalla, sino en
       el punto medio de lo que sobra entre esos dos, que cambia según el
       ancho de cada lado (y en mobile/tablet el lado derecho se achica
       porque el menú completo se oculta, corriendo el logo). Se saca el
       logo del flujo del flex y se centra en el centro ABSOLUTO de la
       barra — así queda igual de centrado sin importar cuánto pesen los
       costados, en cualquier tamaño de pantalla. ===== */
    .mb-gymon-page .header-style-one .d-flex-all.justify-content-between {
      position: relative;
    }
    .mb-gymon-page .header-style-one .header-logo {
      position: absolute;
      left: 50%;
      top: 50%;
      transform: translate(-50%, -50%);
      z-index: 2;
    }

    /* ===== Wordmark arriba del menú lateral desplegable: el logo de texto
       (log_text_mad.png) tiene las letras en NEGRO, y el menú tiene fondo
       negro — sin nada detrás no se leería. En vez de una tarjeta blanca
       sólida (recuadro duro, se notaba demasiado), un HALO: un
       radial-gradient blanco que se desvanece a transparente + blur, en un
       ::before detrás de la imagen — las letras quedan legibles sobre el
       centro del halo, sin ningún borde recto visible. La imagen va
       centrada en el panel (.res-log) y más grande que el emblema redondo
       que estaba antes acá. ===== */
    .res-log { text-align: center; }

    .mb-mobile-brand-top {
      position: relative;
      display: inline-block;
      padding: 18px 26px;
      isolation: isolate;
    }
    .mb-mobile-brand-top::before {
      content: "";
      position: absolute;
      inset: -14px;
      z-index: 0;
      pointer-events: none;
      background: radial-gradient(ellipse at center, rgba(255,255,255,.95) 0%, rgba(255,255,255,.55) 45%, rgba(255,255,255,0) 72%);
      filter: blur(7px);
    }
    .mb-mobile-brand-top img.mb-mobile-wordmark-img {
      position: relative;
      z-index: 1;
      display: block;
      width: auto;
      height: 48px;
      max-width: 100%;
      margin: 0 auto;
    }

    /* ===== Corrección: las tarjetas "Clases de Calistenia / Cardio / Fitness"
       posicionan su título con position:absolute sobre la altura de la foto
       (details{position:absolute; height:100%}). Sin una foto real cargada
       (placeholder roto), la tarjeta colapsa a 0 de alto y el título se
       desparrama hacia abajo, encimándose con el título de la sección
       siguiente ("¡Comienza tu Entrenamiento!"). Fijar una altura mínima
       evita el colapso — se puede quitar cuando haya fotos reales. ===== */
    .mb-gymon-page .history-style-one .history-data { min-height: 480px; }
    .mb-gymon-page .history-style-one .history-data figure,
    .mb-gymon-page .history-style-one .history-data figure img {
      min-height: 480px; height: 100%; object-fit: cover;
    }

    /* ===== Tabla de horario semanal. El título de esta sección venía con
       position:absolute, pensado para flotar sobre el bloque de pestañas
       (Cardio/Crossfit/Gym/Powerlifting) del index-2 original — acá no hay
       pestañas (un solo rubro: calistenia), así que sin eso el título
       quedaba flotando encima de la tabla. Se vuelve a flujo normal para
       que quede arriba, sin superposición. ===== */
    .mb-gymon-page .timetable-section .table-title-box { position: static; padding-bottom: 20px; }
    .mb-gymon-page .timetable-section .table-title-box .data { transform: none; padding-bottom: 0; }
    .mb-gymon-page .timetable-section .inner-container { padding-top: 40px; }

    /* ===== Cajas de horario: mismo estilo que .time-box/.box-inner/.time
       del index-2 de GymOn (dark_version) — caja negra, borde fino gris,
       nombre de la actividad arriba, entrenador + franja horaria abajo en
       gris claro. Días en FILAS (a la izquierda) y franjas en COLUMNAS, al
       revés de como arma esto la plantilla original: ahí el día es una
       columna con position:absolute y las cajas flotan al lado (float),
       un hack que no alinea de forma confiable con una grilla real de 14
       franjas × 6 días. Se arma como <table> real (misma idea que ya se
       usó antes para reemplazar la grilla decorativa de la plantilla),
       con la celda del día "pegada" (position:sticky) para que no se
       pierda de vista al arrastrar el scroll horizontal en mobile/tablet. ===== */
    .mb-horario-tabla { border-collapse: separate; border-spacing: 8px; }

    .mb-horario-day {
      position: sticky; left: 0; z-index: 2;
      width: 120px; min-width: 120px;
      background: #000; color: #fff;
      text-align: center; text-transform: uppercase;
      font-weight: 700; font-size: .95rem; letter-spacing: .03em;
      border: 1px solid rgba(255,255,255,.15);
      border-left: 3px solid var(--theme-color, #FD6A01);
    }

    .mb-horario-box {
      width: 168px; min-width: 168px; height: 92px;
      display: flex; align-items: center; justify-content: center;
      text-align: center; padding: 6px 8px;
      background: #000; border: 1px solid #444;
    }
    .mb-horario-box .mb-horario-actividad {
      font-size: 1rem; font-weight: 700; text-transform: uppercase;
      color: var(--theme-color, #FD6A01);
    }
    .mb-horario-box .mb-horario-detalle {
      display: block; font-size: .74rem; font-weight: 400; color: #bbb;
      line-height: 1.5em; margin-top: 4px;
    }
    .mb-horario-box--cerrado { border-color: rgba(255,255,255,.12); }
    .mb-horario-box--cerrado .mb-horario-actividad { color: #999; font-style: italic; }
    .mb-horario-box--cerrado .mb-horario-detalle { opacity: .55; }

    @media (max-width: 767.98px) {
      .mb-horario-day { width: 92px; min-width: 92px; font-size: .78rem; }
      .mb-horario-box { width: 138px; min-width: 138px; height: 84px; }
      .mb-horario-box .mb-horario-actividad { font-size: .86rem; }
      .mb-horario-box .mb-horario-detalle { font-size: .68rem; }
    }

    /* ===== Bug real de responsive: en mobile/tablet (max-width:1200px), la
       plantilla tiene DOS reglas para el fondo del header que compiten al
       mismo breakpoint — ".header-style-one .desktop-nav{background:black}"
       y, más abajo en el mismo archivo, ".header-style-one.two .desktop-nav
       {background-color:#fff}". Esta última tiene más especificidad (nuestro
       header usa la clase "two") y gana, dejando el header BLANCO en mobile/
       tablet — con el ícono de hamburguesa (líneas blancas) invisible sobre
       ese fondo. En desktop no pasa: ahí el header queda transparente y se
       ve el negro de la página detrás. Se fuerza el mismo fondo oscuro que
       el resto del sitio en ese rango. ===== */
    @media (max-width: 1200px) {
      .header-style-one.two .desktop-nav { background-color: #0a0a0a !important; }
    }

    /* ===== Bug real: fondos de Membresías y Footer se veían MOSAICO con
       fotos reales. La plantilla nunca define background-size/repeat para
       ".payment-product"/".footer-style-one" (solo trae el color de fondo);
       con el placeholder sólido no se notaba, pero con una foto real de
       menor alto que la sección, el navegador la REPETÍA en mosaico hacia
       abajo (default background-repeat:repeat + background-size:auto). Se
       fuerza cover + no-repeat, igual que el hero — la posición exacta
       (arriba/centro/abajo) la elige el Admin por imagen, ver
       ImagenHomeService::posicionCssPara() e inline style de cada sección. ===== */
    .mb-gymon-page .payment-product,
    .mb-gymon-page .footer-style-one {
      background-size: cover;
      background-repeat: no-repeat;
    }

    /* ===== Marquee de Entrenadores: la plantilla no fija un tamaño para
       ".team-style-2 img" (antes dependía del placeholder, que ya venía en
       240x290) — ahora la imagen es la FOTO real del entrenador, con
       cualquier proporción que haya subido el Admin. Se fija el mismo
       tamaño de antes con object-fit:cover para que el marquee no salte de
       tamaño entre tarjetas. El avatar de inicial (sin foto) usa el mismo
       tamaño/redondeo para que ambos casos se vean iguales en la fila. ===== */
    .team-style-2 figure { width: 240px; height: 290px; margin: 0 auto; }
    .team-style-2 figure img {
      width: 240px; height: 290px; object-fit: cover; border-radius: 8px;
    }
    .mb-marquee-avatar {
      width: 240px; height: 290px; border-radius: 8px;
      display: flex; align-items: center; justify-content: center;
      background: linear-gradient(135deg, var(--theme-color, #FD6A01), #1a1a1a);
      color: #fff; font-size: 4rem; font-weight: 800; text-transform: uppercase;
    }
    @media (max-width: 575.98px) {
      .team-style-2 figure, .team-style-2 figure img, .mb-marquee-avatar { width: 180px; height: 218px; }
      .mb-marquee-avatar { font-size: 3rem; }
    }

    /* ===== Ajustes estéticos ===== */

    /* Tarjetas de membresía: sin imagen de fondo (no hay foto por plan); borde naranja a la izquierda
       y un poco de elevación al pasar el mouse. */
    .payment-product-card {
      border-left: 4px solid var(--theme-color, #FD6A01);
      transition: transform .2s ease, box-shadow .2s ease;
    }
    .payment-product-card:hover { transform: translateY(-4px); box-shadow: 0 14px 34px rgba(0,0,0,.45); }

    /* Logo de texto de las secciones: el PNG trae un recuadro claro propio y se notaba el rectángulo.
       Se desvanecen solo los bordes (sin tocar las letras) para que el brillo se funda con el fondo. */
    .mb-gymon-page .heading img.mb-heading-wordmark-img {
      -webkit-mask-image: linear-gradient(to right, transparent 0, #000 12%, #000 88%, transparent 100%),
                          linear-gradient(to bottom, transparent 0, #000 22%, #000 78%, transparent 100%);
      -webkit-mask-composite: source-in;
              mask-image: linear-gradient(to right, transparent 0, #000 12%, #000 88%, transparent 100%),
                          linear-gradient(to bottom, transparent 0, #000 22%, #000 78%, transparent 100%);
              mask-composite: intersect;
    }

    /* Textos corridos en minúscula normal (la plantilla ponía Cada Palabra En Mayúscula). */
    .footer-style-one .footer-col p,
    .acc2 .accordion-item button { text-transform: none; }

    /* Avatar de entrenador sin foto: mismo tamaño que las fotos, más sobrio (oscuro con la inicial en
       naranja y un aro fino) para que no choque con las fotos reales de al lado. */
    .mb-marquee-avatar {
      background: radial-gradient(circle at 50% 35%, #2a2a30 0%, #141417 70%);
      color: var(--theme-color, #FD6A01);
      border: 1px solid rgba(253,106,1,.35);
    }

    /* Celular: menos aire vertical en las secciones. */
    @media (max-width: 575.98px) {
      .mb-gymon-page .gap, .gap { padding-top: 56px; padding-bottom: 56px; }
    }
  </style>
</head>

<body class="mb-gymon-page light-d">

  <div class="preloader">
    <div class="loader-wrap-heading">
      <div class="load-text"> <span>C</span><span>A</span><span>R</span><span>G</span><span>A</span><span>N</span><span>D</span><span>O</span> </div>
    </div>
  </div>

  {{-- ================= HEADER ================= --}}
  <header class="header-style-one two">
    <div class="top-bar">
      <h4>CALISTENIA MADBARZZ · DOMINA TU PROPIO CUERPO</h4>
    </div>
    <div class="container">
      <div class="row">
        <div class="desktop-nav" id="stickyHeader">
          <div class="container">
            <div class="row">
              <div class="col-lg-12">
                <div class="d-flex-all justify-content-between">
                  <div class="nav-bar">
                    <div class="extras">
                      <a href="javascript:void(0)" id="mobile-menu" class="menu-start" aria-label="Abrir menú de navegación">
                        <svg id="ham-menu" viewBox="0 0 100 100">
                          <path class="line line1" d="M 20,29.000046 H 80.000231 C 80.000231,29.000046 94.498839,28.817352 94.532987,66.711331 94.543142,77.980673 90.966081,81.670246 85.259173,81.668997 79.552261,81.667751 75.000211,74.999942 75.000211,74.999942 L 25.000021,25.000058"></path>
                          <path class="line line2" d="M 20,50 H 80"></path>
                          <path class="line line3" d="M 20,70.999954 H 80.000231 C 80.000231,70.999954 94.498839,71.182648 94.532987,33.288669 94.543142,22.019327 90.966081,18.329754 85.259173,18.331003 79.552261,18.332249 75.000211,25.000058 75.000211,25.000058 L 25.000021,74.999942"></path>
                        </svg>
                      </a>
                      @auth
                        <form method="POST" action="{{ route('logout') }}" class="d-inline">@csrf
                          <a href="{{ route('logout') }}" class="login" onclick="event.preventDefault(); this.closest('form').submit();">Cerrar sesión</a>
                        </form>
                      @else
                        <a href="{{ route('login') }}" class="login">Iniciar sesión</a>
                      @endauth
                    </div>
                  </div>
                  <div class="header-logo">
                    <a href="{{ route('home') }}">
                      <figure>
                        <img src="{{ $imagenesHome->url('home_logo_emblema', asset('assets_private/images/logo_mad.png')) }}" alt="MadBarzz" class="mb-header-logo-img">
                      </figure>
                    </a>
                  </div>
                  <div class="nav-bar">
                    <ul>
                      <li><a href="#inicio">Inicio</a></li>
                      <li><a href="#nosotros">Nosotros</a></li>
                      <li><a href="#servicios-horarios">Servicios</a></li>
                      <li><a href="#membresias">Membresías</a></li>
                      <li><a href="#contacto">Contacto</a></li>
                    </ul>
                    <div class="extras">
                      <a href="{{ $waLink }}" target="_blank" data-bs-toggle="modal" data-bs-target="#exampleModal" class="theme-btn">Únete hoy</a>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="mobile-nav" id="mobile-nav">
          <div class="res-log">
            <a href="{{ route('home') }}">
              <span class="mb-mobile-brand-top">
                <img src="{{ $imagenesHome->url('home_logo_texto', asset('assets/images/log_text_mad.png')) }}" alt="MadBarzz" class="mb-mobile-wordmark-img">
              </span>
            </a>
          </div>
          <ul>
            <li><a href="#inicio">Inicio</a></li>
            <li><a href="#nosotros">Nosotros</a></li>
            <li><a href="#servicios-horarios">Servicios</a></li>
            <li><a href="#membresias">Membresías</a></li>
            <li><a href="#contacto">Contacto</a></li>
            @auth
              <li><a href="{{ route('dashboard') }}">Volver al panel</a></li>
              <li>
                <form method="POST" action="{{ route('logout') }}">@csrf
                  <a href="{{ route('logout') }}" onclick="event.preventDefault(); this.closest('form').submit();">Cerrar sesión</a>
                </form>
              </li>
            @else
              <li><a href="{{ route('login') }}">Iniciar sesión</a></li>
            @endauth
          </ul>
          <a href="JavaScript:void(0)" id="res-cross" aria-label="Cerrar menú de navegación"></a>
        </div>
      </div>
    </div>
  </header>

  {{-- ================= HERO (owl carousel) ================= --}}
  <a id="inicio"></a>
  <section class="myslider owl-carousel owl-theme">
    <div data-dot="<button><span>01</span> Calistenia</button>" class="item featured-section-three" style="background-image:url({{ $imagenesHome->url('home_hero_1', asset('images/placeholder-home.svg')) }}); background-position: {{ $imagenesHome->posicionCssPara('home_hero_1') }};">
      <div class="container">
        <div class="featured-text-two">
          <h1>Clases de Calistenia</h1>
          <p>Programas progresivos con equipos de primer nivel y entrenadores expertos para dominar tu peso corporal.</p>
          <a href="{{ $waLink }}" target="_blank" class="theme-btn">ÚNETE AHORA</a>
        </div>
      </div>
    </div>
    <div data-dot="<button><span>02</span> Peso Corporal</button>" class="item featured-section-three" style="background-image:url({{ $imagenesHome->url('home_hero_2', asset('images/placeholder-home.svg')) }}); background-position: {{ $imagenesHome->posicionCssPara('home_hero_2') }};">
      <div class="container">
        <div class="featured-text-two">
          {{-- Semánticamente h2 (solo el primer slide es el <h1> de la página), pero se deja
               la etiqueta h1 + aria-level="2" para no romper el CSS del carrusel (varias
               reglas de animación de la plantilla apuntan a ".featured-text-two h1"). Los
               lectores de pantalla exponen el nivel real gracias a aria-level. --}}
          <h1 aria-level="2">Entrena Tu Peso Corporal</h1>
          <p>Domina movimientos de fuerza y control usando tu propio cuerpo, sin máquinas ni excusas.</p>
          <a href="{{ $waLink }}" target="_blank" class="theme-btn">ÚNETE AHORA</a>
        </div>
      </div>
    </div>
    <div data-dot="<button><span>03</span> Fuerza y Control</button>" class="item featured-section-three" style="background-image:url({{ $imagenesHome->url('home_hero_3', asset('images/placeholder-home.svg')) }}); background-position: {{ $imagenesHome->posicionCssPara('home_hero_3') }};">
      <div class="container">
        <div class="featured-text-two">
          <h1 aria-level="2">Fuerza y Control</h1>
          <p>Progresá de lo básico a la barra avanzada con una comunidad y entrenadores que te acompañan en cada paso.</p>
          <a href="{{ $waLink }}" target="_blank" class="theme-btn">ÚNETE AHORA</a>
        </div>
      </div>
    </div>
  </section>

  {{-- ================= NOSOTROS / SERVICIOS (3 tarjetas) ================= --}}
  <a id="nosotros"></a>
  <section class="gap history-style-one">
    <div class="container">
      <div class="row">
        <div class="col-lg-4 col-md-6 col-sm-12">
          <div class="history-data">
            <figure><img class="w-100" src="{{ $imagenesHome->url('home_nosotros_calistenia', asset('images/placeholder-home.svg')) }}" alt="Clases de Calistenia" style="object-position: {{ $imagenesHome->posicionCssPara('home_nosotros_calistenia') }};"></figure>
            <div class="details">
              <h3>Clases de Calistenia</h3>
              <p>Programas progresivos con equipos de primer nivel y entrenadores expertos para dominar tu peso corporal.</p>
              <a href="#servicios-horarios"><i class="fa-solid fa-arrow-up-long"></i></a>
            </div>
          </div>
        </div>
        <div class="col-lg-4 col-md-6 col-sm-12">
          <div class="history-data">
            <figure><img class="w-100" src="{{ $imagenesHome->url('home_nosotros_cardio', asset('images/placeholder-home.svg')) }}" alt="Programa de Cardio" style="object-position: {{ $imagenesHome->posicionCssPara('home_nosotros_cardio') }};"></figure>
            <div class="details">
              <h3>Programa de Cardio</h3>
              <p>Rutinas diseñadas para mejorar tu resistencia, quemar grasa y potenciar tu fuerza funcional.</p>
              <a href="#servicios-horarios"><i class="fa-solid fa-arrow-up-long"></i></a>
            </div>
          </div>
        </div>
        <div class="col-lg-4 col-md-6 col-sm-12">
          <div class="history-data">
            <figure><img class="w-100" src="{{ $imagenesHome->url('home_nosotros_fitness', asset('images/placeholder-home.svg')) }}" alt="Estudio de Fitness" style="object-position: {{ $imagenesHome->posicionCssPara('home_nosotros_fitness') }};"></figure>
            <div class="details">
              <h3>Estudio de Fitness</h3>
              <p>Espacio equipado con áreas adaptadas para todos los niveles y acompañamiento personalizado.</p>
              <a href="#servicios-horarios"><i class="fa-solid fa-arrow-up-long"></i></a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  {{-- ================= CTA ================= --}}
  <section class="cta-section">
    <div class="container">
      <div class="row align-items-center">
        <div class="col-lg-7">
          <div class="cta-data">
            <h2>¡Comienza tu Entrenamiento!</h2>
            <p>Únete a la Calistenia MadBarzz y deja que nuestros entrenadores expertos te guíen hacia tus metas de fuerza y resistencia. Rutinas personalizadas y asesoramiento profesional.</p>
            <a href="{{ $waLink }}" target="_blank" class="theme-btn">¡Únete Hoy!</a>
          </div>
        </div>
        <div class="col-lg-5">
          <div class="cta-data">
            <figure><img src="{{ $imagenesHome->url('home_cta', asset('images/placeholder-home.svg')) }}" alt="Entrena con nosotros"></figure>
          </div>
        </div>
      </div>
    </div>
  </section>

  {{-- ================= HORARIOS ================= --}}
  <a id="servicios-horarios"></a>
  <section class="gap shop-style-one timetable-section">
    <div class="heading">
      <figure>
        <span class="mb-heading-wordmark-halo">
          <img src="{{ $imagenesHome->url('home_logo_texto', asset('assets/images/log_text_mad.png')) }}" alt="MadBarzz" class="mb-heading-wordmark-img">
        </span>
      </figure>
      <span>PARA TU ENTRENAMIENTO</span>
      <h2>Horarios</h2>
    </div>
    <div class="container">
      <div class="inner-container">
        {{-- El título va FUERA de .table-outer: ese contenedor scrollea
             horizontalmente en mobile (la tabla tiene 7 columnas y no entra
             en 375-768px). Si el título queda adentro, scrollea con la
             tabla y se corta ("Horario Semanal · Cal...") apenas cargada la
             página, antes de que el usuario deslice. Afuera, siempre se ve
             completo sin importar la posición del scroll de la tabla. --}}
        <div class="table-title-box clearfix">
          <div class="data">
            <span>ENCONTRÁ TU HORARIO</span>
            <h2>Horario Semanal · Calistenia</h2>
          </div>
        </div>
        <div class="table-outer">
          <div class="table-inner">
            @php
                // Horario REAL (Parte 2): $diasHorario, $franjasHorario y
                // $gridHorario vienen de HorarioEntrenadorPublicoService,
                // armados a partir de lo que el Admin cargó en el panel
                // privado (tabla horarios_entrenadores). Sin caché: se
                // consulta de nuevo en cada visita.
                $horaFinDe = fn (string $hora) => sprintf('%02d:00', ((int) substr($hora, 0, 2)) + 1);
            @endphp
            <div class="table-responsive">
              <table class="mb-horario-tabla">
                <tbody>
                  @foreach ($diasHorario as $isoDia => $nombreDia)
                    <tr>
                      <td class="mb-horario-day">{{ $nombreDia }}</td>
                      @foreach ($franjasHorario as $hora)
                        @php $nombreEntrenador = $gridHorario[$hora][$isoDia] ?? null; @endphp
                        <td>
                          <div class="mb-horario-box {{ $nombreEntrenador ? '' : 'mb-horario-box--cerrado' }}">
                            <div class="mb-horario-box-inner">
                              @if ($nombreEntrenador)
                                <div class="mb-horario-actividad">Calistenia</div>
                                <span class="mb-horario-detalle">{{ $nombreEntrenador }}<br>{{ $hora }} - {{ $horaFinDe($hora) }}</span>
                              @else
                                <div class="mb-horario-actividad">Cerrado</div>
                                <span class="mb-horario-detalle">{{ $hora }} - {{ $horaFinDe($hora) }}</span>
                              @endif
                            </div>
                          </div>
                        </td>
                      @endforeach
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
            <p class="text-center mt-3 mb-0" style="font-size:.75rem;opacity:.6;">
              Domingo cerrado. Horario sujeto a cambios según disponibilidad de entrenadores.
            </p>
          </div>
        </div>
      </div>
    </div>
  </section>

  {{-- ================= "NOS HACE FELICES" + MARQUEE ENTRENADORES ================= --}}
  <section class="gap">
    <div class="container">
      <div class="row">
        <div class="col-lg-6">
          <div class="data">
            <span>EN CALISTENIA MADBARZZ</span>
            <h2>HACEMOS A LAS PERSONAS MÁS SALUDABLES, FELICES Y FUERTES. ¡ÚNETE A NOSOTROS!</h2>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="making-people">
            <p>Estamos seguros de que nuestro personal y nuestras instalaciones pueden ayudarte a alcanzar tus objetivos. Ya seas principiante o avanzado, ¡estamos aquí para apoyarte!</p>
            <div class="features">
              <ul>
                <li><i class="fa-solid fa-circle-check" aria-hidden="true"></i> Alcanzarás tus objetivos</li>
                <li><i class="fa-solid fa-circle-check" aria-hidden="true"></i> Tendrás soporte ilimitado</li>
                <li><i class="fa-solid fa-circle-check" aria-hidden="true"></i> Encontrarás la ayuda útil</li>
                <li><i class="fa-solid fa-circle-check" aria-hidden="true"></i> Ganarás confianza</li>
                <li><i class="fa-solid fa-circle-check" aria-hidden="true"></i> Aprenderás algo nuevo cada día</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
    {{-- Entrenadores ACTIVOS reales (EntrenadorPublicoService), no
         placeholders fijos: agregar/editar/desactivar un entrenador o
         cambiarle la foto se refleja acá solo, sin tocar código. Sin foto
         cargada, se muestra un avatar con su inicial (mismo patrón
         ".mb-avatar" del panel privado) en vez de un placeholder gris. Sin
         ningún entrenador activo, se oculta el marquee entero (no queda un
         bloque vacío). --}}
    @if (count($entrenadoresPublicos) > 0)
      <div class="marquee_text">
        @foreach ($entrenadoresPublicos as $entrenador)
          <div class="team-style-2">
            <figure>
              @if ($entrenador['foto'])
                <img src="{{ $entrenador['foto'] }}" alt="{{ $entrenador['nombre'] }}">
              @else
                <div class="mb-marquee-avatar">{{ mb_strtoupper(mb_substr($entrenador['nombre'], 0, 1)) }}</div>
              @endif
            </figure>
            <span>{{ $entrenador['etiqueta'] }}</span>
            <h5>{{ $entrenador['nombre'] }}</h5>
          </div>
        @endforeach
      </div>
    @endif
  </section>

  {{-- ================= MEMBRESÍAS ================= --}}
  <a id="membresias"></a>
  <section class="gap payment-product" style="background-image:url({{ $imagenesHome->url('home_membresias_fondo', asset('images/placeholder-home.svg')) }}); background-position: {{ $imagenesHome->posicionCssPara('home_membresias_fondo') }};">
    <div class="heading">
      <figure>
        <span class="mb-heading-wordmark-halo">
          <img src="{{ $imagenesHome->url('home_logo_texto', asset('assets/images/log_text_mad.png')) }}" alt="MadBarzz" class="mb-heading-wordmark-img">
        </span>
      </figure>
      <span>PLANES</span>
      <h2>Elegí tu Membresía</h2>
    </div>
    <div class="container">
      <div class="row g-4">
        @forelse ($membresias as $membresia)
          @php
              $waMembresia = $waLink . rawurlencode(' Me interesa la membresía ' . $membresia->tipo_membresia . '.');
          @endphp
          <div class="col-lg-6">
            <div class="payment-product-card">
              <h5>Membresía {{ $membresia->tipo_membresia }}</h5>
              <h2>Bs {{ number_format($membresia->precio, 0, ',', '.') }}</h2>
              <span>Acceso completo al gimnasio</span>
              <p>Rutinas guiadas, seguimiento de entrenadores y acceso a todas las clases de Calistenia MadBarzz.</p>
              <a href="{{ $waMembresia }}" target="_blank" class="theme-btn">Comenzar</a>
            </div>
          </div>
        @empty
          <div class="col-12">
            <p class="text-center" style="opacity:.7;">Todavía no hay membresías cargadas en el sistema.</p>
          </div>
        @endforelse
      </div>
    </div>
  </section>

  {{-- ================= GUÍA PARA PRINCIPIANTES (FAQ) + PROMO ================= --}}
  <section class="contact-form-one gap">
    <div class="container">
      <div class="row align-items-center">
        <div class="col-lg-6">
          <div class="data">
            <span>PARA PRINCIPIANTES</span>
            <h2>Guía para empezar en Calistenia MadBarzz</h2>
          </div>
          <div class="acc2">
            <div class="accordion" id="accordionExampleGymon">
              <div class="accordion-item">
                <h2 class="accordion-header" id="heading-gymon-One">
                  <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-gymon-One" aria-expanded="true" aria-controls="collapse-gymon-One">
                    ¿Cuál es la información básica que debo conocer?
                  </button>
                </h2>
                <div id="collapse-gymon-One" class="accordion-collapse collapse show" aria-labelledby="heading-gymon-One" data-bs-parent="#accordionExampleGymon">
                  <div class="accordion-body">Aprende sobre los tipos de ejercicios, seguridad, calentamiento y enfriamiento, y la importancia de la constancia.</div>
                </div>
              </div>
              <div class="accordion-item">
                <h2 class="accordion-header" id="heading-gymon-Two">
                  <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-gymon-Two" aria-expanded="false" aria-controls="collapse-gymon-Two">
                    ¿Cómo empezar si soy principiante?
                  </button>
                </h2>
                <div id="collapse-gymon-Two" class="accordion-collapse collapse" aria-labelledby="heading-gymon-Two" data-bs-parent="#accordionExampleGymon">
                  <div class="accordion-body">Comienza con ejercicios básicos de fuerza y flexibilidad, dedicando 20-30 minutos al día para acostumbrarte a la rutina.</div>
                </div>
              </div>
              <div class="accordion-item">
                <h2 class="accordion-header" id="heading-gymon-Three">
                  <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-gymon-Three" aria-expanded="false" aria-controls="collapse-gymon-Three">
                    ¿Qué hacer en tu primera sesión?
                  </button>
                </h2>
                <div id="collapse-gymon-Three" class="accordion-collapse collapse" aria-labelledby="heading-gymon-Three" data-bs-parent="#accordionExampleGymon">
                  <div class="accordion-body">Empieza con flexiones, sentadillas y planchas. Nuestros entrenadores te guiarán para ejecutar cada movimiento correctamente.</div>
                </div>
              </div>
              <div class="accordion-item">
                <h2 class="accordion-header" id="heading-gymon-Four">
                  <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-gymon-Four" aria-expanded="false" aria-controls="collapse-gymon-Four">
                    ¿Cómo encontrar una rutina adecuada?
                  </button>
                </h2>
                <div id="collapse-gymon-Four" class="accordion-collapse collapse" aria-labelledby="heading-gymon-Four" data-bs-parent="#accordionExampleGymon">
                  <div class="accordion-body">Te proporcionamos rutinas personalizadas según tu nivel y objetivos. Consulta con nuestros entrenadores para recibir orientación.</div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="together mb-ig-embed">
            {{-- Publicación de Instagram (reemplaza el bloque "2 meses a mitad de
                 precio"). Embed oficial: el blockquote lo convierte en la
                 tarjeta de Instagram el script embed.js. Para cambiar la
                 publicación, cambiar el permalink (data-instgrm-permalink y
                 el href del enlace) por el de otra publicación pública. --}}
            <style>
              .together.mb-ig-embed { padding: 24px 16px; }
              .together.mb-ig-embed .instagram-media { margin: 0 auto !important; min-width: 0 !important; max-width: 540px !important; width: 100% !important; }
            </style>
            <blockquote class="instagram-media" data-instgrm-permalink="{{ $contenidoHome->get('instagram_post_url') }}" data-instgrm-version="14" style="background:#FFF; border:0; border-radius:3px; box-shadow:0 0 1px 0 rgba(0,0,0,0.5),0 1px 10px 0 rgba(0,0,0,0.15); margin:1px; max-width:540px; min-width:326px; padding:0; width:99.375%;">
              <a href="{{ $contenidoHome->get('instagram_post_url') }}" target="_blank" rel="noopener" style="display:block; padding:16px; color:#c9c8cd; text-align:center;">Ver esta publicación en Instagram</a>
            </blockquote>
            <script async src="https://www.instagram.com/embed.js"></script>
          </div>
        </div>
      </div>
    </div>
  </section>

  {{-- ================= FOOTER ================= --}}
  @include('partials.gymon-footer', ['waLink' => $waLink, 'imagenesHome' => $imagenesHome, 'contenidoHome' => $contenidoHome])

  @auth
    {{-- Volver al panel: solo para quien tiene la sesión iniciada (p. ej. entró con "Ver sitio web").
         Flotante y siempre visible; "dashboard" lleva al panel que corresponde al rol. --}}
    <a href="{{ route('dashboard') }}" class="mb-volver-panel" aria-label="Volver al panel de control">
      <i class="fa-solid fa-arrow-left"></i> Volver al panel
    </a>
    <style>
      .mb-volver-panel{
        position: fixed; left: 20px; bottom: 20px; z-index: 9999;
        display: inline-flex; align-items: center; gap: .55rem;
        padding: .75rem 1.15rem; border-radius: 999px;
        background: var(--theme-color, #FD6A01); color: #fff; text-decoration: none;
        font-weight: 700; font-size: .95rem; letter-spacing: .3px;
        box-shadow: 0 8px 24px rgba(0,0,0,.45);
        transition: transform .15s ease, background .15s ease;
      }
      .mb-volver-panel:hover{ background:#ff7f26; color:#fff; transform: translateY(-2px); }
      @media (max-width: 575.98px){ .mb-volver-panel{ left: 12px; bottom: 12px; padding: .65rem 1rem; font-size: .85rem; } }
    </style>
  @endauth

  <div id="scroll-percentage"><span id="scroll-percentage-value"></span></div>

  {{-- Modal "Únete hoy": SIN backend propio (evita depender del contact.php de la
       plantilla, que era de OTRO sitio e inservible). En vez de fingir que el
       formulario se "envía", el submit arma el mensaje de WhatsApp con lo que el
       visitante escribió acá (ver el <script> al final del archivo) — lo que
       completa es lo que efectivamente se manda, no un campo decorativo. --}}
  <div class="modal fade popups conslt-popup" id="exampleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <img src="{{ $imagenesHome->url('home_modal_unete', asset('images/placeholder-home.svg')) }}" alt="MadBarzz">
          <div class="contact-form-one popup">
            <div class="c-form-2">
              <h3>Únete hoy</h3>
              <div class="parallax" style="background-image: url({{ asset('assets_gymon/images/theme-shape.svg') }});"></div>
              <form id="formUneteModal" data-wa-base="{{ preg_replace('/\?.*/', '', $waLink) }}">
                <div class="row g-0"><input type="text" class="form-control" id="uneteNombre" name="nombre" placeholder="Nombre completo" required></div>
                <div class="row g-0"><input type="email" class="form-control" id="uneteEmail" name="email" placeholder="Correo electrónico"></div>
                <div class="row g-0"><input type="text" class="form-control" id="uneteTelefono" name="telefono" placeholder="Teléfono"></div>
                <div class="row g-0"><textarea id="uneteMensaje" name="mensaje" placeholder="¿En qué te podemos ayudar?"></textarea></div>
                <button type="submit" class="theme-btn">Continuar por WhatsApp</button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="{{ asset('assets_gymon/js/jquery.min.js') }}"></script>
  <script src="{{ asset('assets_gymon/js/jquery.waypoints.min.js') }}"></script>
  <script src="{{ asset('assets_gymon/js/jquery.counterup.min.js') }}"></script>
  <script src="{{ asset('assets_gymon/js/bootstrap.min.js') }}"></script>
  <script src="{{ asset('assets_gymon/js/jquery.fancybox.min.js') }}"></script>
  <script src="{{ asset('assets_gymon/js/jquery.nice-select.js') }}"></script>
  <script src="{{ asset('assets_gymon/js/owl.carousel.min.js') }}"></script>
  <script src="{{ asset('assets_gymon/js/marquee.js') }}"></script>
  <script src="{{ asset('assets_gymon/js/custom.js') }}"></script>

  {{-- ===== Efecto "encendido/apagado" del brillo en el CONTORNO de las
       letras del wordmark MADBARZZ de sección (Horarios / Elegí tu
       Membresía — SOLO esos dos, no el logo del header ni el del menú
       desplegable, que no llevan la clase ".mb-heading-wordmark-halo"): el
       brillo (drop-shadow apilado, seteado en ".mb-heading-wordmark-img",
       ver el <style>) arranca en alpha 0 y se enciende con una transición
       suave (.8s) cada vez que la sección entra en pantalla, y se apaga de
       nuevo cuando sale — repite cada vez que se cruza esa sección, subiendo
       o bajando (no una sola vez). Funciona igual en mobile que en desktop:
       se dispara por scroll real (IntersectionObserver), no por hover. Con
       "prefers-reduced-motion" ni siquiera hace falta este script — el CSS
       ya deja el brillo fijo encendido sin animación (ver el @media en el
       <style>); igual se lo respeta acá para no pelear con eso. Sin soporte
       de IntersectionObserver (navegador muy viejo), se enciende todo de una
       para que las letras nunca queden invisibles. ===== --}}
  <script>
  (function () {
    var halos = document.querySelectorAll('.mb-heading-wordmark-halo');
    if (!halos.length) return;

    if (!('IntersectionObserver' in window)) {
      halos.forEach(function (el) { el.classList.add('mb-halo-lit'); });
      return;
    }

    var prefiereMenosMovimiento = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (prefiereMenosMovimiento) {
      halos.forEach(function (el) { el.classList.add('mb-halo-lit'); });
      return;
    }

    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        entry.target.classList.toggle('mb-halo-lit', entry.isIntersecting);
      });
    }, { threshold: 0.4 });

    halos.forEach(function (el) { observer.observe(el); });
  })();
  </script>

  {{-- Modal "Únete hoy": arma el mensaje de WhatsApp con lo que el visitante
       completó en el formulario, en vez de un link fijo que ignora esos
       datos (ver comentario junto al <form id="formUneteModal">). --}}
  <script>
  (function () {
    var form = document.getElementById('formUneteModal');
    if (!form) return;

    form.addEventListener('submit', function (evento) {
      evento.preventDefault();

      var nombre    = document.getElementById('uneteNombre').value.trim();
      var email     = document.getElementById('uneteEmail').value.trim();
      var telefono  = document.getElementById('uneteTelefono').value.trim();
      var mensaje   = document.getElementById('uneteMensaje').value.trim();

      var lineas = ['Hola, quiero unirme a Calistenia MadBarzz.'];
      if (nombre)   lineas.push('Nombre: ' + nombre);
      if (telefono) lineas.push('Teléfono: ' + telefono);
      if (email)    lineas.push('Correo: ' + email);
      if (mensaje)  lineas.push('Mensaje: ' + mensaje);

      var texto = encodeURIComponent(lineas.join('\n'));
      window.open(form.dataset.waBase + '?text=' + texto, '_blank');
    });
  })();
  </script>
</body>
</html>
