<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Persona extends Model
{
    use SoftDeletes, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'nombre_completo',
                'apellido_paterno',
                'apellido_materno',
                'ci',
                'telefono',
                'telegram_chat_id',
                'genero',
                'fecha_nacimiento',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $event) => "Persona {$event}");
    }

    protected $table = 'personas';

    protected $primaryKey = 'id_persona';  // Clave primaria correcta

    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'nombre_completo',
        'apellido_paterno',
        'apellido_materno',
        'ci',
        'telefono',
        'telegram_chat_id',
        'genero',
        'fecha_nacimiento',
    ];

    protected $dates = ['deleted_at'];

    /**
     * Separa el código de país del número para mostrarlo legible en reportes,
     * ej. "+591 71234567" en vez del string pegado que guarda NormalizarTelefono.
     * Usa los mismos códigos que ofrece <x-phone-input> (mantener sincronizados
     * si se agrega un país ahí); prueba primero los códigos más largos para no
     * confundir, p. ej., +1 (EE.UU./Canadá) con +1809 (Rep. Dominicana).
     */
    public function telefonoLegible(): ?string
    {
        if (!$this->telefono) {
            return null;
        }

        if (!str_starts_with($this->telefono, '+')) {
            return $this->telefono; // dato legado sin prefijo internacional
        }

        static $codigos = [
            '+1809', '+1787', '+1876',
            '+591', '+593', '+595', '+598', '+502', '+504', '+503', '+505', '+506', '+507',
            '+880', '+994', '+375', '+855', '+971', '+421', '+386', '+995', '+974',
            '+381', '+380', '+967', '+263', '+972',
            '+54', '+55', '+56', '+57', '+51', '+58', '+52', '+53',
            '+93', '+355', '+49', '+244', '+966', '+213', '+374', '+61', '+43', '+32',
            '+237', '+86', '+357', '+82', '+850', '+385', '+45', '+20', '+34', '+372',
            '+251', '+63', '+358', '+33', '+233', '+30', '+509', '+36', '+91', '+62',
            '+964', '+98', '+353', '+39', '+81', '+962', '+254', '+965', '+371', '+961',
            '+218', '+370', '+352', '+60', '+212', '+234', '+47', '+64', '+31', '+92',
            '+48', '+351', '+44', '+40', '+65', '+963', '+27', '+46', '+41', '+66',
            '+255', '+216', '+90', '+84', '+7', '+1',
        ];

        $codigo = collect($codigos)
            ->sortByDesc(fn ($c) => strlen($c))
            ->first(fn ($c) => str_starts_with($this->telefono, $c));

        if (!$codigo) {
            return $this->telefono;
        }

        return $codigo . ' ' . substr($this->telefono, strlen($codigo));
    }
}
