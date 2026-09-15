<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Administrador extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['id_persona', 'id_usuario', 'nivel_acceso', 'area_responsable', 'estado'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $event) => "Administrador {$event}");
    }

    protected $table = 'administradores';
    protected $primaryKey = 'id_administrador'; 
    public $timestamps = true;

    protected $fillable = [
        'id_persona',
        'id_usuario',
        'nivel_acceso',
        'area_responsable',
        'estado'
    ];
        protected $dates = ['deleted_at']; // 👈 opcional, Laravel lo infiere


    public function persona()
    {
        return $this->belongsTo(Persona::class, 'id_persona', 'id_persona');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id');
    }
}
