<?php

namespace App\Contracts;

interface CanalNotificacion
{
    /**
     * Envía un mensaje al destino indicado.
     *
     * @param  string      $destino    Email, chat_id de Telegram o número de teléfono según el canal.
     * @param  string      $mensaje    Texto plano del mensaje.
     * @param  string|null $idCliente  Id del cliente, solo para identificarlo en logs de
     *                                 diagnóstico sin loguear el dato personal de $destino
     *                                 en texto plano (ver App\Support\Redactor).
     * @return bool                    true si el envío fue exitoso.
     */
    public function enviar(string $destino, string $mensaje, ?string $idCliente = null): bool;
}
