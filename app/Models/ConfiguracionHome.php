<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Un texto/enlace editable de la home pública (publicación de Instagram,
 * redes, contacto, WhatsApp), identificado por una clave fija (ver
 * ContenidoHomeService::DEFAULTS). Sin fila o con valor vacío, la home usa el
 * valor por defecto de siempre.
 */
class ConfiguracionHome extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['clave', 'valor', 'id_usuario'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $event) => "ConfiguracionHome {$event}");
    }

    protected $table = 'configuracion_home';

    protected $fillable = ['clave', 'valor', 'id_usuario'];
}
