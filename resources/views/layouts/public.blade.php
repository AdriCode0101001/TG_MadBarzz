<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Calistenia MadBarzz · Domina tu propio cuerpo</title>
  <link rel="stylesheet" href="assets/css/bootstrap.min.css">
  <link rel="icon" type="image/png" href="{{ asset('assets_private/images/logo_mad.png') }}">
  <link rel="stylesheet" href="assets/css/owl.carousel.min.css">
  <link rel="stylesheet" href="assets/font/flaticon_mycollection.css">
  <link rel="stylesheet" href="assets/css/owl.theme.default.min.css">
  <link rel="stylesheet" href="assets/css/fontawesome.min.css">
  <link rel="stylesheet" href="assets/css/jquery.fancybox.min.css">
  <link rel="stylesheet" href="assets/css/nice-select.css">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" type="text/css" href="assets/css/style-dark.css">
  <link rel="stylesheet" href="assets/css/responsive.css">
  {{-- Capa moderna MadBarzz --}}
  <link rel="stylesheet" href="{{ asset('assets/css/public-ui.css') }}">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">
  <style>.top-bar{background-color:#FD6A01!important}.header-logo img{max-height:48px;width:auto}</style>
</head>

<body class="light-d mb-landing">
  <a id="inicio"></a>

  <div class="preloader" style="background-color:#000;">
    <div class="loader-wrap-heading"><div class="load-text">
      <span style="color:#FFA500;">C</span><span style="color:#FFA500;">A</span><span style="color:#FFA500;">R</span>
      <span style="color:#FFA500;">G</span><span style="color:#FFA500;">A</span><span style="color:#FFA500;">N</span>
      <span style="color:#FFA500;">D</span><span style="color:#FFA500;">O</span>
    </div></div>
  </div>

  {{-- ================= HEADER (se oculta al bajar, vuelve al subir) ================= --}}
  <header class="mb-site-header" id="mbHeader">
    <div class="mb-topbar">Calistenia MadBarzz</div>
    <nav class="mb-navbar">
      <ul class="mb-nav-links">
        <li><a href="#inicio">Inicio</a></li>
        <li><a href="#servicios">Servicios</a></li>
        <li><a href="#entrenadores">Nosotros</a></li>
        <li><a href="#guias">Guías</a></li>
        <li><a href="#contacto">Contacto</a></li>
      </ul>

      <a href="{{ route('public') }}" class="mb-nav-logo">
        <img src="{{ asset('assets_private/images/logo_mad.png') }}" alt="MadBarzz">
        <b>MADBARZZ</b>
      </a>

      <div class="mb-nav-right">
        @auth
          <form method="POST" action="{{ route('logout') }}" class="d-inline">@csrf
            <a href="{{ route('logout') }}" class="mb-nav-login" onclick="event.preventDefault(); this.closest('form').submit();">Cerrar sesión</a>
          </form>
        @else
          <a href="{{ route('login') }}" class="mb-nav-login">Iniciar sesión</a>
        @endauth
        <a href="https://wa.me/59176372337?text=Hola,%20quiero%20unirme%20a%20la%20Calistenia%20MadBarzz." target="_blank" class="mb-nav-login mb-nav-cta">Comenzar</a>
        <button class="mb-burger" id="mbBurger" aria-label="Menú"><span></span><span></span><span></span></button>
      </div>
    </nav>
  </header>

  {{-- Menú overlay para móvil (se superpone sobre la página) --}}
  <div class="mb-mobile-overlay" id="mbOverlay">
    <button class="mb-mobile-close" id="mbOverlayClose" aria-label="Cerrar">&times;</button>
    <a href="#inicio">Inicio</a>
    <a href="#servicios">Servicios</a>
    <a href="#entrenadores">Nosotros</a>
    <a href="#guias">Guías</a>
    <a href="#contacto">Contacto</a>
    @auth
      <form method="POST" action="{{ route('logout') }}">@csrf
        <a href="{{ route('logout') }}" onclick="event.preventDefault(); this.closest('form').submit();">Cerrar sesión</a>
      </form>
    @else
      <a href="{{ route('login') }}">Iniciar sesión</a>
    @endauth
    <a href="https://wa.me/59176372337?text=Hola,%20quiero%20unirme%20a%20la%20Calistenia%20MadBarzz." target="_blank" class="mb-nav-cta">Comenzar consulta</a>
  </div>

  {{-- ================= HERO ================= --}}
  <section class="mb-hero">
    <div class="mb-hero__bg" style="background-image:url('{{ asset('assets/images/senotagym.jpg') }}');"></div>
    <div class="mb-hero__overlay"></div>
    <div class="container">
      <div class="mb-hero__inner">
        <span class="mb-eyebrow mb-reveal"><i class="fa-solid fa-bolt"></i> Calistenia · Fuerza · Disciplina</span>
        <h1 class="mb-reveal d1">Domina tu propio <span class="mb-accent">cuerpo</span><br>en MadBarzz</h1>
        <p class="lead mb-reveal d2">Entrena con rutinas progresivas, equipos de primer nivel y entrenadores que te llevan de principiante a barra avanzada. Tu mejor versión empieza hoy.</p>
        <div class="mb-hero__cta mb-reveal d3">
          <a href="https://wa.me/59176372337?text=Hola,%20quiero%20unirme%20a%20la%20Calistenia%20MadBarzz." target="_blank" class="mb-btn mb-btn-primary"><i class="fa-brands fa-whatsapp"></i> ¡Únete hoy!</a>
          <a href="{{ route('login') }}" class="mb-btn mb-btn-ghost"><i class="fa-solid fa-right-to-bracket"></i> Iniciar sesión</a>
        </div>
        <div class="mb-hero__chips mb-reveal d4">
          <div class="mb-chip"><i class="fa-solid fa-dumbbell"></i><div><b>+10</b><span>Rutinas</span></div></div>
          <div class="mb-chip"><i class="fa-solid fa-users"></i><div><b>Comunidad</b><span>Activa</span></div></div>
          <div class="mb-chip"><i class="fa-solid fa-medal"></i><div><b>Entrenadores</b><span>Certificados</span></div></div>
        </div>
      </div>
    </div>
    <a href="#servicios" class="mb-scroll-ind"><span class="mb-mouse"></span>Desliza</a>
  </section>

  {{-- ================= ESTADÍSTICAS ================= --}}
  <section class="mb-stats">
    <div class="container"><div class="row text-center">
      <div class="col-6 col-md-3 mb-reveal"><div class="mb-stat-num counter">250</div><div class="mb-stat-txt">Miembros activos</div></div>
      <div class="col-6 col-md-3 mb-reveal d1"><div class="mb-stat-num counter">12</div><div class="mb-stat-txt">Entrenadores</div></div>
      <div class="col-6 col-md-3 mb-reveal d2"><div class="mb-stat-num counter">30</div><div class="mb-stat-txt">Rutinas guiadas</div></div>
      <div class="col-6 col-md-3 mb-reveal d3"><div class="mb-stat-num counter">5</div><div class="mb-stat-txt">Años entrenando</div></div>
    </div></div>
  </section>

  {{-- ================= SERVICIOS ================= --}}
  <section id="servicios" class="mb-services">
    <div class="container">
      <div class="mb-section-head mb-reveal">
        <span class="mb-eyebrow2">Lo que ofrecemos</span>
        <h2>Servicios pensados para tu progreso</h2>
      </div>
      <div class="row g-4">
        <div class="col-lg-4 mb-reveal"><div class="mb-card">
          <div class="mb-card-ico"><i class="fa-solid fa-person-falling-burst"></i></div>
          <h3>Clases de Calistenia</h3>
          <p>Programas progresivos con equipos de primer nivel y entrenadores expertos para dominar tu peso corporal.</p>
        </div></div>
        <div class="col-lg-4 mb-reveal d1"><div class="mb-card">
          <div class="mb-card-ico"><i class="fa-solid fa-heart-pulse"></i></div>
          <h3>Programa de Cardio</h3>
          <p>Rutinas diseñadas para mejorar tu resistencia, quemar grasa y potenciar tu fuerza funcional.</p>
        </div></div>
        <div class="col-lg-4 mb-reveal d2"><div class="mb-card">
          <div class="mb-card-ico"><i class="fa-solid fa-dumbbell"></i></div>
          <h3>Estudio de Fitness</h3>
          <p>Espacio equipado con áreas adaptadas para todos los niveles y acompañamiento personalizado.</p>
        </div></div>
      </div>
    </div>
  </section>

  {{-- ================= CTA ================= --}}
  <section class="cta-section">
    <div class="container"><div class="row align-items-center">
      <div class="col-lg-7"><div class="cta-data mb-reveal">
        <h2>¡Comienza tu Entrenamiento!</h2>
        <p>Únete a la Calistenia MadBarzz y deja que nuestros entrenadores expertos te guíen hacia tus metas de fuerza y resistencia. Rutinas personalizadas y asesoramiento profesional.</p>
        <a href="https://wa.me/59176372337?text=Hola%2C%20quiero%20unirme%20a%20la%20Calistenia%20MadBarzz." target="_blank" class="theme-btn" style="background-color:#FD6A01;">¡Únete Hoy!</a>
      </div></div>
      <div class="col-lg-5"><div class="cta-data mb-reveal d1"><figure>
        <img src="{{ asset('assets/images/entrenaconnosotros.jpg') }}" alt="Entrena con nosotros - MadBarzz" style="width:100%;height:auto;object-fit:contain;border-radius:16px;display:block;">
      </figure></div></div>
    </div></div>
  </section>

  {{-- ================= NOSOTROS / ENTRENADORES ================= --}}
  <section id="entrenadores" class="gap" style="padding-bottom:20px;">
    <div class="container"><div class="row">
      <div class="col-lg-6"><div class="data mb-reveal">
        <span style="color:#FD6A01;">EN CALISTENIA MADBARZZ</span>
        <h2>HACEMOS A LAS PERSONAS MÁS SALUDABLES, FELICES Y FUERTES. ¡ÚNETE A NOSOTROS!</h2>
      </div></div>
      <div class="col-lg-6"><div class="making-people mb-reveal d1">
        <p>Estamos seguros de que nuestro personal y nuestras instalaciones pueden ayudarte a alcanzar tus objetivos. Ya seas principiante o avanzado, ¡estamos aquí para apoyarte!</p>
        <div class="features"><ul>
          <li><i class="fa-solid fa-circle-check" style="color:#FD6A01;"></i> Alcanzarás tus objetivos</li>
          <li><i class="fa-solid fa-circle-check" style="color:#FD6A01;"></i> Tendrás soporte ilimitado</li>
          <li><i class="fa-solid fa-circle-check" style="color:#FD6A01;"></i> Encontrarás la ayuda útil</li>
          <li><i class="fa-solid fa-circle-check" style="color:#FD6A01;"></i> Ganarás confianza</li>
          <li><i class="fa-solid fa-circle-check" style="color:#FD6A01;"></i> Aprenderás algo nuevo cada día</li>
        </ul></div>
      </div></div>
    </div></div>
  </section>

  {{-- ================= GUÍAS (acordeón) ================= --}}
  <section id="guias" class="contact-form-one gap no-bottom" style="padding-top:20px;">
    <div class="container"><div class="row align-items-center">
      <div class="col-lg-6">
        <div class="data mb-reveal"><span style="color:#FD6A01;">PARA PRINCIPIANTES</span><h2>Guía para empezar en Calistenia MadBarzz</h2></div>
        <div class="acc2"><div class="accordion" id="accordionExample">
          <div class="accordion-item"><h2 class="accordion-header" id="heading-One">
            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-One" aria-expanded="true" aria-controls="collapse-One">¿Cuál es la información básica que debo conocer?</button>
          </h2><div id="collapse-One" class="accordion-collapse collapse show" data-bs-parent="#accordionExample"><div class="accordion-body">Aprende sobre los tipos de ejercicios, seguridad, calentamiento y enfriamiento, y la importancia de la constancia.</div></div></div>
          <div class="accordion-item"><h2 class="accordion-header" id="heading-Two">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-Two" aria-expanded="false" aria-controls="collapse-Two">¿Cómo empezar si soy principiante?</button>
          </h2><div id="collapse-Two" class="accordion-collapse collapse" data-bs-parent="#accordionExample"><div class="accordion-body">Comienza con ejercicios básicos de fuerza y flexibilidad, dedicando 20-30 minutos al día para acostumbrarte a la rutina.</div></div></div>
          <div class="accordion-item"><h2 class="accordion-header" id="heading-Three">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-Three" aria-expanded="false" aria-controls="collapse-Three">¿Qué hacer en tu primera sesión?</button>
          </h2><div id="collapse-Three" class="accordion-collapse collapse" data-bs-parent="#accordionExample"><div class="accordion-body">Empieza con flexiones, sentadillas y planchas. Nuestros entrenadores te guiarán para ejecutar cada movimiento correctamente.</div></div></div>
          <div class="accordion-item"><h2 class="accordion-header" id="heading-Four">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-Four" aria-expanded="false" aria-controls="collapse-Four">¿Cómo encontrar una rutina adecuada?</button>
          </h2><div id="collapse-Four" class="accordion-collapse collapse" data-bs-parent="#accordionExample"><div class="accordion-body">Te proporcionamos rutinas personalizadas según tu nivel y objetivos. Consulta con nuestros entrenadores para recibir orientación.</div></div></div>
        </div></div>
      </div>
      <div class="col-lg-6 mb-reveal d1">
        <figure style="margin:0;"><img src="{{ asset('assets/images/senotagym.jpg') }}" alt="Calistenia MadBarzz" style="width:100%;height:100%;object-fit:cover;border-radius:18px;max-height:520px;"></figure>
      </div>
    </div></div>
  </section>

  {{-- ================= FOOTER ================= --}}
  <footer id="contacto" class="footer-style-one">
    <div class="footer-p-2"><div class="container"><div class="row">
      <div class="col-lg-6 col-md-6 col-sm-12"><div class="footer-col">
        <h3>Información</h3>
        <p>En Calistenia MadBarzz creemos que la constancia es la clave. Entrenar regularmente mejora tu fuerza, resistencia y bienestar.</p>
        <ul class="social-media">
          <li><a href="https://www.facebook.com/MadBarzz" target="_blank"><i class="fa-brands fa-facebook-f"></i></a></li>
          <li><a href="https://wa.me/{{ '591' . preg_replace('/\D/', '', $superadmin->persona->telefono ?? '') }}" target="_blank"><i class="fa-brands fa-whatsapp"></i></a></li>
          <li><a href="https://www.instagram.com/calistenia_madbarzz?igsh=aW91eXl6ZnVtMGJ2" target="_blank"><i class="fa-brands fa-instagram"></i></a></li>
        </ul>
      </div></div>
      <div class="col-lg-6 col-md-6 col-sm-12"><div class="footer-col">
        <h3>Contacto</h3>
        <ul>
          <li><span style="background-color:#FD6A01;padding:8px;border-radius:4px;display:inline-flex;align-items:center;justify-content:center;margin-right:10px;"><i class="flaticon-maps" style="color:#fff;font-size:16px;"></i></span><p>Av. Santos Dumont, 4to anillo, al lado de la HOT, Santa Cruz de la Sierra</p></li>
          <li><span style="background-color:#FD6A01;padding:8px;border-radius:4px;display:inline-flex;align-items:center;justify-content:center;margin-right:10px;"><i class="flaticon-iphone" style="color:#fff;font-size:16px;"></i></span><p><a href="https://wa.me/{{ '591' . preg_replace('/\D/', '', $superadmin->persona->telefono ?? '') }}" target="_blank">{{ $superadmin->persona->telefono ?? 'Número no disponible' }}</a></p></li>
          <li><span style="background-color:#FD6A01;padding:8px;border-radius:4px;display:inline-flex;align-items:center;justify-content:center;margin-right:10px;"><i class="flaticon-mail" style="color:#fff;font-size:16px;"></i></span><p><a href="mailto:{{ $superadmin->user->email ?? '' }}">{{ $superadmin->user->email ?? 'Correo no disponible' }}</a></p></li>
        </ul>
      </div></div>
    </div></div></div>
    <div class="footer-p-3 rights"><div class="container"><div class="row"><div class="footer-col">
      <p>Calistenia MadBarzz <i class="fa-solid fa-heart" style="color:#FD6A01;"></i> © {{ date('Y') }} Todos los derechos reservados</p>
    </div></div></div></div>
  </footer>

  <div id="scroll-percentage"><span id="scroll-percentage-value"></span></div>

  <script src="assets/js/jquery.min.js"></script>
  <script src="assets/js/jquery.waypoints.min.js"></script>
  <script src="assets/js/jquery.counterup.min.js"></script>
  <script src="assets/js/bootstrap.min.js"></script>
  <script src="assets/js/jquery.fancybox.min.js"></script>
  <script src="assets/js/jquery.nice-select.js"></script>
  <script src="assets/js/owl.carousel.min.js"></script>
  <script src="assets/js/marquee.js"></script>
  <script src="assets/js/custom.js"></script>

  {{-- ===== Header ocultar al bajar / mostrar al subir + animaciones de aparición ===== --}}
  <script>
  (function(){
    var header = document.querySelector('.mb-site-header');
    var lastY = window.pageYOffset, ticking = false;
    function onScroll(){
      var y = window.pageYOffset;
      if (header){
        header.classList.toggle('mb-nav-scrolled', y > 60);
        // baja => ocultar ; sube => mostrar (con umbral para evitar parpadeo)
        if (y > lastY && y > 180){ header.classList.add('mb-nav-hidden'); }
        else if (y < lastY - 4){ header.classList.remove('mb-nav-hidden'); }
      }
      lastY = y; ticking = false;
    }
    window.addEventListener('scroll', function(){ if(!ticking){ window.requestAnimationFrame(onScroll); ticking = true; } }, {passive:true});

    // Aparición progresiva de secciones
    var obs = new IntersectionObserver(function(entries){
      entries.forEach(function(e){ if(e.isIntersecting){ e.target.classList.add('in'); obs.unobserve(e.target); } });
    }, {threshold:0.12});
    document.querySelectorAll('.mb-reveal').forEach(function(el){ obs.observe(el); });

    // Contadores animados (respaldo si counterup no corre)
    var started=false;
    function runCounters(){
      if(started) return; var band=document.querySelector('.mb-stats'); if(!band) return;
      if(band.getBoundingClientRect().top < window.innerHeight){ started=true;
        document.querySelectorAll('.mb-stats .counter').forEach(function(c){
          var target=parseInt(c.textContent,10)||0, n=0, step=Math.max(1,Math.ceil(target/60));
          var t=setInterval(function(){ n+=step; if(n>=target){n=target;clearInterval(t);} c.textContent=n+'+'; },24);
        });
      }
    }
    window.addEventListener('scroll', runCounters, {passive:true}); runCounters();
  })();
  
  (function(){
    var b=document.getElementById('mbBurger'), o=document.getElementById('mbOverlay'), c=document.getElementById('mbOverlayClose');
    function close(){ if(o){o.classList.remove('open');} if(b){b.classList.remove('open');} document.body.style.overflow=''; }
    if(b&&o){ b.addEventListener('click',function(){ var on=o.classList.toggle('open'); b.classList.toggle('open',on); document.body.style.overflow=on?'hidden':''; }); }
    if(c){ c.addEventListener('click',close); }
    o&&o.querySelectorAll('a').forEach(function(a){ a.addEventListener('click',close); });
  })();
</script>
</body>
</html>
