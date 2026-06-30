<?php

namespace App\Contracts;

interface CanalNotificacion
{
    /**
     * Envía un mensaje al destino indicado.
     *
     * @param  string $destino  Email, chat_id de Telegram o número de teléfono según el canal.
     * @param  string $mensaje  Texto plano del mensaje.
     * @return bool             true si el envío fue exitoso.
     */
    public function enviar(string $destino, string $mensaje): bool;
}
