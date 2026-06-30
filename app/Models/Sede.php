<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sede extends Model
{
    use HasFactory, SoftDeletes;

    protected $table      = 'sedes';
    protected $primaryKey = 'id_sede';

    protected $fillable = [
        'nombre',
        'direccion',
        'telefono',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function agentTokens()
    {
        return $this->hasMany(AgentToken::class, 'id_sede', 'id_sede');
    }

    public function clientes()
    {
        return $this->belongsToMany(Cliente::class, 'cliente_sede', 'id_sede', 'id_cliente');
    }
}
