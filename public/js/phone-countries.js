/**
 * phoneInputInit — inicializa un campo de teléfono con selector de país.
 * Vanilla JS, sin dependencias. Compatible con Bootstrap 5.
 */

const PHONE_COUNTRIES = [
    // ── Latinoamérica (primeros, por ser el mercado principal) ──────────────
    { name: 'Bolivia',              flag: '🇧🇴', dial: '+591' },
    { name: 'Argentina',            flag: '🇦🇷', dial: '+54'  },
    { name: 'Brasil',               flag: '🇧🇷', dial: '+55'  },
    { name: 'Chile',                flag: '🇨🇱', dial: '+56'  },
    { name: 'Colombia',             flag: '🇨🇴', dial: '+57'  },
    { name: 'Ecuador',              flag: '🇪🇨', dial: '+593' },
    { name: 'Paraguay',             flag: '🇵🇾', dial: '+595' },
    { name: 'Perú',                 flag: '🇵🇪', dial: '+51'  },
    { name: 'Uruguay',              flag: '🇺🇾', dial: '+598' },
    { name: 'Venezuela',            flag: '🇻🇪', dial: '+58'  },
    { name: 'México',               flag: '🇲🇽', dial: '+52'  },
    { name: 'Cuba',                 flag: '🇨🇺', dial: '+53'  },
    { name: 'República Dominicana', flag: '🇩🇴', dial: '+1809' },
    { name: 'Guatemala',            flag: '🇬🇹', dial: '+502' },
    { name: 'Honduras',             flag: '🇭🇳', dial: '+504' },
    { name: 'El Salvador',          flag: '🇸🇻', dial: '+503' },
    { name: 'Nicaragua',            flag: '🇳🇮', dial: '+505' },
    { name: 'Costa Rica',           flag: '🇨🇷', dial: '+506' },
    { name: 'Panamá',               flag: '🇵🇦', dial: '+507' },
    // ── Resto del mundo (alfabético) ─────────────────────────────────────────
    { name: 'Afganistán',           flag: '🇦🇫', dial: '+93'  },
    { name: 'Albania',              flag: '🇦🇱', dial: '+355' },
    { name: 'Alemania',             flag: '🇩🇪', dial: '+49'  },
    { name: 'Angola',               flag: '🇦🇴', dial: '+244' },
    { name: 'Arabia Saudita',       flag: '🇸🇦', dial: '+966' },
    { name: 'Argelia',              flag: '🇩🇿', dial: '+213' },
    { name: 'Armenia',              flag: '🇦🇲', dial: '+374' },
    { name: 'Australia',            flag: '🇦🇺', dial: '+61'  },
    { name: 'Austria',              flag: '🇦🇹', dial: '+43'  },
    { name: 'Azerbaiyán',           flag: '🇦🇿', dial: '+994' },
    { name: 'Bélgica',              flag: '🇧🇪', dial: '+32'  },
    { name: 'Bangladesh',           flag: '🇧🇩', dial: '+880' },
    { name: 'Bielorrusia',          flag: '🇧🇾', dial: '+375' },
    { name: 'Camboya',              flag: '🇰🇭', dial: '+855' },
    { name: 'Camerún',              flag: '🇨🇲', dial: '+237' },
    { name: 'Canadá',               flag: '🇨🇦', dial: '+1'   },
    { name: 'China',                flag: '🇨🇳', dial: '+86'  },
    { name: 'Chipre',               flag: '🇨🇾', dial: '+357' },
    { name: 'Corea del Sur',        flag: '🇰🇷', dial: '+82'  },
    { name: 'Corea del Norte',      flag: '🇰🇵', dial: '+850' },
    { name: 'Croacia',              flag: '🇭🇷', dial: '+385' },
    { name: 'Dinamarca',            flag: '🇩🇰', dial: '+45'  },
    { name: 'Egipto',               flag: '🇪🇬', dial: '+20'  },
    { name: 'Emiratos Árabes',      flag: '🇦🇪', dial: '+971' },
    { name: 'Eslovaquia',           flag: '🇸🇰', dial: '+421' },
    { name: 'Eslovenia',            flag: '🇸🇮', dial: '+386' },
    { name: 'España',               flag: '🇪🇸', dial: '+34'  },
    { name: 'Estados Unidos',       flag: '🇺🇸', dial: '+1'   },
    { name: 'Estonia',              flag: '🇪🇪', dial: '+372' },
    { name: 'Etiopía',              flag: '🇪🇹', dial: '+251' },
    { name: 'Filipinas',            flag: '🇵🇭', dial: '+63'  },
    { name: 'Finlandia',            flag: '🇫🇮', dial: '+358' },
    { name: 'Francia',              flag: '🇫🇷', dial: '+33'  },
    { name: 'Georgia',              flag: '🇬🇪', dial: '+995' },
    { name: 'Ghana',                flag: '🇬🇭', dial: '+233' },
    { name: 'Grecia',               flag: '🇬🇷', dial: '+30'  },
    { name: 'Haití',                flag: '🇭🇹', dial: '+509' },
    { name: 'Hungría',              flag: '🇭🇺', dial: '+36'  },
    { name: 'India',                flag: '🇮🇳', dial: '+91'  },
    { name: 'Indonesia',            flag: '🇮🇩', dial: '+62'  },
    { name: 'Irak',                 flag: '🇮🇶', dial: '+964' },
    { name: 'Irán',                 flag: '🇮🇷', dial: '+98'  },
    { name: 'Irlanda',              flag: '🇮🇪', dial: '+353' },
    { name: 'Israel',               flag: '🇮🇱', dial: '+972' },
    { name: 'Italia',               flag: '🇮🇹', dial: '+39'  },
    { name: 'Jamaica',              flag: '🇯🇲', dial: '+1876'},
    { name: 'Japón',                flag: '🇯🇵', dial: '+81'  },
    { name: 'Jordania',             flag: '🇯🇴', dial: '+962' },
    { name: 'Kazajistán',           flag: '🇰🇿', dial: '+7'   },
    { name: 'Kenia',                flag: '🇰🇪', dial: '+254' },
    { name: 'Kuwait',               flag: '🇰🇼', dial: '+965' },
    { name: 'Letonia',              flag: '🇱🇻', dial: '+371' },
    { name: 'Líbano',               flag: '🇱🇧', dial: '+961' },
    { name: 'Libia',                flag: '🇱🇾', dial: '+218' },
    { name: 'Lituania',             flag: '🇱🇹', dial: '+370' },
    { name: 'Luxemburgo',           flag: '🇱🇺', dial: '+352' },
    { name: 'Malasia',              flag: '🇲🇾', dial: '+60'  },
    { name: 'Marruecos',            flag: '🇲🇦', dial: '+212' },
    { name: 'Nigeria',              flag: '🇳🇬', dial: '+234' },
    { name: 'Noruega',              flag: '🇳🇴', dial: '+47'  },
    { name: 'Nueva Zelanda',        flag: '🇳🇿', dial: '+64'  },
    { name: 'Países Bajos',         flag: '🇳🇱', dial: '+31'  },
    { name: 'Pakistán',             flag: '🇵🇰', dial: '+92'  },
    { name: 'Polonia',              flag: '🇵🇱', dial: '+48'  },
    { name: 'Portugal',             flag: '🇵🇹', dial: '+351' },
    { name: 'Puerto Rico',          flag: '🇵🇷', dial: '+1787'},
    { name: 'Qatar',                flag: '🇶🇦', dial: '+974' },
    { name: 'Reino Unido',          flag: '🇬🇧', dial: '+44'  },
    { name: 'Rumania',              flag: '🇷🇴', dial: '+40'  },
    { name: 'Rusia',                flag: '🇷🇺', dial: '+7'   },
    { name: 'Serbia',               flag: '🇷🇸', dial: '+381' },
    { name: 'Singapur',             flag: '🇸🇬', dial: '+65'  },
    { name: 'Siria',                flag: '🇸🇾', dial: '+963' },
    { name: 'Sudáfrica',            flag: '🇿🇦', dial: '+27'  },
    { name: 'Suecia',               flag: '🇸🇪', dial: '+46'  },
    { name: 'Suiza',                flag: '🇨🇭', dial: '+41'  },
    { name: 'Tailandia',            flag: '🇹🇭', dial: '+66'  },
    { name: 'Tanzania',             flag: '🇹🇿', dial: '+255' },
    { name: 'Túnez',                flag: '🇹🇳', dial: '+216' },
    { name: 'Turquía',              flag: '🇹🇷', dial: '+90'  },
    { name: 'Ucrania',              flag: '🇺🇦', dial: '+380' },
    { name: 'Vietnam',              flag: '🇻🇳', dial: '+84'  },
    { name: 'Yemen',                flag: '🇾🇪', dial: '+967' },
    { name: 'Zimbabue',             flag: '🇿🇼', dial: '+263' },
];

