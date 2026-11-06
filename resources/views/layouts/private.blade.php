<!DOCTYPE html>
<html lang="es" data-theme="dark">

<head>
    <!-- Título -->
    <title>MadBarzz</title>

    <!-- Meta -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="author" content="DexignZone">
    <meta name="robots" content="">


    <!-- PWA -->
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#1E88E5">
    <link rel="apple-touch-icon" href="{{ asset('images/icons/icon-144x144.png') }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <meta name="apple-mobile-web-app-title" content="MiApp">



    <script>
        // Leer tema guardado; si no hay ninguno, usar oscuro por defecto
        var _mbTheme = localStorage.getItem('theme') || 'dark';
        document.documentElement.setAttribute('data-theme', _mbTheme);
        // Las reglas oscuras de la plantilla son "[data-theme-version=dark] .card", etc. (sin
        // exigir <body>): al ponerlo YA en <html>, las tarjetas nacen oscuras. Antes el atributo
        // llegaba recién con el <body> y las tarjetas se veían BLANCAS un instante al cargar.
        document.documentElement.setAttribute('data-theme-version', _mbTheme);
        // Menú lateral en "armado": oculto hasta quedar centrado y estable (ver script tras el menú).
        document.documentElement.classList.add('mb-menu-pend');
        setTimeout(function () { document.documentElement.classList.remove('mb-menu-pend'); }, 3000); // red de seguridad
        // Es solo para la carga inicial: al terminar de armarse la página se quita, y desde ahí
        // manda el atributo del <body> como siempre (así el interruptor claro/oscuro no cambia).
        document.addEventListener('DOMContentLoaded', function () {
            document.documentElement.removeAttribute('data-theme-version');
        });
        window.dezSettingsOptions = window.dezSettingsOptions || {};
        window.dezSettingsOptions.version = _mbTheme;
    </script>

    {{-- Sin "pantalla en blanco" al cambiar de sección: (1) el precargador de la
         plantilla es un velo BLANCO a pantalla completa que se quedaba hasta que
         terminaba de cargar todo (fuentes, imágenes), y en cada clic del menú se
         veía un destello blanco; se oculta. (2) El fondo de <html> se pinta con el
         color del tema desde el primer instante, así que ni siquiera mientras el
         navegador arma la página aparece blanco en modo oscuro. --}}
    <style>
        #preloader { display: none !important; }
        /* La plantilla arranca #main-wrapper con opacity:0 y lo muestra recién al
           terminar de cargar todos los scripts: eso dejaba el panel vacío en cada
           cambio de página. Se muestra de una, sin esperar. */
        #main-wrapper { opacity: 1 !important; transition: none !important; }
        html[data-theme="dark"], html[data-theme="dark"] body { background-color: #222327; }
        /* Las tarjetas traen "transition: all .5s" desde un fondo blanco: sin esto se ven
           aclarándose/oscureciéndose al cargar. Sin transición de entrada, nacen con su color. */
        html[data-theme-version="dark"] .card { transition: none !important; }
        /* Espacio al final del menú lateral: sin él, las últimas opciones (Ejercicios,
           Reportes...) no pueden subir hasta el centro del panel porque ya no hay más
           menú para desplazar. Ver el script que está justo después del menú. */
        .deznav .deznav-scroll #menu { padding-bottom: 40vh; }
        /* Los submenús nacen CERRADOS. Antes quedaban todos abiertos (el panel medía ~2000px) hasta
           que el script de la plantilla cerraba los que no correspondían (~1000px): el contenido se
           encogía de golpe y el panel "subía y bajaba" al cambiar de pantalla. El de la sección
           actual se abre al instante con el script que está justo después del menú. */
        .deznav .metismenu ul:not(.mm-show):not(.mm-collapsing) { display: none; }
        /* "Ver sitio web": un poco más chico que el resto del menú y con una línea continua debajo del texto. */
        .deznav .metismenu .mb-ver-sitio > a { font-size: .85rem; }
        .deznav .metismenu .mb-ver-sitio > a > i { font-size: 1.1rem; }
        .deznav .metismenu .mb-ver-sitio > a .nav-text {
            font-size: .85rem;
            text-decoration: underline; text-decoration-thickness: 1px; text-underline-offset: 5px;
        }
        /* Logos del encabezado del panel: configurables desde "Imágenes de la Home". Se limita el
           alto y se usa object-fit:contain para que cualquier proporción quepa sin deformarse. */
        .nav-header .logo-abbr { max-height: 52px; width: auto; object-fit: contain; }
        .nav-header .brand-title { max-height: 46px; width: auto; object-fit: contain; }
        /* Mientras el menú se arma (fuentes, íconos, submenú) se mantiene OCULTO y sin transiciones: se
           muestra ya centrado cuando el alto deja de cambiar. Así no se ve ningún vaivén. */
        html.mb-menu-pend .deznav .deznav-scroll { visibility: hidden; }
        html.mb-menu-pend .deznav, html.mb-menu-pend .deznav * { transition: none !important; }
    </style>
    <meta name="keywords"
        content="admin, panel de administración, plantilla admin, bootstrap, bootstrap 5, plantilla admin bootstrap 5, fitness, admin fitness, moderno, panel de administración responsive, panel de ventas, sass, kit de interfaz, aplicación web">
    <meta name="description"
        content="Descubre Gymove, la solución fitness definitiva diseñada para ayudarte a lograr un estilo de vida más saludable con sus características innovadoras y programas personalizados. Gymove es una plantilla de panel de administración completamente responsive que ofrece la combinación perfecta de ejercicio, nutrición y motivación. Comienza tu viaje fitness hoy con Gymove y visita DexignZone para más información.">

    <meta property="og:title" content="Gymove - Plantilla de Panel de Administración Fitness Bootstrap">
    <meta property="og:description"
        content="Descubre Gymove, la solución fitness definitiva diseñada para ayudarte a lograr un estilo de vida más saludable con sus características innovadoras y programas personalizados. Gymove es una plantilla de panel de administración completamente responsive que ofrece la combinación perfecta de ejercicio, nutrición y motivación. Comienza tu viaje fitness hoy con Gymove y visita DexignZone para más información.">
    <meta property="og:image" content="https://gymove.dexignzone.com/xhtml/social-image.png">
    <meta name="format-detection" content="telephone=no">

    <!-- Mobile Specific -->
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Favicon -->
    <link rel="shortcut icon" href="{{ \App\Services\ImagenHomeService::logoEmblema() }}">
    <link rel="icon" href="{{ \App\Services\ImagenHomeService::logoEmblema() }}">
    <!--<link rel="stylesheet" href="vendor/chartist/css/chartist.min.css">-->
    <link href="{{ asset('assets_private/vendor/bootstrap-select/css/bootstrap-select.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets_private/vendor/owl-carousel/owl.carousel.css') }}" rel="stylesheet">
    <link href="{{ asset('assets_private/css/style.css') }}" rel="stylesheet">

    {{-- ============ Mejoras de UI (capa aditiva MadBarzz) ============ --}}
    {{-- Font Awesome 6 (las vistas usan fa-solid/fas pero antes no se cargaba) --}}
    <link href="{{ asset('assets_private/icons/fontawesome/css/all.min.css') }}" rel="stylesheet">
    {{-- Notificaciones y confirmaciones (ya venían en la plantilla, ahora se usan) --}}
    <link href="{{ asset('assets_private/vendor/toastr/css/toastr.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets_private/vendor/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet">
    {{-- Hoja de estilo propia que moderniza tarjetas, tablas, formularios, etc. --}}
    <link href="{{ asset('assets_private/css/app-ui.css') }}" rel="stylesheet">
    {{-- Piel "Filament" (paleta índigo/zinc): solo actúa en vistas con @section('body-class', 'mb-skin') --}}
    <link href="{{ asset('assets_private/css/mb-skin-filament.css') }}" rel="stylesheet">
    {{-- Acento naranja de la marca sobre la plantilla (menú, encabezado, pie). Se carga al final para ganar. --}}
    <link href="{{ asset('assets_private/css/mb-brand-orange.css') }}?v=2" rel="stylesheet">
    @stack('styles')
    {{-- Las fuentes de Google se cargan SIN bloquear el dibujado de la página: antes
         este <link> detenía todo el render hasta bajar la fuente (pantalla vacía si
         la conexión iba lenta). Con media="print" + onload se aplican apenas llegan. --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800;900&family=Roboto:wght@100;300;400;500;700;900&display=swap"
        rel="stylesheet" media="print" onload="this.media='all'">
    <noscript>
        <link
            href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800;900&family=Roboto:wght@100;300;400;500;700;900&display=swap"
            rel="stylesheet">
    </noscript>
</head>

{{-- La piel "Filament" (mb-skin) aplica a todo el sistema privado; ver mb-skin-filament.css --}}
<body class="mb-skin @yield('body-class')">
    {{-- "Saltar al contenido": primer elemento enfocable de la página, para no
         obligar a navegar por teclado toda la barra/menú lateral en cada
         pantalla. ".visually-hidden-focusable" es la utilidad de Bootstrap 5
         (ya cargado en este layout) pensada justo para este patrón. --}}
    <a href="#contenido-principal" class="visually-hidden-focusable">Saltar al contenido</a>
    <script>
        // Aplica el tema guardado inmediatamente (evita parpadeo y conserva la elección entre páginas)
        (function () {
            try {
                var t = localStorage.getItem('theme') || 'dark';
                document.body.setAttribute('data-theme-version', t);
                document.documentElement.setAttribute('data-theme', t);
            } catch (e) {}
        })();
    </script>

    <!--*******************
        Preloader start
    ********************-->
    <div id="preloader">
        <div class="sk-three-bounce">
            <div class="sk-child sk-bounce1"></div>
            <div class="sk-child sk-bounce2"></div>
            <div class="sk-child sk-bounce3"></div>
        </div>
    </div>
    <!--*******************
        Preloader end
    ********************-->

    <!--**********************************
        Main wrapper start
    ***********************************-->
    <div id="main-wrapper">

        <!--**********************************
            Nav header start
        ***********************************-->
        <div class="nav-header">
            <span class="brand-logo" style="cursor:default;">
                <img class="logo-abbr" src="{{ \App\Services\ImagenHomeService::logoEmblema() }}" alt="">
                <img class="logo-compact" src="{{ \App\Services\ImagenHomeService::logoTexto() }}" alt="">
                <img class="brand-title" src="{{ \App\Services\ImagenHomeService::logoTexto() }}" alt="">
            </span>

            <div class="nav-control">
                <div class="hamburger">
                    <span class="line"></span><span class="line"></span><span class="line"></span>
                </div>
            </div>
        </div>
        <!--**********************************
            Nav header end
        ***********************************-->

        <!--**********************************
            Chat box start
        ***********************************
        <div class="chatbox">
            <div class="chatbox-close"></div>
            <div class="custom-tab-1">
                <ul class="nav nav-tabs">
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#notes">Notas</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#alerts">Alertas</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#chat">Chat</a>
                    </li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane fade active show" id="chat" role="tabpanel">
                        <div class="card mb-sm-3 mb-md-0 contacts_card dz-chat-user-box">
                            <div class="card-header chat-list-header text-center">
                                <a href="#"><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="18px" height="18px" viewBox="0 0 24 24" version="1.1"><g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"><rect fill="#000000" x="4" y="11" width="16" height="2" rx="1"/><rect fill="#000000" opacity="0.3" transform="translate(12.000000, 12.000000) rotate(-270.000000) translate(-12.000000, -12.000000) " x="4" y="11" width="16" height="2" rx="1"/></g></svg></a>
                                <div>
                                    <h6 class="mb-1">Lista de Chat</h6>
                                    <p class="mb-0">Mostrar Todo</p>
                                </div>
                                <a href="#"><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="18px" height="18px" viewBox="0 0 24 24" version="1.1"><g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"><rect x="0" y="0" width="24" height="24"/><circle fill="#000000" cx="5" cy="12" r="2"/><circle fill="#000000" cx="12" cy="12" r="2"/><circle fill="#000000" cx="19" cy="12" r="2"/></g></svg></a>
                            </div>
                            <div class="card-body contacts_body p-0 dz-scroll" id="DZ_W_Contacts_Body">
                                <ul class="contacts">
                                    <li class="name-first-letter">A</li>
                                    <li class="active dz-chat-user">
                                        <div class="d-flex bd-highlight">
                                            <div class="img_cont">
                                                <img src="{{ asset('assets_private/images/avatar/1.jpg') }}" class="rounded-circle user_img" alt="">
                                                <span class="online_icon"></span>
                                            </div>
                                            <div class="user_info">
                                                <span>Archie Parker</span>
                                                <p>Kalid está en línea</p>
                                            </div>
                                        </div>
                                    </li>
                                    <li class="dz-chat-user">
                                        <div class="d-flex bd-highlight">
                                            <div class="img_cont">
                                                <img src="{{ asset('assets_private/images/avatar/2.jpg') }}" class="rounded-circle user_img" alt="">
                                                <span class="online_icon offline"></span>
                                            </div>
                                            <div class="user_info">
                                                <span>Alfie Mason</span>
                                                <p>Taherah salió hace 7 minutos</p>
                                            </div>
                                        </div>
                                    </li>
                                    <li class="dz-chat-user">
                                        <div class="d-flex bd-highlight">
                                            <div class="img_cont">
                                                <img src="{{ asset('assets_private/images/avatar/3.jpg') }}" class="rounded-circle user_img" alt="">
                                                <span class="online_icon"></span>
                                            </div>
                                            <div class="user_info">
                                                <span>AharlieKane</span>
                                                <p>Sami está en línea</p>
                                            </div>
                                        </div>
                                    </li>
                                    <li class="dz-chat-user">
                                        <div class="d-flex bd-highlight">
                                            <div class="img_cont">
                                                <img src="{{ asset('assets_private/images/avatar/4.jpg') }}" class="rounded-circle user_img" alt="">
                                                <span class="online_icon offline"></span>
                                            </div>
                                            <div class="user_info">
                                                <span>Athan Jacoby</span>
                                                <p>Nargis salió hace 30 minutos</p>
                                            </div>
                                        </div>
                                    </li>
                                    <li class="name-first-letter">B</li>
                                    <li class="dz-chat-user">
                                        <div class="d-flex bd-highlight">
                                            <div class="img_cont">
                                                <img src="{{ asset('assets_private/images/avatar/5.jpg') }}" class="rounded-circle user_img" alt="">
                                                <span class="online_icon offline"></span>
                                            </div>
                                            <div class="user_info">
                                                <span>Bashid Samim</span>
                                                <p>Rashid salió hace 50 minutos</p>
                                            </div>
                                        </div>
                                    </li>
                                    <li class="dz-chat-user">
                                        <div class="d-flex bd-highlight">
                                            <div class="img_cont">
                                                <img src="{{ asset('assets_private/images/avatar/1.jpg') }}" class="rounded-circle user_img" alt="">
                                                <span class="online_icon"></span>
                                            </div>
                                            <div class="user_info">
                                                <span>Breddie Ronan</span>
                                                <p>Kalid está en línea</p>
                                            </div>
                                        </div>
                                    </li>
                                    <li class="dz-chat-user">
                                        <div class="d-flex bd-highlight">
                                            <div class="img_cont">
                                                <img src="{{ asset('assets_private/images/avatar/2.jpg') }}" class="rounded-circle user_img" alt="">
                                                <span class="online_icon offline"></span>
                                            </div>
                                            <div class="user_info">
                                                <span>Ceorge Carson</span>
                                                <p>Taherah salió hace 7 minutos</p>
                                            </div>
                                        </div>
                                    </li>
                                    <li class="name-first-letter">D</li>
                                    <li class="dz-chat-user">
                                        <div class="d-flex bd-highlight">
                                            <div class="img_cont">
                                                <img src="{{ asset('assets_private/images/avatar/3.jpg') }}" class="rounded-circle user_img" alt="">
                                                <span class="online_icon"></span>
                                            </div>
                                            <div class="user_info">
                                                <span>Darry Parker</span>
                                                <p>Sami está en línea</p>
                                            </div>
                                        </div>
                                    </li>
                                    <li class="dz-chat-user">
                                        <div class="d-flex bd-highlight">
                                            <div class="img_cont">
                                                <img src="{{ asset('assets_private/images/avatar/4.jpg') }}" class="rounded-circle user_img" alt="">
                                                <span class="online_icon offline"></span>
                                            </div>
                                            <div class="user_info">
                                                <span>Denry Hunter</span>
                                                <p>Nargis salió hace 30 minutos</p>
                                            </div>
                                        </div>
                                    </li>
                                    <li class="name-first-letter">J</li>
                                    <li class="dz-chat-user">
                                        <div class="d-flex bd-highlight">
                                            <div class="img_cont">
                                                <img src="{{ asset('assets_private/images/avatar/5.jpg') }}" class="rounded-circle user_img" alt="">
                                                <span class="online_icon offline"></span>
                                            </div>
                                            <div class="user_info">
                                                <span>Jack Ronan</span>
                                                <p>Rashid salió hace 50 minutos</p>
                                            </div>
                                        </div>
                                    </li>
                                    <li class="dz-chat-user">
                                        <div class="d-flex bd-highlight">
                                            <div class="img_cont">
                                                <img src="{{ asset('assets_private/images/avatar/1.jpg') }}" class="rounded-circle user_img" alt="">
                                                <span class="online_icon"></span>
                                            </div>
                                            <div class="user_info">
                                                <span>Jacob Tucker</span>
                                                <p>Kalid está en línea</p>
                                            </div>
                                        </div>
                                    </li>
                                    <li class="dz-chat-user">
                                        <div class="d-flex bd-highlight">
                                            <div class="img_cont">
                                                <img src="{{ asset('assets_private/images/avatar/2.jpg') }}" class="rounded-circle user_img" alt="">
                                                <span class="online_icon offline"></span>
                                            </div>
                                            <div class="user_info">
                                                <span>James Logan</span>
                                                <p>Taherah salió hace 7 minutos</p>
                                            </div>
                                        </div>
                                    </li>
                                    <li class="dz-chat-user">
                                        <div class="d-flex bd-highlight">
                                            <div class="img_cont">
                                                <img src="{{ asset('assets_private/images/avatar/3.jpg') }}" class="rounded-circle user_img" alt="">
                                                <span class="online_icon"></span>
                                            </div>
                                            <div class="user_info">
                                                <span>Joshua Weston</span>
                                                <p>Sami está en línea</p>
                                            </div>
                                        </div>
                                    </li>
                                    <li class="name-first-letter">O</li>
                                    <li class="dz-chat-user">
                                        <div class="d-flex bd-highlight">
                                            <div class="img_cont">
                                                <img src="{{ asset('assets_private/images/avatar/4.jpg') }}" class="rounded-circle user_img" alt="">
                                                <span class="online_icon offline"></span>
                                            </div>
                                            <div class="user_info">
                                                <span>Oliver Acker</span>
                                                <p>Nargis salió hace 30 minutos</p>
                                            </div>
                                        </div>
                                    </li>
                                    <li class="dz-chat-user">
                                        <div class="d-flex bd-highlight">
                                            <div class="img_cont">
                                                <img src="{{ asset('assets_private/images/avatar/5.jpg') }}" class="rounded-circle user_img" alt="">
                                                <span class="online_icon offline"></span>
                                            </div>
                                            <div class="user_info">
                                                <span>Oscar Weston</span>
                                                <p>Rashid salió hace 50 minutos</p>
                                            </div>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="card chat dz-chat-history-box d-none">
                            <div class="card-header chat-list-header text-center">
                                <a href="#" class="dz-chat-history-back">
                                    <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="18px" height="18px" viewBox="0 0 24 24" version="1.1"><g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"><polygon points="0 0 24 0 24 24 0 24"/><rect fill="#000000" opacity="0.3" transform="translate(15.000000, 12.000000) scale(-1, 1) rotate(-90.000000) translate(-15.000000, -12.000000) " x="14" y="7" width="2" height="10" rx="1"/><path d="M3.7071045,15.7071045 C3.3165802,16.0976288 2.68341522,16.0976288 2.29289093,15.7071045 C1.90236664,15.3165802 1.90236664,14.6834152 2.29289093,14.2928909 L8.29289093,8.29289093 C8.67146987,7.914312 9.28105631,7.90106637 9.67572234,8.26284357 L15.6757223,13.7628436 C16.0828413,14.136036 16.1103443,14.7686034 15.7371519,15.1757223 C15.3639594,15.5828413 14.7313921,15.6103443 14.3242731,15.2371519 L9.03007346,10.3841355 L3.7071045,15.7071045 Z" fill="#000000" fill-rule="nonzero" transform="translate(9.000001, 11.999997) scale(-1, -1) rotate(90.000000) translate(-9.000001, -11.999997) "/></g></svg>
                                </a>
                                <div>
                                    <h6 class="mb-1">Chat con Khelesh</h6>
                                    <p class="mb-0 text-success">En línea</p>
                                </div>
                                <div class="dropdown">
                                    <a href="#" data-bs-toggle="dropdown" aria-expanded="false"><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="18px" height="18px" viewBox="0 0 24 24" version="1.1"><g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"><rect x="0" y="0" width="24" height="24"/><circle fill="#000000" cx="5" cy="12" r="2"/><circle fill="#000000" cx="12" cy="12" r="2"/><circle fill="#000000" cx="19" cy="12" r="2"/></g></svg></a>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li class="dropdown-item"><i class="fa fa-user-circle text-primary me-2"></i> Ver perfil</li>
                                        <li class="dropdown-item"><i class="fa fa-users text-primary me-2"></i> Agregar a amigos cercanos</li>
                                        <li class="dropdown-item"><i class="fa fa-plus text-primary me-2"></i> Agregar a grupo</li>
                                        <li class="dropdown-item"><i class="fa fa-ban text-primary me-2"></i> Bloquear</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="card-body msg_card_body dz-scroll" id="DZ_W_Contacts_Body3">
                                <div class="d-flex justify-content-start mb-4">
                                    <div class="img_cont_msg">
                                        <img src="{{ asset('assets_private/images/avatar/1.jpg') }}" class="rounded-circle user_img_msg" alt="">
                                    </div>
                                    <div class="msg_cotainer">
                                        Hola, ¿cómo estás samim?
                                        <span class="msg_time">8:40 AM, Hoy</span>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-end mb-4">
                                    <div class="msg_cotainer_send">
                                        Hola Khalid, estoy bien, gracias. ¿Y tú?
                                        <span class="msg_time_send">8:55 AM, Hoy</span>
                                    </div>
                                    <div class="img_cont_msg">
                                <img src="{{ asset('assets_private/images/avatar/2.jpg') }}" class="rounded-circle user_img_msg" alt="">
                                    </div>
                                </div>
                                <div class="d-flex justify-content-start mb-4">
                                    <div class="img_cont_msg">
                                        <img src="{{ asset('assets_private/images/avatar/1.jpg') }}" class="rounded-circle user_img_msg" alt="">
                                    </div>
                                    <div class="msg_cotainer">
                                        Yo también estoy bien, gracias por tu plantilla de chat
                                        <span class="msg_time">9:00 AM, Hoy</span>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-end mb-4">
                                    <div class="msg_cotainer_send">
                                        De nada
                                        <span class="msg_time_send">9:05 AM, Hoy</span>
                                    </div>
                                    <div class="img_cont_msg">
                                <img src="{{ asset('assets_private/images/avatar/2.jpg') }}" class="rounded-circle user_img_msg" alt="">
                                    </div>
                                </div>
                                <div class="d-flex justify-content-start mb-4">
                                    <div class="img_cont_msg">
                                        <img src="{{ asset('assets_private/images/avatar/1.jpg') }}" class="rounded-circle user_img_msg" alt="">
                                    </div>
                                    <div class="msg_cotainer">
                                        Estoy buscando tus próximas plantillas
                                        <span class="msg_time">9:07 AM, Hoy</span>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-end mb-4">
                                    <div class="msg_cotainer_send">
                                        Ok, gracias, que tengas un buen día
                                        <span class="msg_time_send">9:10 AM, Hoy</span>
                                    </div>
                                    <div class="img_cont_msg">
                                        <img src="{{ asset('assets_private/images/avatar/2.jpg') }}" class="rounded-circle user_img_msg" alt="">
                                    </div>
                                </div>
                                <div class="d-flex justify-content-start mb-4">
                                    <div class="img_cont_msg">
                                        <img src="{{ asset('assets_private/images/avatar/1.jpg') }}" class="rounded-circle user_img_msg" alt="">
                                    </div>
                                    <div class="msg_cotainer">
                                        Adiós, nos vemos
                                        <span class="msg_time">9:12 AM, Hoy</span>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-start mb-4">
                                    <div class="img_cont_msg">
                                        <img src="{{ asset('assets_private/images/avatar/1.jpg') }}" class="rounded-circle user_img_msg" alt="">
                                    </div>
                                    <div class="msg_cotainer">
                                        Hola, ¿cómo estás samim?
                                        <span class="msg_time">8:40 AM, Hoy</span>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-end mb-4">
                                    <div class="msg_cotainer_send">
                                        Hola Khalid, estoy bien, gracias. ¿Y tú?
                                        <span class="msg_time_send">8:55 AM, Hoy</span>
                                    </div>
                                    <div class="img_cont_msg">
                                <img src="{{ asset('assets_private/images/avatar/2.jpg') }}" class="rounded-circle user_img_msg" alt="">
                                    </div>
                                </div>
                                <div class="d-flex justify-content-start mb-4">
                                    <div class="img_cont_msg">
                                        <img src="{{ asset('assets_private/images/avatar/1.jpg') }}" class="rounded-circle user_img_msg" alt="">
                                    </div>
                                    <div class="msg_cotainer">
                                        Yo también estoy bien, gracias por tu plantilla de chat
                                        <span class="msg_time">9:00 AM, Hoy</span>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-end mb-4">
                                    <div class="msg_cotainer_send">
                                        De nada
                                        <span class="msg_time_send">9:05 AM, Hoy</span>
                                    </div>
                                    <div class="img_cont_msg">
                                <img src="{{ asset('assets_private/images/avatar/2.jpg') }}" class="rounded-circle user_img_msg" alt="">
                                    </div>
                                </div>
                                <div class="d-flex justify-content-start mb-4">
                                    <div class="img_cont_msg">
                                        <img src="{{ asset('assets_private/images/avatar/1.jpg') }}" class="rounded-circle user_img_msg" alt="">
                                    </div>
                                    <div class="msg_cotainer">
                                        Estoy buscando tus próximas plantillas
                                        <span class="msg_time">9:07 AM, Hoy</span>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-end mb-4">
                                    <div class="msg_cotainer_send">
                                        Ok, gracias, que tengas un buen día
                                        <span class="msg_time_send">9:10 AM, Hoy</span>
                                    </div>
                                    <div class="img_cont_msg">
                                        <img src="{{ asset('assets_private/images/avatar/2.jpg') }}" class="rounded-circle user_img_msg" alt="">
                                    </div>
                                </div>
                                <div class="d-flex justify-content-start mb-4">
                                    <div class="img_cont_msg">
                                        <img src="{{ asset('assets_private/images/avatar/1.jpg') }}" class="rounded-circle user_img_msg" alt="">
                                    </div>
                                    <div class="msg_cotainer">
                                        Adiós, nos vemos
                                        <span class="msg_time">9:12 AM, Hoy</span>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer type_msg">
                                <div class="input-group">
                                    <textarea class="form-control" placeholder="Escribe tu mensaje..."></textarea>
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-primary"><i class="fa fa-location-arrow"></i></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="alerts" role="tabpanel">
                        <div class="card mb-sm-3 mb-md-0 contacts_card">
                            <div class="card-header chat-list-header text-center">
                                <a href="#"><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="18px" height="18px" viewBox="0 0 24 24" version="1.1"><g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"><rect x="0" y="0" width="24" height="24"/><circle fill="#000000" cx="5" cy="12" r="2"/><circle fill="#000000" cx="12" cy="12" r="2"/><circle fill="#000000" cx="19" cy="12" r="2"/></g></svg></a>
                                <div>
                                    <h6 class="mb-1">Notificaciones</h6>
                                    <p class="mb-0">Mostrar Todo</p>
                                </div>
                                <a href="#"><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="18px" height="18px" viewBox="0 0 24 24" version="1.1"><g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"><rect x="0" y="0" width="24" height="24"/><path d="M14.2928932,16.7071068 C13.9023689,16.3165825 13.9023689,15.6834175 14.2928932,15.2928932 C14.6834175,14.9023689 15.3165825,14.9023689 15.7071068,15.2928932 L19.7071068,19.2928932 C20.0976311,19.6834175 20.0976311,20.3165825 19.7071068,20.7071068 C19.3165825,21.0976311 18.6834175,21.0976311 18.2928932,20.7071068 L14.2928932,16.7071068 Z" fill="#000000" fill-rule="nonzero" opacity="0.3"/><path d="M11,16 C13.7614237,16 16,13.7614237 16,11 C16,8.23857625 13.7614237,6 11,6 C8.23857625,6 6,8.23857625 6,11 C6,13.7614237 8.23857625,16 11,16 Z M11,18 C7.13400675,18 4,14.8659932 4,11 C4,7.13400675 7.13400675,4 11,4 C14.8659932,4 18,7.13400675 18,11 C18,14.8659932 14.8659932,18 11,18 Z" fill="#000000" fill-rule="nonzero"/></g></svg></a>
                            </div>
                            <div class="card-body contacts_body p-0 dz-scroll" id="DZ_W_Contacts_Body1">
                                <ul class="contacts">
                                    <li class="name-first-letter">ESTADO DEL SERVIDOR</li>
                                    <li class="active">
                                        <div class="d-flex bd-highlight">
                                            <div class="img_cont primary">KK</div>
                                            <div class="user_info">
                                                <span>Cumpleaños de David Nester</span>
                                                <p class="text-primary">Hoy</p>
                                            </div>
                                        </div>
                                    </li>
                                    <li class="name-first-letter">SOCIAL</li>
                                    <li>
                                        <div class="d-flex bd-highlight">
                                            <div class="img_cont success">RU<i class="icon fa-birthday-cake"></i></div>
                                            <div class="user_info">
                                                <span>Perfección Simplificada</span>
                                                <p>Jame Smith comentó tu estado</p>
                                            </div>
                                        </div>
                                    </li>
                                    <li class="name-first-letter">ESTADO DEL SERVIDOR</li>
                                    <li>
                                        <div class="d-flex bd-highlight">
                                            <div class="img_cont primary">AU<i class="icon fa fa-user-plus"></i></div>
                                            <div class="user_info">
                                                <span>AharlieKane</span>
                                                <p>Sami está en línea</p>
                                            </div>
                                        </div>
                                    </li>
                                    <li>
                                        <div class="d-flex bd-highlight">
                                            <div class="img_cont info">MO<i class="icon fa fa-user-plus"></i></div>
                                            <div class="user_info">
                                                <span>Athan Jacoby</span>
                                                <p>Nargis salió hace 30 minutos</p>
                                            </div>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                            <div class="card-footer"></div>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="notes">
                        <div class="card mb-sm-3 mb-md-0 note_card">
                            <div class="card-header chat-list-header text-center">
                                <a href="#"><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="18px" height="18px" viewBox="0 0 24 24" version="1.1"><g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"><rect fill="#000000" x="4" y="11" width="16" height="2" rx="1"/><rect fill="#000000" opacity="0.3" transform="translate(12.000000, 12.000000) rotate(-270.000000) translate(-12.000000, -12.000000) " x="4" y="11" width="16" height="2" rx="1"/></g></svg></a>
                                <div>
                                    <h6 class="mb-1">Notas</h6>
                                    <p class="mb-0">Agregar Nueva Nota</p>
                                </div>
                                <a href="#"><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="18px" height="18px" viewBox="0 0 24 24" version="1.1"><g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"><rect x="0" y="0" width="24" height="24"/><path d="M14.2928932,16.7071068 C13.9023689,16.3165825 13.9023689,15.6834175 14.2928932,15.2928932 C14.6834175,14.9023689 15.3165825,14.9023689 15.7071068,15.2928932 L19.7071068,19.2928932 C20.0976311,19.6834175 20.0976311,20.3165825 19.7071068,20.7071068 C19.3165825,21.0976311 18.6834175,21.0976311 18.2928932,20.7071068 L14.2928932,16.7071068 Z" fill="#000000" fill-rule="nonzero" opacity="0.3"/><path d="M11,16 C13.7614237,16 16,13.7614237 16,11 C16,8.23857625 13.7614237,6 11,6 C8.23857625,6 6,8.23857625 6,11 C6,13.7614237 8.23857625,16 11,16 Z M11,18 C7.13400675,18 4,14.8659932 4,11 C4,7.13400675 7.13400675,4 11,4 C14.8659932,4 18,7.13400675 18,11 C18,14.8659932 14.8659932,18 11,18 Z" fill="#000000" fill-rule="nonzero"/></g></svg></a>
                            </div>
                            <div class="card-body contacts_body p-0 dz-scroll" id="DZ_W_Contacts_Body2">
                                <ul class="contacts">
                                    <li class="active">
                                        <div class="d-flex bd-highlight">
                                            <div class="user_info">
                                                <span>Nuevo pedido realizado..</span>
                                                <p>10 Ago 2020</p>
                                            </div>
                                            <div class="ms-auto">
                                                <a href="#" class="btn btn-primary btn-xs sharp me-1"><i class="fa fa-pencil"></i></a>
                                                <a href="#" class="btn btn-danger btn-xs sharp"><i class="fa fa-trash"></i></a>
                                            </div>
                                        </div>
                                    </li>
                                    <li>
                                        <div class="d-flex bd-highlight">
                                            <div class="user_info">
                                                <span>Youtube, un sitio web para compartir videos..</span>
                                                <p>10 Ago 2020</p>
                                            </div>
                                            <div class="ms-auto">
                                                <a href="#" class="btn btn-primary btn-xs sharp me-1"><i class="fa fa-pencil"></i></a>
                                                <a href="#" class="btn btn-danger btn-xs sharp"><i class="fa fa-trash"></i></a>
                                            </div>
                                        </div>
                                    </li>
                                    <li>
                                        <div class="d-flex bd-highlight">
                                            <div class="user_info">
                                                <span>john acaba de comprar tu producto..</span>
                                                <p>10 Ago 2020</p>
                                            </div>
                                            <div class="ms-auto">
                                                <a href="#" class="btn btn-primary btn-xs sharp me-1"><i class="fa fa-pencil"></i></a>
                                                <a href="#" class="btn btn-danger btn-xs sharp"><i class="fa fa-trash"></i></a>
                                            </div>
                                        </div>
                                    </li>
                                    <li>
                                        <div class="d-flex bd-highlight">
                                            <div class="user_info">
                                                <span>Athan Jacoby</span>
                                                <p>10 Ago 2020</p>
                                            </div>
                                            <div class="ms-auto">
                                                <a href="#" class="btn btn-primary btn-xs sharp me-1"><i class="fa fa-pencil"></i></a>
                                                <a href="#" class="btn btn-danger btn-xs sharp"><i class="fa fa-trash"></i></a>
                                            </div>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>-->
        <!--**********************************
            Chat box End
        ***********************************-->

        <!--**********************************
            Header start
        ***********************************-->
        <header class="header">
            <div class="header-content">
                <nav class="navbar navbar-expand">
                    <div class="collapse navbar-collapse justify-content-between">
                        @php
                            [$_barIcon, $_barText] = match(true) {
                                request()->routeIs('dashboard.cliente')             => ['fa-solid fa-gauge-high',       'Panel de Control'],
                                request()->routeIs('dashboard.entrenador')          => ['fa-solid fa-gauge-high',       'Panel de Control'],
                                request()->routeIs('dashboard')                     => ['fa-solid fa-gauge-high',       'Panel de Control'],
                                request()->routeIs('users.*')                        => ['fa-solid fa-users-gear',        'Usuarios'],
                                request()->routeIs('roles.*')                        => ['fa-solid fa-shield-halved',     'Roles'],
                                request()->routeIs('personas.*')                     => ['fa-solid fa-address-card',      'Personas'],
                                request()->routeIs('clientes.*')                     => ['fa-solid fa-id-card',           'Clientes y Membresías'],
                                request()->routeIs('membresia_cliente.*')            => ['fa-solid fa-id-badge',          'Membresías de Clientes'],
                                request()->routeIs('membresias.*')                   => ['fa-solid fa-medal',             'Membresías'],
                                request()->routeIs('entrenadores.*')                 => ['fa-solid fa-dumbbell',          'Entrenadores'],
                                request()->routeIs('recepcionistas.*')               => ['fa-solid fa-id-badge',          'Recepcionistas'],
                                request()->routeIs('administradores.*')              => ['fa-solid fa-user-shield',       'Administradores'],
                                request()->routeIs('pagos.*')                        => ['fa-solid fa-money-bill-wave',   'Pagos'],
                                request()->routeIs('rutinas.*')                      => ['fa-solid fa-list-check',        'Rutinas'],
                                request()->routeIs('grupos_musculares.*')            => ['fa-solid fa-dumbbell',          'Grupos Musculares'],
                                request()->routeIs('calendario_musculos.*')          => ['fa-solid fa-calendar-days',     'Calendario de Músculos'],
                                request()->routeIs('horarios_entrenadores.*')        => ['fa-solid fa-clock',             'Horario de Entrenadores'],
                                request()->routeIs('ejercicios.*')                   => ['fa-solid fa-book-open',         'Ejercicios'],
                                request()->routeIs('asistencias.qr')                 => ['fa-solid fa-qrcode',            'Registrar Asistencia por QR'],
                                request()->routeIs('asistencias.*')                  => ['fa-solid fa-calendar-check',   'Asistencias'],
                                request()->routeIs('reportes.*')                     => ['fa-solid fa-chart-bar',         'Reportes'],
                                request()->routeIs('notificaciones.*')               => ['fa-solid fa-bell',              'Notificaciones'],
                                request()->routeIs('avisos.*')                       => ['fa-solid fa-bullhorn',          'Avisos'],
                                request()->routeIs('promociones.*')                  => ['fa-solid fa-tags',              'Promociones'],
                                request()->routeIs('descuentos.*')                   => ['fa-solid fa-tags',              'Descuentos'],
                                request()->routeIs('qr_pago.edit')                   => ['fa-solid fa-qrcode',            'QR de Pago'],
                                request()->routeIs('bitacora.*')                     => ['fa-solid fa-clock-rotate-left',  'Bitácora de Auditoría'],
                                request()->routeIs('login_lockouts.*')               => ['fa-solid fa-user-lock',          'Bloqueos de Login'],
                                request()->routeIs('configuracion.imagenes_home.*')  => ['fa-solid fa-images',            'Imágenes de la Home'],
                                request()->routeIs('configuracion.contenido_home.*') => ['fa-solid fa-pen-to-square',      'Contenido de la Home'],
                                request()->routeIs('portal.membresia')               => ['fa-solid fa-id-badge',          'Mi Membresía'],
                                request()->routeIs('portal.rutina')                  => ['fa-solid fa-dumbbell',          'Mi Rutina'],
                                request()->routeIs('profile.*')                      => ['fa-solid fa-circle-user',       'Mi Perfil'],
                                default                                              => ['fa-solid fa-gauge-high',        'Panel de Control'],
                            };
                        @endphp
                        <div class="header-left">
                            <div class="dashboard_bar">
                                <span class="dashboard_bar_text">{{ $_barText }}</span>
                            </div>
                        </div>
                        <ul class="navbar-nav header-right">
                            <!--  <li class="nav-item">
                                <form>
                                    <div class="input-group search-area d-lg-inline-flex d-none me-3">
                                      <span class="input-group-text" id="header-search">
                                            <button class="bg-transparent border-0">
                                                <i class="flaticon-381-search-2"></i>
                                            </button>
                                      </span>
                                      <input type="text" class="form-control" placeholder="Buscar aquí" aria-label="Username" aria-describedby="header-search">
                                    </div>
                                </form>
                            </li>-->
                            <li class="nav-item dropdown notification_dropdown">
                                <a class="nav-link bell dz-theme-mode" href="javascript:void(0);" id="theme-toggle">
                                    <i id="icon-light" class="fas fa-sun"></i>
                                    <i id="icon-dark" class="fas fa-moon"></i>
                                </a>
                            </li>

                            {{-- Campanita de Avisos (Etapas 2 y 3 del sistema de avisos): SOLO para
                                 el rol Cliente. $avisosNoLeidosCount / $avisosParaCampanita vienen del
                                 view composer AvisosClienteComposer (registrado en
                                 AppServiceProvider), que ya corta solo si el rol no es Cliente — acá
                                 el @if es nada más para no imprimir el HTML de más para el resto. --}}
                            @if (Auth::user() && Auth::user()->hasRole('Cliente'))
                                <li class="nav-item" style="position:relative;">
                                    <a class="nav-link" href="javascript:void(0)" onclick="mbToggleAvisos(event)" aria-label="Avisos">
                                        <i class="fa-solid fa-bell"></i>
                                        @if ($avisosNoLeidosCount > 0)
                                            <span id="mbAvisosBadge" class="badge rounded-pill bg-danger" style="position:absolute; top:2px; right:-2px; font-size:.62rem; padding:.25em .45em;">
                                                {{ $avisosNoLeidosCount > 9 ? '9+' : $avisosNoLeidosCount }}
                                            </span>
                                        @endif
                                    </a>
                                    <div id="mbAvisosMenu" style="display:none;position:fixed;z-index:99999;background:#222327;border:1px solid #333a54;border-radius:.5rem;box-shadow:0 4px 24px rgba(0,0,0,.4);max-height:420px;overflow-y:auto;">
                                        <div class="p-2 px-3" style="border-bottom:1px solid #333a54;">
                                            <strong class="text-white">Avisos</strong>
                                        </div>
                                        @forelse ($avisosParaCampanita as $aviso)
                                            <div class="d-flex gap-2 p-2 px-3 mb-aviso-item" style="border-bottom:1px solid #2c2d33;cursor:pointer;{{ $aviso->yaLeido ? 'opacity:.65;' : '' }}"
                                                 onclick="mbAbrirAvisoDetalle(this)"
                                                 data-titulo="{{ $aviso->titulo }}"
                                                 data-texto="{{ $aviso->texto }}"
                                                 data-fecha="{{ $aviso->created_at->format('d/m/Y H:i') }}"
                                                 data-imagen="{{ $aviso->imagen ? \Illuminate\Support\Facades\Storage::disk('public')->url($aviso->imagen) : '' }}">
                                                @if ($aviso->imagen)
                                                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($aviso->imagen) }}" alt=""
                                                         style="width:44px;height:44px;object-fit:cover;border-radius:6px;flex-shrink:0;">
                                                @else
                                                    <div style="width:44px;height:44px;border-radius:6px;flex-shrink:0;background:#2c2d33;display:flex;align-items:center;justify-content:center;">
                                                        <i class="fa-solid fa-bullhorn text-muted"></i>
                                                    </div>
                                                @endif
                                                <div style="min-width:0;">
                                                    <div class="text-white {{ $aviso->yaLeido ? '' : 'fw-semibold' }}" style="font-size:.85rem;">{{ $aviso->titulo }}</div>
                                                    <div class="text-muted text-truncate" style="font-size:.78rem;max-width:220px;">{{ $aviso->texto }}</div>
                                                    <div class="text-muted" style="font-size:.7rem;">{{ $aviso->created_at->format('d/m/Y H:i') }}</div>
                                                </div>
                                            </div>
                                        @empty
                                            <div class="p-3 text-center text-muted" style="font-size:.85rem;">No tenés avisos.</div>
                                        @endforelse
                                    </div>
                                </li>

                                {{-- Detalle completo de un aviso (Etapa 3): overlay simple mostrado/
                                     ocultado a mano, mismo criterio que el resto de esta campanita (sin
                                     el modal de Bootstrap) — se llena con los data-* de la tarjeta
                                     clickeada, sin pedir nada al servidor de nuevo. --}}
                                <div id="mbAvisoDetalleOverlay" style="display:none;position:fixed;inset:0;z-index:100000;background:rgba(0,0,0,.6);align-items:center;justify-content:center;padding:16px;" onclick="mbCerrarAvisoDetalle(event)">
                                    <div style="max-width:480px;width:100%;max-height:85vh;overflow-y:auto;background:#222327;border:1px solid #333a54;border-radius:.75rem;padding:1.25rem;" onclick="event.stopPropagation()">
                                        <div class="d-flex justify-content-between align-items-start mb-2 gap-3">
                                            <h5 class="mb-0 text-white" id="mbAvisoDetalleTitulo"></h5>
                                            <button type="button" class="btn-close btn-close-white" onclick="mbCerrarAvisoDetalle(event)" aria-label="Cerrar"></button>
                                        </div>
                                        <img id="mbAvisoDetalleImagen" src="" alt="" style="display:none;width:100%;max-height:260px;object-fit:contain;border-radius:8px;margin-bottom:.75rem;background:#111;">
                                        <p class="text-white" id="mbAvisoDetalleTexto" style="white-space:pre-line;"></p>
                                        <small class="text-muted" id="mbAvisoDetalleFecha"></small>
                                    </div>
                                </div>
                            @endif

                            <li class="nav-item header-profile" style="position:relative;">
                                <a class="nav-link" href="javascript:void(0)" onclick="mbToggleProfile(event)">
                                    <img src="{{ asset('assets_private/images/profile/17.jpg') }}" width="20" alt="">
                                    <div class="header-info">
                                        <span class="text-black">
                                            {{-- Aquí se muestra el NOMBRE DE USUARIO (users.name), para todos los roles --}}
                                            <strong>{{ Auth::user()->name }}</strong>
                                        </span>
                                        <p class="fs-12 mb-0">{{ Auth::user()->rol->nombre ?? 'Usuario' }}</p>
                                    </div>
                                </a>
                                {{-- .dropdown-menu: misma clase que usa el dropdown de perfil de la
                                     plantilla Gymove original (index.html), así hereda su color de
                                     fondo/borde YA theme-aware (ver [data-theme-version=dark]
                                     .dropdown-menu en style.css) en vez de un color fijo a mano. La
                                     posición la sigue calculando mbToggleProfile() en JS (mismo
                                     mecanismo que ya usa Avisos), pero ahora alineada al borde
                                     DERECHO del trigger y con clamp de "no salirse del viewport",
                                     como el dropdown-menu-end + Popper del original. --}}
                                <div id="mbProfileMenu" class="dropdown-menu" style="display:none;position:fixed;z-index:99999;">

                                    @auth
                                        {{-- El Admin edita desde el módulo de usuarios; los demás roles editan su propio perfil (incluido su username) --}}
                                        <a href="{{ Auth::user()->hasRole('Administrador') ? route('users.edit', Auth::user()->id) : route('profile.edit') }}" class="dropdown-item ai-icon">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="text-primary" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                                <circle cx="12" cy="7" r="4"></circle>
                                            </svg>
                                            <span class="ms-2">Perfil</span>
                                        </a>
                                    @endauth

                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <a href="{{ route('logout') }}" class="dropdown-item ai-icon"
                                            onclick="event.preventDefault(); this.closest('form').submit();">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="text-danger" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                                <polyline points="16 17 21 12 16 7"></polyline>
                                                <line x1="21" y1="12" x2="9" y2="12"></line>
                                            </svg>
                                            <span class="ms-2">Cerrar sesión</span>
                                        </a>
                                    </form>

                                </div>
                            </li>


                        </ul>
                    </div>
                </nav>
            </div>
        </header>
        <!--**********************************
            Header end ti-comment-alt
        ***********************************-->

        <!--**********************************
            Sidebar start
        ***********************************-->
        <div class="deznav d-flex flex-column">
            <div class="deznav-scroll flex-grow-1">
                <ul class="metismenu" id="menu">
                    <li>
                        @php
                            $userRol = Auth::user()->rol->nombre ?? '';
                        @endphp

                        @if ($userRol === 'Cliente')
                            <a href="{{ route('dashboard.cliente') }}">
                                <i class="fa-solid fa-gauge-high"></i>
                                <span class="nav-text">Panel de Control</span>
                            </a>
                        @else
                            <a href="{{ route('dashboard') }}">
                                <i class="fa-solid fa-gauge-high"></i>
                                <span class="nav-text">Panel de Control</span>
                            </a>
                        @endif
                    </li>

                    @if ($userRol === 'Administrador')
                        <!-- Usuarios - Solo para Administrador -->
                        <li class="has-arrow">
                            <a href="javascript:void()">
                                <i class="flaticon-381-user-1"></i>
                                <span class="nav-text">Usuarios</span>
                                <i class="fa-solid fa-chevron-right mb-menu-arr"></i>
                            </a>
                            <ul aria-expanded="false">
                                <li><a href="{{ route('roles.index') }}"><i class="fa-solid fa-shield-alt me-2"></i>Roles</a></li>
                                <li><a href="{{ route('users.index') }}"><i class="fa-solid fa-users-gear me-2"></i>Usuarios del Sistema</a></li>
                                <li><a href="{{ route('personas.index') }}"><i class="fa-solid fa-address-card me-2"></i>Personas</a></li>
                                <li><a href="{{ route('bitacora.index') }}"><i class="fa-solid fa-clock-rotate-left me-2"></i>Bitácora de Auditoría</a></li>
                                <li><a href="{{ route('login_lockouts.index') }}"><i class="fa-solid fa-user-lock me-2"></i>Bloqueos de Login</a></li>
                            </ul>
                        </li>
                    @endif

                    @if (in_array($userRol, ['Administrador', 'Entrenador', 'Recepcionista', 'Cliente']))
                        <!-- Clientes y Membresías - Para todos excepto Cliente (Cliente verá versión limitada) -->
                        <li class="has-arrow">
                            <a href="javascript:void()">
                                <i class="flaticon-381-id-card"></i>
                                <span class="nav-text">{{ $userRol === 'Cliente' ? 'Membresías' : 'Clientes y Membresías' }}</span>
                                <i class="fa-solid fa-chevron-right mb-menu-arr"></i>
                            </a>
                            <ul aria-expanded="false">
                                @if ($userRol === 'Cliente')
                                    <li><a href="{{ route('portal.membresia') }}"><i class="fa-solid fa-id-badge me-2"></i>Mi Membresía</a></li>
                                @else
                                    <li><a href="{{ route('clientes.index') }}"><i class="fa-solid fa-id-card me-2"></i>Clientes</a></li>
                                    <li><a href="{{ route('membresias.index') }}"><i class="fa-solid fa-medal me-2"></i>Membresías</a></li>
                                    <li><a href="{{ route('membresia_cliente.index') }}"><i class="fa-solid fa-id-badge me-2"></i>Membresías de Clientes</a>
                                    </li>
                                    {{-- Catálogo visible para todo el personal (gestión solo Admin) --}}
                                    <li><a href="{{ route('descuentos.index') }}"><i class="fa-solid fa-tags me-2"></i>Descuentos</a></li>
                                    @if ($userRol === 'Administrador')
                                        <li><a href="{{ route('qr_pago.edit') }}"><i class="fa-solid fa-qrcode me-2"></i>QR de Pago</a></li>
                                    @endif
                                @endif
                            </ul>
                        </li>
                    @endif

                    @if ($userRol === 'Administrador')
                        <!-- Personal - SOLO Administrador (la protección real está en las rutas) -->
                        <li class="has-arrow">
                            <a href="javascript:void()">
                                <i class="fas fa-users"></i>
                                <span class="nav-text">Personal</span>
                                <i class="fa-solid fa-chevron-right mb-menu-arr"></i>
                            </a>
                            <ul aria-expanded="false">
                                <li>
                                    <a href="{{ route('entrenadores.index') }}">
                                        <i class="fas fa-dumbbell"></i> Entrenadores
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('recepcionistas.index') }}">
                                        <i class="fas fa-id-badge"></i> Recepcionistas
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('administradores.index') }}">
                                        <i class="fas fa-user-shield"></i>Administradores
                                    </a>
                                </li>
                            </ul>
                        </li>
                    @endif

                    @if (in_array($userRol, ['Administrador', 'Entrenador', 'Recepcionista', 'Cliente']))
                        <!-- Operaciones - Para todos los roles -->
                        <li class="has-arrow">
                            <a href="javascript:void()">
                                <i class="flaticon-381-settings-2"></i>
                                <span class="nav-text">Operaciones</span>
                                <i class="fa-solid fa-chevron-right mb-menu-arr"></i>
                            </a>
                            <ul aria-expanded="false">
                                @if ($userRol === 'Cliente')
                                    <li><a href="{{ route('asistencias.mis') }}"><i class="fa-solid fa-calendar-check me-2"></i>Mis Asistencias</a></li>
                                    <li><a href="{{ route('pagos.mis-pagos') }}"><i class="fa-solid fa-money-bill-wave me-2"></i>Mis Pagos</a></li>
                                    <li><a href="{{ route('portal.rutina') }}"><i class="fa-solid fa-dumbbell me-2"></i>Mi Rutina</a></li>
                                @else
                                    <li><a href="{{ route('asistencias.qr') }}"><i class="fa-solid fa-qrcode me-2"></i>Registrar Asistencia (QR)</a></li>
                                    @if (in_array($userRol, ['Administrador', 'Recepcionista']))
                                        <li><a href="{{ route('notificaciones.index') }}"><i class="fa-solid fa-bell me-2"></i>Notificaciones</a></li>
                                        <li><a href="{{ route('avisos.index') }}"><i class="fa-solid fa-bullhorn me-2"></i>Avisos</a></li>
                                    @endif
                                    <li><a href="{{ route('pagos.index') }}"><i class="fa-solid fa-money-bill-wave me-2"></i>Pagos</a></li>
                                    @if (in_array($userRol, ['Administrador', 'Entrenador']))
                                        <li><a href="{{ route('rutinas.index') }}"><i class="fa-solid fa-list-check me-2"></i>Rutinas</a></li>
                                    @endif
                                    {{-- Grupos Musculares es un catálogo para armar rutinas: a la Recepcionista
                                         no le aporta (no arma rutinas), así que no se lista en su menú. Solo
                                         el menú: el permiso de lectura de la ruta no cambia. --}}
                                    @if ($userRol !== 'Recepcionista')
                                        <li><a href="{{ route('grupos_musculares.index') }}"><i class="fa-solid fa-dumbbell me-2"></i>Grupos Musculares</a></li>
                                    @endif
                                    <li><a href="{{ route('horarios_entrenadores.index') }}"><i class="fa-solid fa-clock me-2"></i>Horario de Entrenadores</a></li>
                                    @if ($userRol === 'Administrador')
                                        <li><a href="{{ route('calendario_musculos.index') }}"><i class="fa-solid fa-calendar-days me-2"></i>Calendario de Músculos</a></li>
                                        <li><a href="{{ route('ejercicios.index') }}"><i class="fa-solid fa-book-open me-2"></i>Ejercicios</a></li>
                                    @endif
                                @endif
                            </ul>
                        </li>
                    @endif

                    @if ($userRol === 'Administrador')
                        <!-- Sitio Web - Solo Administrador: lo que se ve en la página pública (home y login) -->
                        <li class="has-arrow">
                            <a href="javascript:void()">
                                <i class="fa-solid fa-globe"></i>
                                <span class="nav-text">Sitio Web</span>
                                <i class="fa-solid fa-chevron-right mb-menu-arr"></i>
                            </a>
                            <ul aria-expanded="false">
                                <li><a href="{{ route('configuracion.imagenes_home.index') }}"><i class="fa-solid fa-images me-2"></i>Imágenes de la Home</a></li>
                                <li><a href="{{ route('configuracion.contenido_home.edit') }}"><i class="fa-solid fa-pen-to-square me-2"></i>Contenido de la Home</a></li>
                            </ul>
                        </li>
                    @endif

                    @if ($userRol === 'Administrador')
                        <!-- Reportes - Solo Administrador (las rutas de reportes son solo de Admin) -->
                        <li class="has-arrow">
                            <a href="javascript:void()">
                                <i class="fas fa-chart-bar"></i>
                                <span class="nav-text">Reportes</span>
                                <i class="fa-solid fa-chevron-right mb-menu-arr"></i>
                            </a>
                            <ul aria-expanded="false">
                                @if ($userRol === 'Administrador')
                                    <li><a href="{{ url('/estadisticas') }}"><i class="fa-solid fa-chart-pie me-2"></i>Panel de Estadísticas</a></li>
                                @endif
                                <li><a href="{{ route('reportes.clientes.estado') }}"><i class="fa-solid fa-user-check me-2"></i>Clientes Activos/Inactivos</a>
                                </li>
                                <li><a href="{{ route('reportes.clientes.nuevos') }}"><i class="fa-solid fa-user-plus me-2"></i>Clientes Nuevos del Mes</a></li>
                                <li><a href="{{ route('reportes.clientes.membresia_vencida') }}"><i class="fa-solid fa-user-clock me-2"></i>Clientes Membresía
                                        Vencida</a></li>
                                <li><a href="{{ route('reportes.pagos.mes') }}"><i class="fa-solid fa-file-invoice-dollar me-2"></i>Pagos por Mes</a></li>
                            </ul>
                        </li>
                    @endif

                    {{-- Ver el sitio público (home) sin cerrar sesión: abre en pestaña nueva,
                         así el panel queda abierto. La home no desloguea a nadie. --}}
                    <li class="mb-ver-sitio">
                        <a href="{{ route('home') }}" target="_blank" rel="noopener">
                            <i class="fa-solid fa-arrow-up-right-from-square"></i>
                            <span class="nav-text">Ver sitio web</span>
                        </a>
                    </li>
                </ul>
                {{-- Se ejecuta ANTES del primer dibujado: (1) abre la rama del menú de la pantalla actual
                     y (2) la centra en el panel lateral, una sola vez y sin animación. Así no hay saltos
                     (antes: se abría todo, se cerraba y se re-centraba varias veces). --}}
                <script>
                (function () {
                    var menu = document.getElementById('menu');
                    var cont = menu && menu.closest('.deznav-scroll');
                    if (!menu || !cont) return;

                    var ruta = location.pathname.replace(/\/+$/, '') || '/';
                    var mejor = null, mejorLargo = -1;
                    menu.querySelectorAll('a[href]').forEach(function (a) {
                        var h = a.getAttribute('href') || '';
                        if (!h || h.charAt(0) === '#' || h.indexOf('javascript') === 0) return;
                        var p;
                        try { p = new URL(a.href, location.origin).pathname.replace(/\/+$/, '') || '/'; } catch (e) { return; }
                        if (p === '/') return;
                        if ((ruta === p || ruta.indexOf(p + '/') === 0) && p.length > mejorLargo) { mejor = a; mejorLargo = p.length; }
                    });
                    if (!mejor) return;

                    // Abrir la rama: cada <ul> ancestro queda visible y su <li> marcado como activo.
                    var li = mejor.closest('li');
                    while (li && li !== menu) {
                        li.classList.add('mm-active');
                        var ul = li.parentElement;
                        if (ul && ul !== menu) ul.classList.add('mm-show');
                        li = ul ? ul.closest('li') : null;
                    }

                    var tocadoAMano = false;
                    cont.addEventListener('wheel', function () { tocadoAMano = true; }, { passive: true, once: true });
                    cont.addEventListener('touchstart', function () { tocadoAMano = true; }, { passive: true, once: true });

                    function centrar() {
                        if (tocadoAMano) return;
                        var rc = cont.getBoundingClientRect(), ra = mejor.getBoundingClientRect();
                        cont.scrollTop += (ra.top - rc.top) - (rc.height / 2 - ra.height / 2);
                    }
                    function mostrar() { document.documentElement.classList.remove('mb-menu-pend'); try { performance.mark('mb-menu-listo'); } catch (e) {} }

                    centrar();

                    // Esperar a que el menú deje de cambiar de alto (fuentes e íconos que llegan tarde),
                    // centrar UNA vez más y recién ahí mostrarlo. Tope: ~1.6 s.
                    var inicio = Date.now(), ultimo = -1, estable = 0;
                    function esperar() {
                        var h = cont.scrollHeight;
                        estable = (h === ultimo) ? estable + 1 : 0;
                        ultimo = h;
                        var listoLoad = document.readyState === 'complete';
                        if ((listoLoad && estable >= 6) || Date.now() - inicio > 1600) { centrar(); mostrar(); return; }
                        requestAnimationFrame(esperar);
                    }
                    requestAnimationFrame(esperar);
                })();
                </script>
            </div>
        </div>
        <!--**********************************
            Sidebar end
        ***********************************-->

        <!--**********************************
            Content body start
        ***********************************-->
        <div class="content-body default-height" id="contenido-principal" tabindex="-1">
            <!-- row -->
            <div class="container-fluid">
                <div class="row">
                    @yield('contenido')

                </div>
            </div>
        </div>
        <!--**********************************
            Content body end
        ***********************************-->

        <!--**********************************
            Footer start
        ***********************************-->
        <footer class="footer">
            <div class="copyright">
                <p>Copyright © Diseñado y Desarrollado por <a
                        href="https://www.instagram.com/robertadrian.07?igsh=MThyMGpsb3gycjhpZg=="
                        target="_blank">Robert Garrado Adrian</a> 2025</p>
            </div>
        </footer>
        <!--**********************************
            Footer end
        ***********************************-->
    </div>
    <!--**********************************
        Main wrapper end
    ***********************************-->

    <!-- SERVICE WORKER (Web Push, Etapa 2) -->

    {{-- Igual que la campanita de Avisos: solo Cliente ve/ejecuta esto. El
         botón real vive en dashboard/cliente.blade.php (tarjeta "Notificaciones
         push"); acá solo se definen las funciones que ese botón llama. --}}
    @if (Auth::user() && Auth::user()->hasRole('Cliente'))
    <script>
        const publicVapidKey = {!! json_encode(config('services.webpush.public_key')) !!};

        function urlBase64ToUint8Array(base64String) {
            const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
            const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
            const rawData = atob(base64);
            return new Uint8Array([...rawData].map(c => c.charCodeAt(0)));
        }

        // Safari en iOS solo permite push si la web está agregada a la
        // pantalla de inicio (modo standalone) — si no, ni tiene sentido
        // pedir permiso.
        function mbEsIosNoInstalado() {
            const esIos = /iphone|ipad|ipod/i.test(navigator.userAgent) && !window.MSStream;
            const esStandalone = window.navigator.standalone === true
                || window.matchMedia('(display-mode: standalone)').matches;
            return esIos && !esStandalone;
        }

        function mbActualizarEstadoBotonPush(suscrito) {
            const btn = document.getElementById('mbPushBtn');
            if (!btn) return; // esta página no tiene la tarjeta de push
            btn.dataset.suscrito = suscrito ? '1' : '0';
            btn.disabled = false;
            btn.innerHTML = suscrito
                ? '<i class="fa-solid fa-bell-slash me-1"></i> Desactivar notificaciones'
                : '<i class="fa-solid fa-bell me-1"></i> Activar notificaciones';
            btn.style.background = suscrito ? '#64748b' : '#f59e0b';
        }

        async function subscribeUser() {
            const btn = document.getElementById('mbPushBtn');
            if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
                if (typeof toastr !== 'undefined') toastr.error('Tu navegador no soporta notificaciones push.');
                return;
            }

            try {
                // 1️⃣ Registrar SW
                const register = await navigator.serviceWorker.register('/serviceworker.js');
                await navigator.serviceWorker.ready;

                // 2️⃣ Eliminar suscripción previa (por si quedó una vieja/inválida)
                const existing = await register.pushManager.getSubscription();
                if (existing) {
                    await existing.unsubscribe();
                }

                // 3️⃣ Crear nueva suscripción (acá el navegador pide permiso si hace falta)
                const subscription = await register.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: urlBase64ToUint8Array(publicVapidKey)
                });

                // 4️⃣ Guardar en el backend — campos planos (endpoint/p256dh/auth),
                // no el shape anidado nativo del navegador ({endpoint, keys:{...}}).
                const raw = subscription.toJSON();
                await fetch('{{ route('portal.push.suscribir') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        endpoint: raw.endpoint,
                        p256dh: raw.keys.p256dh,
                        auth: raw.keys.auth
                    })
                });

                mbActualizarEstadoBotonPush(true);
                if (typeof toastr !== 'undefined') toastr.success('Notificaciones activadas.');
            } catch (err) {
                console.error('Error registrando suscripción push:', err);
                if (btn) btn.disabled = false;
                if (Notification.permission === 'denied') {
                    const aviso = document.getElementById('mbPushDenegadoAviso');
                    if (aviso) aviso.style.display = 'block';
                } else if (typeof toastr !== 'undefined') {
                    toastr.error('No se pudo activar las notificaciones: ' + err.message);
                }
            }
        }

        async function unsubscribeUser() {
            if (!('serviceWorker' in navigator)) return;

            try {
                const register = await navigator.serviceWorker.getRegistration('/serviceworker.js');
                const subscription = register && await register.pushManager.getSubscription();

                if (subscription) {
                    const endpoint = subscription.endpoint;
                    await subscription.unsubscribe();
                    await fetch('{{ route('portal.push.desuscribir') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ endpoint })
                    });
                }

                mbActualizarEstadoBotonPush(false);
                if (typeof toastr !== 'undefined') toastr.success('Notificaciones desactivadas.');
            } catch (err) {
                console.error('Error al desactivar la suscripción push:', err);
                if (typeof toastr !== 'undefined') toastr.error('No se pudo desactivar las notificaciones.');
            }
        }

        function mbTogglePush() {
            const btn = document.getElementById('mbPushBtn');
            if (!btn || btn.disabled) return;
            btn.disabled = true;
            if (btn.dataset.suscrito === '1') {
                unsubscribeUser();
            } else {
                subscribeUser();
            }
        }

        // Al cargar la página (si esta tiene la tarjeta de push): detecta
        // iOS sin instalar / permiso ya denegado, y si no, refleja el estado
        // real de la suscripción en el botón.
        async function mbInicializarBotonPush() {
            const btn = document.getElementById('mbPushBtn');
            if (!btn) return;

            if (mbEsIosNoInstalado()) {
                const aviso = document.getElementById('mbPushIosAviso');
                if (aviso) aviso.style.display = 'block';
                btn.disabled = true;
                return;
            }

            if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
                btn.disabled = true;
                return;
            }

            if (typeof Notification !== 'undefined' && Notification.permission === 'denied') {
                const aviso = document.getElementById('mbPushDenegadoAviso');
                if (aviso) aviso.style.display = 'block';
            }

            try {
                const register = await navigator.serviceWorker.register('/serviceworker.js');
                const existing = await register.pushManager.getSubscription();
                mbActualizarEstadoBotonPush(!!existing);
            } catch (err) {
                console.error('Error verificando suscripción push:', err);
            }
        }

        document.addEventListener('DOMContentLoaded', mbInicializarBotonPush);
    </script>
    @endif



    <!--**********************************
        Scripts
    ***********************************-->
    <script>
        const savedTheme = localStorage.getItem('theme') || 'dark';
        document.documentElement.setAttribute('data-theme', savedTheme);
        window.dezSettingsOptions = window.dezSettingsOptions || {};
        window.dezSettingsOptions.version = savedTheme;
    </script>
    <!-- Required vendors -->
    <script src="{{ asset('assets_private/vendor/global/global.min.js') }}"></script>
    <script src="{{ asset('assets_private/vendor/bootstrap-select/js/bootstrap-select.min.js') }}"></script>
    <script src="{{ asset('assets_private/vendor/chart-js/chart.bundle.min.js') }}"></script>
    <script src="{{ asset('assets_private/vendor/owl-carousel/owl.carousel.js') }}"></script>
    <!-- Chart piety plugin files -->
    <!--    <script src="{{ asset('assets_private/vendor/peity/jquery.peity.min.js') }}"></script>-->
    <!-- Apex Chart -->
    <!--<script src="{{ asset('assets_private/vendor/apexchart/apexchart.js') }}"></script>-->
    <!-- Dashboard 1 -->
    <!--<script src="{{ asset('assets_private/js/dashboard/dashboard-1.js') }}"></script>-->
    <script src="{{ asset('assets_private/js/custom.min.js') }}"></script>
    <script src="{{ asset('assets_private/js/deznav-init.js') }}"></script>
    <script>
        function carouselReview() {
            /*  testimonial one function by = owl.carousel.js */
            jQuery('.testimonial-one').owlCarousel({
                nav: true,
                loop: true,
                autoplay: true,
                margin: 30,
                dots: false,
                left: true,
                navText: ['<i class="fa fa-chevron-left" aria-hidden="true"></i>',
                    '<i class="fa fa-chevron-right" aria-hidden="true"></i>'
                ],
                responsive: {
                    0: {
                        items: 1
                    },
                    484: {
                        items: 2
                    },
                    882: {
                        items: 3
                    },
                    1200: {
                        items: 2
                    },

                    1540: {
                        items: 3
                    },
                    1740: {
                        items: 4
                    }
                }
            })
        }
        jQuery(window).on('load', function() {
            setTimeout(function() {
                carouselReview();
            }, 1000);
        });
    </script>
    <script>
        // ===== Tema claro/oscuro con persistencia (fuente única: localStorage 'theme') =====
        function mbApplyTheme(t) {
            document.body.setAttribute('data-theme-version', t);     // selector real del CSS de la plantilla
            document.documentElement.setAttribute('data-theme', t);  // compatibilidad
            var iconLight = document.getElementById('icon-light');
            var iconDark  = document.getElementById('icon-dark');
            if (iconLight && iconDark) {
                iconLight.style.display = (t === 'dark') ? 'none' : 'inline-block';
                iconDark.style.display  = (t === 'dark') ? 'inline-block' : 'none';
            }
            try { localStorage.setItem('theme', t); } catch (e) {}
        }

        function toggleTheme() {
            var actual = (document.body.getAttribute('data-theme-version') === 'dark') ? 'dark' : 'light';
            mbApplyTheme(actual === 'dark' ? 'light' : 'dark');
        }

        function mbSavedTheme() {
            try { return localStorage.getItem('theme') || 'dark'; } catch (e) { return 'dark'; }
        }

        document.addEventListener('DOMContentLoaded', function () {
            mbApplyTheme(mbSavedTheme());
            var btn = document.getElementById('theme-toggle');
            if (btn) btn.addEventListener('click', function (e) {
                e.preventDefault();
                // El botón también lleva la clase "dz-theme-mode" de la plantilla,
                // que registra SU PROPIO click handler (custom.js, handleThemeMode) con
                // una lógica de tema paralela basada en cookies en vez de localStorage.
                // Sin esto, cada clic dispara ambos handlers y se pisan entre sí,
                // dejando localStorage y el tema visible desincronizados desde el
                // primer clic (y no solo al hacer zoom). Frenamos el evento aquí para
                // que el handler de la plantilla ni siquiera llegue a ejecutarse.
                e.stopImmediatePropagation();
                toggleTheme();
            });
        });

        jQuery(document).ready(function () {
            var saved = mbSavedTheme();
            window.dezSettingsOptions = window.dezSettingsOptions || {};
            window.dezSettingsOptions.version = saved;
            try { new dezSettings(window.dezSettingsOptions); } catch (e) {}
            mbApplyTheme(saved); // reasegurar después de dezSettings

            // La plantilla (custom.js, handleThemeMode) registra su PROPIO
            // listener de "resize" que reimpone el tema guardado en una
            // cookie ("version") — un sistema paralelo al nuestro, que solo
            // usa localStorage. El zoom del navegador (Ctrl + scroll/rueda)
            // dispara "resize", así que si esa cookie no existe o quedó
            // desactualizada (expira a los 30 min), la plantilla pisaba
            // nuestro tema y la pantalla volvía a claro. Como no tocamos el
            // JS de la plantilla, registramos nuestro propio listener DESPUÉS
            // (los handlers de un mismo evento corren en el orden en que se
            // registraron, y el de la plantilla ya se registró más arriba en
            // este mismo ready()), así el nuestro corre siempre al final y
            // reafirma el valor real de localStorage.
            jQuery(window).on('resize', function () {
                mbApplyTheme(mbSavedTheme());
            });
        });
    </script>
    {{-- ============ Capa de experiencia MadBarzz ============ --}}
    {{-- Mensajes flash de Laravel expuestos a JS para mostrarlos como toasts --}}
    <script>
        window.MB_FLASH = {
            success: @json(session('success')),
            error:   @json(session('error')),
            warning: @json(session('warning')),
            info:    @json(session('info')),
            @php
                $mbStatusTexto = match (session('status')) {
                    'profile-updated'  => 'Perfil actualizado correctamente.',
                    'password-updated' => 'Contraseña actualizada correctamente.',
                    default            => session('status'),
                };
            @endphp
            status:  @json($mbStatusTexto),
            errors:  @json($errors->all())
        };
    </script>
    <script src="{{ asset('assets_private/vendor/toastr/js/toastr.min.js') }}"></script>
    <script src="{{ asset('assets_private/vendor/sweetalert2/sweetalert2.min.js') }}"></script>
    <script src="{{ asset('assets_private/vendor/datatables/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('assets_private/js/app-ui.js') }}"></script>
    {{-- Alpine.js (self-hosted, sin CDN, mismo patrón que el resto de vendor/):
         usado por la pantalla de armar rutinas para el buscador/lista de
         ejercicios dinámica. "defer" lo inicializa solo tras parsear el DOM. --}}
    <script src="{{ asset('assets_private/vendor/alpinejs/alpine.min.js') }}" defer></script>
    @stack('scripts')
    @yield('page_scripts')
