<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSede;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class MembresiaCliente extends Model
{
    use SoftDeletes, BelongsToSede, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $event) => "MembresiaCliente {$event}");
    }

    protected $table = 'membresia_cliente';

    protected $fillable = [
        'id_cliente',
        'id_membresia',
        'fecha_inicio',
        'fecha_fin',
        'nombre_descuento',
        'descuento',
        'id_descuento',
        'precio_final',
        'id_sede',
        'estado',
        'saldo_pendiente',
        'fecha_limite_saldo',
    ];

    protected $casts = [
        'saldo_pendiente'    => 'decimal:2',
        'fecha_limite_saldo' => 'date',
    ];

    /**
     * Sin su pago vinculado, NO da acceso (asistencia/rutinas). Las nuevas
     * asignaciones nacen así; el pago registrado en el mismo paso la activa.
     */
    public const ESTADO_PENDIENTE = 'pendiente_pago';

    /** Ya tiene su pago: da acceso normal (asistencia, rutinas, portal). */
    public const ESTADO_ACTIVA = 'activa';

    /**
     * Pago en 2 Cuotas (Etapa 1, solo terreno de datos): se llegó a
     * fecha_limite_saldo con saldo_pendiente > 0 sin saldarlo. Todavía NADA
     * la asigna — eso es de una etapa posterior (el comando de
     * desactivación automática). Se distingue de ESTADO_PENDIENTE porque
     * significa "tenía un saldo y no lo pagó a tiempo", no "nunca pagó nada".
     */
    public const ESTADO_SUSPENDIDA_SALDO = 'suspendida_saldo';

    // Relaciones

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'id_cliente', 'id_cliente');
    }

    public function membresia()
    {
        return $this->belongsTo(Membresia::class, 'id_membresia', 'id_membresia');
    }

    public function descuentoAplicado()
    {
        return $this->belongsTo(Descuento::class, 'id_descuento');
    }

    public function pagos()
    {
        return $this->hasMany(Pago::class, 'id_membresia_cliente');
    }

    // ── Helpers de Pago en 2 Cuotas (Etapa 1 — solo lectura, no cambian nada) ──

    /**
     * true si debe un saldo (> 0). null o 0 es "pagada completa", igual que
     * hoy — nada distinto de lo que ya vale para el 100% de las membresías
     * existentes.
     */
    public function tieneSaldoPendiente(): bool
    {
        return (float) $this->saldo_pendiente > 0;
    }

    /**
     * true si esta asignación está suspendida por no pagar el saldo a
     * tiempo. Todavía nada la pone en ese estado (eso es de una etapa
     * posterior) — el helper solo lee lo que haya en "estado".
     */
    public function estaSuspendidaPorSaldo(): bool
    {
        return $this->estado === self::ESTADO_SUSPENDIDA_SALDO;
    }
}
