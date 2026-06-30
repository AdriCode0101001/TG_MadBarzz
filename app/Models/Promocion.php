<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Promocion extends Model
{
   use HasFactory, SoftDeletes;

    protected $table = 'promociones';
    protected $primaryKey = 'id_promocion';

    protected $fillable = [
        'id_administrador',
        'titulo',
        'descripcion',
        'fecha_inicio',
        'fecha_fin',
        'estado',
        'imagen',
    ];

    public function administrador()
    {
        return $this->belongsTo(Administrador::class, 'id_administrador');
    }}
