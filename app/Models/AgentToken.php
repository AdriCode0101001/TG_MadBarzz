<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentToken extends Model
{
    protected $table = 'agent_tokens';

    protected $fillable = [
        'id_sede',
        'nombre',
        'token',
        'ultimo_uso',
        'activo',
    ];

    protected $casts = [
        'activo'     => 'boolean',
        'ultimo_uso' => 'datetime',
    ];

    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sede::class, 'id_sede', 'id_sede');
    }
}
