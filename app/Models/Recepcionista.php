<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSede;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Recepcionista extends Model
{
    use HasFactory, SoftDeletes, BelongsToSede, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['id_persona', 'id_usuario', 'turno', 'punto_atencion', 'estado', 'id_sede'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $event) => "Recepcionista {$event}");
    }

    protected $primaryKey = 'id_recepcionista';
    protected $fillable = [
        'id_persona',
        'id_usuario',
        'turno',
        'punto_atencion',
        'estado',
        'id_sede',
    ];
 protected $dates = ['deleted_at']; // opcional, Laravel lo infiere


    public function persona() {
        return $this->belongsTo(Persona::class, 'id_persona');
    }

    public function user() {
        return $this->belongsTo(User::class, 'id_usuario');
    }
}
