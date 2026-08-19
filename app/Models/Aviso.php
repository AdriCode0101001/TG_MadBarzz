<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Aviso enviado por Administrador/Recepcionista a TODOS los clientes (portal
 * del cliente, campanita — Etapa 2). Enviarlo a "todos" es crear UNA sola
 * fila acá: no hay fan-out hacia aviso_cliente al crearlo. La lectura por
 * cliente se registra en AvisoCliente solo cuando ese cliente lo abre.
 */
class Aviso extends Model
{
    use SoftDeletes;

    protected $table = 'avisos';

    protected $fillable = [
        'titulo',
        'texto',
        'imagen',
        'id_usuario',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }

    public function lecturas()
    {
        return $this->hasMany(AvisoCliente::class, 'id_aviso');
    }

    /** true si ESE cliente ya lo leyó (existe su fila en aviso_cliente). */
    public function loLeyoCliente(int $idCliente): bool
    {
        return $this->lecturas()->where('id_cliente', $idCliente)->exists();
    }

    public function cantidadLecturas(): int
    {
        return $this->lecturas()->count();
    }
}
