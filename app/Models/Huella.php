<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Huella extends Model
{
    use SoftDeletes;

    protected $table = 'huellas';

    protected $fillable = [
        'id_cliente',
        'dedo',
        'template',
        'version_formato',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'id_cliente', 'id_cliente');
    }
}
