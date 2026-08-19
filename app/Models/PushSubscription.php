<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Una suscripción de Web Push de un cliente a UN navegador/dispositivo
 * puntual (celular, tablet, tras reinstalar el navegador = fila nueva). Un
 * cliente puede tener varias. El endpoint es lo que identifica al
 * navegador ante el servicio push (FCM, Mozilla autopush, etc.) — p256dh y
 * auth son las claves de cifrado que exige el estándar Web Push.
 */
class PushSubscription extends Model
{
    protected $table = 'push_subscriptions';

    protected $fillable = [
        'id_cliente',
        'endpoint',
        'p256dh',
        'auth',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'id_cliente');
    }
}
