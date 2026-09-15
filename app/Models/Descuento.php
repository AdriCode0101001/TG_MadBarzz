<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Descuento extends Model
{
    use SoftDeletes, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['nombre', 'porcentaje', 'activo'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $event) => "Descuento {$event}");
    }

    protected $table = 'descuentos';

    protected $fillable = [
        'nombre',
        'porcentaje',
        'activo',
    ];

    protected $casts = [
        'porcentaje' => 'decimal:2',
        'activo'     => 'boolean',
    ];

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true);
    }
}
