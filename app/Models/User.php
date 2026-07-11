<?php

namespace App\Models;

use App\Models\Sede;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Rol;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'rol_id', 'id_sede'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $event) => "Usuario {$event}");
    }

    protected $fillable = [
        'name',
        'email',
        'password',
        'rol_id',
        'foto',
        'id_sede',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class, 'rol_id');
    }

    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sede::class, 'id_sede', 'id_sede');
    }

    public function hasRole(string $nombre): bool
    {
        return strcasecmp($this->rol->nombre ?? '', $nombre) === 0;
    }

    /**
     * El panel Filament (estadísticas) es exclusivo del Administrador.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasRole('Administrador');
    }

    /**
     * Nombre real del usuario (personas.nombre_completo), buscando su perfil
     * vinculado de personal o cliente. Null si no tiene persona asociada.
     */
    public function nombreCompleto(): ?string
    {
        foreach (['administradores', 'recepcionistas', 'entrenadores', 'clientes'] as $tabla) {
            $nombre = \Illuminate\Support\Facades\DB::table($tabla)
                ->join('personas', 'personas.id_persona', '=', "{$tabla}.id_persona")
                ->where("{$tabla}.id_usuario", $this->id)
                ->value('personas.nombre_completo');

            if ($nombre) {
                return $nombre;
            }
        }

        return null;
    }
}
