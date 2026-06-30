<?php

return [

    /*
     | Días de anticipación con que se avisa antes del vencimiento.
     | Configurable con NOTIF_DIAS_AVISO en el .env (default: 3 días).
     | También puede sobreescribirse en cada ejecución: --dias=5
     */
    'dias_aviso' => (int) env('NOTIF_DIAS_AVISO', 3),

    /*
     | Canales de envío activos, separados por coma en NOTIF_CANALES.
     | Valores válidos: email, telegram, whatsapp
     | Ejemplos:
     |   NOTIF_CANALES=email
     |   NOTIF_CANALES=email,telegram
     |   NOTIF_CANALES=email,telegram,whatsapp
     */
    'canales' => array_values(array_filter(
        explode(',', env('NOTIF_CANALES', 'email'))
    )),

];
