<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GrupoMuscular extends Model
{
    use SoftDeletes;

    protected $table = 'grupos_musculares';

    protected $fillable = [
        'nombre',
        'descripcion',
        'activo',
        'es_full_body',
    ];

    protected $casts = [
        'activo'       => 'boolean',
        'es_full_body' => 'boolean',
    ];

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true);
    }
}
