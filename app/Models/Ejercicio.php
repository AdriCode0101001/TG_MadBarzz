<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Catálogo base de ejercicios (Etapa previa a rutinas). Los importados del
 * dataset externo (origen='dataset') traen imagen/gif © Gym visual — ver
 * NOTICE.md del dataset: son contenido de arranque para desarrollo/demo,
 * pensado para que los entrenadores los reemplacen con contenido propio antes
 * de un lanzamiento público real.
 */
class Ejercicio extends Model
{
    use SoftDeletes;

    protected $table = 'ejercicios';

    protected $fillable = [
        'dataset_id',
        'nombre',
        'category',
        'target',
        'muscle_group',
        'secondary_muscles',
        'equipment',
        'instrucciones',
        'imagen',
        'gif',
        'atribucion',
        'id_grupo_muscular',
        'origen',
    ];

    protected $casts = [
        'secondary_muscles' => 'array',
    ];

    public function grupoMuscular(): BelongsTo
    {
        return $this->belongsTo(GrupoMuscular::class, 'id_grupo_muscular');
    }
}
