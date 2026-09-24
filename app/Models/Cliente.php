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
            // Bitácora de Auditoría — Etapa 1: huella_digital es la plantilla
            // biométrica (dato sensible, nunca en texto plano en un
            // historial); qr_token es el identificador secreto del carnet QR
            // del cliente — ninguno de los dos debe poder reconstruirse
            // mirando el historial de cambios, aunque solo lo vea el Admin.
            ->logExcept(['huella_digital', 'qr_token'])
            ->logOnlyDirty()
            // Sin esto, Spatie igual crea una fila vacía cuando el único
            // campo que cambió fue uno excluido arriba (ej. solo huella_digital).
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $event) => "Cliente {$event}");
    }

    /** Misma edad mínima que RegisteredUserController::EDAD_MINIMA (auto-registro). */
    public const EDAD_MINIMA_ADULTO = 18;

    protected $table = 'clientes';
    protected $primaryKey = 'id_cliente';

    protected $fillable = [
        'id_persona',
        'id_usuario',
        'dias_asistidos',
        'huella_digital',
        'estado',
        'id_sede',
        'terminos_aceptados_en',
        'consentimiento_huella_en',
        'tutor_nombre',
        'tutor_ci',
        'consentimiento_tutor_en',
        'qr_token',
    ];

    protected $casts = [
        'terminos_aceptados_en'    => 'datetime',
        'consentimiento_huella_en' => 'datetime',
        'consentimiento_tutor_en'  => 'datetime',
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

    public function pushSubscriptions()
    {
        return $this->hasMany(PushSubscription::class, 'id_cliente');
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
                ->withPivot('fecha_inicio', 'fecha_fin', 'estado', 'precio_final', 'descuento', 'nombre_descuento', 'saldo_pendiente', 'fecha_limite_saldo');
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

/**
 * Grupo 3 legal, parte 2: true si, por su fecha de nacimiento, el cliente
 * es menor de {@see EDAD_MINIMA_ADULTO} años. Requiere $this->persona
 * cargada (o la carga sola, lazy) — si no hay persona o fecha de
 * nacimiento, se asume NO menor (falla cerrado del lado "pide más
 * requisitos", no del lado "deja pasar sin consentimiento").
 */
public function esMenorDeEdad(): bool
{
    $fechaNacimiento = $this->persona?->fecha_nacimiento;

    if (!$fechaNacimiento) {
        return false;
    }

    return Carbon::parse($fechaNacimiento)->age < self::EDAD_MINIMA_ADULTO;
}

/**
 * La comprobación real de si HOY se puede enrolar una huella para este
 * cliente — usada por BiometriaController::enrolar() como la protección
 * de verdad (el resto es UI/validación en el alta). Adultos: alcanza con
 * consentimiento_huella_en. Menores: hace falta ADEMÁS
 * consentimiento_tutor_en (que, en el alta, solo se guarda junto con los
 * datos del tutor — ver ClienteController).
 */
public function puedeEnrolarHuella(): bool
{
    if (!$this->consentimiento_huella_en) {
        return false;
    }

    if ($this->esMenorDeEdad() && !$this->consentimiento_tutor_en) {
        return false;
    }

    return true;
}

public function sedes()
{
    return $this->belongsToMany(Sede::class, 'cliente_sede', 'id_cliente', 'id_sede');
}
}
