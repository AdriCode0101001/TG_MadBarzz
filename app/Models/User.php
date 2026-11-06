<?php

namespace App\Models;

use App\Models\Sede;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Rol;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Implementa MustVerifyEmail (la interfaz — el trait que hace el trabajo real
 * ya viene incluido en Illuminate\Foundation\Auth\User, la clase base detrás
 * de "Authenticatable"). Esto activa la verificación de correo para
 * CUALQUIER User. El auto-registro público está desactivado: el personal
 * (Administrador/Recepcionista/Entrenador) y los clientes los da de alta el
 * personal y quedan con `email_verified_at` puesto en el momento de crearse
 * (ver esos controladores) — para ellos `hasVerifiedEmail()` ya da true desde el
 * primer segundo, así que el middleware 'verified' nunca los frena.
 */
class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'rol_id', 'id_sede'])
            ->logOnlyDirty()
            // Igual que en Cliente: evita una fila vacía cuando lo único que
            // cambió fue un campo fuera de logOnly (ej. solo password).
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $event) => "Usuario {$event}");
    }

    protected $fillable = [
        'name',
        'email',
        'password',
        'rol_id',
        'foto',
        'id_sede',
        // El personal y los clientes dados de alta por el personal se
        // marcan verificados desde User::create() (ver ClienteController,
        // RecepcionistaController, EntrenadorController,
        // AdministradorController, UserController) — sin esto en $fillable,
        // ese valor se descarta en silencio y el usuario queda sin verificar.
        'email_verified_at',
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

    /**
     * Igual que nombreCompleto(), pero agrega el apellido paterno (ej. "Marcos
     * Antonio Olea") — para pantallas donde el nombre solo no alcanza para
     * identificar a la persona (ej. elegir entrenador en el portal del
     * cliente). No reemplaza nombreCompleto() para no afectar las pantallas
     * que ya lo usan.
     */
    public function nombreCompletoConApellido(): ?string
    {
        foreach (['administradores', 'recepcionistas', 'entrenadores', 'clientes'] as $tabla) {
            $persona = \Illuminate\Support\Facades\DB::table($tabla)
                ->join('personas', 'personas.id_persona', '=', "{$tabla}.id_persona")
                ->where("{$tabla}.id_usuario", $this->id)
                ->first(['personas.nombre_completo', 'personas.apellido_paterno']);

            if ($persona && $persona->nombre_completo) {
                return trim($persona->nombre_completo . ' ' . ($persona->apellido_paterno ?? ''));
            }
        }

        return null;
    }
}
