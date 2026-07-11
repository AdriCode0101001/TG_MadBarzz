@props(['value' => '', 'required' => false])

@php
    $hasError  = $errors->has('telefono');
    $initValue = old('telefono', $value);

    $countries = [
        ['Bolivia','+591'],['Argentina','+54'],['Brasil','+55'],['Chile','+56'],
        ['Colombia','+57'],['Ecuador','+593'],['Paraguay','+595'],['Perú','+51'],
        ['Uruguay','+598'],['Venezuela','+58'],['México','+52'],['Cuba','+53'],
        ['Guatemala','+502'],['Honduras','+504'],['El Salvador','+503'],
        ['Nicaragua','+505'],['Costa Rica','+506'],['Panamá','+507'],
        ['Rep. Dominicana','+1809'],['Puerto Rico','+1787'],
        ['Afganistán','+93'],['Albania','+355'],['Alemania','+49'],
        ['Angola','+244'],['Arabia Saudita','+966'],['Argelia','+213'],
        ['Armenia','+374'],['Australia','+61'],['Austria','+43'],
        ['Azerbaiyán','+994'],['Bélgica','+32'],['Bangladesh','+880'],
        ['Bielorrusia','+375'],['Camboya','+855'],['Camerún','+237'],
        ['Canadá','+1'],['China','+86'],['Chipre','+357'],
        ['Corea del Sur','+82'],['Corea del Norte','+850'],['Croacia','+385'],
        ['Dinamarca','+45'],['Egipto','+20'],['Emiratos Árabes','+971'],
        ['Eslovaquia','+421'],['Eslovenia','+386'],['España','+34'],
        ['Estados Unidos','+1'],['Estonia','+372'],['Etiopía','+251'],
        ['Filipinas','+63'],['Finlandia','+358'],['Francia','+33'],
        ['Georgia','+995'],['Ghana','+233'],['Grecia','+30'],
        ['Haití','+509'],['Hungría','+36'],['India','+91'],
        ['Indonesia','+62'],['Irak','+964'],['Irán','+98'],
        ['Irlanda','+353'],['Israel','+972'],['Italia','+39'],
        ['Jamaica','+1876'],['Japón','+81'],['Jordania','+962'],
        ['Kazajistán','+7'],['Kenia','+254'],['Kuwait','+965'],
        ['Letonia','+371'],['Líbano','+961'],['Libia','+218'],
        ['Lituania','+370'],['Luxemburgo','+352'],['Malasia','+60'],
        ['Marruecos','+212'],['Nigeria','+234'],['Noruega','+47'],
        ['Nueva Zelanda','+64'],['Países Bajos','+31'],['Pakistán','+92'],
        ['Polonia','+48'],['Portugal','+351'],['Qatar','+974'],
        ['Reino Unido','+44'],['Rumania','+40'],['Rusia','+7'],
        ['Serbia','+381'],['Singapur','+65'],['Siria','+963'],
        ['Sudáfrica','+27'],['Suecia','+46'],['Suiza','+41'],
        ['Tailandia','+66'],['Tanzania','+255'],['Túnez','+216'],
        ['Turquía','+90'],['Ucrania','+380'],['Vietnam','+84'],
        ['Yemen','+967'],['Zimbabue','+263'],
    ];

    // Parsear valor inicial en PHP para pre-seleccionar el código y separar el número
    $selectedCode   = '+591';
    $selectedNumber = '';
    if ($initValue) {
        if (str_starts_with($initValue, '+')) {
            $sorted = collect($countries)->sortByDesc(fn($c) => strlen($c[1]))->values();
            foreach ($sorted as [$name, $dial]) {
                if (str_starts_with($initValue, $dial)) {
                    $selectedCode   = $dial;
                    $selectedNumber = substr($initValue, strlen($dial));
                    break;
                }
            }
        } else {
            $selectedNumber = $initValue; // número legado sin prefijo
        }
    }

    // Si el formulario se re-renderiza por un error de validación, los campos
    // con name propio conservan lo que el operador escribió.
    $selectedCode   = old('telefono_codigo', $selectedCode);
    $selectedNumber = old('telefono_numero', $selectedNumber);

    $uid = uniqid('ph');
@endphp

{{-- bootstrap-select envuelve el <select> en un div; este estilo lo mantiene en el flex --}}
<style>
#{{ $uid }}_wrap > .bootstrap-select { flex:0 0 auto !important; width:auto !important; max-width:195px !important; }
</style>

<div id="{{ $uid }}_wrap" style="display:flex;gap:8px;width:100%;align-items:center;">

    {{--
        Las <option> vienen del servidor → existen antes de que cualquier JS se ejecute.
        data-container="body"     → el desplegable se adjunta al <body>, no queda
                                    cortado por overflow:hidden de la tarjeta.
        data-live-search="true"   → cuadro de búsqueda dentro del dropdown.
    --}}
    {{--
        Los DOS campos viajan al servidor con name propio: el middleware
        NormalizarTelefono los combina en `telefono` (codigo + numero).
        Ya NO hay campo oculto dependiente de JavaScript: aunque el JS de la
        página falle por completo, el teléfono llega bien al servidor.
    --}}
    <select id="{{ $uid }}_code"
            name="telefono_codigo"
            data-container="body"
            data-live-search="true"
            data-live-search-placeholder="Buscar país..."
            style="flex:0 0 auto;width:auto;max-width:195px;{{ $hasError ? 'border-color:#dc3545;' : '' }}">
        @foreach($countries as [$name, $dial])
            <option value="{{ $dial }}" @selected($dial === $selectedCode)>
                {{ $name }} ({{ $dial }})
            </option>
        @endforeach
    </select>

    <input  type="tel"
            name="telefono_numero"
            id="{{ $uid }}_number"
            class="form-control{{ $hasError ? ' is-invalid' : '' }}"
            style="flex:1 1 0%;min-width:0;"
            placeholder="Ej. 71234567"
            value="{{ $selectedNumber }}"
            {{ $required ? 'required' : '' }}
            autocomplete="tel-local">

</div>

@error('telefono')
    <div class="invalid-feedback d-block">{{ $message }}</div>
@enderror
