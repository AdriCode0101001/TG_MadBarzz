<?php

return [
    /*
     * Días de anticipación para avisarle al Administrador que el QR de pago
     * está por vencer (además de avisarle cuando ya venció).
     */
    'dias_aviso_vencimiento' => env('QR_PAGO_DIAS_AVISO', 7),
];
