<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * QR de pago bancario único del gimnasio, configurado por el Administrador.
 * Cada carga crea un registro nuevo (se conserva el historial); el "actual"
 * es siempre el más reciente. Reemplazar = subir uno nuevo con su propia
 * fecha de vencimiento.
 */
class QrPago extends Model
{
    use SoftDeletes;

    protected $table = 'qr_pagos';

    protected $fillable = [
        'imagen',
        'fecha_vencimiento',
        'id_usuario',
    ];

    protected $casts = [
        'fecha_vencimiento' => 'date',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }

    /** El QR configurado actualmente (el más reciente), exista o no, vencido o no. */
    public static function actual(): ?self
    {
        return static::latest('id')->first();
    }

    public function estaVencido(): bool
    {
        return Carbon::parse($this->fecha_vencimiento)->toDateString() < now()->toDateString();
    }

    /** Vigente = configurado Y no vencido: el único caso en que se puede mostrar para cobrar. */
    public function estaVigente(): bool
    {
        return !$this->estaVencido();
    }

    /**
     * Días que faltan para vencer (negativo si ya venció).
     */
    public function diasParaVencer(): int
    {
        return (int) now()->startOfDay()->diffInDays(
            Carbon::parse($this->fecha_vencimiento)->startOfDay(),
            false
        );
    }

    /**
     * true si ya venció o está a "dias_aviso_vencimiento" (config, por defecto 7) o menos de vencer.
     */
    public function requiereAviso(): bool
    {
        return $this->diasParaVencer() <= (int) config('qr_pago.dias_aviso_vencimiento', 7);
    }
}
