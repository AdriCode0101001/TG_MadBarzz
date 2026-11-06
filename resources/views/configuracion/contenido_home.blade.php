@extends('layouts.private')

@section('contenido')
@php
    // Helper chico para repetir el patrón label + input + error de cada campo.
    $v = fn (string $clave) => old($clave, $valores[$clave] ?? '');
@endphp
<div class="col-12">

    <x-page-header title="Contenido de la Home" subtitle="Publicación de Instagram, redes, contacto, datos legales y WhatsApp del sitio público" icon="fa-solid fa-pen-to-square" />

    @if(session('success'))
        <div class="alert alert-success border-0 rounded-3 mb-4">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger mb-4">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('configuracion.contenido_home.update') }}" method="POST">
        @csrf

        {{-- ── Publicación de Instagram ─────────────────────────────────── --}}
        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0"><i class="fa-brands fa-instagram me-2"></i>Publicación de Instagram</h5></div>
            <div class="card-body">
                <p class="text-muted mb-3" style="font-size:.9rem;">
                    Es la publicación que se muestra en el cuadro naranja junto a la guía para principiantes.
                    Se puede <strong>cambiar</strong> pegando el enlace de otra publicación pública, pero <strong>no se puede borrar</strong>:
                    siempre tiene que haber una.
                </p>
                <label for="instagram_post_url" class="form-label">Enlace de la publicación</label>
                <input type="url" name="instagram_post_url" id="instagram_post_url" required maxlength="300"
                       class="form-control @error('instagram_post_url') is-invalid @enderror"
                       placeholder="https://www.instagram.com/p/XXXXXXXXXXX/" value="{{ $v('instagram_post_url') }}">
                @error('instagram_post_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <small class="text-muted">Acepta publicaciones (/p/), reels (/reel/) y videos (/tv/).</small>
            </div>
        </div>

        {{-- ── Footer · Información y redes ─────────────────────────────── --}}
        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0"><i class="fa-solid fa-circle-info me-2"></i>Información y redes sociales</h5></div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="info_texto" class="form-label">Texto de "Información"</label>
                    <textarea name="info_texto" id="info_texto" rows="3" maxlength="500" required
                              class="form-control @error('info_texto') is-invalid @enderror">{{ $v('info_texto') }}</textarea>
                    @error('info_texto')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="facebook_url" class="form-label"><i class="fa-brands fa-facebook-f me-1"></i>Enlace de Facebook</label>
                        <input type="url" name="facebook_url" id="facebook_url" maxlength="300"
                               class="form-control @error('facebook_url') is-invalid @enderror"
                               placeholder="https://www.facebook.com/..." value="{{ $v('facebook_url') }}">
                        @error('facebook_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <small class="text-muted">Déjalo vacío para ocultar el ícono de Facebook.</small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="instagram_url" class="form-label"><i class="fa-brands fa-instagram me-1"></i>Enlace del perfil de Instagram</label>
                        <input type="url" name="instagram_url" id="instagram_url" maxlength="300"
                               class="form-control @error('instagram_url') is-invalid @enderror"
                               placeholder="https://www.instagram.com/..." value="{{ $v('instagram_url') }}">
                        @error('instagram_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <small class="text-muted">Déjalo vacío para ocultar el ícono de Instagram.</small>
                    </div>
                </div>
                <small class="text-muted">El ícono de WhatsApp usa el número de la sección de abajo.</small>
            </div>
        </div>

        {{-- ── Footer · Contacto ────────────────────────────────────────── --}}
        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0"><i class="fa-solid fa-address-book me-2"></i>Contacto</h5></div>
            <div class="card-body">
                <div class="row align-items-end mb-3">
                    <div class="col-md-8">
                        <label for="rellenar_con" class="form-label">Rellenar teléfono y correo con los datos de…</label>
                        <select id="rellenar_con" class="">
                            <option value="">— Elegí una persona del personal —</option>
                            @foreach ($personal as $i => $p)
                                <option value="{{ $i }}" data-telefono="{{ $p['telefono'] }}" data-email="{{ $p['email'] }}">
                                    {{ $p['rol'] }} · {{ $p['nombre'] }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Solo copia el teléfono y el correo a los campos de abajo; la dirección va aparte. Se aplica al guardar.</small>
                    </div>
                    <div class="col-md-4 mt-2 mt-md-0">
                        <button type="button" id="btnRellenar" class="btn btn-outline-primary w-100">
                            <i class="fa-solid fa-wand-magic-sparkles me-1"></i> Rellenar
                        </button>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="contacto_direccion" class="form-label">Dirección</label>
                    <input type="text" name="contacto_direccion" id="contacto_direccion" maxlength="255" required
                           class="form-control @error('contacto_direccion') is-invalid @enderror" value="{{ $v('contacto_direccion') }}">
                    @error('contacto_direccion')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label for="contacto_maps_url" class="form-label">Enlace de Google Maps (opcional)</label>
                    <input type="url" name="contacto_maps_url" id="contacto_maps_url" maxlength="500"
                           class="form-control @error('contacto_maps_url') is-invalid @enderror"
                           placeholder="https://maps.app.goo.gl/..." value="{{ $v('contacto_maps_url') }}">
                    @error('contacto_maps_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <small class="text-muted">Si lo pones, la dirección del footer se vuelve un enlace que abre Google Maps.</small>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="contacto_telefono" class="form-label">Teléfono que se muestra</label>
                        <input type="text" name="contacto_telefono" id="contacto_telefono" maxlength="30"
                               class="form-control @error('contacto_telefono') is-invalid @enderror" value="{{ $v('contacto_telefono') }}">
                        @error('contacto_telefono')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="contacto_email" class="form-label">Correo que se muestra</label>
                        <input type="email" name="contacto_email" id="contacto_email" maxlength="255"
                               class="form-control @error('contacto_email') is-invalid @enderror" value="{{ $v('contacto_email') }}">
                        @error('contacto_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <small class="text-muted">Si dejas teléfono o correo vacíos, el footer muestra los del administrador principal, como antes.</small>
            </div>
        </div>

        {{-- ── Datos legales ────────────────────────────────────────────── --}}
        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0"><i class="fa-solid fa-scale-balanced me-2"></i>Datos legales (Términos y Condiciones y Política de Privacidad)</h5></div>
            <div class="card-body">
                <p class="text-muted mb-3" style="font-size:.9rem;">
                    Estos datos reemplazan automáticamente los avisos <strong>[COMPLETAR]</strong> que hoy se ven en las páginas
                    <a href="{{ route('legal.terminos') }}" target="_blank" rel="noopener">Términos y Condiciones</a> y
                    <a href="{{ route('legal.privacidad') }}" target="_blank" rel="noopener">Política de Privacidad</a>.
                    Si dejas un campo vacío, ese aviso sigue apareciendo. Las demás cláusulas pendientes (mora, reglamento interno,
                    jurisdicción, etc.) son decisiones legales que hay que definir con un abogado.
                </p>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="legal_razon_social" class="form-label">Razón social</label>
                        <input type="text" name="legal_razon_social" id="legal_razon_social" maxlength="255"
                               class="form-control @error('legal_razon_social') is-invalid @enderror" value="{{ $v('legal_razon_social') }}">
                        @error('legal_razon_social')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="legal_nit" class="form-label">NIT</label>
                        <input type="text" name="legal_nit" id="legal_nit" maxlength="30"
                               class="form-control @error('legal_nit') is-invalid @enderror" value="{{ $v('legal_nit') }}">
                        @error('legal_nit')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="legal_correo" class="form-label">Correo de contacto formal</label>
                        <input type="email" name="legal_correo" id="legal_correo" maxlength="255"
                               class="form-control @error('legal_correo') is-invalid @enderror" value="{{ $v('legal_correo') }}">
                        @error('legal_correo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <small class="text-muted">Para temas de privacidad y consultas legales. Si lo dejas vacío se usa el correo de la sección Contacto, si lo pusiste.</small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="legal_fecha_publicacion" class="form-label">Fecha de publicación ("Última actualización")</label>
                        <input type="date" name="legal_fecha_publicacion" id="legal_fecha_publicacion" max="{{ now()->toDateString() }}"
                               class="form-control @error('legal_fecha_publicacion') is-invalid @enderror" value="{{ $v('legal_fecha_publicacion') }}">
                        @error('legal_fecha_publicacion')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- ── WhatsApp ─────────────────────────────────────────────────── --}}
        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0"><i class="fa-brands fa-whatsapp me-2"></i>WhatsApp (¿Tenés dudas? y todos los botones de la home)</h5></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="whatsapp_numero" class="form-label">Número al que se escribe</label>
                        <input type="text" name="whatsapp_numero" id="whatsapp_numero" maxlength="30" required
                               class="form-control @error('whatsapp_numero') is-invalid @enderror"
                               placeholder="59176372337" value="{{ $v('whatsapp_numero') }}">
                        @error('whatsapp_numero')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <small class="text-muted">Con código de país (Bolivia: 591). Si pones solo 8 dígitos, se agrega el 591.</small>
                    </div>
                    <div class="col-md-8 mb-3">
                        <label for="whatsapp_mensaje" class="form-label">Mensaje predeterminado</label>
                        <textarea name="whatsapp_mensaje" id="whatsapp_mensaje" rows="3" maxlength="300" required
                                  class="form-control @error('whatsapp_mensaje') is-invalid @enderror">{{ $v('whatsapp_mensaje') }}</textarea>
                        @error('whatsapp_mensaje')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <small class="text-muted">
                            Es el texto que ya viene escrito cuando la persona abre el chat. La persona puede cambiarlo antes de enviar
                            (por ejemplo, poner su nombre donde dice [tu nombre]).
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">
            <i class="fa-solid fa-save me-1"></i> Guardar cambios
        </button>
        {{-- Salir sin guardar: es un enlace (no envía el formulario), así que no cambia nada. --}}
        <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary ms-2">
            <i class="fa-solid fa-arrow-right-from-bracket me-1"></i> Salir sin guardar
        </a>
    </form>
</div>

@push('scripts')
<script>
    document.getElementById('btnRellenar').addEventListener('click', function () {
        var opcion = document.getElementById('rellenar_con').selectedOptions[0];
        if (!opcion || !opcion.value) return;
        document.getElementById('contacto_telefono').value = opcion.dataset.telefono || '';
        document.getElementById('contacto_email').value = opcion.dataset.email || '';
    });
</script>
@endpush
@endsection
