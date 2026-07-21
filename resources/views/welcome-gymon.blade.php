<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Calistenia MadBarzz · Domina tu propio cuerpo (vista previa GymOn)</title>
  <link rel="stylesheet" href="{{ asset('assets_gymon/css/bootstrap.min.css') }}">
  <link rel="icon" type="image/png" href="{{ asset('assets_gymon/images/favicon.png') }}">
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

    /* ===== Logo del header: la plantilla fija .header-logo a img a
       width:180px (pensado para el wordmark ancho de GymOn, 503x171). El
       logo de MadBarzz es un emblema casi cuadrado (664x622): a lo ancho
       quedaría gigante (~168px de alto). Se iguala por ALTO al del original
       (180px de ancho x su proporción 171/503 ≈ 61px de alto) para que
       ocupe el mismo espacio vertical en el header, sin importar el ancho. ===== */
    .header-logo a img.mb-header-logo-img { width: auto !important; height: 61px; }

    /* ===== Logo del menú lateral desplegable: sin restricción propia, solo
       hereda el reset de Bootstrap img{max-width:100%}. El logo ancho de
       GymOn (503x171) así ya queda moderado (~109px de alto); el emblema
       casi cuadrado de MadBarzz, en cambio, terminaba enorme (~300px de
       alto) porque al ser más "cuadrado" ocupa mucha más altura al mismo
       ancho. Se limita por alto a un tamaño prolijo para el panel. ===== */
    .res-log a img.mb-mobile-logo-img { width: auto !important; height: 56px; max-width: 100%; }

    /* ===== Tarjeta con el wordmark (letras negras) en el menú lateral: como
       el menú tiene fondo negro, necesita un fondo claro propio detrás para
       leerse. Se ancla abajo a la derecha del panel — esa zona queda vacía
       en todos los tamaños (los links ocupan la parte de arriba), así que
       nunca tapa la navegación. ===== */
    .mb-mobile-brand-card {
      position: absolute;
      right: 30px;
      bottom: 30px;
      max-width: 55%;
      background: #fff;
      border-radius: 12px;
      padding: 14px 16px;
      box-shadow: 0 4px 16px rgba(0,0,0,.3);
    }
    .mb-mobile-brand-card img { display: block; width: 100%; height: auto; }

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

    /* ===== Tabla de horario semanal (reemplaza la grilla decorativa de la
       plantilla, que no alineaba realmente cada casilla con su día). El
       título de esta sección venía con position:absolute, pensado para
       flotar sobre el bloque de pestañas (que ya no existe: solo hay
       Calistenia); sin eso, el título quedaba flotando encima de la tabla.
       Se vuelve a flujo normal para que quede arriba, sin superposición. ===== */
    .mb-gymon-page .timetable-section .table-title-box { position: static; padding-bottom: 20px; }
    .mb-gymon-page .timetable-section .table-title-box .data { transform: none; padding-bottom: 0; }
    .mb-gymon-page .timetable-section .inner-container { padding-top: 40px; }

    .mb-horario-tabla { width: 100%; border-collapse: separate; border-spacing: 0 8px; }
    .mb-horario-tabla th, .mb-horario-tabla td {
      padding: 12px 10px; text-align: center; font-size: .82rem; white-space: nowrap;
    }
    .mb-horario-tabla thead th {
      background: var(--theme-color, #ea2127); color: #fff; text-transform: uppercase;
      letter-spacing: .04em; font-size: .74rem; font-weight: 700;
    }
    .mb-horario-tabla thead th:first-child, .mb-horario-tabla tbody td:first-child {
      text-align: left; white-space: nowrap; font-weight: 700;
    }
    .mb-horario-tabla tbody tr { background: #171717; color: #eee; }
    .mb-horario-tabla tbody td:first-child { color: var(--theme-color, #ea2127); }
    .mb-horario-tabla .mb-libre { opacity: .55; font-style: italic; }
    .mb-horario-tabla .mb-cerrado { opacity: .35; }

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
                      <a href="javascript:void(0)" id="mobile-menu" class="menu-start">
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
                    <a href="{{ route('preview.gymon') }}">
                      <figure>
                        <img src="{{ asset('assets_private/images/logo_mad.png') }}" alt="MadBarzz" class="mb-header-logo-img">
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
            <a href="{{ route('preview.gymon') }}">
              <img src="{{ asset('assets_private/images/logo_mad.png') }}" alt="MadBarzz" class="mb-mobile-logo-img">
            </a>
          </div>
          <ul>
            <li><a href="#inicio">Inicio</a></li>
            <li><a href="#nosotros">Nosotros</a></li>
            <li><a href="#servicios-horarios">Servicios</a></li>
            <li><a href="#membresias">Membresías</a></li>
            <li><a href="#contacto">Contacto</a></li>
            @auth
              <li>
                <form method="POST" action="{{ route('logout') }}">@csrf
                  <a href="{{ route('logout') }}" onclick="event.preventDefault(); this.closest('form').submit();">Cerrar sesión</a>
                </form>
              </li>
            @else
              <li><a href="{{ route('login') }}">Iniciar sesión</a></li>
            @endauth
          </ul>
          <div class="mb-mobile-brand-card">
            <img src="{{ asset('assets/images/log_text_mad.png') }}" alt="MadBarzz">
          </div>
          <a href="JavaScript:void(0)" id="res-cross"></a>
        </div>
      </div>
    </div>
  </header>

  {{-- ================= HERO (owl carousel) ================= --}}
  <a id="inicio"></a>
  <section class="myslider owl-carousel owl-theme">
    <div data-dot="<button><span>01</span> Calistenia</button>" class="item featured-section-three" style="background-image:url(https://via.placeholder.com/1920x770);">
      <div class="container">
        <div class="featured-text-two">
          <h1>Clases de Calistenia</h1>
          <p>Programas progresivos con equipos de primer nivel y entrenadores expertos para dominar tu peso corporal.</p>
          <a href="{{ $waLink }}" target="_blank" class="theme-btn">ÚNETE AHORA</a>
        </div>
      </div>
    </div>
    <div data-dot="<button><span>02</span> Peso Corporal</button>" class="item featured-section-three" style="background-image:url(https://via.placeholder.com/1920x770);">
      <div class="container">
        <div class="featured-text-two">
          <h1>Entrena Tu Peso Corporal</h1>
          <p>Domina movimientos de fuerza y control usando tu propio cuerpo, sin máquinas ni excusas.</p>
          <a href="{{ $waLink }}" target="_blank" class="theme-btn">ÚNETE AHORA</a>
        </div>
      </div>
    </div>
    <div data-dot="<button><span>03</span> Fuerza y Control</button>" class="item featured-section-three" style="background-image:url(https://via.placeholder.com/1920x770);">
      <div class="container">
        <div class="featured-text-two">
          <h1>Fuerza y Control</h1>
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
            <figure><img class="w-100" src="https://via.placeholder.com/416x584" alt="Clases de Calistenia"></figure>
            <div class="details">
              <h3>Clases de Calistenia</h3>
              <p>Programas progresivos con equipos de primer nivel y entrenadores expertos para dominar tu peso corporal.</p>
              <a href="#servicios-horarios"><i class="fa-solid fa-arrow-up-long"></i></a>
            </div>
          </div>
        </div>
        <div class="col-lg-4 col-md-6 col-sm-12">
          <div class="history-data">
            <figure><img class="w-100" src="https://via.placeholder.com/416x584" alt="Programa de Cardio"></figure>
            <div class="details">
              <h3>Programa de Cardio</h3>
              <p>Rutinas diseñadas para mejorar tu resistencia, quemar grasa y potenciar tu fuerza funcional.</p>
              <a href="#servicios-horarios"><i class="fa-solid fa-arrow-up-long"></i></a>
            </div>
          </div>
        </div>
        <div class="col-lg-4 col-md-6 col-sm-12">
          <div class="history-data">
            <figure><img class="w-100" src="https://via.placeholder.com/416x584" alt="Estudio de Fitness"></figure>
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
            <figure><img src="https://via.placeholder.com/858x597" alt="Entrena con nosotros"></figure>
          </div>
        </div>
      </div>
    </div>
  </section>

  {{-- ================= HORARIOS ================= --}}
  <a id="servicios-horarios"></a>
  <section class="gap shop-style-one timetable-section">
    <div class="heading">
      <figure><img src="{{ asset('assets_gymon/images/heading-icon-orange.png') }}" alt="icono"></figure>
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
                $dias = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];

                // Horario de EJEMPLO — el gimnasio abre lunes a sábado (domingo cerrado).
                // Reemplazar por el horario real cuando esté definido.
                $filasHorario = [
                    '07:00 - 09:00' => ['Calistenia · Entrenador MadBarzz', 'Gimnasio libre', 'Calistenia · Entrenador MadBarzz', 'Gimnasio libre', 'Calistenia · Entrenador MadBarzz', 'Calistenia · Entrenadora MadBarzz'],
                    '10:00 - 12:00' => ['Gimnasio libre', 'Gimnasio libre', 'Gimnasio libre', 'Gimnasio libre', 'Gimnasio libre', 'Gimnasio libre'],
                    '16:00 - 18:00' => ['Gimnasio libre', 'Calistenia · Entrenadora MadBarzz', 'Gimnasio libre', 'Calistenia · Entrenadora MadBarzz', 'Gimnasio libre', null],
                    '18:30 - 20:30' => ['Calistenia · Entrenador MadBarzz', 'Gimnasio libre', 'Calistenia · Entrenador MadBarzz', 'Gimnasio libre', 'Calistenia · Entrenador MadBarzz', null],
                ];
            @endphp
            <div class="table-responsive">
              <table class="mb-horario-tabla">
                <thead>
                  <tr>
                    <th>Horario</th>
                    @foreach ($dias as $dia)
                      <th>{{ $dia }}</th>
                    @endforeach
                  </tr>
                </thead>
                <tbody>
                  @foreach ($filasHorario as $franja => $porDia)
                    <tr>
                      <td>{{ $franja }}</td>
                      @foreach ($porDia as $celda)
                        @if (is_null($celda))
                          <td class="mb-cerrado">Cerrado</td>
                        @elseif ($celda === 'Gimnasio libre')
                          <td class="mb-libre">{{ $celda }}</td>
                        @else
                          <td>{{ $celda }}</td>
                        @endif
                      @endforeach
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
            <p class="text-center mt-3 mb-0" style="font-size:.75rem;opacity:.6;">
              Domingo cerrado. Horario de ejemplo — se ajusta con el horario real.
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
    <div class="marquee_text">
      @for ($i = 0; $i < 6; $i++)
        <div class="team-style-2">
          <figure><img src="https://via.placeholder.com/240x290" alt="Entrenador MadBarzz"></figure>
          <span>{{ $i % 2 === 0 ? 'Entrenador certificado' : 'Entrenadora certificada' }}</span>
          <h5>MadBarzz</h5>
        </div>
      @endfor
    </div>
  </section>

  {{-- ================= MEMBRESÍAS ================= --}}
  <a id="membresias"></a>
  <section class="gap payment-product" style="background-image:url(https://via.placeholder.com/1920x846);">
    <div class="heading">
      <figure><img src="{{ asset('assets_gymon/images/heading-icon-orange.png') }}" alt="icono"></figure>
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
              <img src="https://via.placeholder.com/363x446" alt="Membresía {{ $membresia->tipo_membresia }}">
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
          <div class="together">
            <h4>juntos entrenamos mejor</h4>
            <h2>2 meses a mitad de precio</h2>
            <span>+ sin costo de inscripción</span>
            <p>promoción por tiempo limitado</p>
            <div id="countdown">
              <ul>
                <li><span id="days"></span>días</li>
                <li><span id="hours"></span>Hs</li>
                <li><span id="minutes"></span>Min</li>
                <li class="mb-0"><span id="seconds"></span>Seg</li>
              </ul>
            </div>
            <a href="{{ $waLink }}" target="_blank" class="theme-btn">comenzar</a>
            <a href="#contacto" class="theme-btn two">contactar ahora</a>
          </div>
        </div>
      </div>
    </div>
  </section>

  {{-- ================= FOOTER ================= --}}
  <footer id="contacto" class="footer-style-one" style="background-image: url(https://via.placeholder.com/1920x732);">
    <div class="container">
      <div class="footer-p-4">
        <div class="footer-p-4-left">
          <i class="flaticon-contact-form"></i>
          <a href="{{ $waLink }}" target="_blank"><h3>únete ahora</h3></a>
          <span>2 meses a mitad de precio + sin costo de inscripción</span>
        </div>
        <div class="footer-p-4-right">
          <i class="flaticon-stopwatch-1"></i>
          <a href="{{ $waLink }}" target="_blank"><h3>agenda una visita</h3></a>
          <span>elegí el horario que más te convenga</span>
        </div>
      </div>
    </div>
    <div class="footer-p-2">
      <div class="container">
        <div class="row">
          <div class="col-lg-4 col-md-6 col-sm-12">
            <div class="footer-col">
              <h3>Información</h3>
              <p>En Calistenia MadBarzz creemos que la constancia es la clave. Entrenar regularmente mejora tu fuerza, resistencia y bienestar.</p>
              <ul class="social-media">
                <li><a href="https://www.facebook.com/MadBarzz" target="_blank"><i class="fa-brands fa-facebook-f"></i></a></li>
                <li><a href="{{ $waLink }}" target="_blank"><i class="fa-brands fa-whatsapp"></i></a></li>
                <li><a href="https://www.instagram.com/calistenia_madbarzz?igsh=aW91eXl6ZnVtMGJ2" target="_blank"><i class="flaticon-instagram"></i></a></li>
              </ul>
            </div>
          </div>
          <div class="col-lg-4 col-md-6 col-sm-12">
            <div class="footer-col">
              <h3>Contacto</h3>
              <ul>
                <li><i class="flaticon-maps"></i><p>Av. Santos Dumont, 4to anillo, al lado de la HOT, Santa Cruz de la Sierra</p></li>
                <li><i class="flaticon-iphone"></i><p><a href="{{ $waLink }}" target="_blank">{{ $superadmin->persona->telefono ?? 'Número no disponible' }}</a></p></li>
                <li><i class="flaticon-mail"></i><p><a href="mailto:{{ $superadmin->user->email ?? '' }}">{{ $superadmin->user->email ?? 'Correo no disponible' }}</a></p></li>
              </ul>
            </div>
          </div>
          <div class="col-lg-4 col-md-6 col-sm-12">
            <div class="footer-col">
              <h3>Newsletter</h3>
              <p>Sin backend todavía — este campo es solo de vista previa.</p>
              <form onsubmit="return false;">
                <input type="email" name="email" placeholder="Tu correo electrónico">
                <button type="button"><i class="fa-solid fa-arrow-up-long"></i></button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="footer-p-3 rights">
      <div class="container">
        <div class="row">
          <div class="footer-col">
            <p>Calistenia MadBarzz <i class="fa-solid fa-heart"></i> © {{ date('Y') }} Todos los derechos reservados</p>
          </div>
        </div>
      </div>
    </div>
  </footer>

  <div id="scroll-percentage"><span id="scroll-percentage-value"></span></div>

  {{-- Modal "Únete hoy": formulario visible de la plantilla, SIN backend propio todavía.
       El botón de envío es un link directo a WhatsApp con los datos que el visitante
       ya ve en pantalla (no procesa el formulario) — evita depender del contact.php
       de la plantilla (era de OTRO sitio, inservible) hasta que se arme un backend real. --}}
  <div class="modal fade popups conslt-popup" id="exampleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <img src="https://via.placeholder.com/420x591" alt="MadBarzz">
          <div class="contact-form-one popup">
            <div class="c-form-2">
              <h3>Únete hoy</h3>
              <div class="parallax" style="background-image: url({{ asset('assets_gymon/images/theme-shape.svg') }});"></div>
              <form onsubmit="return false;">
                <div class="row g-0"><input type="text" class="form-control" placeholder="Nombre completo"></div>
                <div class="row g-0"><input type="email" class="form-control" placeholder="Correo electrónico"></div>
                <div class="row g-0"><input type="text" class="form-control" placeholder="Teléfono"></div>
                <div class="row g-0"><textarea placeholder="¿En qué te podemos ayudar?"></textarea></div>
                <a href="{{ $waLink }}" target="_blank" class="theme-btn">Continuar por WhatsApp</a>
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
</body>
</html>
