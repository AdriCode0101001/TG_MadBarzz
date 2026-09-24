{{-- Footer del sitio público GymOn — extraído de welcome-gymon.blade.php para
     poder reusarlo en otras páginas públicas (ej. legal/documento.blade.php)
     sin duplicar el contacto/redes reales. $waLink es opcional: si quien
     incluye este partial no lo pasa (páginas que no vienen de la ruta "/"),
     se arma acá con el mismo número de siempre. --}}
@php
    $waLink = $waLink ?? 'https://wa.me/59176372337?text=Hola,%20quiero%20unirme%20a%20la%20Calistenia%20MadBarzz.';
    $imagenesHomeFooter = $imagenesHome ?? new \App\Services\ImagenHomeService();
@endphp

<style>
  .mb-legal-links{margin-top:.4rem;font-size:.85rem;opacity:.8;}
  .mb-legal-links a{color:inherit;text-decoration:underline;}
  .mb-legal-links a:hover{color:var(--theme-color,#FD6A01);}
</style>

<footer id="contacto" class="footer-style-one" style="background-image: url({{ $imagenesHomeFooter->url('home_footer_fondo', asset('images/placeholder-home.svg')) }}); background-position: {{ $imagenesHomeFooter->posicionCssPara('home_footer_fondo') }};">
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
              <li><a href="https://www.facebook.com/MadBarzz" target="_blank" aria-label="Facebook de MadBarzz"><i class="fa-brands fa-facebook-f"></i></a></li>
              <li><a href="{{ $waLink }}" target="_blank" aria-label="Escribinos por WhatsApp"><i class="fa-brands fa-whatsapp"></i></a></li>
              <li><a href="https://www.instagram.com/calistenia_madbarzz?igsh=aW91eXl6ZnVtMGJ2" target="_blank" aria-label="Instagram de MadBarzz"><i class="flaticon-instagram"></i></a></li>
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