<script>
function mbToggleProfile(e) {
    e.stopPropagation();
    var menu = document.getElementById('mbProfileMenu');
    var trigger = e.currentTarget.closest('li');
    var rect = trigger.getBoundingClientRect();
    // Ancho fijo: lo da el CSS (.header-right .header-profile .dropdown-menu
    // => 12.5rem = 200px, heredado de la plantilla original), no rect.width
    // del trigger — en celular el trigger se achica a solo el avatar (el
    // texto de usuario/rol se oculta con display:none bajo 575px), y usar
    // ESE ancho para el menú era justamente lo que lo hacía desbordar.
    var anchoMenu = 200;
    // Alineado al borde DERECHO del trigger (como dropdown-menu-end en el
    // original), con clamp para que nunca se salga del viewport a ningún lado.
    var izquierda = rect.right - anchoMenu;
    if (izquierda + anchoMenu > window.innerWidth - 12) izquierda = window.innerWidth - anchoMenu - 12;
    if (izquierda < 12) izquierda = 12;
    menu.style.top   = (rect.bottom + 6) + 'px';
    menu.style.left  = izquierda + 'px';
    menu.style.right = 'auto';
    menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
}
document.addEventListener('click', function() {
    var menu = document.getElementById('mbProfileMenu');
    if (menu) menu.style.display = 'none';
});

