<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Marca de lectura: una fila = ese cliente ya abrió ese aviso (created_at =
 * cuándo). Se crea con firstOrCreate() al leer, nunca al enviar el aviso.
 */
class AvisoCliente extends Model
{
    protected $table = 'aviso_cliente';

    protected $fillable = [
        'id_aviso',
        'id_cliente',
    ];

    public function aviso()
    {
        return $this->belongsTo(Aviso::class, 'id_aviso');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'id_cliente');
    }
}
