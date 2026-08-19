<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Una imagen de contenido de la home pública, subida por el Administrador,
 * identificada por una clave fija (ver ImagenHomeService::CLAVES). Si no
 * existe fila para una clave (o la fila no tiene "ruta"), la home usa el
 * placeholder de siempre — nunca se rompe por falta de imagen.
 */
class ConfiguracionImagen extends Model
{
    protected $table = 'configuracion_imagenes';

    protected $fillable = [
        'clave',
        'ruta',
        'posicion',
        'id_usuario',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }
}
