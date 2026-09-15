<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Promocion extends Model
{
   use HasFactory, SoftDeletes, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['id_administrador', 'titulo', 'descripcion', 'fecha_inicio', 'fecha_fin', 'estado', 'imagen'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $event) => "Promocion {$event}");
    }

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
