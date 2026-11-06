{{-- Footer del sitio público GymOn — extraído de welcome-gymon.blade.php para
     poder reusarlo en otras páginas públicas (ej. legal/documento.blade.php)
     sin duplicar el contacto/redes reales. $waLink es opcional: si quien
     incluye este partial no lo pasa (páginas que no vienen de la ruta "/"),
     se arma acá con el mismo número de siempre. --}}
@php
    $contenidoHome = $contenidoHome ?? new \App\Services\ContenidoHomeService();
    $waLink = $waLink ?? $contenidoHome->whatsappLink();
    $imagenesHomeFooter = $imagenesHome ?? new \App\Services\ImagenHomeService();
@endphp

<style>
  /* Franja de derechos: la plantilla pone el contenido en una fila (display:flex), por eso el
     copyright y los enlaces legales quedaban uno al lado del otro y desalineados. Se apilan y
     se centran: copyright arriba, Política · Términos debajo. */
  .footer-style-one .footer-p-3.rights .footer-col{flex-direction:column;align-items:center;text-align:center;gap:.4rem;padding:26px 15px;}
  .footer-style-one .footer-p-3.rights .footer-col p{margin:0;}
  .mb-legal-links{margin-top:0;font-size:.85rem;opacity:.8;}
  .mb-legal-links a{color:inherit;text-decoration:underline;}
  .mb-legal-links a:hover{color:var(--theme-color,#FD6A01);}
</style>

<footer id="contacto" class="footer-style-one" style="background-image: url({{ $imagenesHomeFooter->url('home_footer_fondo', asset('images/placeholder-home.svg')) }}); background-position: {{ $imagenesHomeFooter->posicionCssPara('home_footer_fondo') }};">
  <div class="footer-p-2">
    <div class="container">
      <div class="row">
        <div class="col-lg-4 col-md-6 col-sm-12">
          <div class="footer-col">
            <h3>Información</h3>
            <p>{{ $contenidoHome->get('info_texto') }}</p>
            <ul class="social-media">
              @if ($contenidoHome->get('facebook_url'))
                <li><a href="{{ $contenidoHome->get('facebook_url') }}" target="_blank" rel="noopener" aria-label="Facebook de MadBarzz"><i class="fa-brands fa-facebook-f"></i></a></li>
              @endif
              <li><a href="{{ $waLink }}" target="_blank" rel="noopener" aria-label="Escribinos por WhatsApp"><i class="fa-brands fa-whatsapp"></i></a></li>
              @if ($contenidoHome->get('instagram_url'))
                <li><a href="{{ $contenidoHome->get('instagram_url') }}" target="_blank" rel="noopener" aria-label="Instagram de MadBarzz"><i class="flaticon-instagram"></i></a></li>
              @endif
            </ul>
          </div>
        </div>
        <div class="col-lg-4 col-md-6 col-sm-12">
          <div class="footer-col">
            <h3>Contacto</h3>
            <ul>
              <li><i class="flaticon-maps"></i><p>
                @if ($contenidoHome->get('contacto_maps_url'))
                  <a href="{{ $contenidoHome->get('contacto_maps_url') }}" target="_blank" rel="noopener">{{ $contenidoHome->get('contacto_direccion') }}</a>
                @else
                  {{ $contenidoHome->get('contacto_direccion') }}
                @endif
              </p></li>
              <li><i class="flaticon-iphone"></i><p><a href="{{ $waLink }}" target="_blank" rel="noopener">{{ $contenidoHome->get('contacto_telefono') ?? $superadmin->persona->telefono ?? 'Número no disponible' }}</a></p></li>
              @php $correoContacto = $contenidoHome->get('contacto_email') ?? $superadmin->user->email ?? null; @endphp
              <li><i class="flaticon-mail"></i><p>@if ($correoContacto)<a href="mailto:{{ $correoContacto }}">{{ $correoContacto }}</a>@else Correo no disponible @endif</p></li>
            </ul>
          </div>
        </div>
        <div class="col-lg-4 col-md-6 col-sm-12">
          <div class="footer-col">
            <h3>¿Tenés dudas?</h3>
            <p>Escribinos directo y te respondemos por WhatsApp.</p>
            <a href="{{ $waLink }}" target="_blank" class="theme-btn">Escribir por WhatsApp</a>
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
          <p class="mb-legal-links">
            <a href="{{ route('legal.privacidad') }}">Política de Privacidad</a>
            <span aria-hidden="true"> · </span>
            <a href="{{ route('legal.terminos') }}">Términos y Condiciones</a>
          </p>
        </div>
      </div>
    </div>
  </div>
</footer>
