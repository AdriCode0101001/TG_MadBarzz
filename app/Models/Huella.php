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

    /**
     * `template` (dato biométrico) se cifra con APP_KEY al guardar y se
     * descifra solo al leer el atributo — transparente para
     * BiometriaController::sincronizar() (el agente C# sigue recibiendo el
     * Base64 en claro por la API, como siempre) y para cualquier otro código
     * que acceda a $huella->template. Los accesos RAW a la tabla (DB::table,
     * assertDatabaseHas, dumps de backup) ven el ciphertext, no el template.
     *
     * ⚠️ Una vez en producción con huellas cifradas, APP_KEY no se puede
     * rotar/regenerar sin perder el acceso a todas ellas — ver
     * DESPLIEGUE.md § "Secretos críticos que el dueño debe custodiar" y
     * RESTAURACION.md § 5b.
     */
    protected $casts = [
        'template' => 'encrypted',
    ];

    protected static function booted(): void
    {
        // template_hash: SHA-256 determinístico del template EN CLARO,
        // recalculado en cada guardado (no solo si "cambió" — más simple y
        // barato que confiar en isDirty() con un cast cifrado). Sirve para
        // que BiometriaController::enrolar() pueda seguir detectando
        // "esta misma huella ya pertenece a otro cliente" con una búsqueda
        // indexada, ya que comparar la columna `template` cifrada
        // directamente (IV aleatorio en cada cifrado) nunca encuentra
        // coincidencias aunque el texto plano sea idéntico.
        static::saving(function (Huella $huella) {
            $huella->template_hash = hash('sha256', $huella->template);
        });
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'id_cliente', 'id_cliente');
    }
}
