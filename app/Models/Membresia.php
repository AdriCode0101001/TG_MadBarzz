<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Membresia extends Model
{
     use SoftDeletes, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['tipo_membresia', 'precio'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $event) => "Membresia {$event}");
    }

    protected $table = 'membresias';
    protected $primaryKey = 'id_membresia';

    protected $fillable = [
        'tipo_membresia',
        'precio',
    ];

public function clientes()
{
    return $this->belongsToMany(Cliente::class, 'membresia_cliente', 'id_membresia', 'id_cliente')
                ->wherePivot('fecha_fin', '>=', now());
}
}
