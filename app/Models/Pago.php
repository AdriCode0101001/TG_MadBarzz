<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSede;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Pago extends Model
{
    use SoftDeletes, BelongsToSede, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $event) => "Pago {$event}");
    }

    protected $table = 'pagos';
    protected $primaryKey = 'id_pago';

    protected $fillable = [
        'id_cliente',
        'id_membresia',
        'id_membresia_cliente',
        'monto',
        'fecha_pago',
        'metodo_pago',
        'observaciones',
        'imagen_qr',
        'id_sede',
    ];
protected $casts = [
    'fecha_pago' => 'date',
];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'id_cliente');
    }

    public function membresia()
    {
        return $this->belongsTo(Membresia::class, 'id_membresia');
    }

    /**
     * La asignación (membresia_cliente) concreta que este pago activó.
     * Si se elimina el pago, esa asignación vuelve a pendiente_pago.
     */
    public function membresiaCliente()
    {
        return $this->belongsTo(MembresiaCliente::class, 'id_membresia_cliente');
    }
}