// Campanita de Avisos (Etapas 2 y 3): mismo mecanismo que mbToggleProfile
// (posicionado a mano con getBoundingClientRect, sin usar el dropdown de
// Bootstrap) para quedar consistente con lo que ya existe — pero como
// función APARTE, sin tocar mbToggleProfile.
function mbToggleAvisos(e) {
    e.stopPropagation();
    var profileMenu = document.getElementById('mbProfileMenu');
    if (profileMenu) profileMenu.style.display = 'none'; // no dejar los dos abiertos a la vez
    var menu = document.getElementById('mbAvisosMenu');
    var trigger = e.currentTarget.closest('li');
    var rect = trigger.getBoundingClientRect();
    var anchoMenu = Math.min(340, window.innerWidth - 24);
    var izquierda = rect.left;
    if (izquierda + anchoMenu > window.innerWidth - 12) {
        izquierda = window.innerWidth - anchoMenu - 12;
    }
    menu.style.top   = (rect.bottom + 6) + 'px';
    menu.style.left  = izquierda + 'px';
    menu.style.width = anchoMenu + 'px';
    menu.style.right = 'auto';
    var seEstaAbriendo = menu.style.display === 'none';
    menu.style.display = seEstaAbriendo ? 'block' : 'none';

    // Al ABRIR (no al cerrar): marca todos los avisos como leídos y saca el
    // badge sin recargar. Si el POST falla (sin conexión, etc.) no rompe
    // nada — el dropdown ya se abrió igual, el badge queda como estaba y se
    // reintenta la próxima vez que se abra.
    if (seEstaAbriendo) {
        mbMarcarAvisosLeidos();
    }
}
document.addEventListener('click', function() {
    var menu = document.getElementById('mbAvisosMenu');
    if (menu) menu.style.display = 'none';
});

