<?php

return [
    /*
     * Minutos que deben transcurrir entre dos marcaciones del mismo cliente
     * en la misma sede para que la segunda se registre (evita duplicados).
     */
    'ventana_duplicado_minutos' => env('BIOMETRIA_VENTANA_DUPLICADO', 5),
];
