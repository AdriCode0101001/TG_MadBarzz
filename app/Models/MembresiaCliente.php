<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSede;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class MembresiaCliente extends Model
{
    use SoftDeletes, BelongsToSede, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $event) => "MembresiaCliente {$event}");
    }

    protected $table = 'membresia_cliente';

    protected $fillable = [
        'id_cliente',
        'id_membresia',
        'fecha_inicio',
        'fecha_fin',
        'nombre_descuento',
        'descuento',
        'id_descuento',
        'precio_final',
        'id_sede',
        'estado',
    ];

    /**
     * Sin su pago vinculado, NO da acceso (asistencia/rutinas). Las nuevas
     * asignaciones nacen así; el pago registrado en el mismo paso la activa.
     */
    public const ESTADO_PENDIENTE = 'pendiente_pago';

    /** Ya tiene su pago: da acceso normal (asistencia, rutinas, portal). */
    public const ESTADO_ACTIVA = 'activa';

    // Relaciones

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'id_cliente', 'id_cliente');
    }

    public function membresia()
    {
        return $this->belongsTo(Membresia::class, 'id_membresia', 'id_membresia');
    }

    public function descuentoAplicado()
    {
        return $this->belongsTo(Descuento::class, 'id_descuento');
    }

    public function pagos()
    {
        return $this->hasMany(Pago::class, 'id_membresia_cliente');
    }
}
