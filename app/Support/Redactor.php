<?php

namespace App\Support;

/**
 * Evita que datos personales (teléfono, chat_id, email) terminen en texto
 * plano en los logs de la aplicación — ver hallazgo de la auditoría de
 * privacidad sobre los canales de notificación.
 */
class Redactor
{
    /**
     * Enmascara un valor conservando solo los últimos caracteres visibles,
     * para poder reconocerlo en un log de diagnóstico sin dejarlo completo.
     */
    public static function enmascarar(string $valor, int $visibles = 3): string
    {
        $limpio = trim($valor);
        if ($limpio === '') {
            return '(vacío)';
        }

        $longitud = mb_strlen($limpio);
        if ($longitud <= $visibles) {
            return str_repeat('*', $longitud);
        }

        return str_repeat('*', $longitud - $visibles) . mb_substr($limpio, -$visibles);
    }

    /**
     * Identificador seguro para logs: preferí el id del cliente (no es un
     * dato personal) y, si no está disponible, enmascará el dato sensible en
     * vez de dejarlo en texto plano.
     */
    public static function identificarParaLog(?string $idCliente, string $valorSensible): string
    {
        return $idCliente !== null && $idCliente !== ''
            ? "cliente #{$idCliente}"
            : self::enmascarar($valorSensible);
    }
}
