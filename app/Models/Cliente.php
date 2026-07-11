<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSede;
use App\Models\Scopes\SedeScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Cliente extends Model
{
    use HasFactory, SoftDeletes, BelongsToSede, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $event) => "Cliente {$event}");
    }

    protected $table = 'clientes';
    protected $primaryKey = 'id_cliente';

    protected $fillable = [
        'id_persona',
        'id_usuario',
        'dias_asistidos',
        'huella_digital',
        'estado',
        'id_sede',
        'qr_token',
    ];

    protected static function booted(): void
    {
        static::creating(function (Cliente $cliente) {
            if (empty($cliente->qr_token)) {
                $cliente->qr_token = (string) Str::uuid();
            }
        });
    }

    public function persona()
    {
        return $this->belongsTo(Persona::class, 'id_persona')->withTrashed();
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_usuario')->withTrashed();
    }

    public function membresiaActual()
    {
        return $this->hasOne(MembresiaCliente::class, 'id_cliente')
                ->where('fecha_fin', '>=', now())
                ->latest();
    }

    /**
     * Última membresía del cliente por fecha_fin, SIN el filtro de sede:
     * la membresía es del cliente sin importar en qué sede se compró.
     * Es el criterio único compartido por asistencia, rutinas y portal.
     */
    public function membresiaMasReciente(): ?MembresiaCliente
    {
        return MembresiaCliente::withoutGlobalScope(SedeScope::class)
            ->where('id_cliente', $this->id_cliente)
            ->orderByDesc('fecha_fin')
            ->first();
    }

    /**
     * Membresía "al día": la más reciente vence hoy o en el futuro Y tiene
     * su pago registrado (estado = activa). Sin pago no hay acceso, aunque
     * la fecha aún no haya vencido. MISMA definición que usa el control de
     * asistencia (AsistenciaService).
     */
    public function tieneMembresiaVigente(): bool
    {
        $ultima = $this->membresiaMasReciente();

        return $ultima !== null
            && $ultima->estado === MembresiaCliente::ESTADO_ACTIVA
            && Carbon::parse($ultima->fecha_fin)->toDateString() >= now()->toDateString();
    }

    public function asistencias()
    {
        return $this->hasMany(Asistencia::class, 'id_cliente');
    }
    public function membresias()
{
    return $this->belongsToMany(Membresia::class, 'membresia_cliente', 'id_cliente', 'id_membresia')
                ->withPivot('fecha_inicio', 'fecha_fin', 'estado', 'precio_final', 'descuento', 'nombre_descuento');
}
public function ultimaMembresia()
{
    return $this->hasOne(MembresiaCliente::class, 'id_cliente')->latest('fecha_fin');
}
public function membresiasClientes() {
    return $this->hasMany(MembresiaCliente::class, 'id_cliente', 'id_cliente');
}

public function huellas()
{
    return $this->hasMany(Huella::class, 'id_cliente', 'id_cliente');
}

public function sedes()
{
    return $this->belongsToMany(Sede::class, 'cliente_sede', 'id_cliente', 'id_sede');
}
}
