<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSede;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Entrenador extends Model
{
    use HasFactory, SoftDeletes, BelongsToSede;

    protected $table = 'entrenadores';
    protected $primaryKey = 'id_entrenador';

    protected $fillable = [
        'id_persona',
        'id_usuario',
        'especialidad',
        'experiencia',
        'disponibilidad',
        'estado',
        'id_sede',
    ];

    protected $casts = [
        'disponibilidad' => 'array',
        'especialidad'   => 'array',
    ];

        protected $dates = ['deleted_at']; // 👈 opcional


    public function persona()
    {
        return $this->belongsTo(Persona::class, 'id_persona');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }
}
