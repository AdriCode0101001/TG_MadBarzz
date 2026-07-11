<?php

namespace App\Services;

use App\Models\Cliente;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;

class CarnetService
{
    /**
     * Genera el código QR del cliente (codifica su qr_token) como data URI PNG.
     */
    public function qrDataUri(Cliente $cliente, int $size = 480): string
    {
        $builder = new Builder(
            writer: new PngWriter(),
            data: $cliente->qr_token,
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: $size,
            margin: 12,
        );

        return $builder->build()->getDataUri();
    }

    /**
     * Logo de MadBarzz como data URI (para incrustar en vistas y PDF).
     */
    public function logoDataUri(): ?string
    {
        $path = public_path('assets_private/images/logo_mad.png');

        if (!is_file($path)) {
            return null;
        }

        return 'data:image/png;base64,' . base64_encode(file_get_contents($path));
    }
}
