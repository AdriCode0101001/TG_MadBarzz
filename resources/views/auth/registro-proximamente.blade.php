{{--
    BLOQUEO TEMPORAL (Etapa 0 del auto-registro): reemplaza la vista real de
    registro mientras se reescribe el flujo completo (Persona + User +
    Cliente) en las Etapas 1-2. Ver routes/auth.php para el porqué.
    Fácil de revertir: cuando el flujo nuevo esté listo, esta vista deja de
    usarse (las rutas vuelven a apuntar a RegisteredUserController) y se
    puede borrar este archivo.
--}}
@extends('layouts.auth')
@section('contenido')

<div class="row justify-content-center align-items-center" style="margin-top: 0; padding-top: 0;">
  <div class="col-lg-6">
    <div class="box register" style="margin-top: 0; padding-top: 0;">
      <h3>Registro próximamente</h3>
      <p>Estamos preparando el registro en línea para nuevos socios. Todavía no está disponible, pero muy pronto vas a poder inscribirte desde acá.</p>
      <p>Mientras tanto, escribinos por WhatsApp o pasá por el gimnasio para inscribirte.</p>
      <a href="https://wa.me/59176372337?text=Hola,%20quiero%20unirme%20a%20la%20Calistenia%20MadBarzz." target="_blank" class="theme-btn">
        <i class="fa-brands fa-whatsapp me-1"></i> Escribir por WhatsApp
      </a>
      <p style="margin-top: 20px;">
        ¿Ya tenés cuenta? <a href="{{ route('login') }}">Iniciar sesión</a>
      </p>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="box" style="margin-top: 0; padding-top: 0;">
      <img src="{{ asset('assets/images/entrenaconnosotros.jpg') }}" alt="Imagen de entrenamiento"
           style="width: 100%; height: 100%; object-fit: cover; display: block; border-radius: 10px;">
    </div>
  </div>
</div>

@endsection