/**
 * Inicializa un campo de teléfono con selector de país.
 * @param {string} uid  - ID único del componente
 * @param {string} init - Valor inicial (puede ser '' o '+59171234567' o '71234567')
 */
function phoneInputInit(uid, init) {
    var sel    = document.getElementById(uid + '_code');
    var numEl  = document.getElementById(uid + '_number');
    var hidden = document.getElementById(uid + '_full');

    if (!sel || !numEl || !hidden) return;

    // Poblar el select
    PHONE_COUNTRIES.forEach(function(c) {
        var opt = document.createElement('option');
        opt.value = c.dial;
        opt.textContent = c.flag + ' ' + c.name + ' (' + c.dial + ')';
        sel.appendChild(opt);
    });

    // Parsear valor inicial
    var code = '+591', number = '';
    if (init) {
        if (init.charAt(0) === '+') {
            var sorted = PHONE_COUNTRIES.slice().sort(function(a, b) {
                return b.dial.length - a.dial.length;
            });
            for (var i = 0; i < sorted.length; i++) {
                if (init.indexOf(sorted[i].dial) === 0) {
                    code   = sorted[i].dial;
                    number = init.slice(sorted[i].dial.length);
                    break;
                }
            }
        } else {
            // Número heredado sin prefijo → asumir Bolivia
            code   = '+591';
            number = init;
        }
    }

    sel.value    = code;
    numEl.value  = number;
    hidden.value = number ? code + number.replace(/[\s\-]/g, '') : '';

    function update() {
        var n = numEl.value.replace(/[\s\-]/g, '');
        hidden.value = n ? sel.value + n : '';
    }

    sel.addEventListener('change', update);
    numEl.addEventListener('input', update);
}