function mbMarcarAvisosLeidos() {
    fetch('{{ route('portal.avisos.marcar_leidos') }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
        },
    }).then(function (resp) {
        if (resp.ok) {
            var badge = document.getElementById('mbAvisosBadge');
            if (badge) badge.remove();
        }
    }).catch(function () {
        // Sin conexión u otro fallo: la campanita ya se abrió igual, no se
        // avisa nada — se vuelve a intentar la próxima vez que se abra.
    });
}

// Detalle completo de un aviso (Etapa 3): lee los data-* de la tarjeta
// clickeada (ya vienen renderizados en el dropdown, sin pedir nada de
// nuevo al servidor) y los muestra en el overlay compartido.
function mbAbrirAvisoDetalle(el) {
    document.getElementById('mbAvisoDetalleTitulo').textContent = el.dataset.titulo || '';
    document.getElementById('mbAvisoDetalleTexto').textContent  = el.dataset.texto || '';
    document.getElementById('mbAvisoDetalleFecha').textContent  = el.dataset.fecha || '';

    var img = document.getElementById('mbAvisoDetalleImagen');
    if (el.dataset.imagen) {
        img.src = el.dataset.imagen;
        img.style.display = 'block';
    } else {
        img.removeAttribute('src');
        img.style.display = 'none';
    }

    document.getElementById('mbAvisoDetalleOverlay').style.display = 'flex';
}

// Tablas anchas: si la última columna es "Acciones", se marca para dejarla fija a la derecha (ver mb-brand-orange.css).
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.content-body table').forEach(function (t) {
        var ths = t.querySelectorAll('thead th');
        if (ths.length && /acciones/i.test(ths[ths.length - 1].textContent)) { t.classList.add('mb-sticky-actions'); }
    });
});
function mbCerrarAvisoDetalle(e) {
    if (e) e.stopPropagation();
    var overlay = document.getElementById('mbAvisoDetalleOverlay');
    if (overlay) overlay.style.display = 'none';
}
</script>
</body>

</html>
